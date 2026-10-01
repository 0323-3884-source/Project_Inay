import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';

function worker() {
    const handlers = {};
    const stores = new Map();
    const key = request => typeof request === 'string' ? request : request.url;
    const caches = {
        async open(name) {
            if (!stores.has(name)) stores.set(name, new Map());
            const entries = stores.get(name);
            return {
                async match(request) { return entries.get(key(request))?.clone(); },
                async put(request, response) { entries.set(key(request), response.clone()); },
                async addAll(urls) { urls.forEach(url => entries.set(url, new Response('offline fallback'))); },
            };
        },
        async keys() { return [...stores.keys()]; },
        async delete(name) { return stores.delete(name); },
    };
    let network = async () => new Response('account A page', { headers: { 'X-INAY-Offline-User': 'A' } });
    const context = {
        URL, Response, caches,
        fetch: async request => {
            const response = await network(request);
            Object.defineProperty(response, 'url', { value: request.url });
            return response;
        },
        self: {
            location: { href: 'https://example.test/portal/sw.js' },
            addEventListener: (type, callback) => { handlers[type] = callback; },
            skipWaiting() {}, clients: { claim() {} },
        },
    };
    vm.runInNewContext(readFileSync('public/sw.js', 'utf8'), context);
    return {
        caches,
        network(fn) { network = fn; },
        async install() { let task; handlers.install({ waitUntil: p => { task = p; } }); await task; },
        async request(path, options = {}) {
            let result;
            handlers.fetch({ request: { url: `https://example.test/portal/${path}`, method: 'GET', mode: 'navigate', ...options }, respondWith: p => { result = p; } });
            return result;
        },
    };
}
const offline = async () => { throw new TypeError('Network unavailable'); };

test('saved pages and app launch work offline under a subdirectory', async () => {
    const app = worker();
    await app.install();
    await app.request('mother/dashboard');
    await app.request('inay-kaalaman');
    app.network(offline);
    assert.equal(await (await app.request('inay-kaalaman')).text(), 'account A page');
    assert.equal(await (await app.request('?source=pwa')).text(), 'account A page');
    assert.equal(await (await app.request('mother/dashboard?source=pwa')).text(), 'account A page');
    assert.equal(await (await app.request('child-health')).text(), 'offline fallback');
});

test('logout removes saved private pages even if the network fails', async () => {
    const app = worker();
    await app.install();
    await app.request('mother/dashboard');
    app.network(offline);
    await assert.rejects(app.request('logout', { method: 'POST' }));
    assert.equal(await (await app.request('mother/dashboard')).text(), 'offline fallback');
});

test('switching accounts discards pages belonging to the previous account', async () => {
    const app = worker();
    await app.install();
    await app.request('child-health');
    app.network(async () => new Response('account B', { headers: { 'X-INAY-Offline-User': 'B' } }));
    await app.request('mother/dashboard');
    app.network(offline);
    assert.equal(await (await app.request('child-health')).text(), 'offline fallback');
    assert.equal(await (await app.request('mother/dashboard')).text(), 'account B');
});

test('errors and unmarked responses are never saved as private information', async () => {
    const app = worker();
    await app.install();
    app.network(async () => new Response('server error', { status: 500 }));
    await app.request('child-health');
    app.network(async () => new Response('login page'));
    await app.request('mother/dashboard');
    app.network(offline);
    assert.equal(await (await app.request('child-health')).text(), 'offline fallback');
    assert.equal(await (await app.request('mother/dashboard')).text(), 'offline fallback');
});

test('a request started before logout cannot restore private information', async () => {
    const app = worker();
    await app.install();
    let finish;
    app.network(() => new Promise(resolve => { finish = resolve; }));
    const pending = app.request('child-health');
    app.network(async () => new Response('logged out'));
    await app.request('logout', { method: 'POST' });
    finish(new Response('old page', { headers: { 'X-INAY-Offline-User': 'A' } }));
    await pending;
    app.network(offline);
    assert.equal(await (await app.request('child-health')).text(), 'offline fallback');
});

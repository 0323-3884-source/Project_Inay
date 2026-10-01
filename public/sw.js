const STATIC = 'project-inay-static-v2';
const PRIVATE = 'project-inay-private-v2';
const base = new URL('./', self.location.href);
const local = path => new URL(path, base).href;
let generation = 0;
let writes = Promise.resolve();
function serialize(action) {
    const result = writes.then(action);
    writes = result.catch(() => {});
    return result;
}
const pathOf = url => new URL(url).pathname.slice(base.pathname.length);
const authPath = path => /^(admin\/)?(login|logout)$/.test(path);
const readable = path => /^(mother\/|staff\/|maternal-monitoring$|child-health$|inay-kaalaman(?:\/|$)|health-services$|api\/mother\/|api\/program-staff\/|notifications$|consultation\/conversations|staff-coordination\/threads)/.test(path);
async function clearPrivate() {
    generation++;
    await serialize(() => caches.delete(PRIVATE));
}
self.addEventListener('install', event => {
    event.waitUntil(caches.open(STATIC).then(cache => cache.addAll([
        local('offline.html'), local('js/pwa.js'), local('manifest.webmanifest')
    ])).then(() => self.skipWaiting()));
});
self.addEventListener('activate', event => {
    event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key =>
        key.startsWith('project-inay-') && ![STATIC, PRIVATE].includes(key)
    ).map(key => caches.delete(key)))).then(() => self.clients.claim()));
});
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);
    if (url.origin !== base.origin || !url.pathname.startsWith(base.pathname)) return;
    const path = pathOf(url);
    const cacheUrl = new URL(url);
    if (cacheUrl.searchParams.get('source') === 'pwa') cacheUrl.searchParams.delete('source');
    const cacheKey = cacheUrl.href;
    if (authPath(path) && request.method !== 'GET') {
        event.respondWith(clearPrivate().then(() => fetch(request)));
        return;
    }
    if (request.method !== 'GET') return;
    const navigation = request.mode === 'navigate';
    const asset = /^(css|js|build|pwa)\//.test(path) || path === 'manifest.webmanifest';
    const privateAsset = /^(storage|images)\//.test(path);
    if (!navigation && !asset && !privateAsset && !readable(path)) return;
    event.respondWith((async () => {
        const epoch = generation;
        let response;
        try { response = await fetch(request); } catch (error) {
            const cache = await caches.open(asset ? STATIC : PRIVATE);
            let cached = await cache.match(cacheKey);
            if (!cached && navigation && (!path || path === 'login')) {
                cached = await cache.match(local('mother/dashboard')) || await cache.match(local('staff/dashboard'));
            }
            if (cached) return cached;
            if (navigation) return (await caches.open(STATIC)).match(local('offline.html'));
            return new Response(JSON.stringify({ message: 'Reconnect to load information that has not been saved offline.' }), {
                status: 503, headers: { 'Content-Type': 'application/json' }
            });
        }
        if (navigation && (authPath(pathOf(response.url)) || [401, 403].includes(response.status))) await clearPrivate();
        try {
            await serialize(async () => {
            if (response.ok && !response.redirected && epoch === generation) {
                if (asset) {
                    await (await caches.open(STATIC)).put(cacheKey, response.clone());
                } else if (readable(path) || privateAsset) {
                    const owner = response.headers.get('X-INAY-Offline-User');
                    let cache = await caches.open(PRIVATE);
                    const identity = await cache.match(local('__offline_owner'));
                    if (owner && identity && await identity.text() !== owner) {
                        generation++;
                        await caches.delete(PRIVATE);
                        cache = await caches.open(PRIVATE);
                    }
                    if (owner) await cache.put(local('__offline_owner'), new Response(owner));
                    if (owner || (privateAsset && identity)) await cache.put(cacheKey, response.clone());
                }
            }
            });
        } catch (error) { /* Storage full: keep the online page usable. */ }
        return response;
    })());
});

// Export fixtures with INAY_ASSET_FIXTURES=storage/framework/testing/production-assets
// while running ProductionAssetsTest, then run: node tests/js/production-assets.browser.mjs
// Uses installed Chrome and Node's WebSocket; no browser test dependencies.
import assert from 'node:assert/strict';
import { readFileSync, existsSync, mkdirSync } from 'node:fs';
import { resolve, sep } from 'node:path';
import { spawn } from 'node:child_process';

const root = resolve('storage/framework/testing/production-assets');
const publicRoot = resolve('public');
mkdirSync(root, { recursive: true });
const chrome = spawn(process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe', [
    '--headless=new', '--no-first-run', '--disable-gpu', '--no-default-browser-check',
    '--remote-debugging-port=9334', `--user-data-dir=${root}/chrome`, 'about:blank',
], { windowsHide: true, stdio: 'ignore' });
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
let ws;
try {
    let targets;
    for (let i = 0; i < 60; i++) {
        try { targets = await (await fetch('http://127.0.0.1:9334/json')).json(); break; }
        catch { await sleep(150); }
    }
    assert.ok(targets, 'Chrome started');
    ws = new WebSocket(targets.find(target => target.type === 'page').webSocketDebuggerUrl);
    await new Promise(resolve => ws.addEventListener('open', resolve, { once: true }));
    const pending = new Map();
    const errors = [];
    let sequence = 0;
    let page;
    let scheme;
    const send = (method, params = {}) => new Promise((resolve, reject) => {
        const id = ++sequence;
        pending.set(id, { resolve, reject });
        ws.send(JSON.stringify({ id, method, params }));
    });
    const fulfill = (requestId, content, type) => send('Fetch.fulfillRequest', {
        requestId, responseCode: 200,
        responseHeaders: [{ name: 'Content-Type', value: type }],
        body: Buffer.from(content).toString('base64'),
    });
    const serve = async ({ requestId, request, resourceType }) => {
        const url = new URL(request.url);
        if (resourceType === 'Document') {
            return fulfill(requestId, readFileSync(`${root}/${page}-${scheme}.html`), 'text/html; charset=utf-8');
        }
        assert.equal(url.origin, `${scheme}://portal.example.test`, 'Assets and APIs use the page origin');
        if (url.pathname === '/notifications' || url.pathname === '/consultation/conversations') {
            return fulfill(requestId, JSON.stringify({ notifications: [], conversations: [], unread_count: 0 }), 'application/json');
        }
        const file = resolve(publicRoot, '.' + url.pathname);
        assert.ok(file.startsWith(publicRoot + sep));
        if (existsSync(file)) {
            const type = file.endsWith('.css') ? 'text/css' : file.endsWith('.js') ? 'application/javascript'
                : file.endsWith('.png') ? 'image/png' : file.endsWith('.webmanifest') ? 'application/manifest+json' : 'application/octet-stream';
            return fulfill(requestId, readFileSync(file), type);
        }
        throw new Error(`Unexpected browser request: ${url.pathname}`);
    };
    ws.addEventListener('message', event => {
        const data = JSON.parse(event.data);
        if (data.id) {
            const callback = pending.get(data.id);
            pending.delete(data.id);
            data.error ? callback.reject(data.error) : callback.resolve(data.result);
        } else if (data.method === 'Fetch.requestPaused') {
            serve(data.params).catch(error => {
                errors.push(error.message);
                send('Fetch.failRequest', { requestId: data.params.requestId, errorReason: 'Aborted' });
            });
        } else if (data.method === 'Runtime.exceptionThrown') {
            errors.push(data.params.exceptionDetails.text);
        }
    });
    const evaluate = async expression => {
        const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        assert.ok(!result.exceptionDetails, JSON.stringify(result.exceptionDetails));
        return result.result.value;
    };
    await send('Page.enable');
    await send('Runtime.enable');
    await send('Network.enable');
    await send('Network.setBypassServiceWorker', { bypass: true });
    await send('Network.setCacheDisabled', { cacheDisabled: true });
    await send('Fetch.enable', { patterns: [{ urlPattern: '*' }] });
    for (page of ['dashboard', 'consultation', 'clinic-schedule', 'dswd-dashboard', 'dswd-reports']) {
        for (const width of [1440, 768, 375, 320]) {
            await send('Emulation.setDeviceMetricsOverride', { width, height: 1000, deviceScaleFactor: 1, mobile: false });
            const snapshots = [];
            for (scheme of ['http', 'https']) {
                await send('Page.navigate', { url: `${scheme}://portal.example.test/mother/${page}?width=${width}` });
                for (let i = 0; i < 80; i++) {
                    if (await evaluate(`location.href === '${scheme}://portal.example.test/mother/${page}?width=${width}' && document.readyState === "complete" && !!document.querySelector(".portal-main")`)) break;
                    await sleep(75);
                }
                await sleep(200);
                assert.ok(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), `${page} has no horizontal page overflow at ${width}px`);
                // Offline capability differs by scheme in the intercepted browser.
                // Compare the page layout with the optional status banner removed.
                await evaluate(`document.querySelectorAll('.pwa-status').forEach(el => el.remove())`);
                snapshots.push(await evaluate(`Array.from(document.querySelectorAll('.portal-main, .account-metrics, .account-panel, .consultation-workspace, .consultation-search svg, .consultation-emoji svg, .appointment-search-field svg, .photo-crop-modal, .filter-modal, .booking-modal')).map(el => {
                    const css = getComputedStyle(el), rect = el.getBoundingClientRect();
                    return {class: el.getAttribute('class'), width: rect.width, height: rect.height, display: css.display, fill: css.fill, gap: css.gap, padding: css.padding, columns: css.gridTemplateColumns};
                })`));
                assert.equal(await evaluate('getComputedStyle(document.querySelector(".photo-crop-modal")).display'), 'none');
                if (page === 'dashboard') {
                    assert.equal(await evaluate('getComputedStyle(document.querySelector(".account-metrics")).display'), 'grid');
                } else if (page === 'consultation' || page === 'clinic-schedule') {
                    const selector = page === 'consultation' ? '.consultation-search svg' : '.appointment-search-field svg';
                    assert.deepEqual(await evaluate(`(() => {const s = getComputedStyle(document.querySelector('${selector}')); return [s.width, s.height, s.fill]})()`), ['18px', '18px', 'none']);
                    if (width === 1440 && scheme === 'https') {
                        const unstyled = await evaluate(`(() => {
                            document.querySelector('link[rel="stylesheet"]').disabled = true;
                            const s = getComputedStyle(document.querySelector('${selector}'));
                            return {width: s.width, height: s.height, fill: s.fill};
                        })()`);
                        assert.equal(unstyled.fill, 'rgb(0, 0, 0)');
                        assert.ok(parseFloat(unstyled.width) > 18);
                        console.log('Reproduced missing CSS circle:', selector, unstyled);
                    }
                } else {
                    assert.equal(await evaluate('document.querySelectorAll(".f1kd-summary .admin-summary-card").length'), 6);
                    if (page === 'dswd-reports') {
                        assert.ok(await evaluate('document.querySelector(".site-pagination").getBoundingClientRect().height < 180'));
                        assert.equal(await evaluate('document.querySelectorAll(".site-pagination svg").length'), 0);
                    }
                }
            }
            assert.deepEqual(snapshots[1], snapshots[0], 'HTTPS and localhost layout must match');
            console.log(`PASS ${page}: HTTP/HTTPS match at ${width}px`);
        }
    }
    assert.deepEqual(errors, [], 'No browser exceptions or unexpected asset/API requests');
} finally {
    ws?.close();
    chrome.kill();
}

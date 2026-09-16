// Run after exporting the fixture with INAY_BROWSER_FIXTURE and serving public/ on port 8765.
// Uses Chromium's DevTools protocol and Node's built-in WebSocket; no npm dependencies.
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { resolve } from 'node:path';
import { spawn } from 'node:child_process';
import assert from 'node:assert/strict';

const chromePath = process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const root = resolve('storage/app/infographic-browser');
mkdirSync(root, { recursive: true });
const chrome = spawn(chromePath, ['--headless=new', '--no-first-run', '--disable-gpu', '--no-sandbox', '--disable-software-rasterizer', '--no-default-browser-check', '--remote-debugging-port=9333', `--user-data-dir=${root}/profile`, 'about:blank'], { windowsHide: true, stdio: 'ignore' });
const sleep = ms => new Promise(r => setTimeout(r, ms));
let ws;
try {
    let targets;
    for (let i = 0; i < 60; i++) {
        try { targets = await (await fetch('http://127.0.0.1:9333/json')).json(); break; } catch { await sleep(150); }
    }
    assert.ok(targets, 'Chromium started');
    ws = new WebSocket(targets.find(t => t.type === 'page').webSocketDebuggerUrl);
    await new Promise(r => ws.addEventListener('open', r, { once: true }));
    let seq = 0;
    const pending = new Map();
    const handlers = new Map();
    ws.addEventListener('message', event => {
        const data = JSON.parse(event.data);
        if (data.id) {
            const promise = pending.get(data.id);
            pending.delete(data.id);
            data.error ? promise.reject(data.error) : promise.resolve(data.result);
        } else handlers.get(data.method)?.(data.params);
    });
    const send = (method, params = {}) => new Promise((resolve, reject) => {
        const id = ++seq; pending.set(id, { resolve, reject }); ws.send(JSON.stringify({ id, method, params }));
    });
    const evaluate = async expression => {
        const result = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
        if (result.exceptionDetails) throw new Error(JSON.stringify(result.exceptionDetails));
        return result.result.value;
    };
    const until = async expression => {
        for (let i = 0; i < 80; i++) { if (await evaluate(expression)) return; await sleep(75); }
        throw new Error(`Timed out: ${expression}; ${JSON.stringify(errors)}`);
    };
    const errors = [];
    handlers.set('Runtime.exceptionThrown', event => errors.push(event.exceptionDetails.text + ': ' + event.exceptionDetails.exception?.description));
    await send('Runtime.enable');
    await send('Page.enable');
    await send('Network.enable');
    await send('Network.setBypassServiceWorker', { bypass: true });
    await send('Network.setCacheDisabled', { cacheDisabled: true });
    const fixture = readFileSync(resolve('storage/app/infographic-browser.html'), 'utf8')
        .replaceAll('http://localhost', 'http://127.0.0.1:8765')
        .replaceAll('127.0.0.1:8000', '127.0.0.1:8765');
    let requests = [];
    let failNext = false;
    handlers.set('Fetch.requestPaused', async ({ requestId, request, resourceType }) => {
        if (request.url.endsWith('/inay-kaalaman/progress') && request.method === 'POST') {
            const payload = JSON.parse(request.postData);
            requests.push(payload);
            const fail = failNext; failNext = false;
            await sleep(80); // Exercise completion while the start request is pending.
            await send('Fetch.fulfillRequest', { requestId, responseCode: fail ? 503 : 200,
                responseHeaders: [{ name: 'Content-Type', value: 'application/json' }],
                body: Buffer.from(JSON.stringify(fail ? { message: 'Offline' } : { progress: { status: payload.status }, month_summary: null })).toString('base64') });
        } else if (request.url.endsWith('/missing-infographic-test.png')) {
            await send('Fetch.fulfillRequest', { requestId, responseCode: 404, body: '' });
        } else if (resourceType === 'Document' && request.url.includes('/infographic-test')) {
            await send('Fetch.fulfillRequest', { requestId, responseCode: 200, responseHeaders: [{ name: 'Content-Type', value: 'text/html; charset=utf-8' }], body: Buffer.from(fixture).toString('base64') });
        } else if (request.url.startsWith('http://127.0.0.1:8765/')) {
            await send('Fetch.continueRequest', { requestId });
        } else {
            await send('Fetch.failRequest', { requestId, errorReason: 'Aborted' });
        }
    });
    await send('Fetch.enable', { patterns: [{ urlPattern: '*' }] });
    const cardSelector = '[data-key="month-1-infographic"]';
    for (const [width, height] of [[375, 812], [768, 1024], [1440, 1000], [320, 568]]) {
        requests = [];
        await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 600 });
        await send('Page.navigate', { url: `http://127.0.0.1:8765/infographic-test?width=${width}` });
        await until(`!!document.querySelector('#inayInfographicViewer')?.dataset.bound`);
        await evaluate(`(() => {
            const card = document.querySelector('${cardSelector}');
            card.dataset.status = 'not_started';
            const panel = card.closest('details'); if (panel) panel.open = true;
            card.querySelector('button').click();
        })()`);
        await until(`document.querySelector('${cardSelector}').dataset.status === 'in_progress'`);
        assert.equal(requests.length, 1, 'Opening saves start only');
        assert.deepEqual(await evaluate(`(() => { const d = document.querySelector('dialog[open]'); const r = d.querySelector('[data-infographic-reader]'); return { fits: d.getBoundingClientRect().width <= innerWidth, overflows: r.scrollWidth > r.clientWidth + 1, scrolls: r.scrollHeight > r.clientHeight }; })()`), { fits: true, overflows: false, scrolls: true });
        if (width === 375) {
            assert.equal(await evaluate(`document.querySelector('dialog[open]').getBoundingClientRect().right <= visualViewport.width`), true);
            const screenshot = await send('Page.captureScreenshot', { format: 'png' });
            writeFileSync(`${root}/mobile-viewer.png`, Buffer.from(screenshot.data, 'base64'));
        }
        await evaluate(`(() => {const r = document.querySelector('[data-infographic-reader]'); r.scrollTop = r.scrollHeight;})()`);
        await until(`document.querySelector('${cardSelector}').dataset.status === 'reviewed'`);
        assert.deepEqual(requests.map(r => r.status), ['in_progress', 'reviewed']);
        await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 });
        await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 });
        await until(`!document.querySelector('#inayInfographicViewer').open`);
        await until(`document.activeElement.matches('${cardSelector} button')`);
        await evaluate(`document.querySelector('${cardSelector} button').click()`);
        await sleep(150);
        assert.equal(requests.length, 2, 'Completed reopens do not write duplicate progress');
        await evaluate(`document.querySelector('#inayInfographicViewer').close()`);
        if (width === 375) {
            await evaluate(`document.querySelector('${cardSelector}').scrollIntoView({block: 'center'})`);
            assert.equal(await evaluate(`document.querySelector('${cardSelector}').getBoundingClientRect().right <= visualViewport.width`), true);
            const screenshot = await send('Page.captureScreenshot', { format: 'png' });
            writeFileSync(`${root}/mobile-card.png`, Buffer.from(screenshot.data, 'base64'));
        }
        console.log(`PASS ${width}px: start, scroll completion, fit, close, focus, reopen`);
    }

    // A failed start must not show a successful badge; retry must recover.
    requests = []; failNext = true;
    await send('Page.navigate', { url: 'http://127.0.0.1:8765/infographic-test?retry=1' });
    await until(`!!document.querySelector('#inayInfographicViewer')?.dataset.bound`);
    await evaluate(`document.querySelector('${cardSelector}').dataset.status = 'not_started'; document.querySelector('${cardSelector} button').click()`);
    await until(`!document.querySelector('[data-infographic-retry]').hidden`);
    assert.equal(await evaluate(`document.querySelector('${cardSelector}').dataset.status`), 'not_started');
    await evaluate(`document.querySelector('[data-infographic-retry]').click()`);
    await until(`document.querySelector('${cardSelector}').dataset.status === 'in_progress'`);
    console.log('PASS failed save and explicit retry');

    await evaluate(`document.querySelector('#inayInfographicViewer').close()`);
    await until(`!document.querySelector('#inayInfographicViewer').open`);
    await evaluate(`(() => {
        const card = document.querySelector('[data-key="month-2-infographic"]');
        card.querySelector('template').content.querySelector('article').insertAdjacentHTML('afterbegin', '<img data-infographic-image src="http://127.0.0.1:8765/missing-infographic-test.png"><p data-image-error hidden>Image unavailable</p>');
        card.querySelector('button').click();
    })()`);
    await until(`!document.querySelector('[data-infographic-reader] [data-image-error]').hidden`);
    await evaluate(`const r = document.querySelector('[data-infographic-reader]'); r.scrollTop = r.scrollHeight;`);
    await sleep(200);
    assert.equal(await evaluate(`document.querySelector('[data-key="month-2-infographic"]').dataset.status`), 'in_progress');
    console.log('PASS missing image cannot complete');
    assert.deepEqual(errors, [], 'No browser JavaScript exceptions');
} finally {
    ws?.close();
    chrome.kill();
}

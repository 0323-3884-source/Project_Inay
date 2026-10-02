// Export synthetic fixtures with INAY_DOCUMENT_FIXTURES=storage/framework/testing/document-library
// while running MotherDocumentsTest, then run this script with Node.
import assert from 'node:assert/strict';
import { readFileSync, existsSync, writeFileSync } from 'node:fs';
import { resolve, sep } from 'node:path';
import { spawn } from 'node:child_process';
const root = resolve('storage/framework/testing/document-library');
const publicRoot = resolve('public');
const chrome = spawn(process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe', [
    '--headless=new', '--no-first-run', '--disable-gpu', '--no-default-browser-check',
    '--remote-debugging-port=9338', `--user-data-dir=${root}/chrome`, 'about:blank',
], { windowsHide: true, stdio: 'ignore' });
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
let ws, page;
try {
    let targets;
    for (let i = 0; i < 60; i++) {
        try { targets = await (await fetch('http://127.0.0.1:9338/json')).json(); break; } catch { await sleep(150); }
    }
    assert.ok(targets, 'Chrome started');
    ws = new WebSocket(targets.find(target => target.type === 'page').webSocketDebuggerUrl);
    await new Promise(resolve => ws.addEventListener('open', resolve, { once: true }));
    let sequence = 0;
    const pending = new Map(), errors = [];
    const send = (method, params = {}) => new Promise((resolve, reject) => {
        const id = ++sequence; pending.set(id, { resolve, reject }); ws.send(JSON.stringify({ id, method, params }));
    });
    const fulfill = (id, body, type) => send('Fetch.fulfillRequest', { requestId: id, responseCode: 200, responseHeaders: [{ name: 'Content-Type', value: type }], body: Buffer.from(body).toString('base64') });
    const serve = async ({ requestId, request, resourceType }) => {
        const url = new URL(request.url);
        if (url.pathname.endsWith('/preview')) return fulfill(requestId, Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aG1cAAAAASUVORK5CYII=', 'base64'), 'image/png');
        if (resourceType === 'Document') return fulfill(requestId, readFileSync(`${root}/${page}.html`), 'text/html');
        // Offline preparation is tested separately; keep this test focused on document controls.
        if (url.pathname === '/js/pwa.js') return fulfill(requestId, '', 'application/javascript');
        const file = resolve(publicRoot, '.' + url.pathname);
        if (file.startsWith(publicRoot + sep) && existsSync(file)) return fulfill(requestId, readFileSync(file), file.endsWith('.css') ? 'text/css' : file.endsWith('.js') ? 'application/javascript' : 'image/png');
        return fulfill(requestId, JSON.stringify({ notifications: [], conversations: [], unread_count: 0 }), 'application/json');
    };
    ws.addEventListener('message', event => {
        const data = JSON.parse(event.data);
        if (data.id) { const task = pending.get(data.id); pending.delete(data.id); data.error ? task.reject(data.error) : task.resolve(data.result); }
        else if (data.method === 'Fetch.requestPaused') serve(data.params).catch(error => errors.push(error.message));
        else if (data.method === 'Runtime.exceptionThrown') errors.push(data.params.exceptionDetails.exception?.description || data.params.exceptionDetails.text);
    });
    const evaluate = async expression => {
        const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        assert.ok(!result.exceptionDetails, JSON.stringify(result.exceptionDetails)); return result.result.value;
    };
    await send('Page.enable'); await send('Runtime.enable');
    await send('Fetch.enable', { patterns: [{ urlPattern: '*' }] });
    for (page of ['mother', 'staff']) {
        for (const width of [1440, 375]) {
            await send('Emulation.setDeviceMetricsOverride', { width, height: 950, deviceScaleFactor: 1, mobile: false });
            const fixture = readFileSync(`${root}/${page}.html`, 'utf8');
            const origin = new URL(fixture.match(/data-preview="([^"]+)"/)[1]).origin;
            await send('Page.navigate', { url: `${origin}/${page}/documents?width=${width}` });
            for (let i = 0; i < 80; i++) {
                if (await evaluate('document.readyState === "complete" && !!document.querySelector("[data-document-library]")')) break;
                await sleep(75);
            }
            if (page === 'staff') await evaluate('document.querySelector("[data-casefile-tab=documents]").click()');
            assert.equal(await evaluate('document.querySelector("[data-document-library]").getBoundingClientRect().width > 0'), true);
            assert.equal(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), true, `${page} fits at ${width}px`);
            await evaluate(`(() => {const field = document.querySelector('[data-document-search]'); field.value = 'receipt'; field.dispatchEvent(new Event('input'));})()`);
            assert.equal(await evaluate('document.querySelectorAll("[data-document-item]:not([hidden])").length'), 1);
            await evaluate(`(() => {const field = document.querySelector('[data-document-month]'); field.value = '3'; field.dispatchEvent(new Event('change'));})()`);
            assert.equal(await evaluate('document.querySelector("[data-document-empty]").hidden'), false);
            await evaluate(`(() => {const field = document.querySelector('[data-document-month]'); field.value = ''; field.dispatchEvent(new Event('change')); document.querySelector('[data-document-item]:not([hidden]) [data-document-card]').click();})()`);
            for (let i = 0; i < 40; i++) { if (await evaluate('!!document.querySelector("[data-document-content] img")')) break; await sleep(75); }
            assert.equal(await evaluate('document.querySelector("#document-preview-dialog").open && !!document.querySelector("[data-document-content] img")'), true, await evaluate('document.querySelector("[data-document-status]").textContent') + JSON.stringify(errors));
            await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 });
            await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 });
            assert.equal(await evaluate('document.querySelector("#document-preview-dialog").open'), false);
            if (page === 'mother') {
                await evaluate('document.querySelector("[data-upload-open]").click()');
                assert.equal(await evaluate('document.querySelector("#document-upload-dialog").open'), true);
                assert.equal(await evaluate('document.querySelector("[data-document-upload-form]").checkValidity()'), false);
                await evaluate('document.querySelector("[data-upload-close]").click()');
            }
            const shot = await send('Page.captureScreenshot', { format: 'png' });
            writeFileSync(`${root}/${page}-${width}.png`, Buffer.from(shot.data, 'base64'));
            console.log(`PASS ${page} at ${width}px: search, filters, preview, keyboard close, layout`);
        }
    }
    assert.deepEqual(errors, []);
} finally { ws?.close(); chrome.kill(); }

// Export fixtures: INAY_ADMIN_FIXTURES=storage/framework/testing php artisan test --filter=DepartmentAdminTest
// Then run: node tests/js/department-admin.browser.mjs
import assert from 'node:assert/strict';
import { mkdirSync, writeFileSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';
import { spawn } from 'node:child_process';

const directory = resolve('storage/framework/testing/department-admin');
mkdirSync(directory, { recursive: true });
const chrome = spawn(process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe', [
    '--headless=new', '--no-first-run', '--disable-gpu', '--no-sandbox', '--disable-software-rasterizer', '--no-default-browser-check',
    '--remote-debugging-port=9342', `--user-data-dir=${directory}/chrome`, 'about:blank',
], { windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });
chrome.stderr.on('data', (chunk) => { if (process.env.DEBUG_BROWSER) process.stderr.write(chunk); });
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
let ws;
try {
    let targets;
    for (let i = 0; i < 60; i++) {
        try { targets = await (await fetch('http://127.0.0.1:9342/json')).json(); break; } catch { await sleep(150); }
    }
    assert.ok(targets, 'Chromium started');
    ws = new WebSocket(targets.find((target) => target.type === 'page').webSocketDebuggerUrl);
    await new Promise((resolve) => ws.addEventListener('open', resolve, { once: true }));
    let seq = 0;
    const pending = new Map();
    ws.addEventListener('close', () => { for (const promise of pending.values()) promise.reject(new Error('Browser connection closed')); pending.clear(); });
    ws.addEventListener('message', (event) => {
        const result = JSON.parse(event.data);
        if (!result.id) return;
        const promise = pending.get(result.id);
        pending.delete(result.id);
        result.error ? promise.reject(result.error) : promise.resolve(result.result);
    });
    const send = (method, params = {}) => new Promise((resolve, reject) => {
        const id = ++seq; pending.set(id, { resolve, reject }); ws.send(JSON.stringify({ id, method, params }));
    });
    const evaluate = async (expression) => {
        const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        if (result.exceptionDetails) throw new Error(JSON.stringify(result.exceptionDetails));
        return result.result.value;
    };
    await send('Page.enable');
    for (const scope of ['dswd', 'cho']) {
        const raw = readFileSync(resolve(`storage/framework/testing/${scope}.html`), 'utf8');
        const html = raw.replace(/http:\/\/(?:localhost|127\.0\.0\.1(?::8000)?)\/((?:css|js)\/[^"?]+)(?:\?[^" ]*)?/g, (_, asset) => pathToFileURL(resolve('public', asset)).href);
        const page = resolve(directory, `${scope}.html`);
        writeFileSync(page, html);
        await send('Page.navigate', { url: pathToFileURL(page).href });
        await sleep(400);
        for (const width of [1440, 390]) {
            await send('Emulation.setDeviceMetricsOverride', { width, height: 950, deviceScaleFactor: 1, mobile: width < 640 });
            await sleep(150);
            assert.equal(await evaluate("document.querySelector('.admin-brand-subtitle').textContent"), `${scope.toUpperCase()} ADMIN CONSOLE`);
            assert.ok(await evaluate('document.documentElement.scrollWidth <= innerWidth'), `${scope} fits ${width}px`);
            assert.ok(await evaluate("document.querySelectorAll('.admin-summary-card').length >= 6"));
            assert.ok(await evaluate("document.querySelectorAll('.department-chart').length >= 3"));
            const forbidden = scope === 'dswd' ? '/admin/cho/' : '/admin/dswd/';
            assert.equal(await evaluate(`Array.from(document.querySelectorAll('.admin-nav a')).some(a => a.href.includes('${forbidden}'))`), false);
            const screenshot = await send('Page.captureScreenshot', { format: 'png' });
            writeFileSync(resolve(directory, `${scope}-${width}.png`), Buffer.from(screenshot.data, 'base64'));
            if (width < 640) {
                assert.equal(await evaluate("document.querySelector('[data-admin-menu-toggle]').getAttribute('aria-expanded')"), 'false');
                await evaluate("document.querySelector('[data-admin-menu-toggle]').click()");
                assert.equal(await evaluate("document.querySelector('[data-admin-menu-toggle]').getAttribute('aria-expanded')"), 'true');
                assert.ok(await evaluate("document.querySelector('.admin-nav').getBoundingClientRect().height > 0"));
                await evaluate("document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }))");
                assert.equal(await evaluate("document.querySelector('[data-admin-menu-toggle]').getAttribute('aria-expanded')"), 'false');
            }
            console.log(`PASS ${scope} dashboard at ${width}px`);
        }
    }
} finally {
    ws?.close();
    chrome.kill();
}

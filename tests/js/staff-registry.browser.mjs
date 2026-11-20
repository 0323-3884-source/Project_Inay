// Generate the synthetic fixture with INAY_ACTIVITY_FIXTURE while running StaffActivityTest.
import assert from 'node:assert/strict';
import {readFileSync, writeFileSync, mkdirSync} from 'node:fs';
import {resolve} from 'node:path';
import {pathToFileURL} from 'node:url';
import {spawn} from 'node:child_process';
const directory = resolve('storage/framework/testing/staff-registry');
mkdirSync(directory, {recursive: true});
const raw = readFileSync('storage/framework/testing/staff-registry.html', 'utf8');
const html = raw.replace(/http:\/\/(?:localhost|127\.0\.0\.1(?::8000)?)\/((?:css|js)\/[^"?]+)(?:\?[^" ]*)?/g, (_, asset) => pathToFileURL(resolve('public', asset)).href);
const page = resolve(directory, 'registry.html');
writeFileSync(page, html);
const chrome = spawn(process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe', ['--headless=new', '--no-first-run', '--no-sandbox', '--disable-gpu', '--no-default-browser-check', '--allow-file-access-from-files', '--remote-debugging-port=9346', `--user-data-dir=${directory}/chrome`, 'about:blank'], {windowsHide: true, stdio: ['ignore', 'ignore', 'pipe']});
chrome.stderr.on('data', chunk => { if (process.env.DEBUG_BROWSER) process.stderr.write(chunk); });
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
let ws;
try {
    let targets;
    for (let i = 0; i < 60; i++) {
        try { targets = await (await fetch('http://127.0.0.1:9346/json')).json(); break; } catch { await sleep(150); }
    }
    assert.ok(targets, 'Chromium starts');
    ws = new WebSocket(targets.find(target => target.type === 'page').webSocketDebuggerUrl);
    await new Promise(resolve => ws.addEventListener('open', resolve, {once: true}));
    let seq = 0;
    const pending = new Map();
    ws.addEventListener('close', () => { for (const item of pending.values()) item.reject(new Error('Chromium connection closed')); pending.clear(); });
    ws.addEventListener('message', event => {
        const data = JSON.parse(event.data);
        if (!data.id) return;
        const promise = pending.get(data.id);
        pending.delete(data.id);
        data.error ? promise.reject(data.error) : promise.resolve(data.result);
    });
    const send = (method, params = {}) => new Promise((resolve, reject) => {const id = ++seq; pending.set(id, {resolve, reject}); ws.send(JSON.stringify({id, method, params}));});
    const evaluate = async expression => (await send('Runtime.evaluate', {expression, returnByValue: true})).result.value;
    await send('Page.enable');
    await send('Page.navigate', {url: pathToFileURL(page).href});
    await sleep(700);
    for (const width of [1440, 390]) {
        await send('Emulation.setDeviceMetricsOverride', {width, height: 1000, deviceScaleFactor: 1, mobile: width < 640});
        await sleep(200);
        assert.equal(await evaluate("document.querySelectorAll('.staff-registry-table thead th').length"), 7);
        assert.ok(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), `page fits ${width}px`);
        assert.equal(await evaluate("getComputedStyle(document.querySelector('.staff-registry-table th')).backgroundColor"), 'rgb(248, 246, 248)');
        assert.ok(await evaluate("document.querySelector('.staff-action.is-chat') && document.querySelector('.staff-action:not(.is-chat)')"));
        await evaluate("document.querySelector('.staff-registry-card').scrollIntoView({block: 'center'})");
        await sleep(150);
        const screenshot = await send('Page.captureScreenshot', {format: 'png'});
        writeFileSync(resolve(directory, `registry-${width}.png`), Buffer.from(screenshot.data, 'base64'));
        console.log(`PASS staff table and responsive containment at ${width}px`);
        if (width === 390) {
            await evaluate("document.querySelector('[data-admin-menu-toggle]').click()");
            await sleep(250);
            assert.equal(await evaluate("document.querySelector('.admin-sidebar').getAttribute('aria-modal')"), 'true');
            assert.equal(await evaluate("document.querySelector('.admin-main').inert"), true);
            assert.ok(await evaluate("document.querySelector('.admin-sidebar-backdrop').getBoundingClientRect().width >= innerWidth"));
            const drawer = await send('Page.captureScreenshot', {format: 'png'});
            writeFileSync(resolve(directory, 'general-admin-drawer.png'), Buffer.from(drawer.data, 'base64'));
            await evaluate("document.querySelector('.admin-sidebar-backdrop').click()");
            assert.equal(await evaluate("document.querySelector('[data-admin-menu-toggle]').getAttribute('aria-expanded')"), 'false');
            console.log('PASS general admin hamburger drawer opens and closes');
        }
    }
} finally {
    ws?.close();
    chrome.kill();
}

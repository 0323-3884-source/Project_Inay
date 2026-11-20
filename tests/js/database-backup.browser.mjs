// Uses the fictional HTML fixture exported by DatabaseBackupTest with INAY_BACKUP_FIXTURE.
import assert from 'node:assert/strict';
import {readFileSync, writeFileSync, mkdirSync} from 'node:fs';
import {resolve} from 'node:path';
import {pathToFileURL} from 'node:url';
import {spawn} from 'node:child_process';
const directory = resolve('storage/framework/testing/backup-preview');
mkdirSync(directory, {recursive: true});
const raw = readFileSync('storage/framework/testing/backups.html', 'utf8');
const html = raw.replace(/http:\/\/(?:localhost|127\.0\.0\.1(?::8000)?)\/((?:css|js)\/[^"?]+)(?:\?[^" ]*)?/g, (_, asset) => pathToFileURL(resolve('public', asset)).href);
const page = resolve(directory, 'backup.html');
writeFileSync(page, html);
const chrome = spawn(process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe', ['--headless=new', '--no-first-run', '--no-sandbox', '--disable-gpu', '--no-default-browser-check', '--allow-file-access-from-files', '--remote-debugging-port=9348', `--user-data-dir=${directory}/chrome`, 'about:blank'], {windowsHide: true, stdio: ['ignore', 'ignore', 'pipe']});
chrome.stderr.on('data', () => {});
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
let ws;
try {
    let targets;
    for (let i = 0; i < 60; i++) {
        try { targets = await (await fetch('http://127.0.0.1:9348/json')).json(); break; } catch { await sleep(150); }
    }
    assert.ok(targets, 'Chromium starts');
    ws = new WebSocket(targets.find(target => target.type === 'page').webSocketDebuggerUrl);
    await new Promise(resolve => ws.addEventListener('open', resolve, {once: true}));
    let seq = 0;
    const pending = new Map();
    ws.addEventListener('close', () => { for (const item of pending.values()) item.reject(new Error('Browser connection closed')); });
    ws.addEventListener('message', event => {
        const data = JSON.parse(event.data);
        if (!data.id) return;
        const promise = pending.get(data.id);
        pending.delete(data.id);
        data.error ? promise.reject(data.error) : promise.resolve(data.result);
    });
    const send = (method, params = {}) => new Promise((resolve, reject) => { const id = ++seq; pending.set(id, {resolve, reject}); ws.send(JSON.stringify({id, method, params})); });
    const evaluate = async expression => (await send('Runtime.evaluate', {expression, returnByValue: true})).result.value;
    await send('Page.enable');
    await send('Page.navigate', {url: pathToFileURL(page).href});
    await sleep(500);
    for (const width of [1440, 390]) {
        await send('Emulation.setDeviceMetricsOverride', {width, height: 1000, deviceScaleFactor: 1, mobile: width < 640});
        await sleep(200);
        assert.equal(await evaluate('document.querySelector("h1").textContent'), 'Backup and Recovery');
        assert.ok(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), `page fits ${width}px`);
        assert.equal(await evaluate('document.querySelector("form[action$=preview]").enctype'), 'multipart/form-data');
        assert.equal(await evaluate('document.querySelectorAll("input[name=current_password]").length'), 0);
        const screenshot = await send('Page.captureScreenshot', {format: 'png'});
        writeFileSync(resolve(directory, `backup-${width}.png`), Buffer.from(screenshot.data, 'base64'));
        console.log(`PASS backup controls and responsive containment at ${width}px`);
    }
} finally { ws?.close(); chrome.kill(); }

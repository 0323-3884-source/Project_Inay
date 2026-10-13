// Run with: node tests/js/inay-language.browser.mjs
import assert from 'node:assert/strict';
import { mkdirSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';
import { spawn } from 'node:child_process';

const directory = resolve('storage/framework/testing/language');
mkdirSync(directory, { recursive: true });
const fixture = resolve(directory, 'fixture.html');
const script = pathToFileURL(resolve('public/js/inay-language.js')).href;
writeFileSync(fixture, `<!doctype html><html><body>
<div data-inay-language-switch><button data-inay-language="en">English</button><button data-inay-language="tl">Tagalog</button></div>
<h1>Log in to the platform</h1><nav>My Appointments</nav>
<input placeholder="Password"><p id="dynamic">Login as Mother/User</p>
<div data-chat-messages><p>Send</p></div><p id="week">Week 1 - Month 1</p>
<p id="weight">Review weight change based on pre-pregnancy BMI and pregnancy week. Pre-pregnancy weight logged. Pre-pregnancy BMI logged.</p>
<script src="${script}"></script></body></html>`);
const chrome = spawn(process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe', [
    '--headless=new', '--no-first-run', '--disable-gpu', '--no-sandbox', '--disable-software-rasterizer', '--no-default-browser-check',
    '--remote-debugging-port=9341', `--user-data-dir=${directory}/chrome`, 'about:blank',
], { windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });
chrome.stderr.on('data', (chunk) => { if (process.env.DEBUG_BROWSER) process.stderr.write(chunk); });
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
let ws;
try {
    let targets;
    for (let i = 0; i < 60; i++) {
        try { targets = await (await fetch('http://127.0.0.1:9341/json')).json(); break; } catch { await sleep(150); }
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
    const untilLoaded = async () => {
        for (let i = 0; i < 60; i++) {
            if (await evaluate('!!window.InayLanguage')) return;
            await sleep(100);
        }
        throw new Error('Language script did not load');
    };
    await send('Page.navigate', { url: pathToFileURL(fixture).href });
    await untilLoaded();
    await evaluate(`InayLanguage.set('en'); document.querySelector('[data-inay-language="tl"]').click()`);
    assert.equal(await evaluate('document.documentElement.lang'), 'tl');
    assert.equal(await evaluate('document.querySelector("h1").textContent'), 'Mag-login sa platform');
    assert.equal(await evaluate('document.querySelector("input").placeholder'), 'Hudyat (Password)');
    assert.equal(await evaluate('document.querySelector("nav").textContent'), 'Aking mga Appointment');
    assert.equal(await evaluate('document.querySelector("[data-chat-messages]").textContent'), 'Send');
    assert.equal(await evaluate('document.querySelector("#week").textContent'), 'Linggo 1 - Buwan 1');
    assert.ok((await evaluate('document.querySelector("#weight").textContent')).includes('Naitala ang BMI bago magbuntis.'));
    await evaluate(`document.querySelector('#dynamic').textContent = 'Login as Program Staff'`);
    await sleep(100);
    assert.equal(await evaluate('document.querySelector("#dynamic").textContent'), 'Mag-login bilang Kawani ng Programa');
    await send('Page.reload');
    await sleep(250);
    await untilLoaded();
    assert.equal(await evaluate('InayLanguage.get()'), 'tl');
    await evaluate(`document.querySelector('[data-inay-language="en"]').click()`);
    assert.equal(await evaluate('document.querySelector("h1").textContent'), 'Log in to the platform');
    assert.equal(await evaluate('document.querySelector("input").placeholder'), 'Password');
    assert.equal(await evaluate('document.querySelector("#week").textContent'), 'Week 1 - Month 1');
    console.log('PASS language switching, dynamic labels, message preservation, and saved preference');
} finally {
    ws?.close();
    chrome.kill();
}

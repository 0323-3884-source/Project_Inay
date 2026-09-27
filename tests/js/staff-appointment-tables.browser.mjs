// Export fixture: INAY_APPOINTMENT_TABLE_FIXTURE=1 php artisan test --filter=ClinicScheduleTest
// Then run: node tests/js/staff-appointment-tables.browser.mjs
import assert from 'node:assert/strict';
import { readFileSync, existsSync, writeFileSync } from 'node:fs';
import { resolve, sep } from 'node:path';
import { spawn } from 'node:child_process';

const root = resolve('storage/framework/testing/appointment-tables');
const publicRoot = resolve('public');
const chrome = spawn(process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe', [
    '--headless=new', '--no-first-run', '--disable-gpu', '--no-default-browser-check',
    '--remote-debugging-port=9336', `--user-data-dir=${root}/chrome`, 'about:blank',
], { windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });
chrome.stderr.on('data', chunk => { if (process.env.DEBUG_BROWSER) process.stderr.write(chunk); });
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
let ws;
try {
    let targets;
    for (let i = 0; i < 60; i++) {
        try { targets = await (await fetch('http://127.0.0.1:9336/json')).json(); break; }
        catch { await sleep(150); }
    }
    assert.ok(targets, 'Chrome started');
    ws = new WebSocket(targets.find(target => target.type === 'page').webSocketDebuggerUrl);
    await new Promise(resolve => ws.addEventListener('open', resolve, { once: true }));
    const pending = new Map();
    const errors = [];
    let sequence = 0;
    let page;
    let bookingConflict = false;
    const send = (method, params = {}) => new Promise((resolve, reject) => {
        const id = ++sequence;
        const timeout = setTimeout(() => reject(new Error(`Browser command timed out: ${method}`)), 15000);
        pending.set(id, {
            resolve: value => { clearTimeout(timeout); resolve(value); },
            reject: error => { clearTimeout(timeout); reject(error); },
        });
        ws.send(JSON.stringify({ id, method, params }));
    });
    const fulfill = (requestId, content, type, responseCode = 200) => send('Fetch.fulfillRequest', {
        requestId, responseCode,
        responseHeaders: [{ name: 'Content-Type', value: type }],
        body: Buffer.from(content).toString('base64'),
    });
    const serve = async ({ requestId, request, resourceType }) => {
        if (resourceType === 'Document') return fulfill(requestId, readFileSync(`${root}/${page}.html`), 'text/html; charset=utf-8');
        const url = new URL(request.url);
        if (url.pathname.endsWith('/clinic-schedule/calendar')) {
            const calendar = JSON.parse(readFileSync(`${root}/calendar.json`));
            if (bookingConflict) calendar.days['2026-09-07'].slots[1].status = 'booked';
            return fulfill(requestId, JSON.stringify(calendar), 'application/json');
        }
        if (request.method === 'POST' && url.pathname.endsWith('/clinic-schedule')) {
            bookingConflict = true;
            return fulfill(requestId, JSON.stringify({message: 'This time slot was just booked. Please choose another open time.'}), 'application/json', 422);
        }
        const file = resolve(publicRoot, '.' + decodeURIComponent(url.pathname));
        if (file.startsWith(publicRoot + sep) && existsSync(file)) {
            const type = file.endsWith('.css') ? 'text/css' : file.endsWith('.js') ? 'application/javascript'
                : file.endsWith('.png') ? 'image/png' : 'application/octet-stream';
            return fulfill(requestId, readFileSync(file), type);
        }
        // Layout notification polling is unrelated to these fixture-only page checks.
        return fulfill(requestId, JSON.stringify({ notifications: [], conversations: [], unread_count: 0 }), 'application/json');
    };
    ws.addEventListener('message', event => {
        const data = JSON.parse(event.data);
        if (data.id) {
            const callback = pending.get(data.id);
            pending.delete(data.id);
            data.error ? callback.reject(data.error) : callback.resolve(data.result);
        } else if (data.method === 'Fetch.requestPaused') {
            serve(data.params).catch(error => { errors.push(error.message); send('Fetch.failRequest', { requestId: data.params.requestId, errorReason: 'Aborted' }); });
        } else if (data.method === 'Runtime.exceptionThrown') errors.push(data.params.exceptionDetails.text);
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
    await send('Fetch.enable', { patterns: [{ urlPattern: '*' }] });
    page = 'staff';
    for (const width of [1440, 768, 375]) {
        await send('Emulation.setDeviceMetricsOverride', { width, height: 1000, deviceScaleFactor: 1, mobile: false });
        await send('Page.navigate', { url: 'http://127.0.0.1:8000/staff/clinic-schedule?tab=appointments' });
        for (let i = 0; i < 80; i++) {
            if (await evaluate(`document.readyState === 'complete' && !!document.querySelector('[data-table-pages] button')`)) break;
            await sleep(75);
        }
        assert.deepEqual(await evaluate(`Array.from(document.querySelectorAll('[data-appointment-table]')).map(p => p.querySelectorAll('[data-appointment-row]:not([hidden])').length)`), [5, 5, 5]);
        await evaluate(`window.panels = [...document.querySelectorAll('[data-appointment-table]')]; panels[0].querySelector('[aria-label="Page 2"]').click()`);
        assert.match(await evaluate(`panels[0].querySelector('[data-table-summary]').textContent`), /Showing 6 to 10 of 18 records/);
        assert.match(await evaluate(`panels[1].querySelector('[data-table-summary]').textContent`), /Showing 1 to 5 of 6 records/);
        await evaluate(`panels[0].querySelector('[data-appointment-row]:not([hidden]) [data-open-details]').click()`);
        assert.equal(await evaluate(`document.querySelector('[data-details-modal]').hidden`), false);
        assert.equal(await evaluate(`document.querySelector('[data-detail="notes"]').textContent`), 'Detailed appointment notes');
        await evaluate(`document.querySelector('[data-details-modal] [data-clinic-modal-close]').click(); const select = panels[0].querySelector('[data-table-status]'); select.value = 'pending'; select.dispatchEvent(new Event('change'))`);
        assert.match(await evaluate(`panels[0].querySelector('[data-table-summary]').textContent`), /Showing 1 to 5 of 6 records/);
        await evaluate(`window.actionRow = panels[0].querySelector('[data-appointment-row]:not([hidden])'); window.rowHeight = actionRow.getBoundingClientRect().height; actionRow.querySelector('.appointment-more summary').click()`);
        assert.equal(await evaluate(`actionRow.querySelector('.appointment-more-content').matches(':popover-open')`), true);
        assert.equal(await evaluate(`actionRow.getBoundingClientRect().height`), await evaluate('rowHeight'), 'Dropdown does not expand row');
        assert.ok(await evaluate(`(() => { const r = actionRow.querySelector('.appointment-more-content').getBoundingClientRect(); return r.left >= 0 && r.right <= innerWidth && r.top >= 0 && r.bottom <= innerHeight; })()`), 'Dropdown stays within viewport');
        await evaluate(`actionRow.querySelector('.appointment-more summary').dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape', bubbles: true}))`);
        assert.equal(await evaluate(`actionRow.querySelector('.appointment-more-content').matches(':popover-open')`), false);
        await evaluate(`actionRow.querySelector('.appointment-more summary').scrollIntoView({block: 'center', inline: 'center'})`);
        await sleep(100);
        const triggerPoint = await evaluate(`(() => { const r = actionRow.querySelector('.appointment-more summary').getBoundingClientRect(); return {x: r.x + r.width / 2, y: r.y + r.height / 2}; })()`);
        for (const expectedOpen of [true, false, true, false]) {
            await send('Input.dispatchMouseEvent', {type: 'mousePressed', ...triggerPoint, button: 'left', clickCount: 1});
            await send('Input.dispatchMouseEvent', {type: 'mouseReleased', ...triggerPoint, button: 'left', clickCount: 1});
            await sleep(50);
            assert.equal(await evaluate(`actionRow.querySelector('.appointment-more-content').matches(':popover-open')`), expectedOpen, 'Real pointer clicks toggle Actions open and closed');
        }
        await evaluate(`{ const input = panels[0].querySelector('[data-table-search]'); input.value = 'zz-no-match'; input.dispatchEvent(new Event('input')); }`);
        assert.match(await evaluate(`panels[0].querySelector('[data-table-summary]').textContent`), /Showing 0 to 0 of 0 records/);
        await evaluate(`{ const input = panels[0].querySelector('[data-table-search]'); input.value = 'prenatal'; input.dispatchEvent(new Event('input')); }`);
        assert.match(await evaluate(`panels[0].querySelector('[data-table-summary]').textContent`), /Showing 1 to 5 of 6 records/);
        await evaluate(`panels[2].querySelector('[aria-label="Page 3"]').click()`);
        assert.match(await evaluate(`panels[2].querySelector('[data-table-summary]').textContent`), /Showing 11 to 12 of 12 records/);
        assert.ok(await evaluate('document.documentElement.scrollWidth <= window.innerWidth + 1'), `No page overflow at ${width}`);
        await evaluate(`panels[0].scrollIntoView({block: 'start'})`);
        const shot = await send('Page.captureScreenshot', { format: 'png' });
        writeFileSync(`${root}/tables-${width}.png`, Buffer.from(shot.data, 'base64'));
        console.log(`PASS independent pagination, filtering, details, and layout at ${width}px`);
    }
    assert.deepEqual(errors, [], 'No browser exceptions');
} finally {
    ws?.close();
    chrome.kill();
}

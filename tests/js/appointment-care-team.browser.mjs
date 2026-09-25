// Export fixtures: INAY_CARE_TEAM_FIXTURES=1 php artisan test --filter=AppointmentCareTeamTest
// Then run: node tests/js/appointment-care-team.browser.mjs
import assert from 'node:assert/strict';
import { readFileSync, existsSync, writeFileSync } from 'node:fs';
import { resolve, sep } from 'node:path';
import { spawn } from 'node:child_process';

const root = resolve('storage/framework/testing/care-team');
const publicRoot = resolve('public');
const chrome = spawn(process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe', [
    '--headless=new', '--no-first-run', '--disable-gpu', '--no-default-browser-check',
    '--remote-debugging-port=9335', `--user-data-dir=${root}/chrome`, 'about:blank',
], { windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });
chrome.stderr.on('data', chunk => { if (process.env.DEBUG_BROWSER) process.stderr.write(chunk); });
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
let ws;
try {
    let targets;
    for (let i = 0; i < 60; i++) {
        try { targets = await (await fetch('http://127.0.0.1:9335/json')).json(); break; }
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
    for (page of ['staff', 'mother', 'booking']) {
        for (const width of [1440, 375]) {
            await send('Emulation.setDeviceMetricsOverride', { width, height: 1000, deviceScaleFactor: 1, mobile: false });
            await send('Page.navigate', { url: `http://127.0.0.1:8000/${page}/clinic-schedule?tab=profile&width=${width}` });
            bookingConflict = false;
            const selector = page === 'staff' ? '[data-midwife-form]' : page === 'booking' ? '[data-book-doctor]' : '.care-appointment';
            for (let i = 0; i < 80; i++) {
                if (await evaluate(`document.readyState === 'complete' && !!document.querySelector('${selector}')`)) break;
                await sleep(75);
            }
            await sleep(150);
            assert.ok(await evaluate('document.documentElement.scrollWidth <= window.innerWidth + 1'), `${page} has no horizontal overflow at ${width}`);
            if (page === 'staff') {
                assert.ok(await evaluate(`document.querySelector('[data-midwife-summary]').textContent.includes('Rebecca Escote')`));
                await evaluate(`document.querySelector('[data-midwife-selection]').value = 'new'; document.querySelector('[data-midwife-selection]').dispatchEvent(new Event('change'))`);
                assert.equal(await evaluate(`document.querySelector('[data-midwife-new]').hidden`), false);
                assert.equal(await evaluate(`document.querySelector('[name=midwife_full_name]').required`), true);
                await evaluate(`document.querySelector('[data-midwife-selection]').value = ''; document.querySelector('[data-midwife-selection]').dispatchEvent(new Event('change'))`);
                assert.equal(await evaluate(`document.querySelector('[name=midwife_full_name]').disabled`), true);
                assert.equal(await evaluate(`document.querySelector('[data-midwife-summary]').hidden`), true);
                await evaluate(`document.querySelector('[data-midwife-selection]').value = 'staff:2'; document.querySelector('[data-midwife-selection]').dispatchEvent(new Event('change'))`);
            } else if (page === 'mother') {
                assert.ok(await evaluate(`document.querySelector('.care-appointment').textContent.includes('Rebecca Escote')`));
                assert.ok(await evaluate(`document.querySelector('[data-view-active-appointment]').getAttribute('href') === '#appointment-1'`));
                assert.equal(await evaluate(`getComputedStyle(document.querySelector('.doctor-book.is-locked, .doctor-book.is-pending, .doctor-book.is-confirmed')).backgroundColor`), 'rgb(52, 64, 84)');
            } else {
                await evaluate(`document.querySelector('[data-book-doctor]').click()`);
                for (let i = 0; i < 60; i++) {
                    if (await evaluate(`!!document.querySelector('.booking-slot.is-booked')`)) break;
                    await sleep(50);
                }
                if (!await evaluate(`!!document.querySelector('.booking-slot.is-booked')`)) {
                    console.log(await evaluate(`({status: document.querySelector('[data-calendar-status]').textContent, hidden: document.querySelector('[data-booking-modal]').hidden, active: document.querySelector('[data-active-appointment-json]').textContent, today: document.querySelector('[data-mother-appointments]').dataset.today, slots: document.querySelector('[data-time-slots]').innerHTML})`), errors);
                }
                assert.equal(await evaluate(`document.querySelector('.booking-slot.is-booked').disabled`), true);
                assert.equal(await evaluate(`getComputedStyle(document.querySelector('.booking-slot.is-booked')).backgroundColor`), 'rgb(52, 64, 84)');
                assert.equal(await evaluate(`document.querySelector('[data-date="2026-09-14"]').disabled`), true);
                assert.equal(await evaluate(`getComputedStyle(document.querySelector('[data-date="2026-09-14"]')).backgroundColor`), 'rgb(52, 64, 84)');
                assert.equal(await evaluate(`document.querySelector('[data-booking-submit]').disabled`), true);
                await evaluate(`document.querySelector('.booking-slot.is-available').click()`);
                assert.equal(await evaluate(`document.querySelector('[data-booking-submit]').disabled`), false);
                assert.equal(await evaluate(`document.querySelector('.booking-slot.is-selected').getAttribute('aria-pressed')`), 'true');
                const shot = await send('Page.captureScreenshot', { format: 'png' });
                writeFileSync(`${root}/calendar-${width}.png`, Buffer.from(shot.data, 'base64'));
                await evaluate(`document.querySelector('[data-booking-submit]').click()`);
                for (let i = 0; i < 60; i++) {
                    if (await evaluate(`document.querySelector('[data-booking-error]').textContent.includes('just booked') && document.querySelectorAll('.booking-slot.is-booked').length === 2`)) break;
                    await sleep(50);
                }
                assert.equal(await evaluate(`document.querySelector('[data-booking-submit]').disabled`), true);
                assert.equal(await evaluate(`document.querySelectorAll('.booking-slot.is-booked').length`), 2);
                assert.ok(await evaluate(`document.querySelector('[data-booking-error]').textContent.includes('just booked')`));
                await evaluate(`document.querySelector('[data-calendar-next]').click()`);
                await sleep(100);
                assert.ok(await evaluate(`document.querySelector('[data-calendar-month]').textContent.includes('October')`));
                await evaluate(`document.querySelector('.booking-close').click()`);
                assert.equal(await evaluate(`document.querySelector('[data-booking-modal]').hidden`), true);
                await evaluate(`document.querySelector('[data-filter-open]').click()`);
                assert.equal(await evaluate(`document.querySelector('[data-filter-modal]').hidden`), false);
                await evaluate(`document.querySelector('[data-barangay-search]').value = 'nonexistent'; document.querySelector('[data-barangay-search]').dispatchEvent(new Event('input'))`);
                assert.equal(await evaluate(`document.querySelector('[data-filter-empty]').hidden`), false);
                await evaluate(`document.querySelector('.filter-close').click()`);
            }
            await evaluate(`document.querySelector('${selector}').scrollIntoView({block:'center'})`);
            await sleep(100);
            const screenshot = await send('Page.captureScreenshot', { format: 'png' });
            writeFileSync(`${root}/${page}-${width}.png`, Buffer.from(screenshot.data, 'base64'));
            console.log(`PASS ${page} care team at ${width}px`);
        }
    }
    assert.deepEqual(errors, [], 'No browser exceptions');
} finally {
    ws?.close();
    chrome.kill();
}

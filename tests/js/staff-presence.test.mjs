import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';

function presence(status = 200) {
    const handlers = {};
    const timers = [];
    const calls = [];
    let time = 1000;
    const document = {hidden: false, currentScript: {dataset: {presenceUrl: '/staff/activity', csrf: 'synthetic-csrf'}}, addEventListener: (name, fn) => { handlers[name] = fn; }};
    vm.runInNewContext(readFileSync('public/js/staff-presence.js', 'utf8'), {
        document, navigator: {onLine: true}, Date: {now: () => time},
        fetch: async (...args) => { calls.push(args); return {status, redirected: false}; },
        setInterval: (fn, ms) => { timers.push({fn, ms}); },
    });
    return {document, calls, handlers, timers, advance: ms => {time += ms;}};
}
const settle = () => new Promise(resolve => setImmediate(resolve));

test('staff entry immediately reports authenticated presence with CSRF', async () => {
    const env = presence();
    await settle();
    assert.equal(env.calls.length, 1);
    assert.equal(env.calls[0][1].method, 'POST');
    assert.equal(env.calls[0][1].headers['X-CSRF-TOKEN'], 'synthetic-csrf');
    assert.equal(env.timers[0].ms, 60000);
    env.document.hidden = true;
    await env.timers[0].fn();
    assert.equal(env.calls.length, 1);
});

test('idle tabs stop extending presence and interaction resumes the heartbeat', async () => {
    const env = presence();
    await settle();
    env.advance(300000);
    await env.timers[0].fn();
    assert.equal(env.calls.length, 1);
    env.handlers.pointerdown();
    await env.timers[0].fn();
    assert.equal(env.calls.length, 2);
});

test('expired authentication stops further heartbeats', async () => {
    const env = presence(419);
    await settle();
    await env.timers[0].fn();
    assert.equal(env.calls.length, 1);
});

test('admin status refreshes immediately then every ten seconds without reloading', async () => {
    const timers = [];
    const badge = {dataset: {staffActivity: 'program_staff', staffId: '7'}, classList: {contains: () => false}};
    let calls = 0;
    vm.runInNewContext(readFileSync('public/js/staff-activity.js', 'utf8'), {
        document: {currentScript: {dataset: {activityUrl: '/admin/staff-activity'}}, querySelectorAll: () => [badge], hidden: false, addEventListener() {}},
        window: {location: {origin: 'https://example.test'}}, URL, Date,
        fetch: async url => { calls++; assert.equal(url.searchParams.get('program_ids[]'), '7'); return {ok: true, headers: {get: () => 'application/json'}, json: async () => ({program_staff: [{id: 7, active: calls === 1}]})}; },
        setInterval: (fn, ms) => {timers.push({fn, ms});},
    });
    await settle();
    assert.equal(badge.textContent, 'Active');
    assert.equal(timers[0].ms, 10000);
    await timers[0].fn();
    assert.equal(badge.textContent, 'Inactive');
});

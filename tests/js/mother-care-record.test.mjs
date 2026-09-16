import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const script = readFileSync(new URL('../../public/js/mother-care-record.js', import.meta.url), 'utf8');

function setup(fetchDocument, pdfViewerEnabled = true, section = null) {
    const buttons = ['print', 'pdf'].map((format) => ({
        dataset: { casefileRecord: format, recordUrl: `/staff/mothers/7/${format}` },
        innerHTML: format,
        setAttribute() {}, removeAttribute() {},
        addEventListener(event, handler) { this.click = handler; },
    }));
    const elements = Object.fromEntries(['error', 'ready', 'open', 'status'].map((key) => [`[data-record-${key}]`, { hidden: true }]));
    if (section) elements['[data-casefile-tab][aria-selected="true"]'] = { dataset: { casefileTab: section } };
    const state = { downloads: [], prints: 0, dashboardPrints: 0, revoked: [], requests: [] };
    const document = {
        querySelectorAll: () => buttons,
        querySelector: (key) => elements[key],
        createElement: (tag) => ({
            tag, style: {}, setAttribute() {}, remove() {},
            contentWindow: { focus() {}, print() { state.prints++; } },
            click() { state.downloads.push(this.download); },
        }),
        body: { appendChild(element) { if (element.tag === 'iframe') queueMicrotask(() => element.onload()); } },
    };
    vm.runInNewContext(script, {
        document,
        fetch: async (...args) => { state.requests.push(args); return fetchDocument(...args); },
        navigator: { pdfViewerEnabled },
        window: { addEventListener() {}, print() { state.dashboardPrints++; } },
        URL: { createObjectURL: () => 'blob:record', revokeObjectURL: (url) => state.revoked.push(url) },
        AbortController, setTimeout, clearTimeout,
    });
    return { buttons, elements, state };
}

const pdf = () => new Response('%PDF-1.4 synthetic test', { headers: {
    'Content-Type': 'application/pdf',
    'Content-Disposition': 'attachment; filename="INAY-00007-mother-care-record.pdf"',
} });

test('export downloads the generated PDF and blocks duplicate clicks while loading', async () => {
    let release;
    const response = new Promise((resolve) => { release = resolve; });
    const { buttons, state, elements } = setup(() => response);
    const pending = buttons[1].click();
    assert.equal(buttons[1].textContent, 'Generating PDF...');
    assert.ok(buttons.every((button) => button.disabled));
    await buttons[1].click();
    await buttons[0].click();
    assert.equal(state.requests.length, 1);
    release(pdf());
    await pending;
    assert.deepEqual(state.downloads, ['INAY-00007-mother-care-record.pdf']);
    assert.ok(buttons.every((button) => !button.disabled));
    assert.equal(elements['[data-record-error]'].hidden, true);
    assert.equal(state.requests[0][1].cache, 'no-store');
});

test('print targets the generated document and never prints the dashboard', async () => {
    const { buttons, state } = setup(pdf);
    await buttons[0].click();
    assert.equal(state.prints, 1);
    assert.equal(state.dashboardPrints, 0);
    assert.deepEqual(state.downloads, []);
    await buttons[0].click();
    assert.deepEqual(state.revoked, ['blob:record']);
});

test('failures, expired sessions, and invalid files show a safe error and re-enable controls', async () => {
    for (const response of [
        () => { throw new Error('PRIVATE network detail'); },
        () => new Response('PRIVATE exception', { status: 500 }),
        () => new Response('<html>Login</html>', { headers: { 'Content-Type': 'text/html' } }),
        () => new Response('not a PDF', { headers: { 'Content-Type': 'application/pdf' } }),
    ]) {
        const { buttons, elements, state } = setup(response);
        await buttons[1].click();
        assert.equal(elements['[data-record-error]'].textContent, 'Unable to generate the mother record. Please try again.');
        assert.equal(elements['[data-record-error]'].hidden, false);
        assert.ok(buttons.every((button) => !button.disabled));
        assert.equal(state.downloads.length, 0);
    }
});

test('browsers without an embedded PDF viewer get a link to the generated document', async () => {
    const { buttons, elements, state } = setup(pdf, false);
    await buttons[0].click();
    assert.equal(elements['[data-record-ready]'].hidden, false);
    assert.equal(elements['[data-record-open]'].href, 'blob:record');
    assert.equal(state.prints, 0);
});

test('both mother controls read the active tab at click time, including after switching tabs', async () => {
    const { buttons, elements, state } = setup(pdf, true, 'overview');
    for (const section of ['overview', 'monitoring', 'learning-documents', 'notes']) {
        elements['[data-casefile-tab][aria-selected="true"]'].dataset.casefileTab = section;
        for (const button of buttons) {
            await button.click();
            assert.equal(state.requests.at(-1)[0], `${button.dataset.recordUrl}?section=${section}`);
        }
    }
});

test('child controls request only the child route and show child-specific errors', async () => {
    const { buttons, elements, state } = setup(() => new Response('', { status: 500 }));
    for (const button of buttons) {
        button.dataset.recordUrl = `/staff/neonatal/12/${button.dataset.casefileRecord}`;
        button.dataset.recordKind = 'child';
        await button.click();
        assert.equal(state.requests.at(-1)[0], button.dataset.recordUrl);
        assert.equal(elements['[data-record-error]'].textContent, 'Unable to generate the child record. Please try again.');
    }
});

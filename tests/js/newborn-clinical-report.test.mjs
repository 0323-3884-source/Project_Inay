import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const script = readFileSync(new URL('../../public/js/newborn-clinical-report.js', import.meta.url), 'utf8');
test('statistics printing selects its own template and document title', async () => {
    let handler;
    let frame;
    let printed = false;
    const button = { addEventListener: (_, callback) => { handler = callback; } };
    vm.runInNewContext(script, {
        document: {
            querySelector: (selector) => selector === '[data-statistics-report-print]' ? button : null,
            getElementById: (id) => id === 'statistics-clinical-report' ? { id, innerHTML: '<body>Statistics only</body>' } : null,
            createElement: () => ({ style: {}, setAttribute() {}, contentDocument: { fonts: { ready: Promise.resolve() } }, contentWindow: { focus() {}, print() { printed = true; } } }),
            body: { appendChild: (element) => { frame = element; } },
        },
        window: { addEventListener() {}, print() { assert.fail('Must not print dashboard'); } },
    });
    handler();
    await frame.onload();
    assert.equal(frame.title, 'Program Staff Clinical Statistics Report');
    assert.match(frame.srcdoc, /Statistics only/);
    assert.equal(printed, true);
});

test('newborn printing uses only the dedicated document and releases the button for subsequent prints', async () => {
    let handler;
    let frame;
    let prints = 0;
    let removed = 0;
    const button = { addEventListener: (_, callback) => { handler = callback; } };
    vm.runInNewContext(script, {
        document: {
            querySelector: () => button,
            getElementById: () => ({ innerHTML: '<head><title>Newborn report</title></head><body>Selected child</body>' }),
            createElement: () => ({
                style: {}, setAttribute() {}, remove() { removed++; },
                contentDocument: { fonts: { ready: Promise.resolve() } },
                contentWindow: { focus() {}, print() { prints++; } },
            }),
            body: { appendChild: (element) => { frame = element; } },
        },
        window: { addEventListener() {}, print() { assert.fail('Dashboard must never be printed'); } },
    });
    handler();
    assert.equal(button.disabled, true);
    handler();
    assert.equal(removed, 0);
    assert.match(frame.srcdoc, /Selected child/);
    assert.doesNotMatch(frame.srcdoc, /Regional Clinical Surveillance/);
    await frame.onload();
    assert.equal(prints, 1);
    assert.equal(button.disabled, false);
    handler();
    assert.equal(removed, 1);
    await frame.onload();
    assert.equal(prints, 2);
});

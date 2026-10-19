import {webcrypto} from 'node:crypto';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';
import assert from 'node:assert/strict';

function workspace(store = new Map()) {
    class Element {
        constructor(tag = 'div') { this.tagName = tag.toUpperCase(); this.children = []; this.style = {}; this.handlers = {}; this.value = ''; }
        append(...items) { this.children.push(...items); }
        replaceChildren(...items) { this.children = items; }
        add(item) { this.children.push(item); }
        addEventListener(type, fn) { this.handlers[type] = fn; }
        querySelector() { return this.button ||= new Element('button'); }
        get selectedOptions() { return this.children.filter(item => String(item.value) === String(this.value)); }
    }
    const elements = Object.fromEntries(['records','entry','status','passphrase','unlock','lock','workspace','refresh','sync','fields','pending'].map(id => [`offline-${id}`, new Element()]));
    const root = elements['offline-records']; root.dataset = {owner:'account-1', context:'/context', sync:'/sync'};
    const form = elements['offline-entry']; form.elements = {kind:new Element('select'), patient_id:new Element('select')}; form.elements.kind.value = 'growth';
    const navigator = {onLine:true}; const events = {}; let uploads = 0, conflict = false;
    const context = {crypto:webcrypto, TextEncoder, TextDecoder, Uint8Array, btoa, atob, console,
        document:{getElementById:id => elements[id], createElement:tag => new Element(tag)},
        navigator, window:{addEventListener:(name, fn) => { events[name] = fn; }},
        localStorage:{getItem:key => store.get(key) ?? null, setItem:(key, value) => store.set(key, value)},
        Option: function(text, value) { this.textContent = text; this.value = value; },
        FormData: class { constructor() { return new Map([['kind','growth'],['patient_id','1'],['measured_at','2026-10-18'],['age_months','6'],['weight','7'],['height','65']]); } },
        confirm:() => true,
        fetch:async url => {
            if (!navigator.onLine) throw new Error('offline');
            const payload = url === '/context' ? {owner:'account-1', csrf:'token', mothers:[], children:[{id:1,name:'Private Child'}],testTypes:{}} : {message:conflict ? 'Existing measurement' : 'Saved'};
            if (url === '/sync') uploads++;
            return {ok: url === '/context' || !conflict, status: url === '/context' || !conflict ? 200 : 409, json:async () => payload};
        }
    };
    vm.runInNewContext(readFileSync('public/js/offline-records.js','utf8'), context);
    return {elements, navigator, events, store, form, uploads:() => uploads, conflict:() => { conflict = true; }, async unlock(pass = 'long device passphrase') { elements['offline-passphrase'].value = pass; await elements['offline-unlock'].onclick(); }};
}

test('offline entries are encrypted, survive reload, and synchronize when reconnected', async () => {
    const app = workspace(); await app.unlock(); app.navigator.onLine = false;
    app.form.elements.patient_id.value = '1';
    await app.form.handlers.submit({preventDefault(){}});
    const encrypted = [...app.store.values()][0];
    assert.equal(encrypted.includes('Private Child'), false);
    assert.equal(encrypted.includes('measured_at'), false);
    assert.equal(app.uploads(), 0);
    const reloaded = workspace(app.store); reloaded.navigator.onLine = false;
    await reloaded.unlock();
    assert.equal(reloaded.elements['offline-pending'].children.length, 1);
    reloaded.navigator.onLine = true; await reloaded.events.online();
    assert.equal(reloaded.uploads(), 1);
    assert.equal(reloaded.elements['offline-pending'].children.length, 0);
});

test('wrong passphrase cannot unlock and conflicts remain pending', async () => {
    const app = workspace(); await app.unlock(); app.navigator.onLine = false;
    app.form.elements.patient_id.value = '1'; await app.form.handlers.submit({preventDefault(){}});
    const second = workspace(app.store); await second.unlock('wrong passphrase here');
    assert.match(second.elements['offline-status'].textContent, /Unable to unlock/);
    app.navigator.onLine = true; app.conflict(); await app.events.online();
    assert.equal(app.elements['offline-pending'].children.length, 1);
    assert.match(app.elements['offline-pending'].children[0].children[0].textContent, /Existing measurement/);
});

(() => {
    const root = document.getElementById('offline-records');
    if (!root) return;
    const $ = id => document.getElementById(`offline-${id}`);
    const form = $('entry');
    const storageKey = `inay-offline-records:${root.dataset.owner}`;
    let key, salt, data, csrf, busy = false, lastSaved;
    const encoder = new TextEncoder();
    const b64 = bytes => btoa(Array.from(bytes, byte => String.fromCharCode(byte)).join(''));
    const bytes = text => Uint8Array.from(atob(text), c => c.charCodeAt(0));
    const status = message => { $('status').textContent = message; };
    async function derive(passphrase, salt) {
        const material = await crypto.subtle.importKey('raw', encoder.encode(passphrase), 'PBKDF2', false, ['deriveKey']);
        return crypto.subtle.deriveKey({name: 'PBKDF2', salt, iterations: 310000, hash: 'SHA-256'}, material, {name: 'AES-GCM', length: 256}, false, ['encrypt', 'decrypt']);
    }
    async function persist() {
        const iv = crypto.getRandomValues(new Uint8Array(12));
        const encrypted = await crypto.subtle.encrypt({name: 'AES-GCM', iv}, key, encoder.encode(JSON.stringify(data)));
        if (localStorage.getItem(storageKey) !== lastSaved) throw new Error('Device records changed in another tab. Lock and unlock this workspace before continuing.');
        const next = JSON.stringify({salt: b64(salt), iv: b64(iv), value: b64(new Uint8Array(encrypted))});
        localStorage.setItem(storageKey, next);
        lastSaved = next;
    }
    async function context() {
        const response = await fetch(root.dataset.context, {cache: 'no-store', headers: {Accept: 'application/json'}});
        if (!response.ok || response.redirected) throw new Error('Sign in as the same staff account online, then reopen this workspace.');
        const result = await response.json();
        if (result.owner !== root.dataset.owner) throw new Error('Account changed. Reopen the workspace for your account.');
        csrf = result.csrf;
        return result;
    }
    const fields = {
        maternal: [['recorded_at','Record date','date'],['pregnancy_week','Pregnancy week','number'],['weight','Weight (kg)','number'],['bp_systolic','Systolic BP (mmHg)','number'],['bp_diastolic','Diastolic BP (mmHg)','number'],['blood_sugar_test_type','Blood sugar test type','select'],['blood_sugar','Blood sugar (mg/dL)','number'],['temperature','Temperature (°C)','number'],['heart_rate','Heart rate (bpm)','number'],['notes','Notes','textarea',true]],
        growth: [['measured_at','Measurement date','date'],['age_months','Age (months)','number'],['weight','Weight (kg)','number'],['height','Length / height (cm)','number'],['head_circumference','Head circumference (cm)','number',true],['temperature','Temperature (°C)','number',true],['remarks','Remarks','textarea',true]]
    };
    function renderForm() {
        const kind = form.elements.kind.value;
        form.elements.patient_id.replaceChildren(...data.context[kind === 'maternal' ? 'mothers' : 'children'].map(p => new Option(`${p.name} (#${p.id})`, p.id)));
        $('fields').replaceChildren();
        for (const [name, title, type, optional] of fields[kind]) {
            const label = document.createElement('label'); label.textContent = title + (optional ? ' (optional)' : '');
            const input = document.createElement(type === 'textarea' ? 'textarea' : type === 'select' ? 'select' : 'input');
            input.name = name; input.required = !optional;
            if (input.tagName === 'INPUT') { input.type = type; if (type === 'number') input.step = 'any'; }
            if (type === 'select') for (const [value, text] of Object.entries(data.context.testTypes)) input.add(new Option(text, value));
            input.style.cssText = 'display:block;width:100%;padding:8px;border:1px solid #aaa;border-radius:6px';
            label.append(input); $('fields').append(label);
        }
    }
    function renderPending() {
        $('pending').replaceChildren();
        for (const entry of data.entries) {
            const row = document.createElement('div'); row.style.cssText = 'padding:12px;border-bottom:1px solid #ddd';
            const text = document.createElement('p'); text.textContent = `${entry.name} — ${entry.kind}: ${JSON.stringify(entry.values)} — ${entry.error || 'Pending synchronization'}`;
            const discard = document.createElement('button'); discard.type = 'button'; discard.textContent = 'Discard entry';
            discard.disabled = busy;
            discard.onclick = async () => {
                if (!confirm('Discard this pending entry? It will not be uploaded.')) return;
                const before = data.entries; data.entries = before.filter(e => e.operation_id !== entry.operation_id);
                try { await persist(); renderPending(); } catch { data.entries = before; status('Could not update device storage.'); }
            };
            row.append(text, discard); $('pending').append(row);
            if (entry.requiresConfirmation) {
                const approve = document.createElement('button'); approve.type = 'button'; approve.textContent = 'Confirm flagged values and retry'; approve.disabled = busy;
                approve.onclick = async () => {
                    if (!confirm(`Review these flagged values before confirming:\n${entry.error}`)) return;
                    entry.values.confirmed_unusual = true; delete entry.error; delete entry.requiresConfirmation;
                    try { await persist(); await sync(); } catch (error) { status(error.message); }
                };
                row.append(approve);
            }
        }
    }
    async function sync() {
        if (!key || busy || !navigator.onLine) return;
        busy = true; form.querySelector('button').disabled = true; renderPending();
        try {
            await context();
            for (const entry of [...data.entries]) {
                if (entry.error) continue; // Never repeatedly retry validation failures or conflicts.
                const response = await fetch(root.dataset.sync, {method: 'POST', headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf}, body: JSON.stringify(entry)});
                if (response.redirected || [401,403,419,429].includes(response.status) || response.status >= 500) throw new Error('Synchronization paused. Check your connection and sign-in, then try again.');
                const result = await response.json();
                if (response.ok) data.entries = data.entries.filter(e => e.operation_id !== entry.operation_id);
                else {
                    entry.error = [result.message, ...Object.values(result.errors || {}).flat(), ...(result.warnings || [])].join(' ');
                    entry.requiresConfirmation = result.requires_confirmation === true;
                }
                await persist();
            }
            status(`${data.entries.length} pending entries. Entries marked for review remain on this device.`);
        } catch (error) { status(error.message || 'Connection lost. Pending entries remain on this device.'); }
        finally { busy = false; form.querySelector('button').disabled = false; renderPending(); }
    }
    $('unlock').onclick = async () => {
        if (busy || key) return;
        if (!crypto.subtle || !crypto.randomUUID) { status('Offline records require HTTPS or localhost and a browser with Web Crypto support.'); return; }
        const passphrase = $('passphrase').value;
        if (passphrase.length < 12) { status('Use a passphrase with at least 12 characters.'); return; }
        try {
            lastSaved = localStorage.getItem(storageKey);
            const saved = JSON.parse(lastSaved || 'null');
            salt = saved ? bytes(saved.salt) : crypto.getRandomValues(new Uint8Array(16));
            const candidate = await derive(passphrase, salt);
            data = saved ? JSON.parse(new TextDecoder().decode(await crypto.subtle.decrypt({name: 'AES-GCM', iv: bytes(saved.iv)}, candidate, bytes(saved.value)))) : {context: await context(), entries: []};
            key = candidate; await persist(); $('passphrase').value = '';
            $('workspace').hidden = false; renderForm(); renderPending(); status('Unlocked. Entries are encrypted when saved on this device.');
            await sync();
        } catch { key = null; data = null; status('Unable to unlock. Check the passphrase, device storage, and connection for first-time setup.'); }
    };
    function lock() { if (busy) return; key = null; data = null; csrf = null; $('workspace').hidden = true; $('pending').replaceChildren(); $('fields').replaceChildren(); form.elements.patient_id.replaceChildren(); $('passphrase').value = ''; status('Locked'); }
    $('lock').onclick = lock;
    $('refresh').onclick = async () => {
        if (busy) return;
        try { data.context = await context(); await persist(); renderForm(); status('Patient list updated.'); } catch (error) { status(error.message); }
    };
    $('sync').onclick = async () => { if (!data || busy) return; data.entries.forEach(e => { delete e.error; }); await sync(); };
    form.elements.kind.onchange = renderForm;
    form.addEventListener('submit', async event => {
        event.preventDefault(); if (busy || !key) return;
        busy = true; form.querySelector('button').disabled = true;
        const values = Object.fromEntries(new FormData(form)); const kind = values.kind; const patient_id = Number(values.patient_id); delete values.kind; delete values.patient_id;
        const entry = {operation_id: crypto.randomUUID(), kind, patient_id, name: form.elements.patient_id.selectedOptions[0].textContent, values};
        data.entries.push(entry);
        try { await persist(); status('Saved encrypted on this device. Awaiting synchronization.'); renderForm(); }
        catch { data.entries.pop(); status('Device storage unavailable or full. Entry was not saved; keep this form open.'); }
        finally { busy = false; form.querySelector('button').disabled = false; renderPending(); }
        await sync();
    });
    window.addEventListener('online', sync);
    window.addEventListener('offline', () => status('Offline. You can save encrypted entries in this workspace.'));
    window.addEventListener('pagehide', lock);
})();

(() => {
    const root = document.querySelector('[data-midwife-form]');
    if (!root) return;
    const select = root.querySelector('[data-midwife-selection]');
    const fields = root.querySelector('[data-midwife-new]');
    const summary = root.querySelector('[data-midwife-summary]');
    const status = root.querySelector('[data-midwife-existing-status]');
    const options = JSON.parse(root.querySelector('[data-midwife-options]').textContent);
    const form = root.closest('form');
    const editor = root.querySelector('[data-midwife-editor]');
    const toggle = root.querySelector('[data-midwife-toggle]');
    const current = root.querySelector('[data-midwife-current]');
    const updateButton = () => {
        toggle.setAttribute('aria-expanded', String(!editor.hidden));
        toggle.textContent = !editor.hidden ? 'Done' : (select.value ? 'Change Midwife' : 'Add Midwife');
    };
    toggle.addEventListener('click', () => {
        if (!editor.hidden) {
            const invalid = [...editor.querySelectorAll('input, select')].find(input => !input.disabled && !input.checkValidity());
            if (invalid) { invalid.reportValidity(); return; }
        }
        editor.hidden = !editor.hidden;
        updateButton();
        if (!editor.hidden) select.focus();
    });
    form.addEventListener('invalid', event => {
        if (editor.contains(event.target)) { editor.hidden = false; updateButton(); }
    }, true);
    const syncLocation = () => {
        fields.querySelector('[name="midwife_barangay"]').value = form.querySelector('[name="assigned_barangay"]').value;
        fields.querySelector('[name="midwife_facility"]').value = form.querySelector('[name="assigned_facility"]').value;
    };
    form.querySelectorAll('[name="assigned_barangay"], [name="assigned_facility"]').forEach(input => input.addEventListener('input', syncLocation));
    form.addEventListener('submit', syncLocation);

    function render() {
        const isNew = select.value === 'new';
        syncLocation();
        fields.hidden = !isNew;
        fields.querySelectorAll('input, select').forEach((input) => {
            input.disabled = !isNew;
            input.required = isNew && input.hasAttribute('data-midwife-required');
        });
        if (status) {
            status.hidden = select.value !== status.dataset.midwifeExistingStatus;
            status.querySelector('select').disabled = status.hidden;
        }
        const chosen = options.find((option) => option.value === select.value);
        current.textContent = isNew ? (fields.querySelector('[name="midwife_full_name"]').value || 'New midwife') : (chosen?.name || 'No assigned midwife');
        updateButton();
        summary.replaceChildren();
        summary.hidden = !chosen;
        if (!chosen) return;
        const facts = {
            'Midwife full name': chosen.name,
            'Availability status': chosen.status,
        };
        Object.entries(facts).forEach(([label, value]) => {
            const fact = document.createElement('div');
            const title = document.createElement('span');
            const text = document.createElement('strong');
            title.textContent = label;
            text.textContent = value;
            fact.append(title, text);
            summary.append(fact);
        });
    }
    select.addEventListener('change', render);
    root.querySelector('[data-midwife-add]').addEventListener('click', () => {
        select.value = 'new';
        render();
        fields.querySelector('[name="midwife_full_name"]').focus();
    });
    fields.querySelector('[name="midwife_full_name"]').addEventListener('input', event => { current.textContent = event.target.value || 'New midwife'; });
    render();
})();

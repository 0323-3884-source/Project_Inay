(() => {
    const root = document.querySelector('[data-midwife-form]');
    if (!root) return;
    const select = root.querySelector('[data-midwife-selection]');
    const fields = root.querySelector('[data-midwife-new]');
    const summary = root.querySelector('[data-midwife-summary]');
    const status = root.querySelector('[data-midwife-existing-status]');
    const options = JSON.parse(root.querySelector('[data-midwife-options]').textContent);

    function render() {
        const isNew = select.value === 'new';
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
        summary.replaceChildren();
        summary.hidden = !chosen;
        if (!chosen) return;
        const facts = {
            'Midwife full name': chosen.name,
            'Professional role': 'Midwife',
            'Assigned barangay': chosen.barangay || 'Not set',
            'Assigned healthcare facility': chosen.facility || 'Not set',
            'Contact number': chosen.contact || 'Not provided',
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
    render();
})();

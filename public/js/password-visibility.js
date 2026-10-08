(() => {
    document.querySelectorAll('input[type="password"]').forEach((input, index) => {
        if (input.closest('.auth-password-wrap')) return;
        const label = input.labels?.[0]?.textContent.trim() || 'password';
        if (!input.id) {
            let id = `password-field-${index}`;
            while (document.getElementById(id)) id += '-toggle';
            input.id = id;
        }
        const wrapper = document.createElement('span');
        wrapper.className = 'auth-password-wrap';
        input.before(wrapper);
        wrapper.append(input);
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'auth-password-toggle';
        const eye = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
        const eyeOff = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18M10.6 5.1 12 5c6.5 0 10 7 10 7a19 19 0 0 1-3.1 4M6.3 6.3A21 21 0 0 0 2 12s3.5 7 10 7a12 12 0 0 0 5.7-1.7M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>';
        toggle.innerHTML = eye;
        toggle.setAttribute('aria-controls', input.id);
        toggle.title = 'Show password';
        toggle.setAttribute('aria-label', `Show ${label.toLowerCase()}`);
        toggle.setAttribute('aria-pressed', 'false');
        toggle.addEventListener('click', () => {
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            toggle.innerHTML = visible ? eyeOff : eye;
            toggle.title = visible ? 'Hide password' : 'Show password';
            toggle.setAttribute('aria-label', `${visible ? 'Hide' : 'Show'} ${label.toLowerCase()}`);
            toggle.setAttribute('aria-pressed', String(visible));
        });
        wrapper.append(toggle);
    });
})();

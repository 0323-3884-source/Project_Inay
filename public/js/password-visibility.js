(() => {
    document.querySelectorAll('.auth-card input[type="password"]').forEach((input) => {
        if (input.closest('.auth-password-wrap')) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'auth-password-wrap';
        input.before(wrapper);
        wrapper.append(input);
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'auth-password-toggle';
        toggle.textContent = 'Show';
        toggle.setAttribute('aria-controls', input.id);
        const label = input.labels?.[0]?.textContent.trim() || 'password';
        toggle.setAttribute('aria-label', `Show ${label.toLowerCase()}`);
        toggle.setAttribute('aria-pressed', 'false');
        toggle.addEventListener('click', () => {
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            toggle.textContent = visible ? 'Hide' : 'Show';
            toggle.setAttribute('aria-label', `${visible ? 'Hide' : 'Show'} ${label.toLowerCase()}`);
            toggle.setAttribute('aria-pressed', String(visible));
        });
        wrapper.append(toggle);
    });
})();

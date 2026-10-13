(() => {
    document.querySelectorAll('[data-remove-dswd-account]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const name = form.dataset.accountName || 'this staff member';
            if (!window.confirm(`Permanently remove the account for ${name}? Their evaluations and conversations will also be deleted. This cannot be undone.`)) {
                event.preventDefault();
            }
        });
    });
    const toggle = document.querySelector('[data-admin-menu-toggle]');
    const sidebar = document.querySelector('.admin-sidebar');
    if (!toggle || !sidebar) return;
    toggle.addEventListener('click', () => {
        const open = sidebar.classList.toggle('is-navigation-open');
        toggle.setAttribute('aria-expanded', String(open));
        toggle.textContent = open ? 'Close menu' : 'Menu';
    });
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !sidebar.classList.contains('is-navigation-open')) return;
        sidebar.classList.remove('is-navigation-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.textContent = 'Menu';
        toggle.focus();
    });
})();

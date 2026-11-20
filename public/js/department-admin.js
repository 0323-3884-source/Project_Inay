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
    const mobile = window.matchMedia('(max-width: 860px)');
    const backdrop = document.querySelector('.admin-sidebar-backdrop');
    const main = document.querySelector('.admin-main');
    const header = document.querySelector('.admin-mobile-header');
    function setOpen(open, returnFocus = true) {
        open = mobile.matches && open;
        sidebar.classList.toggle('is-navigation-open', open);
        document.body.classList.toggle('admin-drawer-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', 'Toggle navigation menu');
        if (backdrop) backdrop.hidden = !open;
        sidebar.inert = mobile.matches && !open;
        if (main) main.inert = open;
        if (header) header.inert = open;
        if (open) {
            sidebar.setAttribute('role', 'dialog');
            sidebar.setAttribute('aria-modal', 'true');
            sidebar.querySelector('[data-admin-menu-close]')?.focus();
        } else {
            sidebar.removeAttribute('role');
            sidebar.removeAttribute('aria-modal');
            if (returnFocus && mobile.matches) toggle.focus();
        }
    }
    toggle.addEventListener('click', () => setOpen(!sidebar.classList.contains('is-navigation-open')));
    document.querySelectorAll('[data-admin-menu-close]').forEach(button => button.addEventListener('click', () => setOpen(false)));
    sidebar.querySelectorAll('.admin-nav a').forEach(link => link.addEventListener('click', () => setOpen(false, false)));
    mobile.addEventListener('change', () => setOpen(false, false));
    document.addEventListener('keydown', (event) => {
        if (!sidebar.classList.contains('is-navigation-open')) return;
        if (event.key === 'Escape') { event.preventDefault(); setOpen(false); }
        if (event.key === 'Tab') {
            const items = [...sidebar.querySelectorAll('a[href], button, input, select, textarea, [tabindex="0"]')].filter(item => !item.disabled && item.getClientRects().length);
            const first = items[0];
            const last = items.at(-1);
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        }
    });
    setOpen(false, false);
})();

(() => {
    document.querySelectorAll('.appointment-more').forEach((menu, index) => {
        const trigger = menu.querySelector('summary');
        const popup = menu.querySelector('.appointment-more-content');
        if (!popup.showPopover) return;
        popup.setAttribute('popover', 'auto');
        popup.id = `appointment-actions-${index}`;
        trigger.setAttribute('aria-controls', popup.id);
        trigger.setAttribute('aria-expanded', 'false');
        const close = (restoreFocus = false) => {
            popup.hidePopover();
            menu.open = false;
            trigger.setAttribute('aria-expanded', 'false');
            if (restoreFocus) trigger.focus();
        };
        // Native light dismissal runs before click when the trigger is outside the popup.
        let openOnPointerDown = false;
        trigger.addEventListener('pointerdown', () => {
            openOnPointerDown = popup.matches(':popover-open');
        });
        trigger.addEventListener('pointercancel', () => { openOnPointerDown = false; });
        trigger.addEventListener('click', event => {
            event.preventDefault();
            const shouldClose = popup.matches(':popover-open') || (event.detail > 0 && openOnPointerDown);
            openOnPointerDown = false;
            if (shouldClose) { close(); return; }
            menu.open = true;
            popup.showPopover();
            const rect = trigger.getBoundingClientRect();
            const below = window.innerHeight - rect.bottom - 12;
            const above = rect.top - 12;
            const opensBelow = below >= popup.scrollHeight || below >= above;
            popup.style.maxHeight = `${Math.max(80, opensBelow ? below : above)}px`;
            popup.style.left = `${Math.max(8, Math.min(rect.right - popup.offsetWidth, window.innerWidth - popup.offsetWidth - 8))}px`;
            popup.style.top = `${Math.max(8, opensBelow ? rect.bottom + 6 : rect.top - popup.offsetHeight - 6)}px`;
            trigger.setAttribute('aria-expanded', 'true');
        });
        popup.addEventListener('toggle', event => {
            if (event.newState === 'closed') {
                menu.open = false;
                trigger.setAttribute('aria-expanded', 'false');
            }
        });
        menu.addEventListener('toggle', () => {
            if (!menu.open && popup.matches(':popover-open')) close();
        });
        popup.addEventListener('click', event => {
            if (event.target.closest('button, a[href]')) setTimeout(() => close(), 0);
        });
        menu.addEventListener('keydown', event => {
            if (event.key === 'Escape' && menu.open) {
                event.preventDefault();
                event.stopPropagation();
                close(true);
            }
            if (event.key === 'ArrowDown' && document.activeElement === trigger) {
                event.preventDefault();
                if (!menu.open) trigger.click();
                popup.querySelector('button, a[href]')?.focus();
            }
        });
        window.addEventListener('resize', () => close());
        document.addEventListener('scroll', event => {
            if (menu.open && !popup.contains(event.target)) close();
        }, true);
    });
    const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    document.querySelectorAll('[data-appointment-table]').forEach(panel => {
        const rows = Array.from(panel.querySelectorAll('[data-appointment-row]'));
        const search = panel.querySelector('[data-table-search]');
        const status = panel.querySelector('[data-table-status]');
        const pages = panel.querySelector('[data-table-pages]');
        const summary = panel.querySelector('[data-table-summary]');
        let page = 1;
        const render = (focusPage = false) => {
            const terms = normalize(search.value).split(/\s+/).filter(Boolean);
            const matches = rows.filter(row => (!status.value || row.dataset.status === status.value)
                && terms.every(term => normalize(row.dataset.search).includes(term)));
            const pageCount = Math.max(1, Math.ceil(matches.length / 5));
            page = Math.min(page, pageCount);
            rows.forEach(row => { row.hidden = true; row.querySelectorAll('details').forEach(menu => { menu.open = false; }); });
            matches.slice((page - 1) * 5, page * 5).forEach(row => { row.hidden = false; });
            panel.querySelector('[data-table-empty]').hidden = matches.length > 0;
            summary.textContent = `Showing ${matches.length ? (page - 1) * 5 + 1 : 0} to ${Math.min(page * 5, matches.length)} of ${matches.length} records`;
            pages.replaceChildren();
            const button = (label, target, disabled = false) => {
                const node = document.createElement('button');
                node.type = 'button';
                node.textContent = label;
                node.disabled = disabled;
                if (typeof label === 'number') {
                    node.setAttribute('aria-label', `Page ${label}`);
                    if (target === page) node.setAttribute('aria-current', 'page');
                }
                node.addEventListener('click', () => { page = target; render(true); });
                pages.append(node);
            };
            button('Previous', page - 1, page === 1);
            const numbers = new Set([1, pageCount, page - 1, page, page + 1]);
            if (pageCount <= 7) for (let i = 1; i <= pageCount; i++) numbers.add(i);
            let previous = 0;
            [...numbers].filter(n => n >= 1 && n <= pageCount).sort((a, b) => a - b).forEach(n => {
                if (previous && n > previous + 1) {
                    const dots = document.createElement('span');
                    dots.textContent = '…';
                    pages.append(dots);
                }
                button(n, n);
                previous = n;
            });
            button('Next', page + 1, page === pageCount);
            if (focusPage) pages.querySelector('[aria-current="page"]')?.focus();
        };
        search.addEventListener('input', () => { page = 1; render(); });
        status.addEventListener('change', () => { page = 1; render(); });
        render();
    });
})();

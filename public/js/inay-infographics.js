(() => {
    'use strict';

    window.InayInfographics = {
        init({ saveProgress, updateSummary }) {
            const dialog = document.getElementById('inayInfographicViewer');
            if (!dialog || dialog.dataset.bound) return;
            dialog.dataset.bound = 'true';
            const reader = dialog.querySelector('[data-infographic-reader]');
            const title = dialog.querySelector('h2');
            const message = dialog.querySelector('[data-infographic-save-message]');
            const retry = dialog.querySelector('[data-infographic-retry]');
            const states = new WeakMap();
            let active = null;
            let observer;
            let resizeObserver;
            let opener;
            let viewVersion = 0;

            const syncViewport = () => {
                const viewport = window.visualViewport;
                const width = viewport?.width || document.documentElement.clientWidth;
                const height = viewport?.height || window.innerHeight;
                const dialogWidth = Math.min(800, width - 24);
                const dialogHeight = Math.min(920, height - 32);
                Object.assign(dialog.style, {
                    position: 'fixed', margin: '0', width: `${dialogWidth}px`,
                    height: `${dialogHeight}px`, maxHeight: `${dialogHeight}px`,
                    left: `${(viewport?.offsetLeft || 0) + (width - dialogWidth) / 2}px`,
                    top: `${(viewport?.offsetTop || 0) + (height - dialogHeight) / 2}px`,
                });
            };

            const stateFor = (card) => {
                if (!states.has(card)) states.set(card, { pending: null, reachedEnd: false, failed: false });
                return states.get(card);
            };

            const setStatus = (card, status) => {
                card.dataset.status = status;
                const badge = card.querySelector('[data-infographic-badge]');
                badge.dataset.status = status;
                badge.textContent = status === 'reviewed' ? '✓ Natapos' : status === 'in_progress' ? 'Kasalukuyang Ginagawa' : 'Hindi pa nasisimulan';
            };

            const updateMessage = (card) => {
                if (active !== card) return;
                const state = stateFor(card);
                retry.hidden = !state.failed || !!state.pending;
                message.textContent = state.failed ? 'Hindi na-save ang progreso. Suriin ang koneksyon at subukang muli.'
                    : state.pending ? 'Sine-save ang progreso…'
                    : card.dataset.status === 'reviewed' ? '✓ Natapos — na-save na ang iyong progreso.'
                    : 'Basahin hanggang sa dulo para awtomatikong makumpleto.';
            };

            // A card has one request at a time. Completion waits for its start save.
            const save = async (card) => {
                const state = stateFor(card);
                if (state.pending || card.dataset.status === 'reviewed') return;
                const status = state.reachedEnd && card.dataset.status === 'in_progress' ? 'reviewed' : 'in_progress';
                if (status === 'in_progress' && card.dataset.status === 'in_progress') return;
                state.failed = false;
                state.pending = Promise.resolve().then(() => saveProgress({
                    month: Number(card.dataset.month), activity_type: 'infographic',
                    item_key: card.dataset.key, item_title: card.dataset.title, status,
                }));
                updateMessage(card);
                try {
                    const data = await state.pending;
                    setStatus(card, data.progress?.status || status);
                    updateSummary(data.month_summary, data.overall);
                } catch (error) {
                    state.failed = true;
                } finally {
                    state.pending = null;
                    updateMessage(card);
                }
                if (!state.failed && state.reachedEnd && card.dataset.status !== 'reviewed') save(card);
            };

            const checkEnd = () => {
                if (!active || !dialog.open || document.visibilityState === 'hidden') return;
                const card = active;
                const state = stateFor(card);
                if (state.failed || card.dataset.status === 'reviewed') return;
                const images = [...reader.querySelectorAll('img')];
                if (images.some((img) => !img.complete || img.naturalWidth === 0)) return;
                const end = reader.querySelector('[data-infographic-end]');
                if (!end) return;
                const bounds = end.getBoundingClientRect();
                const viewport = reader.getBoundingClientRect();
                if (bounds.top >= viewport.top - 2 && bounds.bottom <= viewport.bottom + 2) {
                    state.reachedEnd = true;
                    save(card);
                }
            };

            const open = (card, button) => {
                active = card;
                opener = button;
                const version = ++viewVersion;
                const state = stateFor(card);
                state.failed = false;
                title.textContent = card.dataset.title;
                reader.replaceChildren(card.querySelector('template').content.cloneNode(true));
                syncViewport();
                dialog.showModal();
                document.documentElement.classList.add('inay-infographic-opened');
                reader.scrollTop = 0;
                title.focus({ preventScroll: true });
                updateMessage(card);
                if (card.dataset.status === 'not_started' || state.reachedEnd) save(card);

                reader.querySelectorAll('img').forEach((img) => {
                    const loaded = () => { if (version === viewVersion) checkEnd(); };
                    const failed = () => {
                        if (version !== viewVersion || !img.matches('[data-infographic-image]')) return;
                        reader.querySelector('[data-image-error]').hidden = false;
                        message.textContent = 'Hindi ma-load ang infographic. Hindi pa ito mamarkahang natapos.';
                    };
                    img.addEventListener('load', loaded, { once: true });
                    img.addEventListener('error', failed, { once: true });
                    if (img.complete && !img.naturalWidth) failed();
                });
                observer?.disconnect();
                if ('IntersectionObserver' in window) {
                    observer = new IntersectionObserver(checkEnd, { root: reader, threshold: 1 });
                    observer.observe(reader.querySelector('[data-infographic-end]'));
                }
                resizeObserver?.disconnect();
                if ('ResizeObserver' in window) {
                    resizeObserver = new ResizeObserver(checkEnd);
                    resizeObserver.observe(reader);
                }
                requestAnimationFrame(() => { if (version === viewVersion) checkEnd(); });
            };

            document.querySelectorAll('[data-learning-infographic]').forEach((card) => {
                const button = card.querySelector('[data-infographic-open]');
                button.addEventListener('click', () => open(card, button));
                card.addEventListener('click', (event) => {
                    if (!event.target.closest('button')) open(card, button);
                });
            });
            reader.addEventListener('scroll', checkEnd, { passive: true });
            window.visualViewport?.addEventListener('resize', () => { if (dialog.open) syncViewport(); });
            window.visualViewport?.addEventListener('scroll', () => { if (dialog.open) syncViewport(); });
            window.addEventListener('resize', () => { if (dialog.open) syncViewport(); });
            document.addEventListener('visibilitychange', checkEnd);
            dialog.querySelectorAll('[data-infographic-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));
            dialog.addEventListener('click', (event) => {
                const bounds = dialog.getBoundingClientRect();
                if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) dialog.close();
            });
            dialog.addEventListener('close', () => {
                observer?.disconnect();
                resizeObserver?.disconnect();
                active = null;
                viewVersion++;
                document.documentElement.classList.remove('inay-infographic-opened');
                opener?.focus({ preventScroll: true });
            });
            retry.addEventListener('click', () => {
                if (!active) return;
                stateFor(active).failed = false;
                save(active);
                checkEnd();
            });
        },
    };
})();

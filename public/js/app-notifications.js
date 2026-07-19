(() => {
    document.querySelectorAll('[data-notification-root]').forEach((root) => {
        if (root.dataset.notificationBooted === 'true') return;
        root.dataset.notificationBooted = 'true';

        const state = {
            initialized: false,
            knownIds: new Set(),
            open: false,
            pollId: null,
        };

        const csrf = root.dataset.csrf || '';
        const listUrl = root.dataset.notificationsUrl;
        const readTemplate = root.dataset.notificationReadUrlTemplate;
        const readAllUrl = root.dataset.notificationReadAllUrl;
        const storageKey = `project-inay-notifications-${root.dataset.notificationRole || 'user'}-${root.dataset.notificationUser || '0'}`;
        const toggle = root.querySelector('[data-notification-toggle]');
        const menu = root.querySelector('[data-notification-menu]');
        const list = root.querySelector('[data-notification-list]');
        const count = root.querySelector('[data-notification-count]');
        const enable = root.querySelector('[data-notification-enable]');
        const markAll = root.querySelector('[data-notification-mark-all]');

        const stored = (() => {
            try {
                return new Set(JSON.parse(localStorage.getItem(storageKey) || '[]'));
            } catch (error) {
                return new Set();
            }
        })();

        const saveKnown = () => {
            localStorage.setItem(storageKey, JSON.stringify(Array.from(state.knownIds).slice(-80)));
        };

        stored.forEach((id) => state.knownIds.add(Number(id)));

        const fetchJson = async (url, options = {}) => {
            const headers = {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers || {}),
            };

            if ((options.method || 'GET').toUpperCase() !== 'GET') {
                headers['X-CSRF-TOKEN'] = csrf;
            }

            const response = await fetch(url, {
                credentials: 'same-origin',
                ...options,
                headers,
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(data.message || 'Notifications could not be updated.');
            }

            return data;
        };

        const updateCount = (value) => {
            const unread = Number(value || 0);
            count.textContent = unread > 99 ? '99+' : String(unread);
            count.hidden = unread === 0;
            toggle.classList.toggle('has-unread', unread > 0);
        };

        const notificationSupported = () => 'Notification' in window;

        const updatePermissionButton = () => {
            if (!enable) return;
            if (!notificationSupported()) {
                enable.hidden = true;
                return;
            }

            enable.hidden = Notification.permission === 'granted';
            enable.disabled = Notification.permission === 'denied';
            enable.textContent = Notification.permission === 'denied'
                ? 'Browser alerts blocked'
                : 'Enable browser alerts';
        };

        const renderList = (notifications = []) => {
            list.innerHTML = '';

            if (notifications.length === 0) {
                list.innerHTML = '<div class="app-notification-empty">No notifications yet.</div>';
                return;
            }

            notifications.forEach((notification) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = `app-notification-item${notification.is_read ? '' : ' is-unread'}`;
                button.dataset.notificationId = notification.id;
                button.dataset.notificationUrl = notification.url || '';

                const title = document.createElement('strong');
                title.textContent = notification.title || 'Notification';
                const body = document.createElement('span');
                body.textContent = notification.body || '';
                const meta = document.createElement('small');
                meta.textContent = notification.created_label || '';

                button.append(title, body, meta);
                list.append(button);
            });
        };

        const showBrowserNotification = (notification) => {
            if (!notificationSupported() || Notification.permission !== 'granted') return;
            if (!state.initialized || notification.is_read || state.knownIds.has(Number(notification.id))) return;

            const browserNotification = new Notification(notification.title || 'Project INAY', {
                body: notification.body || 'You have a new notification.',
                tag: `project-inay-${notification.id}`,
            });

            browserNotification.onclick = () => {
                window.focus();
                if (notification.url) window.location.href = notification.url;
                browserNotification.close();
            };
        };

        const loadNotifications = async () => {
            const data = await fetchJson(listUrl);
            updateCount(data.unread_count || 0);
            renderList(data.notifications || []);

            (data.notifications || []).forEach((notification) => {
                showBrowserNotification(notification);
                state.knownIds.add(Number(notification.id));
            });

            state.initialized = true;
            saveKnown();
        };

        const markRead = async (notificationId) => {
            if (!notificationId || !readTemplate) return;
            await fetchJson(readTemplate.replace('__NOTIFICATION__', notificationId), { method: 'POST' });
            await loadNotifications();
        };

        toggle.addEventListener('click', async () => {
            state.open = !state.open;
            menu.hidden = !state.open;
            if (state.open) {
                await loadNotifications().catch(() => {
                    list.innerHTML = '<div class="app-notification-empty">Notifications could not load.</div>';
                });
            }
        });

        list.addEventListener('click', async (event) => {
            const item = event.target.closest('[data-notification-id]');
            if (!item) return;

            const url = item.dataset.notificationUrl || '';
            await markRead(item.dataset.notificationId).catch(() => {});
            if (url) window.location.href = url;
        });

        markAll?.addEventListener('click', async () => {
            await fetchJson(readAllUrl, { method: 'POST' });
            await loadNotifications();
        });

        enable?.addEventListener('click', async () => {
            if (!notificationSupported() || Notification.permission === 'granted') return;
            await Notification.requestPermission();
            updatePermissionButton();
        });

        document.addEventListener('click', (event) => {
            if (!state.open || root.contains(event.target)) return;
            state.open = false;
            menu.hidden = true;
        });

        updatePermissionButton();
        loadNotifications().catch(() => {});
        state.pollId = window.setInterval(() => loadNotifications().catch(() => {}), 10000);

        window.addEventListener('beforeunload', () => {
            if (state.pollId) window.clearInterval(state.pollId);
        });
    });
})();

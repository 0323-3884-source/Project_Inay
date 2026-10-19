(() => {
    if (! ('serviceWorker' in navigator)) {
        return;
    }

    const config = document.currentScript;
    const base = new URL(config.dataset.base, location.href);
    const role = config.dataset.role;
    const authenticated = ['mother', 'staff'].includes(role);
    const prepareButton = document.querySelector('[data-offline-prepare]');
    const status = document.createElement('div');
    status.className = 'pwa-status';
    status.setAttribute('role', 'status');
    status.style.cssText = 'position:fixed;bottom:12px;left:12px;right:12px;z-index:10000;margin:auto;width:fit-content;max-width:calc(100% - 24px);padding:10px 12px;border-radius:8px;background:#10213f;color:white;font:13px/1.5 system-ui;box-shadow:0 3px 14px #0002';
    const statusMessage = document.createElement('span');
    const dismissButton = document.createElement('button');
    dismissButton.type = 'button';
    dismissButton.textContent = '\u00d7';
    dismissButton.setAttribute('aria-label', 'Dismiss offline notice');
    dismissButton.style.cssText = 'margin-left:12px;padding:0;width:28px;height:28px;border:0;border-radius:6px;background:transparent;color:white;font:24px/1 system-ui;cursor:pointer;vertical-align:middle';
    status.append(statusMessage, dismissButton);
    let statusTimer;
    function dismissStatus() {
        window.clearTimeout(statusTimer);
        status.hidden = true;
    }
    dismissButton.addEventListener('click', dismissStatus);
    const consultation = document.querySelector('[data-consultation-root]');
    if (consultation) {
        consultation.prepend(status);
    } else {
        document.body.append(status);
    }
    let onlineStatus = '';
    function showStatus() {
        window.clearTimeout(statusTimer);
        // Prepare staff pages quietly without a floating progress notice.
        if (role === 'staff' && navigator.onLine) {
            status.hidden = true;
            return;
        }
        statusMessage.textContent = navigator.onLine ? onlineStatus : (role === 'staff'
            ? 'Offline — showing saved information. Use Offline records to save maternal vitals and child growth entries for later synchronization.'
            : 'Offline — showing saved information. Changes, messages and online videos need internet.');
        status.hidden = !statusMessage.textContent;
        if (!status.hidden) statusTimer = window.setTimeout(dismissStatus, 5000);
    }
    showStatus();
    window.addEventListener('offline', showStatus);
    // Block writes before individual module handlers can display a false success.
    document.addEventListener('submit', event => {
        if (!navigator.onLine && event.target.method.toLowerCase() !== 'get' && !event.target.matches('[data-offline-entry]')) {
            event.preventDefault();
            event.stopImmediatePropagation();
            showStatus();
        }
    }, true);
    const originalFetch = window.fetch.bind(window);
    window.fetch = (input, options = {}) => {
        const method = (options.method || input.method || 'GET').toUpperCase();
        if (!navigator.onLine && !['GET', 'HEAD'].includes(method)) {
            showStatus();
            return Promise.reject(new Error('Reconnect to save changes.'));
        }
        return originalFetch(input, options);
    };

    let syncing = false;
    async function savePages() {
        if (!authenticated || !navigator.onLine || syncing || !navigator.serviceWorker.controller) return;
        syncing = true;
        if (prepareButton) { prepareButton.disabled = true; prepareButton.textContent = 'Saving for offline…'; }
        onlineStatus = 'Saving pages for offline viewing… Keep this page open.';
        showStatus();
        const seeds = role === 'mother'
            ? ['mother/dashboard', 'mother/documents', 'mother/profile', 'mother/settings', 'mother/consultation', 'maternal-monitoring', 'child-health', 'inay-kaalaman', 'health-services', 'mother/clinic-schedule', 'api/mother/maternal-vitals', 'consultation/conversations', 'notifications']
            : ['staff/dashboard', 'staff/profile', 'staff/mothers', 'staff/neonatal-vaccines', 'staff/dynamic-reports', 'staff/clinic-schedule', 'staff/offline-records'];
        const queue = [...new Set([location.href.split('#')[0], ...seeds.map(path => new URL(path, base).href)])];
        if (role === 'mother') {
            for (let month = 1; month <= 10; month++) {
                queue.push(new URL(`inay-kaalaman/infographics/${month}/pdf`, base).href);
                queue.push(new URL(`inay-kaalaman/videos/${month}`, base).href);
            }
        }
        const seen = new Set();
        let failed = 0;
        function enqueue(value, page, isAsset = false) {
            if (!value) return;
            const url = window.InayOfflineResources.normalize(value, page, base, isAsset);
            if (url && !seen.has(url) && !queue.includes(url)) queue.push(url);
        }
        try {
            while (queue.length && navigator.onLine) {
                const url = queue.shift();
                if (seen.has(url)) continue;
                seen.add(url);
                try {
                    const response = await fetch(url, { credentials: 'same-origin' });
                    if (!response.ok || response.redirected) { failed++; continue; }
                    const cacheUrl = new URL(url);
                    if (cacheUrl.searchParams.get('source') === 'pwa') cacheUrl.searchParams.delete('source');
                    const saved = await (await caches.open('project-inay-private-v2')).match(cacheUrl.href)
                        || await (await caches.open('project-inay-static-v2')).match(cacheUrl.href);
                    if (!saved) failed++;
                    if (response.headers.get('content-type')?.includes('text/html')) {
                        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                        window.InayOfflineResources.discover(page, (value, asset) => enqueue(value, url, asset));
                    } else if (response.headers.get('content-type')?.includes('text/css')) {
                        window.InayOfflineResources.css(await response.text(), value => enqueue(value, url, true));
                    }
                } catch (error) { failed++; }
            }
            onlineStatus = failed || queue.length
                ? 'Some information was not saved. Reconnect or reload to retry offline preparation.'
                : `Pages saved for offline viewing at ${new Date().toLocaleString()}. Online videos need internet.`;
        } finally {
            syncing = false;
            if (prepareButton) {
                prepareButton.disabled = false;
                prepareButton.textContent = failed || queue.length ? 'Retry offline save' : 'Offline info ready';
                prepareButton.title = onlineStatus;
            }
            showStatus();
        }
    }
    prepareButton?.addEventListener('click', () => {
        if (!navigator.onLine) { showStatus(); return; }
        savePages();
    });
    window.addEventListener('online', () => { showStatus(); savePages(); });
    window.addEventListener('load', async () => {
        try {
            await navigator.serviceWorker.register(new URL('sw.js', base), { scope: base.pathname });
            await navigator.serviceWorker.ready;
            if (!navigator.serviceWorker.controller) {
                await new Promise(resolve => navigator.serviceWorker.addEventListener('controllerchange', resolve, { once: true }));
            }
            await savePages();
        } catch (error) {
            onlineStatus = 'Offline preparation is unavailable in this browser. Use HTTPS or localhost.';
            showStatus();
        }
    });

    const installButton = document.querySelector('[data-pwa-install]');
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;

    if (! installButton || isStandalone) {
        return;
    }

    let installPrompt = null;

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        installPrompt = event;
        installButton.hidden = false;
    });

    installButton.addEventListener('click', async () => {
        if (! installPrompt) {
            return;
        }

        installButton.hidden = true;
        installPrompt.prompt();

        try {
            await installPrompt.userChoice;
        } finally {
            installPrompt = null;
        }
    });

    window.addEventListener('appinstalled', () => {
        installPrompt = null;
        installButton.hidden = true;
    });
})();

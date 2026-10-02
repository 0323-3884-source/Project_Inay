(() => {
    if (! ('serviceWorker' in navigator)) {
        return;
    }

    const config = document.currentScript;
    const base = new URL(config.dataset.base, location.href);
    const role = config.dataset.role;
    const authenticated = ['mother', 'staff'].includes(role);
    const status = document.createElement('div');
    status.setAttribute('role', 'status');
    status.style.cssText = 'position:fixed;bottom:12px;left:12px;right:12px;z-index:10000;margin:auto;width:fit-content;max-width:calc(100% - 24px);padding:10px 16px;border-radius:8px;background:#10213f;color:white;font:13px/1.5 system-ui;box-shadow:0 3px 14px #0002';
    document.body.append(status);
    let onlineStatus = '';
    function showStatus() {
        status.textContent = navigator.onLine ? onlineStatus : 'Offline — showing saved information. Changes, messages and online videos need internet.';
        status.hidden = !status.textContent;
    }
    showStatus();
    window.addEventListener('offline', showStatus);
    // Block writes before individual module handlers can display a false success.
    document.addEventListener('submit', event => {
        if (!navigator.onLine && event.target.method.toLowerCase() !== 'get') {
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
        onlineStatus = 'Saving pages for offline viewing… Keep this page open.';
        showStatus();
        const seeds = role === 'mother'
            ? ['mother/dashboard', 'mother/documents', 'mother/profile', 'mother/settings', 'mother/consultation', 'maternal-monitoring', 'child-health', 'inay-kaalaman', 'health-services', 'mother/clinic-schedule', 'api/mother/maternal-vitals', 'consultation/conversations', 'notifications']
            : ['staff/dashboard', 'staff/profile', 'staff/mothers', 'staff/neonatal-vaccines', 'staff/dynamic-reports', 'staff/clinic-schedule'];
        const queue = [...new Set([location.href.split('#')[0], ...seeds.map(path => new URL(path, base).href)])];
        if (role === 'mother') {
            for (let month = 1; month <= 10; month++) {
                queue.push(new URL(`inay-kaalaman/infographics/${month}/pdf`, base).href);
            }
        }
        const seen = new Set();
        let failed = 0;
        function enqueue(value, page, isAsset = false) {
            if (!value) return;
            const url = new URL(value, page);
            if (url.origin !== base.origin || !url.pathname.startsWith(base.pathname)) return;
            url.hash = '';
            const path = url.pathname.slice(base.pathname.length);
            const allowed = isAsset
                ? /^(css|js|build|pwa|images|storage)\//.test(path)
                : /^(inay-kaalaman(?:\/|$)|mother\/(dashboard|profile)$|maternal-monitoring$|child-health$|health-services$|staff\/(dashboard|profile|mothers(?:\/\d+)?|neonatal-vaccines|dynamic-reports|clinic-schedule)$)/.test(path);
            if (allowed && !seen.has(url.href) && !queue.includes(url.href)) queue.push(url.href);
        }
        try {
            while (queue.length && navigator.onLine) {
                const url = queue.shift();
                if (seen.has(url)) continue;
                seen.add(url);
                try {
                    const response = await fetch(url, { credentials: 'same-origin' });
                    if (!response.ok || response.redirected) { failed++; continue; }
                    const saved = await (await caches.open('project-inay-private-v2')).match(url)
                        || await (await caches.open('project-inay-static-v2')).match(url);
                    if (!saved) failed++;
                    if (response.headers.get('content-type')?.includes('text/html')) {
                        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                        page.querySelectorAll('a[href]').forEach(link => enqueue(link.getAttribute('href'), url));
                        page.querySelectorAll('script[src], img[src], link[rel="stylesheet"][href], link[rel="icon"][href]').forEach(asset => {
                            enqueue(asset.getAttribute('src') || asset.getAttribute('href'), url, true);
                        });
                    }
                } catch (error) { failed++; }
            }
            onlineStatus = failed || queue.length
                ? 'Some information was not saved. Reconnect or reload to retry offline preparation.'
                : `Pages saved for offline viewing at ${new Date().toLocaleString()}. Online videos need internet.`;
        } finally {
            syncing = false;
            showStatus();
        }
    }
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

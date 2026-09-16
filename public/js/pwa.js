(() => {
    if (! ('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
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

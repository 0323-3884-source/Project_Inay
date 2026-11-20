(() => {
    const endpoint = document.currentScript?.dataset.presenceUrl;
    const token = document.currentScript?.dataset.csrf;
    if (!endpoint || !token) return;
    let busy = false;
    let stopped = false;
    let lastInteraction = Date.now();
    for (const event of ['pointerdown', 'keydown', 'scroll']) {
        document.addEventListener(event, () => { lastInteraction = Date.now(); }, {passive: true});
    }
    async function heartbeat() {
        if (busy || stopped || document.hidden || navigator.onLine === false || Date.now() - lastInteraction > 240000) return;
        busy = true;
        try {
            const response = await fetch(endpoint, {method: 'POST', credentials: 'same-origin', cache: 'no-store', headers: {Accept: 'application/json', 'X-CSRF-TOKEN': token}});
            if ([401, 403, 419].includes(response.status) || response.redirected) stopped = true;
        } catch { /* Retry on the next interval after connectivity returns. */ }
        finally { busy = false; }
    }
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) { lastInteraction = Date.now(); heartbeat(); }
    });
    heartbeat();
    setInterval(heartbeat, 60000);
})();

(() => {
    const script = document.currentScript;
    const endpoint = script?.dataset.activityUrl;
    const badges = [...document.querySelectorAll('[data-staff-activity]')];
    if (!endpoint || !badges.length) return;
    const url = new URL(endpoint, window.location.origin);
    for (const badge of badges) {
        const key = badge.dataset.staffActivity === 'program_staff' ? 'program_ids[]' : 'dswd_ids[]';
        url.searchParams.append(key, badge.dataset.staffId);
    }
    let pending = false;
    async function refresh() {
        if (pending || document.hidden) return;
        pending = true;
        try {
            const response = await fetch(url, {credentials: 'same-origin', cache: 'no-store', headers: {Accept: 'application/json'}});
            if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) return;
            const data = await response.json();
            for (const badge of badges) {
                const activity = data[badge.dataset.staffActivity]?.find(item => String(item.id) === badge.dataset.staffId);
                if (!activity) continue;
                badge.textContent = activity.active ? 'Active' : 'Inactive';
                if (badge.classList.contains('staff-status')) {
                    badge.classList.toggle('is-approved', activity.active);
                    badge.classList.toggle('is-missing', !activity.active);
                }
                badge.title = `Checked ${new Date().toLocaleTimeString()}; active means activity within five minutes`;
            }
        } catch { /* Keep the last observed status when disconnected. */ }
        finally { pending = false; }
    }
    refresh();
    setInterval(refresh, 10000);
    document.addEventListener('visibilitychange', refresh);
})();

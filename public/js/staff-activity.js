(() => {
    const script = document.currentScript;
    const endpoint = script?.dataset.activityUrl;
    const badges = [...document.querySelectorAll('[data-staff-activity]')];
    const connection = document.querySelector?.('[data-activity-connection]');
    if (!endpoint || !badges.length) return;
    const url = new URL(endpoint, window.location.origin);
    for (const badge of badges) {
        const key = {program_staff: 'program_ids[]', dswd_staff: 'dswd_ids[]', mother: 'mother_ids[]'}[badge.dataset.staffActivity];
        if (!key) continue;
        url.searchParams.append(key, badge.dataset.staffId);
    }
    let pending = false;
    async function refresh() {
        if (pending || document.hidden) return;
        pending = true;
        try {
            const response = await fetch(url, {credentials: 'same-origin', cache: 'no-store', headers: {Accept: 'application/json'}});
            if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
                if (connection) connection.textContent = response.redirected || [401, 403, 419].includes(response.status)
                    ? 'Your admin session has ended. Please sign in again.'
                    : 'Activity updates are unavailable. Retrying automatically...';
                return;
            }
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
                const row = badge.closest?.('tr');
                const lastLogin = row?.querySelector('[data-last-login]');
                const lastSeen = row?.querySelector('[data-last-seen]');
                if (lastLogin) lastLogin.textContent = activity.last_login || 'Not recorded';
                if (lastSeen) lastSeen.textContent = activity.last_seen || 'Not recorded';
            }
            if (connection) connection.textContent = `Live updates connected. Last checked ${new Date().toLocaleTimeString()}.`;
        } catch {
            if (connection) connection.textContent = 'Connection interrupted. Retrying automatically...';
        }
        finally { pending = false; }
    }
    refresh();
    setInterval(refresh, 10000);
    document.addEventListener('visibilitychange', refresh);
})();

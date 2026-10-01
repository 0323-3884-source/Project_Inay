# Offline viewing

Open the site using HTTPS or localhost, sign in while online, and leave the page open until the “Pages saved for offline viewing” message appears. The same browser/device can then reopen the app and navigate saved pages while disconnected.

The mother portal prepares the dashboard, profile, settings, maternal monitoring, child health, Kaalaman month libraries and infographic PDFs, health services, schedule, and consultation overview. Linked staff case pages and main staff pages are also prepared. Other supported page/API responses are saved when loaded. Information is a snapshot from the last successful download; a browser storage limit or interrupted download may leave some pages unavailable. The status message reports incomplete preparation.

Signing in, changing records, uploading, messaging, calls, and YouTube/external media require a connection. Chat histories that have not been opened/downloaded are unavailable offline. Offline access does not create a new server login. Login/logout requests clear private saved data, and account changes replace the previous account's saved pages.

## Verification

1. Sign in online and wait for preparation to finish.
2. Set the browser's Network panel to Offline, then open Kaalaman, a month library, an infographic PDF, child health, and maternal monitoring.
3. Refresh and reopen the installed app. Saved content should load with an offline notice.
4. Restore internet and reload to refresh the saved snapshot.
5. Log out online, disconnect, and revisit a private page. It must show the unsaved-page fallback.
6. Sign in as another account and verify that the previous account's pages are unavailable offline.

Automated checks: `node --test tests/js/offline-pwa.test.mjs` and `php artisan test --filter=OfflineResponseTest`.

# Offline maternal and child records

## Mother offline viewing

Mothers who sign in online automatically prepare their portal for offline viewing. **Save for offline** retries preparation; **Offline info ready** means the requested pages and local files were saved successfully. Wait for completion before disconnecting. The saved copy includes the dashboard, maternal records, every child selected through the child dropdown, profile, document list and available previews/downloads, monthly INAY Kaalaman pages, infographics and checklists. Images in learning templates and local stylesheet dependencies are included. Reconnect and prepare again to refresh the saved information.

YouTube and other external media, live services, messages not previously opened, new uploads, and edits need internet. Offline viewing uses the browser and account that prepared the copy; logout clears private viewing caches. It does not create a new authenticated server session while disconnected.

## Staff offline entry

Program Staff can open **Offline records** from the button at the bottom of the portal. Use HTTPS or localhost in a browser supporting service workers and Web Crypto.

1. Sign in online and open Offline records. Select a device passphrase of at least 12 characters and choose Prepare / unlock. This downloads the staff member's assigned mothers and children. Wait for the app's offline page preparation to finish before disconnecting.
2. Keep the passphrase: it is separate from the account password and cannot be recovered. Patient lists and pending entries are encrypted using AES-256-GCM and a PBKDF2-SHA256 key derived with 310,000 iterations. The key stays in page memory. Lock the workspace before leaving the device.
3. Offline, reopen the saved workspace and unlock with the same passphrase. Select the patient and record maternal vitals or child growth. Save each completed measurement. Only saved entries survive closing the page; partially completed forms do not.
4. Reconnect and keep the unlocked workspace open. Synchronization starts automatically or through Synchronize now. If the session expired, sign in again as the same account, reopen the workspace, and unlock.
5. Review entries that could not synchronize. Same-date measurements are retained for review and never overwrite server records. Open the existing clinical record online to reconcile the difference, then discard the pending entry if appropriate. Unusual maternal values require explicit confirmation. Validation errors remain visible; record corrected values in a new entry and discard the invalid entry after review.

Server authorization and clinical validation are checked at synchronization time. A transactional receipt per staff account and operation UUID prevents duplicate uploads when a response is lost. Offline entries are append-only; patient editing, vaccination changes, uploads, messages, and appointments still require a connection. Encrypted pending entries remain in this browser across sign-out and are accessible only with their account-specific workspace and device passphrase. Existing offline viewing caches retain their existing behavior.

Deployment requires the migration `2026_10_19_000001_create_offline_sync_receipts.php`. No background synchronization occurs while the browser page is closed. Browser storage clearing removes unsynchronized entries. Multiple tabs detect changed device storage and require unlocking again before saving over another tab's changes.

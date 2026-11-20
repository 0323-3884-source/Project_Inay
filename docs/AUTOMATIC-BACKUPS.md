# Automatic database backups

General, CHO and DSWD administrators with existing backup permission can open
Backup and Recovery, choose Every week or Every month, and save.
This is one shared system schedule, initially disabled. Signed-in authorized
administrators do not need to enter their password again for backup actions.
Changing frequency starts a new interval; saving the same frequency preserves
the due date. Monthly intervals clamp to the last available day. Dates display
in Asia/Manila. No records are restored automatically.

Scheduled SQL files use the existing signed backup format and remain in
`storage/app/private/database-backups`. Download them directly
from Recent private backups, then use the existing validation/recovery flow.
The existing 20 MB size limit and database support restrictions still apply.
Files are retained until the server operator removes them; monitor disk usage.
Uploaded files and attachments need a separate storage backup.

## Scheduler deployment

The Docker Apache startup launches `php artisan schedule:work` as www-data.
Deploy the updated image to enable it. The Backup and Recovery page displays
the last scheduler check and warns if it is more than five minutes old.
Persist `storage/app/private` on the server volume, including its schedule JSON.
Use one scheduler per installation and a shared cache/storage when scaling.

For local XAMPP development, keep this running from the project folder:

```powershell
php artisan schedule:work
```

For a non-Docker production server, configure a service for that command, or
run `php artisan schedule:run` every minute using cron/Windows Task Scheduler.
The computer/server must be running. No admin browser session is required.

The scheduler checks every minute, catches up once if overdue, shares the
existing backup/recovery lock, and retries a failed backup after one hour.
Successes, failures and setting changes go to the security log. Settings and
run status are private files, independent of database restore and cache clears.
Do not delete the schedule file during deployments. Local copies require an
independent off-site backup to protect against server/volume loss.

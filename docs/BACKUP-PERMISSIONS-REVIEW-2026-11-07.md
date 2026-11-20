# Backup permission review — 7 November 2026

## Initial finding and approval decision

The existing implementation authorizes every database account whose role is `admin`, `admin_cho` or `admin_dswd` for full-system backup, download and restoration. This matches the earlier instruction allowing all three roles, but does not meet the newer requirement for explicit account-level approval. No account-level backup grant is currently defined.

The user subsequently answered "all" to the account approval question. This was interpreted and communicated as approval for all currently existing administrator accounts, not automatic access for future accounts. A read-only account query identified `admin` (ID 1, admin), `admindswd` (ID 2, admin_dswd), and `admincho` (ID 3, admin_cho). These exact identities are now explicitly approved in configuration. No patient records, passwords or database account roles were changed.

## Implementation inventory

| Component | Responsibility |
| --- | --- |
| `routes/web.php` | GET `/admin/backups`; POST `/admin/backups`, `/admin/backups/preview`, `/admin/backups/restore`, `/admin/backups/{file}/download`; admin authentication and POST rate limits |
| `bootstrap/app.php` | Web middleware including CSRF, recovery guard, security audit and department restriction |
| `app/Http/Middleware/EnsureAdminAuthenticated.php` | Requires an existing administrator session; rejects ordinary authenticated users |
| `app/Http/Middleware/RestrictDepartmentAdmin.php` | Reloads the account from the database, rejects retired roles and applies route permissions; submitted/session role fields do not determine authority |
| `app/Models/AdminUser.php` | Broad `admin` route access and shared `admin.backups.*` permission for both department admin roles |
| `app/Http/Controllers/Admin/BackupController.php` | Checks active admin role for all actions; verifies current password for create/download/preview/restore; audit events, preview ownership/expiry, locking and safety backup |
| `app/Support/DatabaseBackup.php` | Private signed SQL files, signature/schema checks, transactional recovery and relationship validation; not a separate web authorization boundary |
| `app/Http/Middleware/DatabaseRecoveryGuard.php` | Recovery request guard and stale-session invalidation |
| `app/Http/Middleware/SecurityAudit.php`, `config/logging.php` | Sanitized event logging and denial warnings |
| `config/backups.php` | Size limit and excluded operational tables; no account approval configuration |
| `resources/views/layouts/admin.blade.php`, `resources/views/admin/backups.blade.php` | Existing navigation, password forms and recovery confirmation UI |
| `routes/console.php` | Read-only `backups:check` diagnostics; no web grant management |

## Implemented authorization

The deployment-managed `backups.approved_accounts` list matches exact database ID, username and role. There is no role-wide fallback or implicit general-admin bypass. The department middleware reloads the account on every request and checks this permission before allowing backup routes. The controller also authorizes the `manage-database-backups` Gate on every action, including restore after preview. The existing navigation link is conditional on the same permission. Request parameters and session role labels do not grant access. No self-approval form or schema change was added. Configuration is not part of the database dump; restored accounts must still match the deployment's approved identities. New or changed accounts require operator review and a configuration-cache rebuild through normal deployment.

Password checks, CSRF protection, throttling, private storage, signed-file validation, schema checks, safety copies, transactions, locks and audit logging were preserved. Full-system access for the three approved accounts remains intentionally broad; institutional privacy approval is separate from the user's technical authorization.

## Tests performed in this review

Added `tests/Feature/BackupPermissionBoundaryTest.php`:

- Ordinary Mother, Program Staff and DSWD staff sessions receive HTTP 403 on all five endpoints, including requests containing forged admin fields.
- Retired administrators cannot override the persisted role using session or request fields; access redirects to admin login.
- Create, download, preview and restore require the current password before any backup service work or file access.

The initial boundary/schema run passed 5 tests with 62 assertions. After implementation, the boundary, backup/recovery, schema and department administrator suites passed **25 tests with 310 assertions**. Additional cases cover unapproved active administrators of all three roles across all five endpoints, forged approval fields, revoked approval before restore, exact identity matching, database role changes, non-admin Gate denial and hidden backup navigation while preserving ordinary dashboards. Existing backup tests explicitly approve their synthetic accounts instead of relying on roles.

Execution explicitly used testing mode, SQLite `:memory:`, array cache/sessions and fake private storage. Backup service execution was mocked in sensitive-operation boundary tests. Existing recovery regression tests restored only synthetic in-memory records. No real restoration was executed, no real application records were modified, and no existing backup files were deleted. Blade template compilation passed. This is not a full regression suite, a MySQL integration test or a Railway deployment verification.

Modified files: `config/backups.php`, `app/Models/AdminUser.php`, `app/Providers/AppServiceProvider.php`, `app/Http/Controllers/Admin/BackupController.php`, `resources/views/layouts/admin.blade.php`, `tests/Feature/DatabaseBackupTest.php`, `tests/Feature/BackupPermissionBoundaryTest.php`, `docs/DATABASE-BACKUP-RECOVERY.md`, and this review. Routes, styles, the backup form, database schema and recovery service were unchanged.

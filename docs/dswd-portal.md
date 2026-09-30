# DSWD / 4Ps portal

Apply migrations with `php artisan migrate`. On the configured local database, the migration was applied on September 30, 2026.

An administrator creates accounts under **Admin → DSWD / 4Ps Staff** (`/admin/dswd-staff`). Accounts have their own `dswd_staff` table; existing mother, program staff and admin accounts retain their original tables and behavior. No default DSWD password or public self-registration is provided. Select **DSWD / 4Ps Staff** on the shared login page. Staff can change their password in Profile or use the existing email recovery flow.

The portal includes overview cards, beneficiary search and pagination, restricted beneficiary profiles, filtered statistics, aggregate CSV/print reports, system evaluation submissions and a staff profile. Evaluation feedback is stored in `dswd_evaluations`; staff see only their own submissions.

## Data definitions

- Only mothers with `is_4ps_beneficiary = true` are included. This existing field is self-reported, not official DSWD verification.
- Beneficiary IDs are Project INAY mother IDs, prefixed `INAY-`. The displayed “Registered” status describes presence in Project INAY, not eligibility or payment status.
- Pregnancy status comes from the recorded profile. Missing/unknown statuses are shown separately, not counted as confirmed non-pregnant.
- Child counts use recorded birth dates from 0–24 completed calendar months as of today, with ranges 0–6, 7–12 and 13–24 months. Future birth dates and missing dates are excluded. This follows the requested 24-month reporting range rather than all children below age three.
- Registration date filters apply to mothers, including when counting their children. Pregnancy and location filters use the same beneficiary cohort throughout statistics and reports.
- `municipality_city` is nullable. Existing records show “Not recorded” until entered in Mother registration or Program Staff → Mother Information. No location was inferred or backfilled.

## Access boundaries

`EnsureDswdAuthenticated` verifies the DSWD session, account existence and active status on every DSWD request. Admin deactivation revokes access on the next request. Responses are marked private/no-store.

`RestrictDswdPortal` limits a DSWD session to named DSWD routes, home, login and logout. It denies other Laravel web routes, including healthcare pages, APIs, downloads and messaging. New shared routes must be explicitly reviewed before adding them to this allowlist.

DSWD beneficiary queries select only IDs, names, locations, pregnancy status and registration timestamps, plus aggregate child counts. They do not select medical fields or load growth, vaccination, consultation, laboratory or prenatal relationships. Reports contain aggregate counts only and escape spreadsheet formula prefixes in CSV labels.

## Verification

`php artisan test --filter=DswdPortalTest` covers role login, access restrictions, account deactivation, permitted profile fields, beneficiary-only scope, date/age boundaries, search, pagination, location/pregnancy filters, aggregate reports, account provisioning, profile updates, evaluation and role-scoped password recovery.

The full suite passed with 142 tests. Live MySQL/browser checks additionally exercised DSWD login, six portal pages, responsive login cards, the mobile menu and horizontal overflow. MySQL aggregate queries explicitly replace the beneficiary column selection to support strict `ONLY_FULL_GROUP_BY` mode.

# DSWD / 4Ps F1KD monitoring

Both portals use `mothers` as the shared 4Ps household profile and `f1kd_monitorings` as the single monthly attendance source. All mothers flagged `is_4ps_beneficiary=true` appear in current-month maternal monitoring, irrespective of pregnancy status. Children through 24 completed calendar months have individual child records linked by `mother_id`. Future and missing birth dates are excluded from current child monitoring. Existing classification/filter key `pregnant` is retained for URL compatibility but now labels the maternal category, not a claim that every mother is pregnant.

## Pages and access

- `/dswd/dashboard`: six summary cards and four simple charts.
- `/dswd/f1kd`: searchable, filtered, paginated monitoring roster.
- `/dswd/f1kd/mother-{id}` or `/dswd/f1kd/child-{id}`: basic identity, monthly attendance, optional remark and monthly history.
- `/dswd/f1kd/reports`: monthly aggregate report, location breakdown, print and CSV export.
- `GET /staff/f1kd/mother-{id}` or `/staff/f1kd/child-{id}`: attendance form, linked from the mother's existing casefile.
- `PUT /staff/f1kd/{subject}`: save attendance with `month`, `attendance_status` and optional `remark_code`.

Approved, assigned Program Staff can record attendance. Active DSWD staff can update and verify attendance via `PUT /dswd/f1kd/{subject}`. Both write the same unique subject/month row. Mothers/public users and unauthorized staff cannot write. DSWD verification stores its officer ID and timestamp; a later Program Staff correction clears that verification for re-review. DSWD updates preserve the original Program Staff recorder when present. DSWD-origin rows may have no Program Staff recorder; no fake staff ID is assigned. Profile headers include assigned staff and all registered children without clinical details. Existing checklist JSON remains untouched. Data is shared on the next request/page refresh; there is no separate portal copy or push synchronization.

Household references use Project INAY mother registration IDs, not official DSWD household numbers. Membership uses the existing `is_4ps_beneficiary` registration flag. The module does not claim to sync with an external DSWD registry.

## Monthly records

`f1kd_monitorings` stores one record per beneficiary and reporting month, with location snapshots, recording staff and timestamps. An additive migration adds nullable `attendance_status` and `remark_code`. The existing unique `(subject_key, reporting_month)` index is preserved. An atomic upsert updates attendance, remark, status and recording staff only; existing checklist JSON and location snapshots are preserved. No old attendance is inferred or backfilled.

Current-month eligible beneficiaries without saved attendance appear as **For Verification / Not Yet Recorded**. Earlier aggregate rosters/reports include saved monthly records only, including women who have since delivered and children who have since aged out. Detail pages can display an empty month for a currently eligible beneficiary or one with saved history. Historical records remain available while the household is registered as 4Ps. Use `month=YYYY-MM` and View Period to select a month; saving only affects that period. Future periods can be viewed but not recorded before that month begins.

Attendance is the sole basis for the new monthly status: `attended` = **Compliant**, `did_not_attend` = **Non-Compliant**, missing attendance = **For Verification**. Old checklist-only records remain stored and appear as attendance not yet recorded, regardless of their legacy status. ○ is the unshaded Attended indicator; ● is the shaded Did Not Attend indicator. Both have text labels and badges. Missing records are never shown as confirmed attendance.

Optional remarks reuse existing outcome keys: `service_unavailable`, `miscarriage`, `delivered`, `death`, plus `other_verification` (Other / needs verification). These are application codes, not invented official DSWD numeric codes. Neither attendance choice requires a remark. No checklist payload is required or accepted as attendance.

Monthly exports contain aggregated counts, selected filters and location labels only. CSV values beginning with spreadsheet formula characters are escaped. Print output hides navigation and controls and keeps the reporting month and selected filters visible.

The simplified attendance workflow follows the supplied DSWD interview: a shaded month records non-attendance. The module is not an official CV-F5 filing integration.

## Validation

Run `php artisan migrate` locally or `php artisan migrate --force` in the hosted app service to apply the existing table migration and additive attendance migration. Do not use `migrate:fresh` on beneficiary data. Run `php artisan test --filter="F1kdMonitoringTest|DswdPortalTest"` for role restrictions, age boundaries, 4Ps scope, attendance validation, pagination, month isolation, legacy preservation and safe exports.

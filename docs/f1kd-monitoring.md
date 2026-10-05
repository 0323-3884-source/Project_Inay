# DSWD / 4Ps F1KD monitoring

The DSWD dashboard and **F1KD Monitoring** navigation focus on pregnant 4Ps beneficiaries and children through 24 completed calendar months in registered 4Ps households. Eligibility follows the existing DSWD age definition: birth date after today minus 25 months, through today. Future and missing birth dates are excluded. Each eligible child has an individual row; a pregnant mother and her child are separate beneficiaries.

## Pages and access

- `/dswd/dashboard`: six summary cards and four simple charts.
- `/dswd/f1kd`: searchable, filtered, paginated monitoring roster.
- `/dswd/f1kd/mother-{id}` or `/dswd/f1kd/child-{id}`: basic identity, monthly attendance, optional remark and monthly history.
- `/dswd/f1kd/reports`: monthly aggregate report, location breakdown, print and CSV export.
- `GET /staff/f1kd/mother-{id}` or `/staff/f1kd/child-{id}`: attendance form, linked from the mother's existing casefile.
- `PUT /staff/f1kd/{subject}`: save attendance with `month`, `attendance_status` and optional `remark_code`.

DSWD routes require an active DSWD account and remain read-only. Only approved Program Staff with an assigned mother casefile can save the mother's or her child's attendance. DSWD, mothers, unapproved staff, and unassigned staff cannot write monitoring records. No clinical notes, diagnoses, prescriptions, medical uploads or detailed medical measurements are passed to DSWD pages. The former DSWD medical document preview route has been removed; old beneficiary links lead to the appropriate F1KD records.

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

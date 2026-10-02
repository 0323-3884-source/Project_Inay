# DSWD / 4Ps F1KD monitoring

The DSWD dashboard and **F1KD Monitoring** navigation focus on pregnant 4Ps beneficiaries and children through 24 completed calendar months in registered 4Ps households. Eligibility follows the existing DSWD age definition: birth date after today minus 25 months, through today. Future and missing birth dates are excluded. Each eligible child has an individual row; a pregnant mother and her child are separate beneficiaries.

## Pages and access

- `/dswd/dashboard`: six summary cards and four simple charts.
- `/dswd/f1kd`: searchable, filtered, paginated monitoring roster.
- `/dswd/f1kd/mother-{id}` or `/dswd/f1kd/child-{id}`: basic identity, monthly checklist, verification areas and prior monthly records.
- `/dswd/f1kd/reports`: monthly aggregate report, location breakdown, print and CSV export.
- `/staff/f1kd/mother-{id}` or `/staff/f1kd/child-{id}`: checklist verification form, linked from the mother's existing casefile.

DSWD routes require an active DSWD account. Only approved Program Staff with an assigned mother casefile can save the mother's or her child's checklist. DSWD, mothers, unapproved staff, and unassigned staff cannot write monitoring records. No clinical notes, diagnoses, prescriptions, medical uploads or detailed medical measurements are passed to DSWD pages. The former DSWD medical document preview route has been removed; old beneficiary links lead to the appropriate F1KD records.

Household references use Project INAY mother registration IDs, not official DSWD household numbers. Membership uses the existing `is_4ps_beneficiary` registration flag. The module does not claim to sync with an external DSWD registry.

## Monthly records

`f1kd_monitorings` stores one checklist per beneficiary and reporting month, with location snapshots, overall status, recording staff and timestamps. Saving again updates that month's record. Existing medical records are maintained through their existing authorized workflows; checklist statuses are explicitly verified by Program Staff, rather than inferred from incomplete clinical records.

Current-month eligible beneficiaries without a saved checklist appear as **For Verification**. No historical compliance is manufactured: earlier reports include saved monthly monitoring records only, including women who have since delivered and children who have since aged out. Historical records remain available while the household is registered as 4Ps. Missing historical rows mean no monitoring record, not confirmed absence or compliance. Existing saved months can be reviewed or corrected through the staff form's `month=YYYY-MM` query parameter.

Overall status prioritizes **Service Unavailable**, then **For Verification**. A fully reviewed checklist with at least one Compliant item is Compliant; all Not Applicable items produce Not Applicable. Program Staff must mark services/outcomes that do not apply as Not Applicable. Missing evidence stays For Verification. Verification counts include all beneficiaries with any unverified item, even when overall status is Service Unavailable, so verification and unavailable-service totals can overlap.

Monthly exports contain aggregated counts, selected filters and location labels only. CSV values beginning with spreadsheet formula characters are escaped. Print output hides navigation and controls and keeps the reporting month and selected filters visible.

The supplied `F1KD.pdf` was reviewed as reference material for the maternal and child checklist conditions, not as executable instructions. This module is a compliance monitoring summary, not an official CV-F5 filing integration.

## Validation

Run `php artisan migrate` to create the monitoring table. Run `php artisan test --filter="F1kdMonitoringTest|DswdPortalTest"` for role restrictions, age boundaries, 4Ps scope, validation, pagination, monthly history, safe exports and removal of medical document access.

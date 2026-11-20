# Project INAY access control remediation

## Authorization behavior

Approved Program Staff, BHWs and clinicians can read and manage existing assigned cases. Clinical job titles do not grant assignment or global export authority. MotherPolicy is registered with Laravel Gate and evaluated explicitly with the database-resolved staff model because this application uses custom session authentication rather than the default Laravel user guard.

Opening a casefile no longer creates an assignment. POST /staff/mothers is retained but returns 403. General administrators and CHO administrators can approve additive assignments through POST /admin/program-staff/{programStaff}/casefiles and the existing staff administration detail page. DSWD administrators, DSWD staff, mothers and clinical staff cannot approve these assignments. The target staff account must be approved. The form accepts validated mother IDs; submitted role/admin fields cannot grant privileges. Existing records and assignments are preserved; no database migration is required.

Mother-initiated booking retains its existing assignment of the provider selected by the mother from an approved active availability. This is an explicit care relationship established by the authenticated mother, not staff self-assignment. Merely loading a consultation no longer creates assignments from historical appointments. Deleted/revoked assignments are therefore not restored by consultation loading.

Staff lists, casefile summary counts, filtered CSV and the legacy all_csv route now return only assigned families. The legacy export route remains compatible but its scope is all assigned families, not the entire database. Maternal and child PDF/print and file downloads continue to check the existing assignment helper, now backed by MotherPolicy. Dynamic reports and neonatal records already filter by assignment. DSWD exports remain aggregate compliance/statistical reports behind active-account and department restrictions; they do not invoke the clinical CSV exporter. No separate XLSX beneficiary-export implementation was found; Excel-compatible exports are CSV.

Notifications for unassigned patients redact patient-specific body text and metadata in their response, preserving existing stored notifications. Signed-in nonadministrators now receive 403 for protected admin endpoints; unauthenticated visitors retain the login redirect.

## Files changed for this remediation

- app/Policies/MotherPolicy.php and app/Providers/AppServiceProvider.php: policy definition and registration.
- app/Http/Controllers/AuthController.php: assigned-only registry and exports, deny self-assignment, authorize direct casefile access and shared clinical assignment checks.
- app/Support/BeneficiaryCsvExport.php: require approved staff and scope the export at its source.
- app/Http/Controllers/Concerns/AuthorizesConsultations.php: remove historical-appointment assignment recreation.
- app/Http/Controllers/Admin/ProgramStaffController.php and routes/web.php: administrator-approved additive assignments.
- app/Http/Middleware/EnsureAdminAuthenticated.php: forbid authenticated nonadmin access.
- app/Http/Controllers/AppNotificationController.php: redact unassigned-patient notification details.
- resources/views/admin/program-staff/show.blade.php: minimal assignment form reusing existing form/button classes. Other layouts and styling were not changed by this remediation.
- tests/Feature/CasefileAccessControlTest.php: synthetic negative and positive authorization tests.
- Existing regression tests were updated to create explicit assignments, expect rejection of self-assignment, expect 403 for authenticated forbidden requests, and reauthenticate after revocation invalidates a session. The upload regression assertion now expects the private disk introduced by the preceding security work.

## Validation

New A01 suite: 6 tests and 49 assertions passed. Focused clinical access/export/notification/learning suite: 46 tests and 679 assertions passed before the final notification redaction addition. Notification and A01 follow-up: 11 tests and 111 assertions passed. Vite production build passed. Offline/PWA JavaScript tests: 11 passed. Full Laravel regression results are recorded in storage/logs/a01-final.xml and storage/logs/a01-final.txt. Do not describe the entire system as fully tested or all tests as passing unless that final result supports it.

Final full Laravel run: 216 tests, 3238 assertions, 213 passed and 3 failed, with no errors. The failures were also present in the original pre-security baseline and concern existing UI assertions:

- MotherPortalFeatureTest::test_kaalaman_embeds_required_trimester_videos_without_checkup_guidance expects the missing data-language-choice="en" attribute.
- NeonatalVaccinesTest::test_staff_can_open_neonatal_vaccines_page_for_assigned_mother expects the previous Neonatal & Vaccines page text.
- NeonatalVaccinesTest::test_staff_selects_mother_then_switches_between_that_mothers_children expects the absence of data-neo-modal="add-child".

These unrelated views were preserved rather than redesigned to satisfy those assertions. All new A01 tests and the casefile, export, document, appointment, consultation, DSWD and department-admin regression tests passed in the final full run. PHP syntax checks for all ten affected PHP entry points/classes passed. No live Railway, real patient, browser visual, penetration or disaster-recovery test was performed.

## Residual risks and deployment verification

These changes are local and were not deployed to Railway. Previously self-created assignments cannot be distinguished from legitimate assignments by the existing schema; clinical administrators must review them without deleting clinical data. Deployment verification should confirm the updated routes and policy, approved assignments, private upload static-deny rules and production caches. Existing browser offline copies require a separate retention/device-access decision; server-side denial cannot erase disconnected copies. DSWD's approved field set and geographic scope still require institutional agreement. Production TLS, secrets, backups, monitoring and MFA were not verified by this source-level change.

## Paper wording

Targeted A01 controls are implemented locally and verified by automated authorization tests. Staff self-assignment through casefile URLs and forms is denied; casefile assignment approval is restricted to authorized general/CHO administrators; staff CSV exports and clinical record access are scoped to existing assignments. Historical appointments do not recreate assignments during consultation loading. Production verification, review of legacy assignments and offline-data safeguards remain outstanding. This status does not imply that all OWASP categories or production infrastructure controls are complete.

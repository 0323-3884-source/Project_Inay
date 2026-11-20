# Staff activity and security event monitoring

## Implemented behavior

The existing Program Staff management page now includes Active or Inactive login activity badges. DSWD Staff management separates account access (Enabled or Disabled) from login activity. A new Staff Login Activity link in the admin sidebar opens /admin/staff-activity.

CHO administrators see only Program Staff activity. DSWD administrators see only DSWD Staff activity. General administrators see both in separate sections. The controller derives scope from the current database-backed administrator role, including JSON polling requests; request parameters cannot expand scope.

Active means an approved/enabled account with authenticated activity during the last five minutes. Login establishes presence, authorized requests refresh it, and logout clears it. A closed or idle browser expires after five minutes. Changing a staff record is not proof of login. The admin view deliberately does not use the chat helper's updated_at/demo fallback. Polling refreshes displayed statuses every 30 seconds while the admin page is visible; offline pages retain their last observation. Last login and last activity timestamps are displayed in Philippine time, retained in the configured cache for up to 30 days. Cache eviction can remove these timestamps; the durable event stream is separate. Multiple sessions share account presence; logout can show Inactive until another session next makes an authenticated request.

## Security logging

SecurityAudit records login successes/failures reaching the middleware, logout, forbidden responses, export/download requests, staff administration requests, and selected clinical/compliance write requests. Request events are not proof that a file finished downloading or that a database update committed. Logs contain actor role/ID, route name, HTTP method, response status and timestamp. They exclude submitted emails, passwords, tokens, raw URLs/queries, patient details, message bodies and exception messages.

The security log channel writes daily files under storage/logs/security-YYYY-MM-DD.log (14-day retention) and stderr for Railway log collection. Five login failures or access denials in 60 seconds, or ten export/download requests in 60 seconds, emit a security_alert warning. Login-failure burst grouping uses a transient hashed IP cache key; the IP itself is not included in these records. Rate-limiter or framework failures occurring before this middleware may not produce these application events. This is application monitoring, not a complete centralized SIEM or immutable audit trail.

## Modified files

- app/Support/StaffActivity.php: strict presence and activity timestamps.
- app/Http/Middleware/UpdateChatPresence.php: authenticated active-account checks and DSWD activity updates.
- app/Http/Middleware/SecurityAudit.php and config/logging.php: sanitized security events and burst warnings.
- app/Http/Controllers/AuthController.php and app/Http/Controllers/Admin/AdminAuthController.php: explicit successful-login marker to avoid treating a failed login from an existing session as a success.
- app/Http/Controllers/Admin/StaffActivityController.php, app/Models/AdminUser.php, routes/web.php: department-scoped activity view and JSON data.
- bootstrap/app.php: security audit middleware registration.
- resources/views/layouts/admin.blade.php: activity navigation link.
- resources/views/admin/staff-activity.blade.php, admin/program-staff/index.blade.php and admin/dswd-staff.blade.php: activity indicators using existing layout and styles.
- public/js/staff-activity.js: scoped status refresh without reloading or disrupting account forms.
- tests/Feature/StaffActivityTest.php: login/logout, idle expiry, disabled accounts, scope isolation, polling authorization, redaction and warning tests.

No migrations, production deployment, account privilege changes, or deletion of application records were performed. Use a shared persistent cache (database or Redis) across Railway replicas for consistent presence; do not use the array cache in production. Protect and retain Railway logs using an institution-approved external log destination before claiming centralized monitoring.

## Verification

StaffActivityTest passed 6 tests with 65 assertions. The initial focused authentication/admin/A01 regression group passed 50 tests with 545 assertions. Blade template compilation, PHP syntax checks, JavaScript syntax check and Vite production build passed. The full-suite results are in storage/logs/activity-final.xml and activity-final.txt. No live Railway or visual browser verification was performed.

Final full suite: 222 tests, 3303 assertions, 219 passed, 3 failed, no errors or skipped tests. The failures are the same pre-existing UI assertions in MotherPortalFeatureTest::test_kaalaman_embeds_required_trimester_videos_without_checkup_guidance and NeonatalVaccinesTest::test_staff_can_open_neonatal_vaccines_page_for_assigned_mother / test_staff_selects_mother_then_switches_between_that_mothers_children. All staff activity and department-scope tests passed.

## Paper status

OWASP A09 Logging and Alerting Failures: Partially remediated locally; centralized monitoring and operational response remain pending. Department-scoped staff activity monitoring, sanitized authentication/access/export/admin-action event logging, and threshold-based warning events are implemented. External log retention, tamper protection, notification delivery, alert ownership and incident-response drills require production configuration and verification.

## Automatic presence and registry layout follow up

Program Staff Management now uses a Mother Case File style table with separate login, approval and ID-verification columns, existing identity previews and Chat/View Edit actions. The filters fit the available width and the table scrolls inside its container on small screens. Browser checks at 1440px and 390px passed and screenshots were visually inspected.

Admin status polling now runs immediately and every 10 seconds (superseding the earlier 30-second interval). Authenticated staff/DSWD portal pages immediately send a CSRF-protected heartbeat and repeat it every minute while visible and recently used. Hidden/disconnected tabs skip heartbeats; after four minutes without interaction they stop extending presence. Existing five-minute presence expiry still applies. Expired or revoked authentication stops heartbeats. Backend endpoints derive identity from the session and reject role/ID spoofing.

Follow-up verification: 41 relevant Laravel tests passed with 503 assertions; seven activity tests account for 77 assertions. Four JavaScript tests passed for immediate entry, hidden/idle tabs, expired authentication and automatic admin refresh. Desktop/mobile browser layout checks and Blade compilation passed. Changes remain local, with no migrations or production deployment.

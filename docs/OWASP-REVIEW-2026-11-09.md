# Project INAY OWASP 2025 source review

Reviewed 9 November 2026, against the risk table supplied in chat. Scope: current local working tree, selected security tests and dependency audits. Existing local modifications were included in the review. No production deployment or live penetration testing was performed. This report does not establish production security or compliance.

The screenshot's category names match the official OWASP Top 10:2025 list: https://top10.owasp.org/2025/. OWASP category order is not a Project INAY likelihood/impact score. Ratings require an explicit scoring matrix, exposure assumptions and assessment of the deployed controls.

## Current evidence

| Category | Local evidence | Remaining uncertainty or action |
| --- | --- | --- |
| A01 Broken Access Control | MotherPolicy requires an approved staff account and an existing case assignment. Staff self-assignment is denied; assigned-only exports and administrator-approved assignments are implemented. Medical records and worker IDs use private storage with authorized legacy reads. Backup privileges require exact configured administrator identities. | Verify deployment, Apache legacy static-deny rules and historical assignments. Mother/child photos still use public storage; review intended disclosure and access requirements. |
| A02 Security Misconfiguration | SecurityHeaders adds nosniff, SAMEORIGIN framing protection and same-origin referrer policy. Authenticated responses receive private/no-store. Session defaults include HttpOnly, SameSite=Lax and secure cookies in production. | Production environment overrides, debug mode, TLS, database privileges and ingress are unverified. All proxies are trusted in bootstrap/app.php; verify the application can only receive forwarded headers from trusted ingress. |
| A03 Software Supply Chain Failures | Fresh composer audit --locked and npm audit returned zero known advisories. Lockfiles exist. | This does not exclude undisclosed vulnerabilities, compromised packages or deployment drift. Maintain dependency scanning and controlled builds. The older initial report's vulnerable dependency finding does not describe the current audit result. |
| A04 Cryptographic Failures | Passwords are hashed. Password reset tokens are hashed. The offline entry queue uses PBKDF2 and AES-GCM. | Service-worker cached pages/JSON are not protected by that entry-queue encryption. SQL backups are HMAC authenticated but are not encrypted by the application. Verify encrypted device/off-site storage, production TLS and key management. |
| A05 Injection | Reviewed query paths use Eloquent, parameter binding or controlled query fragments. SQL recovery verifies HMAC and schema before executing installation-generated statements. | No confirmed injection was established in this review. This is not exhaustive SQL injection, XSS, command injection or upload penetration testing. A High rating cannot be justified solely by the category's existence. |
| A06 Insecure Design | Explicit clinical assignments, departmental permissions, backup password checks and recovery safeguards exist. | Offline access retains sensitive information outside a live server authorization check. Define device access, retention, data-sharing scope and recovery requirements. |
| A07 Authentication Failures | Both login endpoints use account/IP rate limiting. Login regenerates session IDs; logout invalidates sessions. Approval revocation checks and generic reset responses exist. Role-scoped single-use reset behavior passed selected tests. | No MFA control was identified in reviewed authentication paths. Review privileged-account MFA and revocation of other sessions after password changes/reset. |
| A08 Software or Data Integrity Failures | Backups use HMAC verification, schema checks, password reauthentication and session-bound preview. Synthetic tests cover modified SQL rejection and recovery boundaries. | Verify deployed build provenance and operational controls. HMAC uses APP_KEY; compromise of that key compromises backup authenticity. |
| A09 Security Logging and Alerting Failures | SecurityAudit emits sanitized authentication, denial, export and selected write events, with threshold warnings. Department-scoped activity monitoring exists. | Warnings are log events; external notification delivery, immutable retention, alert ownership and incident response remain unverified. General exception reporting still deserves sensitive-data review. |
| A10 Mishandling of Exceptional Conditions | Validation, transaction rollback, recovery locks and safe failure messages exist in reviewed paths. Synthetic recovery tests passed. | No production MySQL recovery drill, load/resource-exhaustion testing or exhaustive exception-path review was performed. |

## Verification performed now

- Composer locked dependency audit: no advisories and no abandoned packages reported.
- npm dependency audit: zero reported vulnerabilities.
- Direct PHPUnit run selecting CasefileAccessControlTest, BackupPermissionBoundaryTest, StaffActivityTest, ForgotPasswordTest, SeparatedRoleAuthTest and DatabaseBackupTest: **38 tests, 412 assertions, all passed**. Synthetic SQLite in-memory records and test storage were used by the selected suites.
- An initial artisan test invocation ran with the application environment reported as local, causing CSRF 419 responses and cache-dependent failures. Running PHPUnit directly allowed phpunit.xml to establish the testing environment; the same selected suites then passed. Do not interpret the initial harness result as a demonstrated authorization bypass.
- No new full-suite run, browser review, production inspection or live restoration was performed.

## How to revise the supplied risk table

Describe its ratings as preliminary qualitative estimates, rather than confirmed vulnerabilities. Add a control/status or evidence column so implemented protections and remaining gaps are visible. Distinguish initial/inherent risk from residual risk after controls; the current table does not make that distinction clear.

The screenshot assigns Medium likelihood and High impact to both High-risk rows and Medium-risk rows (A03 and A08). If a common likelihood-impact matrix is used, identical inputs should yield the same risk level. If additional factors change a row, document those factors and the scoring method. The present source review supports the statuses above, but does not independently establish numerical likelihoods or final production residual ratings.

Suggested report wording: "A local source review identified implemented access-control, authentication, dependency-management, integrity and logging safeguards. Selected security regression tests passed, and current dependency audits reported no known advisories. Residual concerns include offline sensitive-data retention, unencrypted application SQL backups and unverified production infrastructure and monitoring controls. Ratings remain qualitative and require a documented scoring matrix and deployment verification."

## Additional maintenance observations

config/backups.php repeats the approved_accounts key with identical values. PHP uses the latter value, so the duplication currently adds no permission, but it should be consolidated to avoid future configuration mistakes. routes/web.php also repeats the mother clinic-schedule calendar route. Neither item was changed during this review.

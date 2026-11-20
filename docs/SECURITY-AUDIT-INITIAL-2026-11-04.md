# Project INAY — initial security assessment (before remediation)

Assessment date: 4 November 2026. Local source review and synthetic tests only; no production access, patient data inspection, secret disclosure, deployment, or database changes. Existing edits to the layout and neonatal vaccine view are outside this assessment's edits.

## Architecture and trust boundaries

Laravel 12 monolith, Blade/Vite/PWA, Eloquent, MySQL/PostgreSQL/SQLite configuration, Dompdf reports, Apache/PHP Docker deployment on Railway. All application HTTP routes, including `/api/...`, use the web middleware stack and CSRF protection. No separate API routes were found. Mothers, ProgramStaff, DswdStaff, and AdminUser are separate tables. Authentication uses custom session fields rather than Laravel's default web guard. Department administrators and DSWD have route allowlists; most mother/staff checks are controller-local. StaffMotherCasefile is the clinical assignment relation. Consultations use participant roles and IDs; attachments use private local storage. Offline pages and queued measurements persist in the browser.

## Findings frozen before code changes

| ID | Severity | Evidence and consequence | OWASP 2025 | Confidence |
|---|---|---|---|---|
| S01 | High | AuthController::uploadInayKaalamanRecord uses `store('inay-kaalaman-records', 'public')`; MotherDocumentController and staff file endpoints read that disk. Docker startup creates public/storage. Direct static URLs bypass ownership checks for medical files. Random filenames do not provide authorization. | A01 | Confirmed source; live static exposure not probed |
| S02 | High | AuthController::registerStaff and Admin/ProgramStaffController store healthcare-worker ID images on public disk; ProgramStaff accessor returns a public URL. | A01 | Confirmed source |
| S03 | High | AuthController::staffMotherCasefile calls StaffMotherCasefile::firstOrCreate on GET; storeStaffMothers accepts any existing mother ID; staffMothers supports BeneficiaryCsvExport::download across all records. Staff can obtain their own authorization. | A01/A06 | Confirmed behavior; approved geographic/clinical scope needs owner decision |
| S04 | High | Controllers and consultation helpers commonly check only that a staff row exists. Existing sessions remain usable after approval is revoked. | A01/A07 | Confirmed source |
| S05 | High | POST /login and /admin/login have no throttling, including administrator authentication through the shared form. | A07 | Confirmed source |
| S06 | Medium | ForgotPasswordController::send reports `No account found with this email and selected role.` versus success for existing accounts. | A07 | Confirmed source and existing test expectation |
| S07 | Medium | AuthController::logout forgets identity fields but does not invalidate the session; session default lifetime is 43200 minutes, secure cookie has no production default. | A07/A02 | Confirmed defaults; Railway overrides unknown |
| S08 | Medium | AuthController::login runs `Artisan::call('migrate', ['--force' => true])` before authentication when DSWD schema is missing. Admin/DswdStaffController also performs request-time migrations. | A02/A06 | Confirmed source; conditional on missing schema |
| S09 | High | composer audit and npm audit report vulnerable locked dependencies, including critical advisories. Exact package advisories saved separately; installed version match is not proof of reachable exploitation. | A03 | Confirmed advisory matches; exploitability requires assessment |
| S10 | Medium | public/sw.js caches clinical HTML, JSON, messages and private assets; offline storage is accessible to the browser profile without a server session check while disconnected. Logout/account-switch cleanup exists, but local-device theft and session expiry are unresolved. | A04/A06 | Confirmed design; device safeguards unknown |
| S11 | Low | No application-wide nosniff, framing or referrer protection; consultation attachments are served inline. Cache-Control no-store is not global for clinical responses. | A02 | Confirmed source; edge overrides unknown |
| S12 | Medium | Exception reporting in message storage/mail paths can include database binding values or transport details. No centralized sensitive-field exception redaction was found. | A09 | Potential disclosure; no patient logs inspected |
| S13 | Medium | Mother/child photos are public; DSWD child view deliberately includes weight/height history and recorder names. Whether these disclosures exceed approved purpose is unverified. | A01/A06 | Confirmed fields, authorization purpose needs governance review |

## Existing safeguards and non-findings

Passwords are hashed, login regenerates session IDs, password reset tokens are role-scoped, hashed, expire and are consumed transactionally. DSWD accounts are checked for activity; department admin permissions resolve from the database. Mother child/document ownership, staff clinical mutation assignment checks, conversation participants, sender-only unsend and appointment ownership checks exist. Uploads have MIME-derived extension and size validation and randomized storage names. Reviewed raw SQL uses bound parameters or controlled fragments; no confirmed SQL injection or arbitrary executable upload was found. Explicit controller field allowlists reduce mass-assignment exposure despite permissive model fillable lists. CSRF has no configured exemptions. PDF remote access, embedded PHP and JavaScript are disabled. Docker ignores .env, keys, SQL, storage data and development artifacts. .env is not tracked in the current index. Historical secret scanning and production controls remain unverified.

## Planned safe remediation

Private new medical/ID uploads with authorized legacy reads, Apache static-deny rules for legacy sensitive folders, current staff approval middleware, login throttles, non-enumerating recovery messages, logout invalidation, production session defaults, security headers, and removal of HTTP-triggered migrations. Authorized mother owners, assigned approved clinicians, and permitted administrators retain their existing access. No automatic production file migration or secret changes. Staff self-assignment/global exports require a deliberate assignment policy before restricting the existing workflow.

## Scope limits

This is a source assessment and local regression exercise, not a penetration-test certificate, ISO certification, or legal compliance determination. Railway variables, public ingress isolation, TLS, MySQL permissions/encryption, backups, malware scanning, WAF, MFA, retention, lawful processing basis, data-sharing agreements, and incident handling cannot be established from repository files alone. The final report records actual fixes, tests and residual risk.

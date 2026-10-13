# Department administrator accounts

The existing `admin_users` table now has a `role` column. The original shared administrator is retired: its record and historical references remain, but login and existing sessions are disabled. No beneficiary or clinical tables are duplicated.

| Username | Initial password | Role | Dashboard |
| --- | --- | --- | --- |
| admindswd | ProjectInay2026 | admin_dswd | /admin/dswd/dashboard |
| admincho | ProjectInay2026 | admin_cho | /admin/cho/dashboard |

Use `/login` and enter the username in the email/admin username field. The stored account role determines the redirect regardless of the selected portal button. The dedicated `/admin/login` also works. Passwords use Laravel hashing and verification.

For another installation:

```sh
php artisan migrate
php artisan db:seed --class=DepartmentAdminSeeder
```

The seeder preserves existing passwords on reruns and refuses usernames already owned by another role. The configured original shared administrator is disabled by assigning `admin_retired`; it is not deleted.

## Authorization

`RestrictDepartmentAdmin` runs before route-model binding and resolves the administrator role from the database on every request. Department accounts use explicit route allowlists, including shared APIs. Unauthorized GET, download, POST, PATCH, and DELETE requests return HTTP 403. Deleted or retired administrator accounts lose their session. Existing admin module routes remain available to their assigned department and the legacy administrator.

DSWD can manage 4Ps household numbers and location information, review program attendance, manage DSWD Staff, and download program statistics. It cannot edit pregnancy or clinical fields or access non-4Ps records through its beneficiary editor. Its queries reuse `DswdStatistics` and `F1kdCompliance` projections rather than passing clinical models to views.

CHO can view maternal and child clinical records and manage Program Staff, educational content, and clinical settings. It cannot manage DSWD Staff or enter DSWD-only modules. Program Staff admin-message threads are restricted to legacy and CHO administrators. Notifications remain scoped to the owning administrator ID.

## Statistics

- DSWD registered totals are current 4Ps mother records and DSWD staff. F1KD population and attendance follow the existing F1KD service for the selected month, including eligible children. Mothers and children have separate attendance charts/tables. Only explicitly recorded `did_not_attend` entries count as missed; missing submissions remain pending.
- CHO registered totals count existing mothers, active pregnancies, children, maternal monitoring records, clinical checkup records (`InayKaalamanCheckup`), and vaccine records. Pending screenings include active pregnancies without monitoring records and those whose latest record needs review, professional interpretation, or urgent referral. Consultation totals represent recorded checkups, not chat messages or scheduled appointments.
- Barangay and vaccine charts use database groups. The maternal-record trend covers six months ending in the selected reporting month. CSV reports export the same authorized counts and chart groups; spreadsheet formula prefixes are escaped.

Both dashboards reuse the green/white admin layout. Mobile navigation uses a collapsible Menu button.

## Validation

```sh
php artisan test --filter="DepartmentAdminTest|AdminModuleTest|AdminProgramStaffTest|AdminStaffMessagesTest|EducationalContentManagementTest|AppNotificationsTest|SeparatedRoleAuthTest|DswdPortalTest|F1kdMonitoringTest"
```

Browser layout checks use synthetic database fixtures, not live beneficiary records. In PowerShell:

```powershell
$env:INAY_ADMIN_FIXTURES = 'storage/framework/testing'
php artisan test --filter=DepartmentAdminTest
node tests/js/department-admin.browser.mjs
```

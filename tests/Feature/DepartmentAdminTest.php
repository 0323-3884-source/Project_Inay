<?php

namespace Tests\Feature;

use App\Models\{AdminUser, AppNotification, DswdStaff, F1kdMonitoring, Infant, InfantVaccineRecord, InayKaalamanCheckup, MaternalMonitoringRecord, Mother};
use Database\Seeders\DepartmentAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DepartmentAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role): AdminUser
    {
        return AdminUser::create(['username' => $role, 'role' => $role, 'password' => Hash::make('ProjectInay2026')]);
    }

    private function asAdmin(AdminUser $admin): static
    {
        return $this->withSession(['admin_authenticated' => true, 'admin_id' => $admin->id, 'admin_username' => $admin->username]);
    }

    private function mother(string $name, bool $fourPs = true): Mother
    {
        return Mother::create(['first_name' => $name, 'last_name' => 'Test', 'email' => strtolower($name).'@example.test', 'password' => Hash::make('secret'), 'contact_number' => '09170000000', 'barangay' => 'Barangay A', 'pregnancy_status' => 'pregnant', 'is_4ps_beneficiary' => $fourPs]);
    }

    public function test_seeded_accounts_login_independently_and_retire_original_admin(): void
    {
        $original = AdminUser::where('username', 'admin')->firstOrFail()->getRawOriginal();
        $this->seed(DepartmentAdminSeeder::class);
        $dswd = AdminUser::where('username', 'admindswd')->firstOrFail();
        $hash = $dswd->password;
        $this->seed(DepartmentAdminSeeder::class);
        $this->assertSame($hash, $dswd->fresh()->password);
        $retired = AdminUser::where('username', 'admin')->firstOrFail();
        $this->assertSame($original['id'], $retired->id);
        $this->assertSame($original['password'], $retired->password);
        $this->assertSame('admin_retired', $retired->role);
        $this->assertFalse($retired->canAuthenticate());
        foreach (['admindswd' => 'dswd', 'admincho' => 'cho'] as $username => $scope) {
            $admin = AdminUser::where('username', $username)->firstOrFail();
            $this->assertTrue(Hash::check('ProjectInay2026', $admin->password));
            $this->post('/login', ['role' => $scope === 'dswd' ? 'dswd_staff' : 'mother', 'email' => $username, 'password' => 'ProjectInay2026'])
                ->assertRedirect('/admin/'.$scope.'/dashboard')->assertSessionHas('admin_role', 'admin_'.$scope)->assertSessionMissing('auth_role');
            $this->get('/admin/'.$scope.'/dashboard')->assertOk()->assertSee(strtoupper($scope).' ADMIN CONSOLE');
            $this->get('/login')->assertRedirect('/admin/'.$scope.'/dashboard');
            $this->get('/admin')->assertRedirect('/admin/'.$scope.'/dashboard');
            $this->post('/admin/logout')->assertRedirect('/login')->assertSessionMissing('admin_authenticated');
        }
    }

    public function test_retired_admin_cannot_log_in_or_keep_an_existing_session(): void
    {
        $admin = $this->admin('admin_retired');
        $this->post('/login', ['role' => 'mother', 'email' => $admin->username, 'password' => 'ProjectInay2026'])->assertSessionHasErrors();
        $this->post('/admin/login', ['username' => $admin->username, 'password' => 'ProjectInay2026'])->assertSessionHasErrors();
        $this->asAdmin($admin)->get('/admin/statistics')->assertRedirect('/admin/login')->assertSessionMissing('admin_authenticated');
    }

    public function test_seeder_preserves_changed_passwords_and_rejects_unrelated_username_collision(): void
    {
        $this->seed(DepartmentAdminSeeder::class);
        $admin = AdminUser::where('username', 'admindswd')->firstOrFail();
        $admin->update(['password' => Hash::make('new-password')]);
        $this->seed(DepartmentAdminSeeder::class);
        $this->assertTrue(Hash::check('new-password', $admin->fresh()->password));
        $admin->update(['role' => 'admin']);
        $this->expectException(\RuntimeException::class);
        $this->seed(DepartmentAdminSeeder::class);
    }

    public function test_department_roles_reject_cross_department_reads_writes_and_shared_clinical_apis(): void
    {
        $dswd = $this->admin('admin_dswd');
        $cho = $this->admin('admin_cho');
        $this->asAdmin($dswd);
        foreach (['/admin/cho/dashboard', '/admin/cho/mothers', '/admin/cho/reports?download=1', '/admin/program-staff', '/admin/educational-content', '/admin/maternal-vital-thresholds', '/admin/statistics', '/staff/dashboard', '/maternal-monitoring', '/consultation/conversations', '/admin-staff-messages/threads'] as $path) $this->get($path)->assertForbidden();
        $this->post('/admin/educational-content', [])->assertForbidden();
        $this->patch('/admin/maternal-vital-thresholds', [])->assertForbidden();
        $this->delete('/admin/program-staff/999')->assertForbidden();
        $this->asAdmin($cho);
        foreach (['/admin/dswd/dashboard', '/admin/dswd/beneficiaries', '/admin/dswd/attendance', '/admin/dswd/reports?download=1', '/admin/dswd-staff', '/dswd/dashboard'] as $path) $this->get($path)->assertForbidden();
        $this->post('/admin/dswd-staff', [])->assertForbidden();
        $this->patch('/admin/dswd/beneficiaries/999', [])->assertForbidden();
    }

    public function test_database_role_overrides_forged_session_role_and_deleted_accounts_lose_access(): void
    {
        $admin = $this->admin('admin_dswd');
        $this->asAdmin($admin)->withSession(['admin_role' => 'admin'])->get('/admin/cho/dashboard')->assertForbidden();
        $admin->update(['role' => 'admin_cho']);
        $this->get('/admin/dswd/dashboard')->assertForbidden();
        $this->get('/admin/cho/dashboard')->assertOk();
        $admin->delete();
        $this->get('/admin/cho/dashboard')->assertRedirect('/admin/login')->assertSessionMissing('admin_authenticated');
    }

    public function test_scoped_modules_and_dashboards_render_from_real_records(): void
    {
        $beneficiary = $this->mother('Beneficiary');
        $other = $this->mother('Private', false);
        $child = Infant::create(['mother_id' => $beneficiary->id, 'full_name' => 'Child A', 'birth_date' => today()->subMonths(3), 'sex' => 'female']);
        MaternalMonitoringRecord::create(['mother_id' => $other->id, 'pregnancy_week' => 10, 'bp_systolic' => 150, 'bp_diastolic' => 100, 'screening_summary_status' => 'For Review', 'recorded_at' => now()]);
        InayKaalamanCheckup::create(['mother_id' => $beneficiary->id, 'month' => 1, 'checkup_date' => today(), 'healthcare_worker_name' => 'Healthcare Worker', 'facility_name' => 'Health Center']);
        InfantVaccineRecord::create(['infant_id' => $child->id, 'vaccine_name' => 'BCG', 'dose_label' => 'First dose', 'vaccine_group' => 'neonatal', 'status' => 'pending', 'due_date' => today()]);
        F1kdMonitoring::create(['mother_id' => $beneficiary->id, 'subject_key' => 'mother-'.$beneficiary->id, 'classification' => 'pregnant', 'reporting_month' => today()->startOfMonth(), 'attendance_status' => 'attended', 'status' => 'compliant', 'checklist' => []]);
        F1kdMonitoring::create(['mother_id' => $beneficiary->id, 'infant_id' => $child->id, 'subject_key' => 'child-'.$child->id, 'classification' => 'child', 'reporting_month' => today()->startOfMonth(), 'attendance_status' => 'did_not_attend', 'status' => 'non_compliant', 'checklist' => []]);
        $dswd = $this->admin('admin_dswd');
        $cho = $this->admin('admin_cho');
        $this->asAdmin($dswd)->get('/admin/dswd/dashboard')->assertOk()->assertViewHas('cards', fn ($cards) => $cards['Total Registered 4Ps Beneficiaries'] === 1 && $cards['Monthly Health Attendance'] === 1 && $cards['Mothers and Children with Missed Checkups'] === 1)->assertDontSee('Program Staff')->assertDontSee('Clinical Settings');
        $this->get('/admin/dswd/beneficiaries')->assertOk()->assertSee('Beneficiary Test')->assertDontSee('Private Test')->assertDontSee('150/100');
        foreach (['f1kd', 'attendance', 'notifications'] as $module) $this->get('/admin/dswd/'.$module)->assertOk();
        $this->get('/admin/dswd/attendance')->assertSee('Mothers')->assertSee('Children')->assertSee('Missed checkup');
        $this->get('/admin/dswd-staff')->assertOk();
        $this->get('/admin/dswd/reports?download=1')->assertOk()->assertDownload('inay-dswd-'.now()->format('Y-m').'.csv');
        $this->asAdmin($cho)->get('/admin/cho/dashboard')->assertOk()->assertViewHas('cards', fn ($cards) => $cards['Total Registered Mothers'] === 2 && $cards['Total Registered Children'] === 1 && $cards['Consultation Records'] === 1 && $cards['Pending Health Screenings'] === 2)->assertDontSee('DSWD / 4Ps Staff');
        foreach (['mothers', 'pregnancy', 'consultations', 'vaccinations', 'notifications'] as $module) $this->get('/admin/cho/'.$module)->assertOk();
        foreach (['/admin/program-staff', '/admin/educational-content', '/admin/maternal-vital-thresholds'] as $path) $this->get($path)->assertOk();
        $this->get('/admin/cho/mothers/'.$other->id)->assertOk()->assertSee('150/100 mmHg');
        $this->get('/admin/cho/reports?download=1')->assertOk()->assertDownload('inay-cho-'.now()->format('Y-m').'.csv');
        if ($directory = getenv('INAY_ADMIN_FIXTURES')) {
            foreach (['dswd' => $dswd, 'cho' => $cho] as $scope => $admin) {
                file_put_contents($directory.'/'.$scope.'.html', $this->asAdmin($admin)->get('/admin/'.$scope.'/dashboard')->getContent());
            }
        }
    }

    public function test_dswd_can_only_update_program_fields_of_existing_four_ps_beneficiaries(): void
    {
        $beneficiary = $this->mother('Beneficiary');
        $other = $this->mother('Private', false);
        $this->asAdmin($this->admin('admin_dswd'))->patch('/admin/dswd/beneficiaries/'.$beneficiary->id, ['barangay' => 'New Barangay', 'four_ps_household_number' => '4PS-001', 'pregnancy_status' => 'postpartum', 'is_4ps_beneficiary' => false])->assertRedirect();
        $this->assertSame('New Barangay', $beneficiary->fresh()->barangay);
        $this->assertSame('pregnant', $beneficiary->fresh()->pregnancy_status);
        $this->assertTrue($beneficiary->fresh()->is_4ps_beneficiary);
        $this->patch('/admin/dswd/beneficiaries/'.$other->id, ['barangay' => 'Unauthorized'])->assertNotFound();
        $this->assertSame('Barangay A', $other->fresh()->barangay);
    }

    public function test_notifications_are_isolated_between_the_two_admins(): void
    {
        $dswd = $this->admin('admin_dswd');
        $cho = $this->admin('admin_cho');
        $notification = AppNotification::create(['recipient_id' => $cho->id, 'recipient_role' => 'admin', 'type' => 'test', 'title' => 'CHO private notice', 'body' => 'Private clinical notification']);
        $this->asAdmin($dswd)->get('/admin/dswd/notifications')->assertOk()->assertDontSee('CHO private notice');
        $this->post('/notifications/'.$notification->id.'/read')->assertForbidden();
        $this->asAdmin($cho)->get('/admin/cho/notifications')->assertOk()->assertSee('CHO private notice');
    }

    public function test_dswd_admin_can_remove_staff_accounts_but_cho_cannot(): void
    {
        $account = DswdStaff::create(['name' => 'Officer', 'email' => 'remove@example.test', 'password' => 'secure-password']);
        $this->asAdmin($this->admin('admin_cho'))->delete('/admin/dswd-staff/'.$account->id)->assertForbidden();
        $this->assertDatabaseHas('dswd_staff', ['id' => $account->id]);
        $this->asAdmin($this->admin('admin_dswd'))->get('/admin/dswd-staff')->assertOk()->assertSee('Remove')->assertSee(route('admin.dswd-staff.destroy', $account));
        $this->delete('/admin/dswd-staff/'.$account->id)->assertRedirect('/admin/dswd-staff')->assertSessionHas('status');
        $this->assertDatabaseMissing('dswd_staff', ['id' => $account->id]);
        $this->delete('/admin/dswd-staff/'.$account->id)->assertNotFound();
        $this->flushSession();
        $this->withSession(['auth_role' => 'dswd_staff', 'auth_id' => $account->id])->get('/dswd/dashboard')->assertRedirect('/login')->assertSessionMissing('auth_role');
    }

    public function test_program_staff_messaging_does_not_expose_dswd_admin_threads(): void
    {
        $dswd = $this->admin('admin_dswd');
        $cho = $this->admin('admin_cho');
        $staff = \App\Models\ProgramStaff::create(['first_name' => 'Staff', 'last_name' => 'Tester', 'email' => 'staff@example.test', 'password' => Hash::make('secret'), 'staff_id' => 'DEPARTMENT-001', 'position' => 'Program Staff', 'contact_number' => '09170000000']);
        $oldThread = \App\Models\AdminStaffThread::create(['admin_user_id' => $dswd->id, 'program_staff_id' => $staff->id]);
        $this->withSession(['auth_role' => 'staff', 'auth_id' => $staff->id])->get('/admin-staff-messages/threads')->assertOk()->assertJsonPath('threads', fn ($threads) => ! collect($threads)->contains('id', $oldThread->id));
        $this->get('/admin-staff-messages/threads/'.$oldThread->id.'/messages')->assertForbidden();
        $this->post('/admin-staff-messages/threads/'.$oldThread->id.'/messages', ['message' => 'Unauthorized'])->assertForbidden();
        $this->assertDatabaseHas('admin_staff_threads', ['admin_user_id' => $cho->id, 'program_staff_id' => $staff->id]);
    }
}

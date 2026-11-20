<?php

namespace Tests\Feature;

use App\Models\{AdminUser, DswdStaff, ProgramStaff};
use App\Support\{ChatPresence, StaffActivity};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Log};
use Tests\TestCase;

class StaffActivityTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): ProgramStaff
    {
        return ProgramStaff::create(['first_name' => 'Synthetic', 'last_name' => 'CHO', 'email' => 'activity-cho@example.test', 'password' => Hash::make('synthetic-password'), 'staff_id' => 'ACTIVITY-1', 'position' => 'Program Staff', 'approval_status' => 'approved', 'contact_number' => '09000000000']);
    }

    private function dswd(): DswdStaff
    {
        return DswdStaff::create(['name' => 'Synthetic DSWD', 'email' => 'activity-dswd@example.test', 'password' => 'synthetic-password', 'is_active' => true]);
    }

    private function admin(string $role): void
    {
        $this->flushSession();
        $admin = AdminUser::create(['username' => 'activity-'.$role, 'password' => 'synthetic-password', 'role' => $role]);
        $this->withSession(['admin_authenticated' => true, 'admin_id' => $admin->id]);
    }

    public function test_login_logout_and_idle_expiry_for_both_departments(): void
    {
        foreach (['staff' => $this->staff(), 'dswd_staff' => $this->dswd()] as $role => $account) {
            $this->assertFalse(StaffActivity::summary($account)['active']);
            $this->post('/login', ['role' => $role, 'email' => $account->email, 'password' => 'synthetic-password'])->assertRedirect();
            $this->assertTrue(StaffActivity::summary($account)['active']);
            $this->assertNotNull(StaffActivity::summary($account)['last_login']);
            $this->post('/logout')->assertRedirect('/login');
            $this->assertFalse(StaffActivity::summary($account)['active']);
            $this->assertNotNull(StaffActivity::summary($account)['last_seen']);
            ChatPresence::recordPresence($role, $account->id);
            $this->travel(6)->minutes();
            $this->assertFalse(StaffActivity::summary($account)['active']);
            $this->travelBack();
        }
    }

    public function test_department_boundaries_and_general_admin_combined_view(): void
    {
        $cho = $this->staff();
        $dswd = $this->dswd();
        $this->admin('admin_cho');
        $this->get('/admin/staff-activity')->assertOk()->assertSee('Synthetic CHO')->assertDontSee('Synthetic DSWD');
        $this->getJson('/admin/staff-activity?role=admin')->assertOk()->assertJsonCount(1, 'program_staff')->assertJsonPath('dswd_staff', null);
        $this->getJson('/admin/staff-activity?program_ids[]='.$cho->id.'&dswd_ids[]='.$dswd->id)->assertJsonCount(1, 'program_staff')->assertJsonPath('dswd_staff', null);
        $this->get('/admin/dswd-staff')->assertForbidden();
        $this->admin('admin_dswd');
        $this->get('/admin/staff-activity')->assertOk()->assertSee('Synthetic DSWD')->assertDontSee('Synthetic CHO');
        $this->getJson('/admin/staff-activity?role=admin')->assertJsonPath('program_staff', null)->assertJsonCount(1, 'dswd_staff');
        $this->getJson('/admin/staff-activity?program_ids[]='.$cho->id.'&dswd_ids[]='.$dswd->id)->assertJsonPath('program_staff', null)->assertJsonCount(1, 'dswd_staff');
        $this->get('/admin/program-staff')->assertForbidden();
        $this->admin('admin');
        $this->get('/admin/staff-activity')->assertOk()->assertSee('Synthetic DSWD')->assertSee('Synthetic CHO');
        $registry = $this->get('/admin/program-staff')->assertOk()->assertSee('Inactive')->assertSee('staff-registry-table');
        if ($fixture = getenv('INAY_ACTIVITY_FIXTURE')) file_put_contents($fixture, $registry->getContent());
        $this->get('/admin/dswd-staff')->assertOk()->assertSee('Login activity');
    }

    public function test_recent_edits_and_disabled_accounts_are_not_active(): void
    {
        $staff = $this->staff();
        $dswd = $this->dswd();
        $this->assertFalse(StaffActivity::summary($staff)['active']);
        ChatPresence::recordPresence('staff', $staff->id);
        ChatPresence::recordPresence('dswd_staff', $dswd->id);
        $staff->update(['approval_status' => 'rejected']);
        $dswd->update(['is_active' => false]);
        $this->assertFalse(StaffActivity::summary($staff->fresh())['active']);
        $this->assertFalse(StaffActivity::summary($dswd->fresh())['active']);
    }

    public function test_authenticated_requests_refresh_dswd_activity_and_nonadmins_cannot_read_it(): void
    {
        $account = $this->dswd();
        $this->get('/admin/staff-activity')->assertRedirect('/admin/login');
        $this->withSession(['auth_role' => 'dswd_staff', 'auth_id' => $account->id]);
        $this->get('/dswd/profile')->assertOk();
        $this->assertTrue(StaffActivity::summary($account)['active']);
        $this->get('/admin/staff-activity')->assertForbidden();
    }

    public function test_failed_login_is_audited_without_credentials_or_email(): void
    {
        $logger = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        Log::shouldReceive('channel')->with('security')->andReturn($logger);
        $logger->shouldReceive('info')->once()->with('login_failed', \Mockery::on(function ($context) {
            $this->assertSame(['actor_role', 'actor_id', 'route', 'method', 'status'], array_keys($context));
            $this->assertSame(422, $context['status']);
            $this->assertStringNotContainsString('submitted-secret', json_encode($context));
            $this->assertStringNotContainsString('missing@example.test', json_encode($context));
            return true;
        }));
        $this->postJson('/login', ['role' => 'staff', 'email' => 'missing@example.test', 'password' => 'submitted-secret'])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_repeated_login_failures_emit_a_security_warning(): void
    {
        $logger = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        Log::shouldReceive('channel')->with('security')->andReturn($logger);
        $logger->shouldReceive('info')->times(5)->with('login_failed', \Mockery::type('array'));
        $logger->shouldReceive('warning')->once()->with('security_alert', \Mockery::on(fn ($context) => $context['rule'] === 'login_failed_burst' && $context['count'] === 5));
        for ($index = 0; $index < 5; $index++) {
            $this->postJson('/login', ['role' => 'staff', 'email' => "missing{$index}@example.test", 'password' => 'synthetic-password'])->assertUnprocessable();
        }
    }

    public function test_heartbeat_uses_the_authenticated_account_and_rejects_role_swaps(): void
    {
        $staff = $this->staff();
        $dswd = $this->dswd();
        $this->postJson('/staff/activity')->assertForbidden();
        $this->withSession(['auth_role' => 'staff', 'auth_id' => $staff->id]);
        $this->get('/staff/dashboard')->assertOk()->assertSee('js/staff-presence.js', false);
        ChatPresence::recordOffline('staff', $staff->id);
        $this->postJson('/staff/activity', ['auth_role' => 'dswd_staff', 'auth_id' => $dswd->id])->assertOk()->assertJsonPath('active', true);
        $this->assertTrue(StaffActivity::summary($staff)['active']);
        $this->assertFalse(StaffActivity::summary($dswd)['active']);
        $this->postJson('/dswd/activity')->assertForbidden();
        $this->withSession(['auth_role' => 'dswd_staff', 'auth_id' => $dswd->id]);
        $this->postJson('/dswd/activity')->assertOk();
        $this->assertTrue(StaffActivity::summary($dswd)['active']);
        $this->postJson('/staff/activity')->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Support\DatabaseBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupPermissionBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private function endpoints(): array
    {
        return [
            ['GET', '/admin/backups'],
            ['POST', '/admin/backups'],
            ['POST', '/admin/backups/schedule'],
            ['POST', '/admin/backups/preview'],
            ['POST', '/admin/backups/restore'],
            ['POST', '/admin/backups/20261107-120000-aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa.sql/download'],
        ];
    }

    public function test_active_but_unapproved_administrators_are_denied_on_every_endpoint(): void
    {
        Storage::fake('local');
        $this->mock(DatabaseBackup::class, function ($mock) {
            $mock->shouldNotReceive('create');
            $mock->shouldNotReceive('inspect');
            $mock->shouldNotReceive('restore');
        });

        foreach (['admin', 'admin_cho', 'admin_dswd'] as $role) {
            $admin = AdminUser::create(['username' => 'unapproved-'.$role, 'role' => $role, 'password' => 'synthetic-password']);
            $this->flushSession();
            $this->withSession(['admin_authenticated' => true, 'admin_id' => $admin->id]);
            $this->get(route($admin->dashboardRoute()))->assertOk()->assertDontSee('href="'.route('admin.backups.index').'"', false);
            foreach ($this->endpoints() as [$method, $url]) {
                $this->flushSession();
                $this->withSession(['admin_authenticated' => true, 'admin_id' => $admin->id, 'admin_role' => 'admin']);
                $this->call($method, $url, ['role' => 'admin', 'username' => 'admin', 'approved_accounts' => [$admin->id], 'current_password' => 'synthetic-password'])->assertForbidden();
                $this->travel(61)->seconds();
            }
        }
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_approval_revocation_blocks_a_previously_staged_restore_before_service_execution(): void
    {
        Storage::fake('local');
        $admin = AdminUser::create(['username' => 'revoked-backup-test', 'role' => 'admin', 'password' => 'synthetic-password']);
        config(['backups.approved_accounts' => [['id' => $admin->id, 'username' => $admin->username, 'role' => $admin->role]]]);
        $this->assertTrue($admin->allowsRoute('admin.backups.restore'));
        $this->mock(DatabaseBackup::class, function ($mock) {
            $mock->shouldNotReceive('create');
            $mock->shouldNotReceive('inspect');
            $mock->shouldNotReceive('restore');
        });
        config(['backups.approved_accounts' => []]);
        $this->withSession([
            'admin_authenticated' => true, 'admin_id' => $admin->id,
            'backup_preview' => ['admin_id' => $admin->id, 'token' => str_repeat('a', 64), 'expires' => now()->addMinutes(10)->timestamp],
        ])->post('/admin/backups/restore', [
            'current_password' => 'synthetic-password', 'token' => str_repeat('a', 64),
            'confirmation' => 'RESTORE ALL DATA', 'maintenance_confirmed' => '1',
        ])->assertForbidden();
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_approval_requires_exact_persisted_identity_and_role_not_role_membership(): void
    {
        $admin = AdminUser::create(['username' => 'identity-backup-test', 'role' => 'admin_cho', 'password' => 'synthetic-password']);
        $grant = ['id' => $admin->id, 'username' => $admin->username, 'role' => $admin->role];
        config(['backups.approved_accounts' => [$grant]]);
        $this->assertTrue($admin->canManageDatabaseBackups());
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($admin)->allows('manage-database-backups'));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser(new \App\Models\Mother)->allows('manage-database-backups'));
        foreach (['id' => $admin->id + 1, 'username' => 'another-account', 'role' => 'admin_dswd'] as $field => $value) {
            config(['backups.approved_accounts' => [array_replace($grant, [$field => $value])]]);
            $this->assertFalse($admin->canManageDatabaseBackups());
        }
        config(['backups.approved_accounts' => [$grant]]);
        $admin->update(['role' => 'admin_dswd']);
        $this->withSession(['admin_authenticated' => true, 'admin_id' => $admin->id, 'admin_role' => 'admin_cho'])
            ->get('/admin/backups')->assertForbidden();
        $this->assertTrue($admin->allowsRoute('admin.dswd.dashboard'));
    }

    public function test_ordinary_users_cannot_use_any_backup_endpoint_or_submit_admin_privileges(): void
    {
        Storage::fake('local');
        $this->mock(DatabaseBackup::class, function ($mock) {
            $mock->shouldNotReceive('create');
            $mock->shouldNotReceive('inspect');
            $mock->shouldNotReceive('restore');
        });

        foreach (['mother', 'staff', 'dswd_staff'] as $role) {
            foreach ($this->endpoints() as [$method, $url]) {
                $this->flushSession();
                $this->withSession(['auth_role' => $role, 'auth_id' => 99999, 'admin_role' => 'admin']);
                $this->call($method, $url, [
                    'role' => 'admin', 'admin_authenticated' => true,
                    'admin_id' => 1, 'current_password' => 'synthetic-password',
                ])->assertForbidden();
                $this->travel(61)->seconds();
            }
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_retired_administrator_cannot_override_database_role_with_session_or_request_fields(): void
    {
        Storage::fake('local');
        $admin = AdminUser::create(['username' => 'retired-backup-test', 'role' => 'admin_retired', 'password' => 'synthetic-password']);

        foreach ($this->endpoints() as [$method, $url]) {
            $this->flushSession();
            $this->withSession(['admin_authenticated' => true, 'admin_id' => $admin->id, 'admin_role' => 'admin']);
            $this->call($method, $url, ['role' => 'admin', 'current_password' => 'synthetic-password'])
                ->assertRedirect('/admin/login')->assertSessionMissing('admin_authenticated');
            $this->travel(61)->seconds();
        }

        $this->assertSame('admin_retired', $admin->fresh()->role);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_every_backup_endpoint_still_requires_an_admin_login(): void
    {
        Storage::fake('local');
        $this->mock(DatabaseBackup::class, function ($mock) {
            $mock->shouldNotReceive('create');
            $mock->shouldNotReceive('inspect');
            $mock->shouldNotReceive('restore');
        });

        foreach ($this->endpoints() as [$method, $url]) {
            $this->flushSession();
            $this->call($method, $url)->assertRedirect('/admin/login');
            $this->travel(61)->seconds();
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
    }
}

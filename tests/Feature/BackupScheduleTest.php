<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Support\{BackupSchedule, DatabaseBackup};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache, Storage};
use Tests\TestCase;

class BackupScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-01-31 10:00:00', 'Asia/Manila'));
    }

    public function test_all_configured_admin_roles_can_save_the_shared_schedule_without_password_reentry(): void
    {
        foreach (['admin', 'admin_cho', 'admin_dswd'] as $role) {
            $admin = AdminUser::create(['username' => 'schedule-'.$role, 'role' => $role, 'password' => 'test-password']);
            config(['backups.approved_accounts' => [['id' => $admin->id, 'username' => $admin->username, 'role' => $role]]]);
            $this->flushSession();
            $this->withSession(['admin_authenticated' => true, 'admin_id' => $admin->id]);
            $this->post('/admin/backups/schedule', ['frequency' => 'daily', 'current_password' => 'test-password'])->assertSessionHasErrors('frequency');
            $this->post('/admin/backups/schedule', ['frequency' => 'weekly', 'current_password' => 'test-password'])->assertRedirect('/admin/backups');
            $this->get('/admin/backups')->assertOk()->assertSee('Save Automatic Backup Schedule');
            $this->assertSame('weekly', app(BackupSchedule::class)->status()['frequency']);
            $this->travel(61)->seconds();
        }
    }

    public function test_weekly_backup_runs_once_when_due_and_is_a_valid_recovery_file(): void
    {
        $schedule = app(BackupSchedule::class);
        $this->artisan('backups:run-due')->assertSuccessful();
        $schedule->configure('weekly', 1);
        $this->artisan('backups:run-due')->assertSuccessful();
        $this->assertEmpty(Storage::disk('local')->files('database-backups'));
        $this->travel(8)->days(); // Catch up after downtime.
        $this->artisan('backups:run-due')->assertSuccessful();
        $this->artisan('backups:run-due')->assertSuccessful();
        $files = Storage::disk('local')->files('database-backups');
        $this->assertCount(1, $files);
        $this->assertNotEmpty(app(DatabaseBackup::class)->inspect(Storage::disk('local')->get($files[0]))['metadata']['tables']);
        $this->assertSame(basename($files[0]), $schedule->status()['last_backup']);
        $this->assertTrue(\Carbon\CarbonImmutable::parse($schedule->status()['next_run_at'])->equalTo(now()->addWeek()));
        $schedule->configure('disabled', 1);
        $this->travel(40)->days();
        $this->artisan('backups:run-due')->assertSuccessful();
        $this->assertCount(1, Storage::disk('local')->files('database-backups'));
    }

    public function test_month_end_and_repeated_save_do_not_overflow_or_postpone_backup(): void
    {
        $schedule = app(BackupSchedule::class);
        $schedule->configure('monthly', 1);
        $next = $schedule->status()['next_run_at'];
        $this->assertSame('2026-02-28T10:00:00+08:00', $next);
        $this->travel(1)->days();
        $schedule->configure('monthly', 2);
        $this->assertSame($next, $schedule->status()['next_run_at']);
    }

    public function test_lock_defers_scheduled_backup_and_failure_retries_after_one_hour(): void
    {
        $schedule = app(BackupSchedule::class);
        $schedule->configure('weekly', 1);
        $this->travel(7)->days();
        $lock = Cache::lock('database-backup-operation', 300);
        $lock->get();
        $this->assertSame('busy', $schedule->runDue(app(DatabaseBackup::class)));
        $lock->release();
        $this->mock(DatabaseBackup::class, function ($mock) {
            $mock->shouldReceive('create')->once()->andThrow(new \RuntimeException('Private error details'));
        });
        $this->artisan('backups:run-due')->assertExitCode(1);
        $this->assertNotNull($schedule->status()['last_error_at']);
        $this->artisan('backups:run-due')->assertSuccessful();
        $this->forgetMock(DatabaseBackup::class);
        $this->travel(1)->hours();
        $this->artisan('backups:run-due')->assertSuccessful();
        $this->assertNull($schedule->status()['last_error_at']);
        $this->assertCount(1, Storage::disk('local')->files('database-backups'));
    }
}

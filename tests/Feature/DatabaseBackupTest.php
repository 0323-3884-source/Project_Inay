<?php

namespace Tests\Feature;

use App\Models\{AdminUser, DswdStaff, Infant, Mother, ProgramStaff};
use App\Support\DatabaseBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Cache, DB, Hash, Storage};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'admin'): AdminUser
    {
        $admin = AdminUser::create(['username' => 'backup-'.$role, 'role' => $role, 'password' => 'synthetic-backup-password']);
        config(['backups.approved_accounts' => [...config('backups.approved_accounts', []), ['id' => $admin->id, 'username' => $admin->username, 'role' => $admin->role]]]);
        $this->withSession(['admin_authenticated' => true, 'admin_id' => $admin->id]);
        return $admin;
    }

    private function mother(): Mother
    {
        return Mother::create(['first_name' => "Fictional Nanay ' ; DROP TABLE mothers; --", 'last_name' => 'Synthetic', 'email' => 'backup-mother@example.test', 'password' => 'unused', 'barangay' => "Test\nBarangay", 'contact_number' => '09000000000']);
    }

    public function test_every_admin_role_can_generate_private_signed_sql_without_password_reentry(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $mother = $this->mother();
        foreach (['admin', 'admin_cho', 'admin_dswd'] as $role) {
            $this->admin($role);
            $page = $this->get('/admin/backups')->assertOk()->assertSee('Generate and Download SQL Backup')->assertDontSee('name="current_password"', false);
            if ($role === 'admin' && ($fixture = getenv('INAY_BACKUP_FIXTURE'))) file_put_contents($fixture, $page->getContent());
            $this->post('/admin/backups', [])->assertOk()->assertHeader('Content-Type', 'application/sql');
            $this->flushSession();
            $this->travel(61)->seconds();
        }
        $files = Storage::disk('local')->files('database-backups');
        $this->assertCount(3, $files);
        $contents = Storage::disk('local')->get($files[0]);
        $this->assertStringContainsString('INSERT INTO "mothers"', $contents);
        $this->assertStringNotContainsString('INSERT INTO "sessions"', $contents);
        $this->assertStringNotContainsString('INSERT INTO "password_reset_tokens"', $contents);
        $this->assertNotEmpty(app(DatabaseBackup::class)->inspect($contents)['metadata']['tables']);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertDatabaseHas('mothers', ['id' => $mother->id]);
    }

    public function test_round_trip_restores_all_families_accounts_and_relationships_with_safety_copy(): void
    {
        Storage::fake('local');
        $admin = $this->admin('admin_dswd');
        $mother = $this->mother();
        $child = Infant::create(['mother_id' => $mother->id, 'full_name' => 'Synthetic Child', 'sex' => 'female', 'birth_date' => today()]);
        $staff = ProgramStaff::create(['first_name' => 'Synthetic', 'last_name' => 'Staff', 'email' => 'backup-staff@example.test', 'password' => Hash::make('synthetic-staff-password'), 'staff_id' => 'BACKUP-1', 'position' => 'Program Staff', 'approval_status' => 'approved', 'contact_number' => '09000000000']);
        $staff->casefileMothers()->attach($mother);
        DswdStaff::create(['name' => 'Synthetic Officer', 'email' => 'backup-dswd@example.test', 'password' => 'synthetic-dswd-password']);
        $service = app(DatabaseBackup::class);
        $path = $service->create();
        $contents = Storage::disk('local')->get($path);
        $originalName = $mother->first_name;
        $mother->update(['first_name' => 'Changed after backup']);
        DB::table('infants')->where('id', $child->id)->delete();
        $this->post('/admin/backups/preview', ['backup' => UploadedFile::fake()->createWithContent('records.sql', $contents)])->assertRedirect('/admin/backups');
        $token = session('backup_preview.token');
        $this->get('/admin/backups')->assertOk()->assertSee('Confirm full system recovery');
        $this->post('/admin/backups/restore', ['token' => $token, 'confirmation' => 'WRONG', 'maintenance_confirmed' => '1'])->assertSessionHasErrors('confirmation');
        $this->assertSame('Changed after backup', $mother->fresh()->first_name);
        $this->post('/admin/backups/restore', ['token' => $token, 'confirmation' => 'RESTORE ALL DATA', 'maintenance_confirmed' => '1'])->assertRedirect('/admin/login')->assertSessionMissing('admin_authenticated');
        $this->assertSame($originalName, $mother->fresh()->first_name);
        $this->assertDatabaseHas('infants', ['id' => $child->id, 'mother_id' => $mother->id]);
        $this->assertDatabaseHas('staff_mother_casefiles', ['mother_id' => $mother->id, 'staff_id' => $staff->id]);
        $this->assertDatabaseHas('dswd_staff', ['email' => 'backup-dswd@example.test']);
        $this->assertTrue(Hash::check('synthetic-backup-password', $admin->fresh()->password));
        $this->assertCount(2, Storage::disk('local')->files('database-backups'));
        $this->assertNotSame('initial', Cache::get('database-security-epoch'));
        $this->withSession(['admin_authenticated' => true, 'admin_id' => $admin->id])->get('/admin/backups')->assertRedirect('/login');
        $this->post('/admin/login', ['username' => $admin->username, 'password' => 'synthetic-backup-password'])->assertRedirect('/admin/dswd/dashboard');
        $this->get('/admin/backups')->assertOk();
    }

    public function test_modified_and_arbitrary_sql_are_rejected_before_any_write(): void
    {
        Storage::fake('local');
        $this->admin();
        $mother = $this->mother();
        $service = app(DatabaseBackup::class);
        $contents = Storage::disk('local')->get($service->create());
        foreach (['DROP TABLE mothers;', $contents."DROP TABLE mothers;\n"] as $invalid) {
            $this->post('/admin/backups/preview', ['backup' => UploadedFile::fake()->createWithContent('invalid.sql', $invalid)])->assertSessionHasErrors('backup');
            $this->assertDatabaseHas('mothers', ['id' => $mother->id]);
        }
        $this->assertSame([], Storage::disk('local')->files('database-backup-staging'));
    }

    public function test_non_admin_roles_cannot_backup_or_restore(): void
    {
        $this->get('/admin/backups')->assertRedirect('/admin/login');
        foreach (['mother', 'staff', 'dswd_staff'] as $role) {
            $this->travel(61)->seconds();
            $this->withSession(['auth_role' => $role, 'auth_id' => 99999]);
            $this->get('/admin/backups')->assertForbidden();
            $this->withSession(['auth_role' => $role, 'auth_id' => 99999]);
            $this->post('/admin/backups')->assertForbidden();
            $this->withSession(['auth_role' => $role, 'auth_id' => 99999]);
            $this->post('/admin/backups/restore')->assertForbidden();
        }
    }

    public function test_invalid_schema_and_failed_restore_leave_records_intact(): void
    {
        Storage::fake('local');
        $this->admin();
        $mother = $this->mother();
        $service = app(DatabaseBackup::class);
        $contents = Storage::disk('local')->get($service->create());
        $verified = $service->inspect($contents);
        // Inject a simulated execution failure into the already-verified internal plan.
        $verified['statements'][] = 'INSERT INTO table_that_does_not_exist (id) VALUES (1);';
        try { $service->restore($verified); $this->fail('Expected database error'); } catch (\Illuminate\Database\QueryException) {}
        $this->assertDatabaseHas('mothers', ['id' => $mother->id, 'first_name' => $mother->first_name]);
        DB::statement('CREATE TABLE new_schema_table (id INTEGER PRIMARY KEY)');
        try { $service->inspect($contents); $this->fail('Expected schema rejection'); } catch (ValidationException) {}
        $this->assertDatabaseHas('mothers', ['id' => $mother->id]);
        DB::statement('DROP TABLE new_schema_table');
    }

    public function test_recovery_lock_blocks_other_web_requests_and_restore_requires_a_preview(): void
    {
        $this->admin();
        $this->post('/admin/backups/restore', ['token' => str_repeat('a', 64), 'confirmation' => 'RESTORE ALL DATA', 'maintenance_confirmed' => '1'])->assertForbidden();
        Cache::put('database-recovery-in-progress', true, 60);
        $this->get('/admin/statistics')->assertStatus(503);
        $this->get('/admin/backups')->assertOk();
    }

    public function test_backup_download_works_without_password_reentry_and_rejects_paths_and_changed_keys(): void
    {
        Storage::fake('local');
        $this->admin('admin_cho');
        $service = app(DatabaseBackup::class);
        $path = $service->create();
        $url = '/admin/backups/'.basename($path).'/download';
        $this->post($url, [])->assertOk()->assertHeader('Content-Type', 'application/sql');
        $this->post('/admin/backups/not-a-backup.sql/download', [])->assertNotFound();
        $contents = Storage::disk('local')->get($path);
        config(['app.key' => 'a-different-test-key']);
        $this->expectException(ValidationException::class);
        $service->inspect($contents);
    }
}

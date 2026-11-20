<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Database\Seeders\DepartmentAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RestoreGeneralAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_restores_general_console_without_changing_password_or_departments(): void
    {
        $this->seed(DepartmentAdminSeeder::class);
        $departments = AdminUser::whereIn('role', ['admin_cho', 'admin_dswd'])->get()->map->getRawOriginal()->all();
        $admin = AdminUser::where('username', config('admin.default_username'))->firstOrFail();
        $admin->update(['role' => 'admin_retired', 'password' => Hash::make('synthetic-original-password')]);
        $originalId = $admin->id;
        $originalHash = $admin->password;
        $this->artisan('admin:restore-general')->assertExitCode(0);
        $this->assertSame('admin_retired', $admin->fresh()->role);
        $this->artisan('admin:restore-general', ['--apply' => true])->assertExitCode(0);
        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertSame($originalId, $admin->fresh()->id);
        $this->assertSame($originalHash, $admin->fresh()->password);
        $this->assertSame($departments, AdminUser::whereIn('role', ['admin_cho', 'admin_dswd'])->get()->map->getRawOriginal()->all());
        $this->seed(DepartmentAdminSeeder::class);
        $this->assertSame('admin', $admin->fresh()->role);
        $this->post('/admin/login', ['username' => $admin->username, 'password' => 'synthetic-original-password'])
            ->assertRedirect('/admin/statistics')->assertSessionHas('admin_role', 'admin');
        $this->get('/admin/statistics')->assertOk()->assertSee('Admin Console')->assertSee('Statistics');
        $this->get('/admin/program-staff')->assertOk();
        $this->get('/admin/dswd-staff')->assertOk();
        $this->get('/admin/staff-activity')->assertOk()->assertSee('CHO Program Staff')->assertSee('DSWD Staff');
    }

    public function test_restore_does_not_promote_a_department_account(): void
    {
        $admin = AdminUser::where('username', config('admin.default_username'))->firstOrFail();
        $admin->update(['role' => 'admin_dswd']);
        $this->artisan('admin:restore-general', ['--apply' => true])->assertExitCode(1);
        $this->assertSame('admin_dswd', $admin->fresh()->role);
    }
}

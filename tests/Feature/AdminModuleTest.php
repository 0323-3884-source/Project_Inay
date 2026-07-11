<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\MaternalMonitoringRecord;
use App\Models\Mother;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_is_dedicated_to_admin_credentials(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Admin Login')
            ->assertSee('Username')
            ->assertSee('Password')
            ->assertDontSee('Mother')
            ->assertDontSee('Program Staff');
    }

    public function test_admin_statistics_requires_admin_session(): void
    {
        $this->get('/admin/statistics')
            ->assertRedirect('/admin/login');
    }

    public function test_admin_can_login_and_logout_without_using_mother_staff_session(): void
    {
        $admin = $this->createAdmin('console-admin', 'secret-pass');

        $this->post('/admin/login', [
            'username' => $admin->username,
            'password' => 'secret-pass',
        ])
            ->assertRedirect('/admin/statistics')
            ->assertSessionHas('admin_authenticated', true)
            ->assertSessionHas('admin_id', $admin->id)
            ->assertSessionMissing('auth_role');

        $admin->refresh();
        $this->assertNotNull($admin->last_login_at);

        $this->post('/admin/logout')
            ->assertRedirect('/admin/login')
            ->assertSessionMissing('admin_authenticated')
            ->assertSessionMissing('admin_id');
    }

    public function test_admin_login_rejects_invalid_password_cleanly(): void
    {
        $admin = $this->createAdmin('wrong-password-admin', 'secret-pass');

        $this->from('/admin/login')->post('/admin/login', [
            'username' => $admin->username,
            'password' => 'bad-password',
        ])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('username');
    }

    public function test_statistics_dashboard_uses_database_records(): void
    {
        $highRiskMother = $this->createMother([
            'email' => 'high-risk-admin@example.test',
            'barangay' => 'Barangay San Francisco',
            'pregnancy_status' => 'pregnant',
            'is_4ps_beneficiary' => true,
            'created_at' => now()->startOfMonth(),
        ]);
        $lowRiskMother = $this->createMother([
            'email' => 'low-risk-admin@example.test',
            'barangay' => 'Barangay San Francisco',
            'pregnancy_status' => 'pregnant',
            'is_4ps_beneficiary' => false,
            'created_at' => now()->startOfMonth(),
        ]);
        $secondBarangayMother = $this->createMother([
            'email' => 'second-barangay-admin@example.test',
            'barangay' => 'Barangay Del Remedio',
            'pregnancy_status' => 'pregnant',
            'is_4ps_beneficiary' => false,
            'created_at' => now()->startOfMonth(),
        ]);
        $completedMother = $this->createMother([
            'email' => 'completed-admin@example.test',
            'barangay' => 'Barangay Sta. Elena',
            'pregnancy_status' => 'postpartum',
            'is_4ps_beneficiary' => false,
            'created_at' => now()->subMonth()->startOfMonth(),
        ]);

        MaternalMonitoringRecord::create([
            'mother_id' => $highRiskMother->id,
            'risk_level' => 'high',
            'recorded_at' => now(),
        ]);
        MaternalMonitoringRecord::create([
            'mother_id' => $lowRiskMother->id,
            'risk_level' => 'low',
            'recorded_at' => now(),
        ]);

        $this->withSession([
            'admin_authenticated' => true,
            'admin_id' => 1,
            'admin_username' => 'admin',
        ])
            ->get('/admin/statistics')
            ->assertOk()
            ->assertSee('Statistics Dashboard')
            ->assertSee('Total Mothers')
            ->assertSee('Total 4Ps Beneficiaries')
            ->assertSee('Total Non-4Ps')
            ->assertSee('Barangays Represented')
            ->assertSee('High-Risk Pregnancies')
            ->assertSee('Active Pregnancies')
            ->assertSee('Barangay San Francisco')
            ->assertSee('Barangay Del Remedio')
            ->assertSee('25%')
            ->assertSee('2');

        $this->assertSame('completed-admin@example.test', $completedMother->email);
        $this->assertSame('second-barangay-admin@example.test', $secondBarangayMother->email);
    }

    private function createAdmin(string $username, string $password): AdminUser
    {
        return AdminUser::create([
            'username' => $username,
            'password' => Hash::make($password),
        ]);
    }

    private function createMother(array $overrides): Mother
    {
        $email = $overrides['email'];
        $createdAt = $overrides['created_at'] ?? null;
        unset($overrides['created_at']);

        $mother = Mother::create(array_merge([
            'first_name' => 'Maria',
            'middle_name' => null,
            'last_name' => 'Reyes',
            'email' => $email,
            'password' => Hash::make('password123'),
            'barangay' => 'Barangay I',
            'contact_number' => '09170000000',
            'pregnancy_status' => 'pregnant',
            'is_4ps_beneficiary' => false,
        ], $overrides));

        if ($createdAt) {
            $mother->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();
        }

        return $mother;
    }
}

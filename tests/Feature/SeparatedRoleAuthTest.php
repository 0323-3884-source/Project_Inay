<?php

namespace Tests\Feature;

use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Models\StaffMotherCasefile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeparatedRoleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_email_can_register_for_mother_and_program_staff(): void
    {
        $email = 'same-email@example.test';

        $this->post('/register/mother', [
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'last_name' => 'Reyes',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'barangay' => 'San Pablo',
            'contact_number' => '09170000000',
            'is_4ps_beneficiary' => 'yes',
            'privacy_policy' => '1',
        ])->assertRedirect('/login');

        $this->post('/register/staff', [
            'first_name' => 'Ana',
            'middle_name' => null,
            'last_name' => 'Cruz',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'staff_id' => 'STAFF-001',
            'position' => 'Program Nurse',
            'contact_number' => '09171111111',
            'privacy_policy' => '1',
        ])->assertRedirect('/login');

        $this->assertDatabaseHas('mothers', ['email' => $email]);
        $this->assertDatabaseHas('program_staff', ['email' => $email]);
    }

    public function test_home_page_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_login_uses_the_selected_role_table(): void
    {
        $email = 'role-login@example.test';

        Mother::create([
            'first_name' => 'Lina',
            'middle_name' => null,
            'last_name' => 'Dela Cruz',
            'email' => $email,
            'password' => Hash::make('motherpass123'),
            'barangay' => 'San Pablo',
            'contact_number' => '09172222222',
            'is_4ps_beneficiary' => false,
        ]);

        ProgramStaff::create([
            'first_name' => 'Lina',
            'middle_name' => null,
            'last_name' => 'Dela Cruz',
            'email' => $email,
            'password' => Hash::make('staffpass123'),
            'staff_id' => 'STAFF-002',
            'position' => 'Coordinator',
            'contact_number' => '09173333333',
        ]);

        $this->post('/login', [
            'role' => 'mother',
            'email' => $email,
            'password' => 'motherpass123',
        ])
            ->assertRedirect('/mother/dashboard')
            ->assertSessionHas('auth_role', 'mother');

        $this->post('/logout')->assertRedirect('/login');

        $this->post('/login', [
            'role' => 'staff',
            'email' => $email,
            'password' => 'staffpass123',
        ])
            ->assertRedirect('/staff/dashboard')
            ->assertSessionHas('auth_role', 'staff');
    }

    public function test_staff_shell_does_not_use_mother_profile_photo_when_same_email_casefile_is_open(): void
    {
        $email = 'shared-profile@example.test';

        $mother = Mother::create([
            'first_name' => 'Lina',
            'middle_name' => null,
            'last_name' => 'Reyes',
            'email' => $email,
            'password' => Hash::make('motherpass123'),
            'barangay' => 'San Pablo',
            'contact_number' => '09172222222',
            'is_4ps_beneficiary' => false,
            'profile_photo_path' => 'mother-profile-photos/mother-only.png',
        ]);

        $staff = ProgramStaff::create([
            'first_name' => 'Ana',
            'middle_name' => null,
            'last_name' => 'Cruz',
            'email' => $email,
            'password' => Hash::make('staffpass123'),
            'staff_id' => 'STAFF-SHELL',
            'position' => 'Coordinator',
            'contact_number' => '09173333333',
        ]);

        StaffMotherCasefile::create([
            'staff_id' => $staff->id,
            'mother_id' => $mother->id,
        ]);

        $this->withSession([
            'auth_role' => 'staff',
            'auth_id' => $staff->id,
            'auth_name' => $staff->full_name,
            'auth_email' => $staff->email,
        ])
            ->get(route('staff.mothers.show', $mother))
            ->assertOk()
            ->assertSee($staff->full_name)
            ->assertDontSee('mother-profile-photos/mother-only.png');
    }

    public function test_screenshot_style_registration_payloads_create_records(): void
    {
        $this->post('/register/mother', [
            'full_name' => 'Maria Santos Reyes',
            'email' => 'mother-screenshot@example.test',
            'password' => 'password123',
            'barangay' => 'San Gabriel',
            'contact_number' => '09171234567',
            'age' => 28,
            'blood_type' => 'O+',
            'pregnancy_status' => 'pregnant',
            'is_4ps_beneficiary' => 'yes',
            'location_latitude' => '14.0683000',
            'location_longitude' => '121.3256000',
            'location_accuracy' => 12,
            'privacy_policy' => '1',
        ])->assertRedirect('/login');

        $this->assertDatabaseHas('mothers', [
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'last_name' => 'Reyes',
            'email' => 'mother-screenshot@example.test',
            'age' => 28,
            'blood_type' => 'O+',
            'pregnancy_status' => 'pregnant',
            'is_4ps_beneficiary' => 1,
        ]);

        $this->post('/register/staff', [
            'full_name' => 'Ana Dela Cruz',
            'email' => 'staff-screenshot@example.test',
            'password' => 'password123',
            'privacy_policy' => '1',
        ])->assertRedirect('/login');

        $staff = ProgramStaff::where('email', 'staff-screenshot@example.test')->first();

        $this->assertNotNull($staff);
        $this->assertSame('Ana', $staff->first_name);
        $this->assertSame('Dela', $staff->middle_name);
        $this->assertSame('Cruz', $staff->last_name);
        $this->assertSame('Program Staff', $staff->position);
        $this->assertSame('Not provided', $staff->contact_number);
        $this->assertStringStartsWith('STAFF-', $staff->staff_id);
    }

    public function test_mother_registration_saves_civil_status(): void
    {
        $this->post(route('mother.register.store'), [
            'full_name' => 'Maria Santos Reyes',
            'email' => 'mother-civil-status@example.test',
            'password' => 'password123',
            'barangay' => 'San Gabriel',
            'contact_number' => '09171234567',
            'age' => 35,
            'civil_status' => 'Married',
            'gravidity' => 2,
            'parity' => 1,
            'is_4ps_beneficiary' => 'no',
            'privacy_policy' => '1',
        ])->assertRedirect('/login');

        $this->assertDatabaseHas('mothers', [
            'email' => 'mother-civil-status@example.test',
            'civil_status' => 'Married',
            'gravidity' => null,
            'parity' => null,
        ]);
    }

    public function test_mother_registration_does_not_accept_staff_only_obstetric_fields(): void
    {
        $this->get(route('mother.register'))->assertOk()
            ->assertDontSee('name="gravidity"', false)
            ->assertDontSee('name="parity"', false);
        $this->from(route('mother.register'))
            ->post(route('mother.register.store'), [
                'full_name' => 'Maria Santos Reyes',
                'email' => 'mother-invalid-obstetric@example.test',
                'password' => 'password123',
                'barangay' => 'San Gabriel',
                'contact_number' => '09171234567',
                'gravidity' => 1,
                'parity' => 2,
                'is_4ps_beneficiary' => 'no',
                'privacy_policy' => '1',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('mothers', [
            'email' => 'mother-invalid-obstetric@example.test',
            'gravidity' => null,
            'parity' => null,
        ]);
    }

    public function test_mother_registration_lists_all_san_pablo_barangays_for_search(): void
    {
        $content = $this->get(route('mother.register'))
            ->assertOk()
            ->assertSee('list="san-pablo-barangays"', false)
            ->getContent();

        preg_match('/<datalist id="san-pablo-barangays">(.*?)<\/datalist>/s', $content, $matches);

        $this->assertNotEmpty($matches[1] ?? null);
        $this->assertSame(80, preg_match_all('/<option value="[^"]+"><\/option>/', $matches[1], $options));
        $this->assertStringContainsString('Bagong Bayan II-A', $matches[1]);
        $this->assertStringContainsString('Barangay I-A', $matches[1]);
        $this->assertStringContainsString('Santo Niño', $matches[1]);
    }
}

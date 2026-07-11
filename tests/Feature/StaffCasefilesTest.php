<?php

namespace Tests\Feature;

use App\Models\InayKaalamanUpload;
use App\Models\MaternalMonitoringRecord;
use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Models\StaffMotherCasefile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffCasefilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_assigned_mother_casefiles_and_add_modal(): void
    {
        $staff = $this->createStaff();
        $miguel = $this->createMother('Miguel', 'Ponce', 'Isles', 'miguel@example.test', '09923245009', 'Concepcion');
        $testUser = $this->createMother('Test', null, 'User', 'test-user@example.test', '09170001111', 'San Gabriel');
        $this->assignMother($staff, $miguel);

        $this->withStaffSession($staff)
            ->get('/staff/mothers')
            ->assertOk()
            ->assertSee('Laguna Maternal Patient Register')
            ->assertSee('Add Another Patient')
            ->assertSee($miguel->full_name)
            ->assertSee($testUser->full_name)
            ->assertSee('Already in your list');
    }

    public function test_staff_can_search_assigned_mothers_by_name_phone_email_or_barangay(): void
    {
        $staff = $this->createStaff();
        $miguel = $this->createMother('Miguel', 'Ponce', 'Isles', 'miguel@example.test', '09923245009', 'Concepcion');
        $testUser = $this->createMother('Test', null, 'User', 'test-user@example.test', '09170001111', 'San Gabriel');
        $this->assignMother($staff, $miguel);
        $this->assignMother($staff, $testUser);

        $this->withStaffSession($staff)
            ->get('/staff/mothers?q=Miguel Isles')
            ->assertOk()
            ->assertSee($miguel->full_name);

        $this->withStaffSession($staff)
            ->get('/staff/mothers?q=09170001111')
            ->assertOk()
            ->assertSee($testUser->full_name);

        $this->withStaffSession($staff)
            ->get('/staff/mothers?q=Concepcion')
            ->assertOk()
            ->assertSee($miguel->full_name);
    }

    public function test_staff_can_add_registered_mother_to_casefiles(): void
    {
        $staff = $this->createStaff();
        $mother = $this->createMother('Ana', 'Mendoza', 'Lopez', 'ana@example.test', '09178889999', 'San Marcos');

        $this->withStaffSession($staff)
            ->post('/staff/mothers', [
                'mother_ids' => [$mother->id],
            ])
            ->assertRedirect('/staff/mothers');

        $this->assertDatabaseHas('staff_mother_casefiles', [
            'staff_id' => $staff->id,
            'mother_id' => $mother->id,
        ]);

        $this->withStaffSession($staff)
            ->get('/staff/mothers')
            ->assertOk()
            ->assertSee($mother->full_name);
    }

    public function test_staff_can_open_mother_casefile_detail(): void
    {
        $staff = $this->createStaff();
        $mother = $this->createMother('Maria', 'Santos', 'Reyes', 'maria@example.test', '09175551234', 'San Pablo');
        $this->assignMother($staff, $mother);

        MaternalMonitoringRecord::create([
            'mother_id' => $mother->id,
            'pregnancy_week' => 28,
            'pregnancy_month' => 7,
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'blood_sugar' => 95,
            'weight' => 74,
            'hemoglobin' => 12.5,
            'risk_level' => 'low',
            'recorded_at' => now(),
        ]);

        InayKaalamanUpload::create([
            'mother_id' => $mother->id,
            'month' => 7,
            'record_type' => 'Checkup Records',
            'original_name' => 'checkup.pdf',
            'path' => 'inay-kaalaman-records/checkup.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $this->withStaffSession($staff)
            ->get('/staff/mothers/'.$mother->id)
            ->assertOk()
            ->assertSee('Mother Care Summary')
            ->assertSee($mother->full_name)
            ->assertSee('120/80 mmHg')
            ->assertSee('Low Risk')
            ->assertSee('checkup.pdf');
    }

    public function test_staff_can_create_and_update_maternal_vitals_for_assigned_mother(): void
    {
        $staff = $this->createStaff();
        $mother = $this->createMother('Vitals', null, 'Patient', 'vitals@example.test', '09175551111', 'San Pablo');
        $this->assignMother($staff, $mother);

        $payload = [
            'recorded_at' => '2026-07-10',
            'pregnancy_week' => 32,
            'weight' => 74.5,
            'bp_systolic' => 118,
            'bp_diastolic' => 78,
            'blood_sugar' => 95,
            'hemoglobin' => 12.4,
            'temperature' => 36.8,
            'heart_rate' => 82,
            'notes' => 'Stable maternal vitals.',
        ];

        $this->withStaffSession($staff)
            ->postJson('/api/program-staff/mothers/'.$mother->id.'/maternal-vitals', $payload)
            ->assertCreated()
            ->assertJsonPath('latest.bp_systolic', 118)
            ->assertJsonPath('latest.risk_level', 'low')
            ->assertJsonCount(1, 'weight_history')
            ->assertJsonCount(1, 'blood_pressure_history');

        $record = MaternalMonitoringRecord::first();

        $this->assertNotNull($record);
        $this->assertSame($staff->id, $record->recorded_by_staff_id);
        $this->assertSame($mother->id, $record->mother_id);
        $this->assertNotNull($record->staff_mother_casefile_id);

        $this->withStaffSession($staff)
            ->putJson('/api/program-staff/maternal-vitals/'.$record->id, [
                ...$payload,
                'bp_systolic' => 145,
                'bp_diastolic' => 92,
                'notes' => 'Needs follow-up.',
            ])
            ->assertOk()
            ->assertJsonPath('latest.bp_systolic', 145)
            ->assertJsonPath('risk_status', 'high');

        $this->assertSame(1, MaternalMonitoringRecord::count());
        $this->assertDatabaseHas('maternal_monitoring_records', [
            'id' => $record->id,
            'bp_systolic' => 145,
            'bp_diastolic' => 92,
            'risk_level' => 'high',
        ]);
    }

    public function test_staff_maternal_vitals_validation_returns_readable_errors(): void
    {
        $staff = $this->createStaff();
        $mother = $this->createMother('Invalid', null, 'Vitals', 'invalid-vitals@example.test', '09175552222', 'San Pablo');
        $this->assignMother($staff, $mother);

        $this->withStaffSession($staff)
            ->postJson('/api/program-staff/mothers/'.$mother->id.'/maternal-vitals', [
                'recorded_at' => '2026-07-10',
                'pregnancy_week' => 45,
                'weight' => -1,
                'bp_systolic' => 100,
                'bp_diastolic' => 110,
                'blood_sugar' => 401,
                'hemoglobin' => 3,
                'temperature' => 44,
                'heart_rate' => 190,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'pregnancy_week',
                'weight',
                'bp_diastolic',
                'blood_sugar',
                'hemoglobin',
                'temperature',
                'heart_rate',
            ]);
    }

    public function test_staff_must_add_mother_before_opening_casefile_detail(): void
    {
        $staff = $this->createStaff();
        $mother = $this->createMother('Nina', null, 'Cruz', 'nina@example.test', '09175550000', 'Santa Cruz');

        $this->withStaffSession($staff)
            ->get('/staff/mothers/'.$mother->id)
            ->assertRedirect('/staff/mothers');
    }

    public function test_mother_cannot_access_staff_casefiles(): void
    {
        $mother = $this->createMother('Lina', null, 'Dela Cruz', 'lina@example.test', '09170002222', 'San Jose');

        $this->withSession([
            'auth_role' => 'mother',
            'auth_id' => $mother->id,
            'auth_name' => $mother->full_name,
            'auth_email' => $mother->email,
        ])->get('/staff/mothers')->assertRedirect('/login');
    }

    private function createStaff(): ProgramStaff
    {
        return ProgramStaff::create([
            'first_name' => 'Nurse',
            'middle_name' => null,
            'last_name' => 'Admin',
            'email' => 'casefiles-staff@example.test',
            'password' => Hash::make('password123'),
            'staff_id' => 'STAFF-CASEFILES-001',
            'position' => 'Program Staff',
            'contact_number' => '09170000001',
        ]);
    }

    private function createMother(
        string $firstName,
        ?string $middleName,
        string $lastName,
        string $email,
        string $contactNumber,
        string $barangay,
    ): Mother {
        return Mother::create([
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => Hash::make('password123'),
            'barangay' => $barangay,
            'contact_number' => $contactNumber,
            'age' => 21,
            'blood_type' => 'Unknown',
            'pregnancy_status' => 'pregnant',
            'is_4ps_beneficiary' => false,
        ]);
    }

    private function withStaffSession(ProgramStaff $staff): self
    {
        return $this->withSession([
            'auth_role' => 'staff',
            'auth_id' => $staff->id,
            'auth_name' => $staff->full_name,
            'auth_email' => $staff->email,
        ]);
    }

    private function assignMother(ProgramStaff $staff, Mother $mother): void
    {
        StaffMotherCasefile::create([
            'staff_id' => $staff->id,
            'mother_id' => $mother->id,
        ]);
    }
}

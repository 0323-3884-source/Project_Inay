<?php

namespace Tests\Feature;

use App\Models\InayKaalamanUpload;
use App\Models\Infant;
use App\Models\MaternalMonitoringRecord;
use App\Models\Mother;
use App\Models\ProgramStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MotherPortalFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_mother_can_view_read_only_maternal_monitoring_records(): void
    {
        $mother = $this->createMother('monitoring-mother@example.test');

        MaternalMonitoringRecord::create([
            'mother_id' => $mother->id,
            'pregnancy_week' => 32,
            'pregnancy_month' => 8,
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'blood_sugar' => 95,
            'weight' => 74,
            'hemoglobin' => 12.5,
            'risk_level' => 'low',
            'recorded_at' => now(),
        ]);

        $this->withMotherSession($mother)
            ->get('/maternal-monitoring')
            ->assertOk()
            ->assertSee('Maternal Vitals Overview')
            ->assertSee('120/80')
            ->assertSee('Low Risk')
            ->assertSee('View Only');
    }

    public function test_maternal_monitoring_uses_empty_state_without_saved_records(): void
    {
        $mother = $this->createMother('monitoring-empty@example.test');

        $this->withMotherSession($mother)
            ->get('/maternal-monitoring')
            ->assertOk()
            ->assertSee('Monitoring pending')
            ->assertSee('No maternal monitoring record has been synced yet.')
            ->assertSee('Awaiting Program Staff record');
    }

    public function test_mother_maternal_vitals_endpoint_returns_only_authenticated_mother_records(): void
    {
        $mother = $this->createMother('mother-vitals-api@example.test');
        $otherMother = $this->createMother('other-mother-vitals-api@example.test');

        MaternalMonitoringRecord::create([
            'mother_id' => $mother->id,
            'pregnancy_week' => 24,
            'pregnancy_month' => 6,
            'bp_systolic' => 116,
            'bp_diastolic' => 76,
            'blood_sugar' => 92,
            'weight' => 68,
            'hemoglobin' => 12.1,
            'temperature' => 36.6,
            'heart_rate' => 80,
            'risk_level' => 'low',
            'recorded_at' => '2026-07-10',
        ]);

        MaternalMonitoringRecord::create([
            'mother_id' => $otherMother->id,
            'pregnancy_week' => 30,
            'pregnancy_month' => 8,
            'bp_systolic' => 150,
            'bp_diastolic' => 95,
            'blood_sugar' => 150,
            'weight' => 72,
            'hemoglobin' => 10.4,
            'temperature' => 37.8,
            'heart_rate' => 105,
            'risk_level' => 'high',
            'recorded_at' => '2026-07-10',
        ]);

        $this->withMotherSession($mother)
            ->getJson('/api/mother/maternal-vitals')
            ->assertOk()
            ->assertJsonPath('latest.mother_id', $mother->id)
            ->assertJsonPath('latest.bp_systolic', 116)
            ->assertJsonCount(1, 'records')
            ->assertJsonMissing(['mother_id' => $otherMother->id]);
    }

    public function test_staff_cannot_access_mother_only_monitoring_and_kaalaman_routes(): void
    {
        $staff = ProgramStaff::create([
            'first_name' => 'James',
            'middle_name' => null,
            'last_name' => 'Santos',
            'email' => 'staff-mother-block@example.test',
            'password' => Hash::make('password123'),
            'staff_id' => 'STAFF-BLOCK-001',
            'position' => 'Program Staff',
            'contact_number' => '09170000001',
        ]);

        $session = [
            'auth_role' => 'staff',
            'auth_id' => $staff->id,
            'auth_name' => $staff->full_name,
            'auth_email' => $staff->email,
        ];

        $this->withSession($session)->get('/maternal-monitoring')->assertRedirect('/login');
        $this->withSession($session)->get('/inay-kaalaman')->assertRedirect('/login');
        $this->withSession($session)->get('/inay-kaalaman/videos/1')->assertRedirect('/login');
        $this->withSession($session)->get('/inay-kaalaman/infographics/1/pdf')->assertRedirect('/login');
    }

    public function test_kaalaman_uploads_are_saved_and_update_documentation_status(): void
    {
        Storage::fake('public');

        $mother = $this->createMother('kaalaman-upload@example.test');

        $this->withMotherSession($mother)
            ->post('/inay-kaalaman/uploads', [
                'month' => 1,
                'record_type' => 'Checkup Records',
                'document' => UploadedFile::fake()->create('checkup-record.pdf', 64, 'application/pdf'),
            ])
            ->assertRedirect('/inay-kaalaman');

        $upload = InayKaalamanUpload::first();

        $this->assertNotNull($upload);
        $this->assertSame($mother->id, $upload->mother_id);
        $this->assertSame(1, $upload->month);
        $this->assertSame('Checkup Records', $upload->record_type);
        Storage::disk('public')->assertExists($upload->path);

        $this->withMotherSession($mother)
            ->get('/inay-kaalaman')
            ->assertOk()
            ->assertSee('Uploaded')
            ->assertSee('1 saved upload');
    }

    public function test_mother_can_update_child_profile_and_photo(): void
    {
        Storage::fake('public');

        $mother = $this->createMother('child-profile-update@example.test');
        $infant = Infant::create([
            'mother_id' => $mother->id,
            'full_name' => 'Baby One',
            'sex' => 'female',
            'birth_date' => '2026-07-01',
        ]);

        $this->withMotherSession($mother)
            ->patch('/child-health/children/'.$infant->id, [
                'full_name' => 'Baby Updated',
                'sex' => 'female',
                'birth_date' => '2026-07-01',
                'birth_weight' => 3.1,
                'birth_height' => 49,
                'blood_type' => 'O+',
            ])
            ->assertRedirect('/child-health?child='.$infant->id);

        $this->assertDatabaseHas('infants', [
            'id' => $infant->id,
            'full_name' => 'Baby Updated',
            'blood_type' => 'O+',
        ]);
        $infant->refresh();
        $this->assertNull($infant->birth_weight);
        $this->assertNull($infant->birth_height);

        $this->withMotherSession($mother)
            ->patch('/child-health/children/'.$infant->id.'/photo', [
                'child_photo' => $this->fakePng('baby-profile.png'),
            ])
            ->assertRedirect('/child-health?child='.$infant->id);

        $infant->refresh();
        $this->assertNotNull($infant->photo_path);
        Storage::disk('public')->assertExists($infant->photo_path);
    }

    public function test_mother_can_upload_sidebar_profile_photo(): void
    {
        Storage::fake('public');

        $mother = $this->createMother('mother-profile-photo@example.test');

        $this->withMotherSession($mother)
            ->from('/child-health')
            ->patch('/mother/profile-photo', [
                'profile_photo' => $this->fakePng('mother-profile.png'),
            ])
            ->assertRedirect('/child-health');

        $mother->refresh();
        $this->assertNotNull($mother->profile_photo_path);
        Storage::disk('public')->assertExists($mother->profile_photo_path);
    }

    private function createMother(string $email): Mother
    {
        return Mother::create([
            'first_name' => 'Miguel',
            'middle_name' => 'Ponce',
            'last_name' => 'Isles',
            'email' => $email,
            'password' => Hash::make('password123'),
            'barangay' => 'Concepcion',
            'contact_number' => '09923245009',
            'is_4ps_beneficiary' => false,
        ]);
    }

    private function withMotherSession(Mother $mother): self
    {
        return $this->withSession([
            'auth_role' => 'mother',
            'auth_id' => $mother->id,
            'auth_name' => $mother->full_name,
            'auth_email' => $mother->email,
        ]);
    }

    private function fakePng(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}

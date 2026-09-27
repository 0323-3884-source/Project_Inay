<?php

namespace Tests\Feature;

use App\Models\InayKaalamanUpload;
use App\Models\InayKaalamanProgress;
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
            'blood_sugar_test_type' => 'fasting_plasma_glucose',
            'blood_sugar' => 88,
            'weight' => 74,
            'hemoglobin' => 12.5,
            'screening_summary_status' => 'Within Reference Range',
            'risk_level' => 'Within Reference Range',
            'recorded_at' => now(),
        ]);

        $this->withMotherSession($mother)
            ->get('/maternal-monitoring')
            ->assertOk()
            ->assertSee('Maternal Vitals Overview')
            ->assertSee('120/80')
            ->assertDontSee('Hemoglobin')
            ->assertSee('Within Reference Range')
            ->assertSee('Project INAY provides threshold-based screening alerts for monitoring purposes only.')
            ->assertSee('View Only');

        $this->withMotherSession($mother)
            ->getJson('/api/mother/maternal-vitals')
            ->assertOk()
            ->assertJsonPath('latest.screening_summary_status', 'Within Reference Range')
            ->assertJsonPath('latest.statuses.temperature', 'Logged')
            ->assertJsonPath('latest.statuses.heart_rate', 'Logged');
    }

    public function test_maternal_monitoring_uses_empty_state_without_saved_records(): void
    {
        $mother = $this->createMother('monitoring-empty@example.test');

        $this->withMotherSession($mother)
            ->get('/maternal-monitoring')
            ->assertOk()
            ->assertSee('Monitoring pending')
            ->assertSee('No maternal monitoring record has been synced yet.')
            ->assertSee('No measurement available');
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
            'blood_sugar_test_type' => 'fasting_plasma_glucose',
            'blood_sugar' => 88,
            'weight' => 68,
            'hemoglobin' => 12.1,
            'temperature' => 36.6,
            'heart_rate' => 80,
            'screening_summary_status' => 'Within Reference Range',
            'risk_level' => 'Within Reference Range',
            'recorded_at' => '2026-07-10',
        ]);

        MaternalMonitoringRecord::create([
            'mother_id' => $otherMother->id,
            'pregnancy_week' => 30,
            'pregnancy_month' => 8,
            'bp_systolic' => 150,
            'bp_diastolic' => 95,
            'blood_sugar_test_type' => 'fasting_plasma_glucose',
            'blood_sugar' => 150,
            'weight' => 72,
            'hemoglobin' => 10.4,
            'temperature' => 37.8,
            'heart_rate' => 105,
            'screening_summary_status' => 'For Review',
            'risk_level' => 'For Review',
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

    public function test_kaalaman_embeds_required_trimester_videos_without_checkup_guidance(): void
    {
        $mother = $this->createMother('kaalaman-videos@example.test');

        $this->withMotherSession($mother)
            ->get('/inay-kaalaman')
            ->assertOk()
            ->assertSee('https://www.youtube.com/embed/D_jxGJsEY2A', false)
            ->assertSee('https://www.youtube.com/embed/e4UPKPv7v38', false)
            ->assertSee('https://www.youtube.com/embed/_Ux1wPgEpJo', false)
            ->assertSee('https://www.youtube.com/embed/jgnciIgOFmg', false)
            ->assertSee('https://www.youtube.com/embed/GiRnUn0ApKE', false)
            ->assertSee('https://www.youtube.com/embed/gAkxR_Ept1k', false)
            ->assertSee('https://www.youtube.com/embed/yRoHw6rnntc', false)
            ->assertSee('https://www.youtube.com/embed/ciG1YICJrDA', false)
            ->assertSee('https://www.youtube.com/embed/vtg1w7TUJ3w', false)
            ->assertSee('https://www.youtube.com/embed/_QB0qkJ4zRk', false)
            ->assertSee('https://www.youtube.com/embed/H6mZRds0dHo', false)
            ->assertSee('https://www.youtube.com/embed/IPj4dJnP85o', false)
            ->assertSee('https://www.youtube.com/embed/B9xiKEWc9SM', false)
            ->assertSee('https://www.youtube.com/embed/hmWtKtbIolE', false)
            ->assertSee('https://www.youtube.com/embed/shyWWLkA61I', false)
            ->assertSee('https://www.youtube.com/embed/HTIV2AdFTnc', false)
            ->assertSee('https://www.youtube.com/embed/tycuzmo-s34', false)
            ->assertSee('https://www.youtube.com/embed/wM7I0krDPTg', false)
            ->assertSee('https://www.youtube.com/embed/L-9NcufiDOo', false)
            ->assertSee('https://www.youtube.com/embed/MLZ0lbkKbgM', false)
            ->assertSee('https://www.youtube.com/embed/umgAThOkJgw', false)
            ->assertSee('https://www.youtube.com/embed/QctcxLDsWB0', false)
            ->assertSee('https://www.youtube.com/embed/OmivW51zGvs', false)
            ->assertSee('https://www.youtube.com/embed/VYYgOi9AkHU', false)
            ->assertSee('https://www.youtube.com/embed/orLWetHISck', false)
            ->assertSee('https://www.youtube.com/embed/k71_-M5q_H0', false)
            ->assertSee('https://www.youtube.com/embed/14kbze3sSaY', false)
            ->assertSee('https://www.youtube.com/embed/f2dcTHQXwTI', false)
            ->assertSee('https://www.youtube.com/embed/lpDW00nQhUo', false)
            ->assertSee('https://www.youtube.com/embed/OzY_2-0NvVM', false)
            ->assertSee('https://www.youtube.com/embed/GkcPIcxGy9g', false)
            ->assertSee('https://www.youtube.com/embed/AyEm6K295iE', false)
            ->assertSee('https://www.youtube.com/embed/_pE5PqZgleI', false)
            ->assertSee('https://www.youtube.com/embed/nFnlovPk1ro', false)
            ->assertSee('https://www.youtube.com/embed/wITeiIVieao', false)
            ->assertSee('https://www.youtube.com/embed/9HzZeSX6b2o', false)
            ->assertSee('https://www.youtube.com/embed/OtG-M8pHODY', false)
            ->assertSee('https://www.youtube.com/embed/eFWuydRmFQg', false)
            ->assertSee('https://www.youtube.com/embed/_oB6wbpWCSo', false)
            ->assertSee('https://www.youtube.com/embed/c701W2pzuyo', false)
            ->assertSee('https://www.youtube.com/embed/ww8s7PQWWuY', false)
            ->assertSee('https://www.youtube.com/embed/yO6GYS3PnFY', false)
            ->assertSee('0/2 Watched')
            ->assertSee('0/4 Watched')
            ->assertSee('0/6 Watched')
            ->assertSee('0/7 Watched')
            ->assertDontSee('No required video')
            ->assertDontSee('data-month="1" open', false)
            ->assertSee('data-language-choice="en"', false)
            ->assertSee('data-language-choice="tl"', false)
            ->assertSee('inayInfographicViewer', false)
            ->assertSee('data-infographic-reader', false)
            ->assertSee('data-infographic-end', false)
            ->assertSee('data-key="month-1-infographic"', false)
            ->assertSee('Family Planning')
            ->assertSee('Reproductive Health')
            ->assertSee('Informed choice')
            ->assertDontSee('Labor &amp; Delivery', false)
            ->assertDontSee('Prenatal Checkup Guidance')
            ->assertDontSee('Gabay sa Prenatal Checkup')
            ->assertDontSee('Program Staff records completed prenatal checkups')
            ->assertDontSee('data-checkup-card', false);
    }

    public function test_kaalaman_infographic_pdf_opens_for_mother(): void
    {
        $mother = $this->createMother('kaalaman-infographic-pdf@example.test');

        $this->withMotherSession($mother)
            ->get('/inay-kaalaman/infographics/1/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_kaalaman_video_progress_is_watched_only_after_completion_status(): void
    {
        $mother = $this->createMother('kaalaman-progress@example.test');

        $this->withMotherSession($mother)
            ->postJson('/inay-kaalaman/progress', [
                'month' => 1,
                'activity_type' => 'video',
                'item_key' => 'month-1-required-trimester-video',
                'item_title' => 'First Trimester Prenatal Care Guide',
                'status' => 'in_progress',
            ])
            ->assertOk()
            ->assertJsonPath('progress.status', 'in_progress');

        $progress = InayKaalamanProgress::first();
        $this->assertNotNull($progress);
        $this->assertSame('in_progress', $progress->status);
        $this->assertNull($progress->completed_at);

        $this->withMotherSession($mother)
            ->postJson('/inay-kaalaman/progress', [
                'month' => 1,
                'activity_type' => 'video',
                'item_key' => 'month-1-required-trimester-video',
                'item_title' => 'First Trimester Prenatal Care Guide',
                'status' => 'watched',
            ])
            ->assertOk()
            ->assertJsonPath('progress.status', 'watched');

        $progress->refresh();
        $this->assertSame('watched', $progress->status);
        $this->assertNotNull($progress->completed_at);

        $this->withMotherSession($mother)
            ->postJson('/inay-kaalaman/progress', [
                'month' => 2,
                'activity_type' => 'video',
                'item_key' => 'month-2-first-trimester-video-1',
                'item_title' => 'The Tiny Heart Beats Video 1',
                'status' => 'watched',
            ])
            ->assertOk()
            ->assertJsonPath('month_summary.watched_videos', 1)
            ->assertJsonPath('month_summary.total_videos', 4)
            ->assertJsonPath('month_summary.required_count', 6);
    }

    public function test_mother_cannot_record_prenatal_checkup_from_learning_path(): void
    {
        $mother = $this->createMother('kaalaman-checkup@example.test');

        $this->withMotherSession($mother)
            ->get('/inay-kaalaman')
            ->assertOk()
            ->assertDontSee('Program Staff records completed prenatal checkups')
            ->assertDontSee('Prenatal Checkup Guidance')
            ->assertDontSee('Submit Checkup Record')
            ->assertDontSee('Record Checkup');

        $this->withMotherSession($mother)
            ->post('/inay-kaalaman/checkups', [
                'month' => 4,
                'checkup_date' => now()->subDay()->toDateString(),
            ])
            ->assertNotFound();
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

<?php

namespace Tests\Feature;

use App\Models\InayKaalamanUpload;
use App\Models\MaternalMonitoringRecord;
use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Models\StaffMotherCasefile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffCasefilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_all_registered_mothers_without_manual_add(): void
    {
        $staff = $this->createStaff();
        $miguel = $this->createMother('Miguel', 'Ponce', 'Isles', 'miguel@example.test', '09923245009', 'Concepcion');
        $testUser = $this->createMother('Test', null, 'User', 'test-user@example.test', '09170001111', 'San Gabriel');

        $this->withStaffSession($staff)
            ->get('/staff/mothers')
            ->assertOk()
            ->assertSee('Mother Case File')
            ->assertSee('Registered Mothers')
            ->assertSee($miguel->full_name)
            ->assertSee($testUser->full_name)
            ->assertDontSee('Add Another Patient')
            ->assertDontSee('Already in your list');
    }

    public function test_staff_can_search_registered_mothers_by_name_case_number_phone_or_barangay(): void
    {
        $staff = $this->createStaff();
        $miguel = $this->createMother('Miguel', 'Ponce', 'Isles', 'miguel@example.test', '09923245009', 'Concepcion');
        $testUser = $this->createMother('Test', null, 'User', 'test-user@example.test', '09170001111', 'San Gabriel');

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

        $this->withStaffSession($staff)
            ->get('/staff/mothers?q=INAY-'.str_pad((string) $miguel->id, 5, '0', STR_PAD_LEFT))
            ->assertOk()
            ->assertSee($miguel->full_name)
            ->assertDontSee($testUser->full_name);
    }

    public function test_staff_can_filter_registered_mothers_by_4ps_status(): void
    {
        $staff = $this->createStaff();
        $beneficiary = $this->createMother('Clarissa', null, 'Aquino', 'clarissa@example.test', '09170001112', 'Concepcion');
        $nonBeneficiary = $this->createMother('Angelica', null, 'Bautista', 'angelica@example.test', '09170001113', 'San Rafael');
        $beneficiary->forceFill(['is_4ps_beneficiary' => true])->save();

        $beneficiaryResponse = $this->withStaffSession($staff)
            ->get('/staff/mothers?four_ps=beneficiary')
            ->assertOk();

        $this->assertStringContainsString(route('staff.mothers.show', $beneficiary), $beneficiaryResponse->getContent());
        $this->assertStringNotContainsString(route('staff.mothers.show', $nonBeneficiary), $beneficiaryResponse->getContent());

        $nonBeneficiaryResponse = $this->withStaffSession($staff)
            ->get('/staff/mothers?four_ps=non_4ps')
            ->assertOk();

        $this->assertStringContainsString(route('staff.mothers.show', $nonBeneficiary), $nonBeneficiaryResponse->getContent());
        $this->assertStringNotContainsString(route('staff.mothers.show', $beneficiary), $nonBeneficiaryResponse->getContent());
    }

    public function test_staff_can_filter_mothers_by_pregnancy_category(): void
    {
        $staff = $this->createStaff();
        $patients = [];
        foreach (['pregnant', 'not_pregnant', 'postpartum', 'planning', null] as $index => $status) {
            $mother = $this->createMother('Patient'.$index, null, 'Santos', "pregnancy.$index@example.test", '09170001114', 'Concepcion');
            $mother->forceFill(['pregnancy_status' => $status])->save();
            $patients[] = $mother;
        }

        foreach (['all' => [0, 1, 2, 3, 4], 'pregnant' => [0], 'not_pregnant' => [1], 'postpartum' => [2], 'planning' => [3], 'pending' => [4], 'invalid' => [0, 1, 2, 3, 4]] as $filter => $expected) {
            $response = $this->withStaffSession($staff)->get('/staff/mothers?pregnancy='.$filter)->assertOk();
            foreach ($patients as $index => $mother) {
                if (in_array($index, $expected, true)) {
                    $response->assertSee($mother->email);
                } else {
                    $response->assertDontSee($mother->email);
                }
            }
        }

        $patients[1]->forceFill(['is_4ps_beneficiary' => true])->save();
        $this->withStaffSession($staff)
            ->get('/staff/mothers?pregnancy=not_pregnant&four_ps=beneficiary&q=Patient1')
            ->assertOk()
            ->assertSee($patients[1]->email)
            ->assertDontSee($patients[2]->email)
            ->assertSee('Clear');
    }

    public function test_screening_filter_uses_latest_result_and_supports_legacy_and_missing_results(): void
    {
        $staff = $this->createStaff();
        $mother = $this->createMother('Screening', null, 'Patient', 'screening@example.test', '09170001115', 'Concepcion');
        $unmonitored = $this->createMother('No', null, 'Records', 'unmonitored@example.test', '09170001116', 'Concepcion');
        $record = ['mother_id' => $mother->id, 'pregnancy_week' => 20, 'risk_level' => 'high'];
        MaternalMonitoringRecord::create($record + ['screening_summary_status' => 'Urgent Referral Recommended', 'recorded_at' => now()->subDay()]);
        MaternalMonitoringRecord::create($record + ['recorded_at' => now()]);

        $this->withStaffSession($staff)->get('/staff/mothers?screening=for_review')
            ->assertOk()->assertSee($mother->email)->assertDontSee($unmonitored->email)->assertSee('Clear');
        $this->withStaffSession($staff)->get('/staff/mothers?screening=urgent_referral_recommended')
            ->assertOk()->assertDontSee($mother->email)->assertDontSee($unmonitored->email);
        $this->withStaffSession($staff)->get('/staff/mothers?screening=logged')
            ->assertOk()->assertDontSee($mother->email)->assertSee($unmonitored->email);
        $this->withStaffSession($staff)->get('/staff/mothers?screening=for_review&q=No')
            ->assertOk()->assertDontSee($mother->email)->assertDontSee($unmonitored->email);
        $this->withStaffSession($staff)->get('/staff/mothers?screening=invalid')
            ->assertOk()->assertSee($mother->email)->assertSee($unmonitored->email);
    }

    public function test_staff_can_export_all_filtered_mothers_to_excel_compatible_csv(): void
    {
        $staff = $this->createStaff();
        $expected = [];
        for ($index = 0; $index < 12; $index++) {
            $mother = $this->createMother('Export'.$index, null, 'Santos', "export.$index@example.test", '09170001114', 'Concepcion');
            $mother->forceFill(['pregnancy_status' => 'postpartum', 'is_4ps_beneficiary' => true])->save();
            $expected[] = $mother->email;
        }
        $excluded = $this->createMother('Excluded', null, 'Santos', 'excluded@example.test', '09170001115', 'Concepcion');
        $excluded->forceFill(['first_name' => '=HYPERLINK("example")'])->save();

        $response = $this->withStaffSession($staff)
            ->get('/staff/mothers?export=csv&pregnancy=postpartum&four_ps=beneficiary&q=Export&page=2')
            ->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $lines = preg_split('/\r?\n/', trim(substr($csv, 3)));
        $this->assertCount(13, $lines);
        $this->assertCount(11, str_getcsv($lines[0], ',', '"', ''));
        foreach ($expected as $email) {
            $this->assertStringContainsString($email, $csv);
        }
        $this->assertStringNotContainsString($excluded->email, $csv);
        $this->assertStringContainsString("'09170001114", $csv);
        $this->assertStringContainsString('Postpartum', $csv);

        $allCsv = $this->withStaffSession($staff)->get('/staff/mothers?export=csv')->assertOk()->streamedContent();
        $rows = array_map(fn ($line) => str_getcsv($line, ',', '"', ''), preg_split('/\r?\n/', trim(substr($allCsv, 3))));
        $exported = collect($rows)->first(fn ($row) => ($row[2] ?? '') === $excluded->email);
        $this->assertSame("'".$excluded->full_name, $exported[1]);
    }

    public function test_guests_cannot_export_mother_casefiles(): void
    {
        $this->get('/staff/mothers?export=csv')->assertRedirect(route('login'));
    }

    public function test_staff_dashboard_shows_registered_mothers_count(): void
    {
        $staff = $this->createStaff();
        $this->createMother('Maria', null, 'Santos', 'maria-count@example.test', '09170001114', 'Concepcion');
        $this->createMother('Lorna', null, 'Reyes', 'lorna-count@example.test', '09170001115', 'San Rafael');

        $this->withStaffSession($staff)
            ->get('/staff/dashboard')
            ->assertOk()
            ->assertSee('Registered Mothers')
            ->assertSee('data-dashboard-registered-mothers', false)
            ->assertSee('>2<', false);
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

    public function test_monitoring_desk_scopes_work_lists_to_the_staff_and_assigned_mothers(): void
    {
        $staff = $this->createStaff();
        $otherStaff = $staff->replicate();
        $otherStaff->email = 'other-desk@example.test';
        $otherStaff->staff_id = 'OTHER-DESK';
        $otherStaff->save();
        $assigned = $this->createMother('Assigned', null, 'Mother', 'assigned-desk@example.test', '09171112222', 'Concepcion');
        $outside = $this->createMother('Outside', null, 'Mother', 'outside-desk@example.test', '09171113333', 'Concepcion');
        $this->assignMother($staff, $assigned);
        $this->assignMother($otherStaff, $outside);

        $attributes = [
            'mother_id' => $assigned->id, 'staff_id' => $staff->id,
            'appointment_type' => 'prenatal_checkup', 'meeting_type' => 'in_person',
            'appointment_date' => today(), 'start_time' => '09:00', 'end_time' => '10:00',
            'status' => 'confirmed', 'created_by_id' => $staff->id, 'created_by_role' => 'program_staff',
        ];
        $today = \App\Models\Appointment::create($attributes);
        $next = \App\Models\Appointment::create(array_merge($attributes, ['appointment_date' => today()->addDay()]));
        \App\Models\Appointment::create(array_merge($attributes, ['status' => 'pending']));
        \App\Models\Appointment::create(array_merge($attributes, ['staff_id' => $otherStaff->id]));
        \App\Models\Appointment::create(array_merge($attributes, ['mother_id' => $outside->id, 'status' => 'pending']));
        \App\Models\Appointment::create(array_merge($attributes, ['status' => 'cancelled']));
        MaternalMonitoringRecord::create(['mother_id' => $outside->id, 'pregnancy_week' => 20, 'recorded_at' => now()]);

        $this->withStaffSession($staff)->get('/staff/dashboard')->assertOk()
            ->assertSee('Monitoring Desk')->assertSee($assigned->full_name)->assertDontSee($outside->full_name)
            ->assertViewHas('assignedMotherCount', 1)->assertViewHas('pendingAppointmentCount', 1)
            ->assertViewHas('withoutMonitoringCount', 1)
            ->assertViewHas('todayAppointments', fn ($rows) => $rows->modelKeys() === [$today->id])
            ->assertViewHas('upcomingAppointments', fn ($rows) => $rows->modelKeys() === [$next->id])
            ->assertViewHas('recentMonitoring', fn ($rows) => $rows->isEmpty());

        $record = MaternalMonitoringRecord::create(['mother_id' => $assigned->id, 'pregnancy_week' => 24, 'recorded_at' => now()]);
        $this->withStaffSession($staff)->get('/staff/dashboard')->assertOk()
            ->assertViewHas('withoutMonitoringCount', 0)
            ->assertViewHas('recentMonitoring', fn ($rows) => $rows->modelKeys() === [$record->id]);
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
            'blood_sugar_test_type' => 'fasting_plasma_glucose',
            'blood_sugar' => 88,
            'weight' => 74,
            'hemoglobin' => 12.5,
            'screening_summary_status' => 'Within Reference Range',
            'risk_level' => 'Within Reference Range',
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
            ->assertSee('Weight Progress and Blood Pressure Trends')
            ->assertSee('Weight Progression')
            ->assertSee('data-chart="weight"', false)
            ->assertSee('View Weight History')
            ->assertSee('monitoring-history-icon', false)
            ->assertSee('Blood Pressure Status')
            ->assertSee('120 / 80')
            ->assertSee('Systolic:')
            ->assertSee('Diastolic:')
            ->assertSee('Normal / Stable')
            ->assertSee('View Blood Pressure History')
            ->assertSee('data-bp-status-icon="normal"', false)
            ->assertDontSee('data-chart="bp"', false)
            ->assertSee('120/80 mmHg')
            ->assertDontSee('Hemoglobin')
            ->assertSee('Within Reference Range')
            ->assertSee('checkup.pdf');

        $this->withStaffSession($staff)
            ->getJson('/api/program-staff/mothers/'.$mother->id.'/maternal-vitals')
            ->assertOk()
            ->assertJsonPath('latest.screening_summary_status', 'Within Reference Range')
            ->assertJsonPath('latest.statuses.temperature', 'Logged')
            ->assertJsonPath('latest.statuses.heart_rate', 'Logged');
    }

    public function test_staff_casefile_does_not_expose_prenatal_checkup_recorder(): void
    {
        $staff = $this->createStaff();
        $mother = $this->createMother('Checkup', null, 'Patient', 'checkup-patient@example.test', '09175553333', 'San Pablo');
        $this->assignMother($staff, $mother);

        $this->withStaffSession($staff)
            ->get('/staff/mothers/'.$mother->id)
            ->assertOk()
            ->assertSee('INAY Kaalaman Learning & Documents', false)
            ->assertDontSee('Prenatal Checkup Record')
            ->assertDontSee('Save Staff Checkup Record')
            ->assertDontSee('Update Staff Checkup Record')
            ->assertDontSee('staff-recorded prenatal checkup')
            ->assertDontSee('kaalaman-checkups', false);

        $this->withStaffSession($staff)
            ->post('/staff/mothers/'.$mother->id.'/inay-kaalaman/checkups', [
                'month' => 7,
                'checkup_date' => '2026-07-10',
            ])
            ->assertNotFound();
    }

    public function test_assigned_staff_can_download_mother_prenatal_records_and_receipts(): void
    {
        Storage::fake('public');

        $staff = $this->createStaff();
        $mother = $this->createMother('Download', null, 'Patient', 'download-patient@example.test', '09175556666', 'San Pablo');
        $this->assignMother($staff, $mother);

        Storage::disk('public')->put('inay-kaalaman-records/checkup.pdf', 'PDF content');

        $upload = InayKaalamanUpload::create([
            'mother_id' => $mother->id,
            'month' => 4,
            'record_type' => 'Prenatal Records and Receipts',
            'original_name' => 'prenatal-receipt.pdf',
            'path' => 'inay-kaalaman-records/checkup.pdf',
            'mime_type' => 'application/pdf',
            'size' => 11,
        ]);

        $this->withStaffSession($staff)
            ->get('/staff/mothers/'.$mother->id)
            ->assertOk()
            ->assertSee('Prenatal Records and Receipts History')
            ->assertSee('prenatal-receipt.pdf')
            ->assertSee('Download');

        $this->withStaffSession($staff)
            ->get('/staff/mothers/'.$mother->id.'/inay-kaalaman/uploads/'.$upload->id.'/download')
            ->assertOk()
            ->assertDownload('prenatal-receipt.pdf');
    }

    public function test_document_previews_are_scoped_and_only_render_supported_files(): void
    {
        Storage::fake('public');
        $staff = $this->createStaff();
        $mother = $this->createMother('Preview', null, 'Patient', 'preview@example.test', '09175556666', 'San Pablo');
        $upload = InayKaalamanUpload::create([
            'mother_id' => $mother->id, 'month' => 1, 'record_type' => 'Prenatal Records and Receipts',
            'original_name' => 'record.pdf', 'path' => 'records/record.pdf', 'mime_type' => 'application/pdf', 'size' => 20,
        ]);
        Storage::disk('public')->put($upload->path, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
        $url = route('staff.mothers.kaalaman-uploads.preview', [$mother, $upload]);
        $this->get($url)->assertUnauthorized();
        $this->withStaffSession($staff)->get($url)->assertForbidden();
        $this->assignMother($staff, $mother);
        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $other = $this->createMother('Other', null, 'Patient', 'other-preview@example.test', '09175556667', 'San Pablo');
        $this->assignMother($staff, $other);
        $this->get(route('staff.mothers.kaalaman-uploads.preview', [$other, $upload]))->assertForbidden();
        Storage::disk('public')->put($upload->path, '<html><script>alert(1)</script></html>');
        $this->get($url)->assertStatus(415);
        Storage::disk('public')->put($upload->path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aG1cAAAAASUVORK5CYII='));
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
        Storage::disk('public')->delete($upload->path);
        $this->get($url)->assertNotFound();
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
            'blood_sugar_test_type' => 'fasting_plasma_glucose',
            'blood_sugar' => 88,
            'temperature' => 36.8,
            'heart_rate' => 82,
            'notes' => 'Stable maternal vitals.',
        ];

        $this->withStaffSession($staff)
            ->postJson('/api/program-staff/mothers/'.$mother->id.'/maternal-vitals', $payload)
            ->assertCreated()
            ->assertJsonPath('latest.bp_systolic', 118)
            ->assertJsonPath('latest.risk_level', 'Within Reference Range')
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
            ->assertStatus(409)
            ->assertJsonPath('requires_confirmation', true);

        $this->withStaffSession($staff)
            ->putJson('/api/program-staff/maternal-vitals/'.$record->id, [
                ...$payload,
                'bp_systolic' => 145,
                'bp_diastolic' => 92,
                'confirmed_unusual' => 1,
                'notes' => 'Needs follow-up.',
            ])
            ->assertOk()
            ->assertJsonPath('latest.bp_systolic', 145)
            ->assertJsonPath('risk_status', 'For Review');

        $this->assertSame(1, MaternalMonitoringRecord::count());
        $this->assertDatabaseHas('maternal_monitoring_records', [
            'id' => $record->id,
            'bp_systolic' => 145,
            'bp_diastolic' => 92,
            'bp_status' => 'For Review',
            'risk_level' => 'For Review',
        ]);
    }

    public function test_staff_can_save_maternal_vitals_without_blood_sugar_test_type(): void
    {
        $staff = $this->createStaff();
        $mother = $this->createMother('Vitals', null, 'Patient', 'no-test-type@example.test', '09175551111', 'San Pablo');
        $this->assignMother($staff, $mother);

        $this->withStaffSession($staff)
            ->postJson('/api/program-staff/mothers/'.$mother->id.'/maternal-vitals', [
                'recorded_at' => '2026-07-10',
                'pregnancy_week' => 32,
                'weight' => 74.5,
                'bp_systolic' => 118,
                'bp_diastolic' => 78,
                'blood_sugar' => 88,
                'temperature' => 36.8,
                'heart_rate' => 82,
                'confirmed_unusual' => 1,
            ])->assertCreated();

        $this->assertDatabaseHas('maternal_monitoring_records', [
            'mother_id' => $mother->id,
            'blood_sugar_test_type' => null,
            'blood_sugar' => 88,
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
                'blood_sugar_test_type' => 'invalid_test_type',
                'blood_sugar' => 701,
                'temperature' => 46,
                'heart_rate' => 221,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'pregnancy_week',
                'weight',
                'bp_diastolic',
                'blood_sugar_test_type',
                'blood_sugar',
                'temperature',
                'heart_rate',
            ]);
    }

    public function test_staff_maternal_vitals_normalizes_height_and_calculates_bmi(): void
    {
        $staff = $this->createStaff();
        $mother = $this->createMother('Height', null, 'Patient', 'height-vitals@example.test', '09175552223', 'San Pablo');
        $this->assignMother($staff, $mother);

        $payload = [
            'recorded_at' => '2026-07-10',
            'pregnancy_week' => 20,
            'height_unit' => 'ft_in',
            'height_feet' => 5,
            'height_inches' => 3,
            'weight' => 70,
            'pre_pregnancy_weight' => 60,
            'pre_pregnancy_bmi' => 40,
            'bp_systolic' => 118,
            'bp_diastolic' => 78,
            'blood_sugar_test_type' => 'fasting_plasma_glucose',
            'blood_sugar' => 88,
            'temperature' => 36.8,
            'heart_rate' => 82,
        ];

        $this->withStaffSession($staff)
            ->postJson('/api/program-staff/mothers/'.$mother->id.'/maternal-vitals', $payload)
            ->assertCreated()
            ->assertJsonPath('latest.height_cm', 160.02)
            ->assertJsonPath('weight_history.0.bmi', 27.34)
            ->assertJsonPath('latest.pre_pregnancy_bmi', 23.43);

        $this->assertDatabaseHas('maternal_monitoring_records', [
            'mother_id' => $mother->id,
            'height_cm' => '160.02',
            'pre_pregnancy_weight' => '60.00',
            'pre_pregnancy_bmi' => '23.43',
        ]);
    }

    public function test_staff_maternal_vitals_reuses_existing_height_and_pre_pregnancy_weight(): void
    {
        $staff = $this->createStaff();
        $mother = $this->createMother('Reuse', null, 'Patient', 'reuse-vitals@example.test', '09175552224', 'San Pablo');
        $this->assignMother($staff, $mother);

        $basePayload = [
            'recorded_at' => '2026-07-10',
            'pregnancy_week' => 20,
            'height_unit' => 'cm',
            'height_cm' => 160,
            'weight' => 70,
            'pre_pregnancy_weight' => 60,
            'bp_systolic' => 118,
            'bp_diastolic' => 78,
            'blood_sugar_test_type' => 'fasting_plasma_glucose',
            'blood_sugar' => 88,
            'temperature' => 36.8,
            'heart_rate' => 82,
        ];

        $this->withStaffSession($staff)
            ->postJson('/api/program-staff/mothers/'.$mother->id.'/maternal-vitals', $basePayload)
            ->assertCreated();

        $this->withStaffSession($staff)
            ->postJson('/api/program-staff/mothers/'.$mother->id.'/maternal-vitals', [
                ...$basePayload,
                'recorded_at' => '2026-07-17',
                'pregnancy_week' => 21,
                'height_unit' => 'cm',
                'height_cm' => '',
                'pre_pregnancy_weight' => '',
            ])
            ->assertCreated()
            ->assertJsonPath('latest.height_cm', 160)
            ->assertJsonPath('latest.pre_pregnancy_weight', 60)
            ->assertJsonPath('latest.pre_pregnancy_bmi', 23.44);
    }

    public function test_mother_casefile_lists_only_that_mothers_children(): void
    {
        $staff = $this->createStaff();
        $mother = $this->createMother('Nina', null, 'Cruz', 'children-casefile@example.test', '09175550001', 'Santa Cruz');
        $child = \App\Models\Infant::create(['mother_id' => $mother->id, 'full_name' => 'Baby Nina Cruz', 'sex' => 'female', 'birth_date' => now()->subMonths(3)->toDateString()]);
        $otherMother = $this->createMother('Other', null, 'Mother', 'other-children@example.test', '09175550002', 'Santa Cruz');
        \App\Models\Infant::create(['mother_id' => $otherMother->id, 'full_name' => 'Unrelated Baby', 'sex' => 'male', 'birth_date' => now()->toDateString()]);
        $this->withStaffSession($staff)->get(route('staff.mothers.show', $mother))
            ->assertOk()->assertSee('View Children (1)')->assertSee('Baby Nina Cruz')
            ->assertSee(route('staff.neonatal', ['child' => $child->id]), false)->assertDontSee('Unrelated Baby');
        $this->get(route('staff.neonatal', ['child' => $child->id, 'from' => 'mother']))
            ->assertOk()->assertSee('Back to Mother Case File')
            ->assertSee(route('staff.mothers.show', $mother), false);
        // Returning after an edit keeps the original navigation context.
        $this->get(route('staff.neonatal', ['child' => $child->id]))
            ->assertOk()->assertSee('Back to Mother Case File');
        $this->get(route('staff.neonatal', ['child' => $child->id, 'from' => 'registry']))
            ->assertOk()->assertSee('Back to Child Case Files')->assertDontSee('Back to Mother Case File');
    }

    public function test_staff_can_open_registered_mother_casefile_without_manual_assignment(): void
    {
        $staff = $this->createStaff();
        $mother = $this->createMother('Nina', null, 'Cruz', 'nina@example.test', '09175550000', 'Santa Cruz');

        $this->withStaffSession($staff)
            ->get('/staff/mothers/'.$mother->id)
            ->assertOk()
            ->assertSee($mother->full_name)
            ->assertSee('Weight Progression')
            ->assertSee('No blood pressure record available yet.')
            ->assertSee('Update Vitals')
            ->assertSee('View Blood Pressure History')
            ->assertSee('Height (cm / ft-in)')
            ->assertSee('name="height_unit"', false)
            ->assertSee('name="pre_pregnancy_bmi"', false)
            ->assertSee('readonly', false);

        $this->assertDatabaseHas('staff_mother_casefiles', [
            'staff_id' => $staff->id,
            'mother_id' => $mother->id,
        ]);
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

    public function test_f1kd_summary_tab_is_only_shown_for_4ps_households(): void
    {
        $staff = $this->createStaff();
        $mother = $this->createMother('Ana', null, 'Cruz', 'summary@example.test', '09170002222', 'San Jose');
        $this->withStaffSession($staff)->get('/staff/mothers/'.$mother->id)->assertOk()
            ->assertDontSee('data-f1kd-monitoring-url=', false)->assertDontSee('F1KD 4Ps Beneficiary Summary');
        $mother->update(['is_4ps_beneficiary'=>true]);
        $this->withStaffSession($staff)->get('/staff/mothers/'.$mother->id)->assertOk()
            ->assertSee('data-f1kd-monitoring-url=', false)
            ->assertSee(route('staff.f1kd.edit', ['subject' => 'mother-'.$mother->id, 'month' => now()->format('Y-m')]))
            ->assertSee('data-casefile-panel="f1kd" hidden', false)
            ->assertSee('F1KD 4Ps Beneficiary Summary')
            ->assertViewHas('f1kdBeneficiaries', fn ($rows)=>$rows->count()===1 && $rows->first()->mother_id===$mother->id);
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

<?php

namespace Tests\Feature;

use App\Models\InayKaalamanProgress;
use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Support\MotherCareRecordPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class MotherCareRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_routes_require_staff_identity_approval_and_an_existing_casefile(): void
    {
        $staff = $this->staff();
        $mother = $this->mother();

        foreach (['print', 'pdf'] as $format) {
            $url = route('staff.mothers.'.$format, $mother);
            $this->flushSession();
            $this->getJson($url)->assertUnauthorized();
            $this->withSession(['auth_role' => 'mother', 'auth_id' => $staff->id])->getJson($url)->assertUnauthorized();
            $this->withSession(['auth_role' => 'staff', 'auth_id' => 999999, 'auth_email' => $staff->email])->getJson($url)->assertUnauthorized();
            $this->withSession(['auth_role' => 'staff', 'auth_id' => $staff->id])->getJson($url)->assertForbidden();
        }

        $this->assertDatabaseCount('staff_mother_casefiles', 0);
        $staff->casefileMothers()->attach($mother);
        $staff->update(['approval_status' => 'rejected']);
        $this->getJson(route('staff.mothers.pdf', $mother))->assertForbidden();
        $staff->update(['approval_status' => 'approved']);
        $otherMother = $this->mother('other@example.test');
        $this->getJson(route('staff.mothers.pdf', $otherMother))->assertForbidden();
        $this->getJson('/staff/mothers/999999/pdf')->assertNotFound();
    }

    public function test_print_and_export_isolate_each_section_and_use_current_escaped_database_values(): void
    {
        $this->freezeTime();
        $staff = $this->staff();
        $mother = $this->mother();
        $staff->casefileMothers()->attach($mother);
        $mother->update(['age' => 36, 'civil_status' => 'Married', 'gravidity' => 2, 'parity' => 0, 'blood_type' => 'O+', 'pregnancy_status' => 'pregnant']);
        // Deliberately insert the newest first to verify chronological output.
        $mother->maternalMonitoringRecords()->create([
            'recorded_at' => '2026-08-25', 'pregnancy_week' => 28, 'notes' => 'Newest monitoring entry',
            'height_cm' => 160, 'pre_pregnancy_bmi' => 22.5, 'recorded_by_staff_id' => $staff->id,
            'screening_summary_status' => 'For Review', 'risk_level' => 'For Review',
            'confirmed_unusual_at' => now(), 'confirmed_unusual_by_staff_id' => $staff->id,
            'screening_explanations' => ['blood_pressure' => 'Saved clinical explanation'],
        ]);
        $mother->maternalMonitoringRecords()->create([
            'recorded_at' => '2026-07-10', 'pregnancy_week' => 20, 'pregnancy_month' => 5,
            'bp_systolic' => 110, 'bp_diastolic' => 70, 'blood_sugar' => 88, 'weight' => 65,
            'temperature' => 36.5, 'heart_rate' => 75, 'hemoglobin' => 12,
            'blood_sugar_test_type' => 'fasting_plasma_glucose', 'pre_pregnancy_weight' => 60,
            'weight_change_from_previous' => 0, 'notes' => 'Oldest monitoring entry <script>alert(1)</script>',
        ]);
        $child = $mother->infants()->create(['full_name' => 'Child Alpha', 'sex' => 'female', 'birth_date' => '2025-01-01', 'birth_weight' => 3.2, 'birth_height' => 49, 'notes' => 'Saved neonatal notes']);
        $mother->infants()->create(['full_name' => 'Child Beta', 'sex' => 'male']);
        $child->growthRecords()->create(['measured_at' => '2025-02-01', 'age_months' => 1, 'weight' => 4.1, 'height' => 54, 'head_circumference' => 36, 'remarks' => 'Saved growth remarks']);
        $child->vaccineRecords()->create(['vaccine_name' => 'BCG', 'vaccine_group' => 'At birth', 'dose_label' => 'Single dose', 'due_date' => '2025-01-01', 'administered_at' => '2025-01-02', 'status' => 'completed', 'lot_number' => 'LOT-SAVED', 'vaccinator' => 'Saved Vaccinator', 'remarks' => 'Saved vaccination remarks']);
        $child->healthAlerts()->create(['alert_type' => 'follow_up_needed', 'title' => 'Saved child alert', 'notes' => 'Saved alert notes']);
        $mother->inayKaalamanCheckups()->create(['month' => 1, 'checkup_date' => '2026-07-10', 'healthcare_worker_name' => 'Saved Worker', 'facility_name' => 'Saved Clinic', 'notes' => 'Saved prenatal notes']);
        InayKaalamanProgress::create(['mother_id' => $mother->id, 'month' => 1, 'activity_type' => 'reading', 'item_key' => 'month-1-reading', 'status' => 'read', 'completed_at' => now()]);
        $otherMother = $this->mother('unrelated@example.test');
        $otherMother->maternalMonitoringRecords()->create(['notes' => 'PRIVATE OTHER MOTHER']);
        $otherMother->infants()->create(['full_name' => 'PRIVATE OTHER CHILD']);

        $documents = [];
        $this->mock(MotherCareRecordPdf::class, function (MockInterface $mock) use (&$documents): void {
            $mock->shouldReceive('render')->times(10)->andReturnUsing(function ($html) use (&$documents) {
                $documents[] = $html;

                return '%PDF-1.4 test';
            });
        });
        $this->withSession(['auth_role' => 'staff', 'auth_id' => $staff->id, 'auth_name' => 'Stale session name']);
        foreach (['overview', 'monitoring', 'learning-documents', 'notes'] as $section) {
            foreach (['print' => 'inline', 'pdf' => 'attachment'] as $format => $disposition) {
                $response = $this->get(route('staff.mothers.'.$format, [$mother, 'section' => $section]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
                $this->assertStringStartsWith($disposition.';', $response->headers->get('Content-Disposition'));
                $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            }
            $this->assertSame($documents[count($documents) - 2], $documents[count($documents) - 1]);
        }
        $this->assertStringContainsString('3rd Trimester', $documents[0]);
        $this->assertStringContainsString('160.00', $documents[0]);
        $this->assertStringNotContainsString('MONITORING HISTORY', $documents[0]);
        $this->assertStringNotContainsString('Oldest monitoring entry', $documents[0]);
        $this->assertStringContainsString('Saved clinical explanation', $documents[2]);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $documents[2]);
        $this->assertLessThan(strpos($documents[2], '25 Aug 2026'), strpos($documents[2], '10 Jul 2026'));
        $this->assertStringContainsString('Saved prenatal notes', $documents[4]);
        $this->assertStringContainsString('Completed learning activities: 1 /', $documents[4]);
        $this->assertStringNotContainsString('MONITORING HISTORY', $documents[4]);
        $this->assertStringContainsString('Newest monitoring entry', $documents[6]);
        $this->assertStringNotContainsString('Oldest monitoring entry', $documents[6]);
        $this->assertStringNotContainsString('MONITORING HISTORY', $documents[6]);
        foreach ($documents as $html) {
            foreach (['PROJECT INAY', 'San Jose', 'fo4a@dswd.gov.ph', 'data:image/jpeg;base64,'] as $expected) {
                $this->assertStringContainsString($expected, $html);
            }
            foreach (['Child Alpha', 'Child Beta', 'Saved neonatal notes', 'VACCINATION RECORDS', 'PRIVATE OTHER MOTHER', 'PRIVATE OTHER CHILD', 'Stale session name', '<script>', 'Edit Information', 'portal-sidebar'] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, $html);
            }
        }
        foreach (['print', 'pdf'] as $format) {
            $this->get(route('staff.neonatal.'.$format, $child))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        }
        $this->assertSame($documents[8], $documents[9]);
        foreach (['Child Alpha', 'Saved neonatal notes', 'Saved growth remarks', '36.00', 'BCG', 'LOT-SAVED', 'Saved Vaccinator', 'Saved vaccination remarks', 'Saved child alert', '4.10 kg', '54.00 cm', '01 Feb 2025', 'PROJECT INAY'] as $expected) {
            $this->assertStringContainsString($expected, $documents[8]);
        }
        foreach (['Child Beta', 'MONITORING HISTORY', 'CLINICAL NOTES', 'LEARNING &amp; DOCUMENTS', 'Newest monitoring entry', 'PRIVATE OTHER CHILD'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $documents[8]);
        }
    }

    public function test_real_pdf_handles_missing_optional_records_and_long_history_on_a4_pages(): void
    {
        $staff = $this->staff();
        $mother = $this->mother();
        $staff->casefileMothers()->attach($mother);
        $this->withSession(['auth_role' => 'staff', 'auth_id' => $staff->id]);
        $empty = $this->get(route('staff.mothers.pdf', $mother))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $empty->getContent());

        for ($i = 0; $i < 45; $i++) {
            $mother->maternalMonitoringRecords()->create([
                'recorded_at' => now()->subDays(45 - $i), 'pregnancy_week' => 20,
                'weight' => 65, 'bp_systolic' => 110, 'bp_diastolic' => 70,
                'notes' => 'History entry '.$i.'. '.($i === 44 ? str_repeat('Long saved monitoring note. ', 180) : 'Saved monitoring notes.'),
            ]);
        }
        $response = $this->get(route('staff.mothers.pdf', [$mother, 'section' => 'monitoring']))->assertOk();
        $pdf = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertMatchesRegularExpression('/\/MediaBox\s*\[0\.000 0\.000 595\.\d+ 841\.\d+\]/', $pdf);
        $this->assertGreaterThan(2, preg_match_all('/\/Type\s*\/Page\b/', $pdf));
        $this->assertStringContainsString('/Subtype /Image', $pdf);
        // Optional local artifacts for visual review; all content is synthetic test data.
        if (getenv('INAY_RECORD_REVIEW') === '1') {
            $directory = storage_path('framework/testing/mother-record-review');
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            file_put_contents($directory.'/empty.pdf', $empty->getContent());
            file_put_contents($directory.'/history.pdf', $pdf);
        }
    }

    public function test_generation_failures_return_a_clean_message_without_exception_details(): void
    {
        $staff = $this->staff();
        $mother = $this->mother();
        $staff->casefileMothers()->attach($mother);
        $this->mock(MotherCareRecordPdf::class, function (MockInterface $mock): void {
            $mock->shouldReceive('render')->once()->andThrow(new \RuntimeException('PRIVATE exception details'));
        });
        $this->withSession(['auth_role' => 'staff', 'auth_id' => $staff->id])
            ->getJson(route('staff.mothers.pdf', $mother))
            ->assertStatus(500)
            ->assertExactJson(['message' => 'Unable to generate the mother record. Please try again.']);
    }

    public function test_invalid_sections_are_rejected_and_child_access_is_scoped_to_assigned_casefiles(): void
    {
        $staff = $this->staff();
        $mother = $this->mother();
        $child = $mother->infants()->create(['full_name' => 'Assigned child', 'sex' => 'female']);
        foreach (['print', 'pdf'] as $format) {
            $this->flushSession();
            $this->getJson(route('staff.neonatal.'.$format, $child))->assertUnauthorized();
            $this->withSession(['auth_role' => 'mother', 'auth_id' => $mother->id])->getJson(route('staff.neonatal.'.$format, $child))->assertUnauthorized();
            $this->withSession(['auth_role' => 'staff', 'auth_id' => $staff->id])->getJson(route('staff.neonatal.'.$format, $child))->assertForbidden();
        }
        $staff->casefileMothers()->attach($mother);
        $this->getJson(route('staff.mothers.pdf', [$mother, 'section' => 'all']))->assertUnprocessable();
        $this->getJson(route('staff.mothers.print', [$mother, 'section' => ['notes']]))->assertUnprocessable();
        $staff->update(['approval_status' => 'rejected']);
        $this->getJson(route('staff.neonatal.pdf', $child))->assertForbidden();
        $staff->update(['approval_status' => 'approved']);
        $pdf = $this->get(route('staff.neonatal.pdf', $child))->assertOk()->getContent();
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertMatchesRegularExpression('/\/MediaBox\s*\[0\.000 0\.000 595\.\d+ 841\.\d+\]/', $pdf);
        $this->getJson('/staff/neonatal/999999/pdf')->assertNotFound();
    }

    private function staff(): ProgramStaff
    {
        return ProgramStaff::create(['first_name' => 'Isabel', 'last_name' => 'Peña', 'email' => 'record-staff@example.test', 'password' => 'unused', 'staff_id' => 'STAFF-RECORD', 'position' => 'Program Staff', 'role' => 'Midwife', 'contact_number' => '09170000000', 'approval_status' => 'approved']);
    }

    private function mother(string $email = 'record-mother@example.test'): Mother
    {
        return Mother::create(['first_name' => 'María', 'last_name' => 'Santos', 'email' => $email, 'password' => 'unused', 'barangay' => 'San Jose', 'contact_number' => '09171111111']);
    }
}

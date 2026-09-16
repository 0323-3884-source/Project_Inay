<?php

namespace Tests\Feature;

use App\Models\Infant;
use App\Models\InfantGrowthRecord;
use App\Models\InfantVaccineRecord;
use App\Models\MaternalMonitoringRecord;
use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Models\StaffMotherCasefile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffDynamicReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_dynamic_reports_use_casefile_and_neonatal_records(): void
    {
        $staff = ProgramStaff::create([
            'first_name' => 'Miguel',
            'last_name' => 'Ponce Isles',
            'email' => 'staff-dynamic-report@example.test',
            'password' => Hash::make('password123'),
            'staff_id' => 'STAFF-REPORTS',
            'position' => 'Program Staff',
            'contact_number' => '09990001111',
        ]);

        $mother = Mother::create([
            'first_name' => 'Maria',
            'last_name' => 'Reyes',
            'email' => 'mother-dynamic-report@example.test',
            'password' => Hash::make('password123'),
            'barangay' => 'Concepcion',
            'contact_number' => '09923245009',
            'age' => 27,
            'blood_type' => 'O+',
            'pregnancy_status' => 'pregnant',
            'is_4ps_beneficiary' => true,
        ]);

        $casefile = StaffMotherCasefile::create([
            'staff_id' => $staff->id,
            'mother_id' => $mother->id,
        ]);

        MaternalMonitoringRecord::create([
            'mother_id' => $mother->id,
            'staff_mother_casefile_id' => $casefile->id,
            'recorded_by_staff_id' => $staff->id,
            'pregnancy_week' => 28,
            'pregnancy_month' => 7,
            'bp_systolic' => 150,
            'bp_diastolic' => 95,
            'blood_sugar_test_type' => 'fasting_plasma_glucose',
            'blood_sugar' => 150,
            'temperature' => 37.2,
            'heart_rate' => 92,
            'screening_summary_status' => 'For Review',
            'risk_level' => 'For Review',
            'recorded_at' => '2026-07-28 09:00:00',
        ]);

        $infant = Infant::create([
            'mother_id' => $mother->id,
            'full_name' => 'Baby Reyes',
            'sex' => 'female',
            'birth_date' => '2026-07-01',
            'birth_weight' => 3.2,
            'birth_height' => 49,
            'facility' => 'RHU - Concepcion',
        ]);

        InfantGrowthRecord::create([
            'infant_id' => $infant->id,
            'recorded_by_staff_id' => $staff->id,
            'measured_at' => '2026-07-14',
            'age_months' => 1,
            'weight' => 4.2,
            'height' => 51.5,
            'head_circumference' => 34.2,
            'temperature' => 36.8,
        ]);

        InfantVaccineRecord::create([
            'infant_id' => $infant->id,
            'recorded_by_staff_id' => $staff->id,
            'vaccine_group' => 'Birth Vaccines',
            'vaccine_name' => 'BCG',
            'dose_label' => 'Birth dose',
            'due_date' => '2026-07-01',
            'status' => 'completed',
            'administered_at' => '2026-07-01',
            'facility' => 'RHU - Concepcion',
        ]);

        $session = [
            'auth_role' => 'staff',
            'auth_id' => $staff->id,
            'auth_name' => $staff->full_name,
            'auth_email' => $staff->email,
        ];

        $this->withSession($session)
            ->get(route('staff.dynamic-reports'))
            ->assertOk()
            ->assertSee('Maternal Screening Registry', false)
            ->assertSee('Maria Reyes')
            ->assertSee('150/95 mmHg')
            ->assertSee('Fasting plasma glucose')
            ->assertSee('For Review')
            ->assertDontSee('id="newborn-clinical-report"', false);

        $sibling = $mother->infants()->create(['full_name' => 'Unselected Sibling', 'sex' => 'male']);
        $response = $this->withSession($session)
            ->get(route('staff.dynamic-reports', ['tab' => 'newborn', 'newborn' => $infant->id]))
            ->assertOk()
            ->assertSee('Newborn Monitoring Logs', false)
            ->assertSee('Baby Reyes')
            ->assertSee('Newborn Growth Monitoring Logs')
            ->assertSee('BCG');

        preg_match('/<template id="newborn-clinical-report">(.*?)<\/template>/s', $response->getContent(), $match);
        $report = preg_replace('/data:image\/jpeg;base64,[^"]+/', 'branding-image', $match[1] ?? '');
        foreach (['PROJECT INAY', 'NEWBORN / NEONATAL CLINICAL MONITORING REPORT', 'Baby Reyes', 'Maria Reyes', 'Miguel Ponce Isles', 'NBN-RH-', 'Female', 'RHU - Concepcion', '4.2 kg', '51.5 cm', '34.2 cm', '14 Jul 2026', 'Longitudinal Weight Growth', 'viewBox="0 0 600 230"', 'Newborn Growth Monitoring Logs', 'Newborn Vaccine Monitoring Logs', 'BCG', 'Birth dose', 'Completed'] as $expected) {
            $this->assertStringContainsString($expected, $report);
        }
        foreach (['Regional Clinical Surveillance Reports Desk', 'Maternal Screening Registry', '150/95', 'Infant Registry Lookup', 'Unselected Sibling', 'Open Neonatal', '<button', '<input', '<nav', 'Logout'] as $excluded) {
            $this->assertStringNotContainsString($excluded, $report);
        }
        $this->assertStringContainsString('size: A4 portrait', $report);
        $this->assertStringContainsString('table-header-group', $report);

        $otherResponse = $this->withSession($session)
            ->get(route('staff.dynamic-reports', ['tab' => 'newborn', 'newborn' => $sibling->id]))
            ->assertOk();
        preg_match('/<template id="newborn-clinical-report">(.*?)<\/template>/s', $otherResponse->getContent(), $otherMatch);
        $otherReport = preg_replace('/data:image\/jpeg;base64,[^"]+/', 'branding-image', $otherMatch[1] ?? '');
        $this->assertStringContainsString('Unselected Sibling', $otherReport);
        $this->assertStringContainsString('No weight records available', $otherReport);
        $this->assertStringContainsString('No neonatal growth monitoring logs', $otherReport);
        $this->assertStringContainsString('No vaccine monitoring logs', $otherReport);
        $this->assertStringNotContainsString('Baby Reyes', $otherReport);
        $this->assertStringNotContainsString('BCG', $otherReport);

        $privateMother = Mother::create([
            'first_name' => 'Private', 'last_name' => 'Patient', 'email' => 'private-stats@example.test',
            'password' => 'unused', 'contact_number' => '09170000000', 'barangay' => 'Private Barangay', 'pregnancy_status' => 'pregnant', 'is_4ps_beneficiary' => true,
        ]);
        $privateInfant = $privateMother->infants()->create(['full_name' => 'Private child', 'sex' => 'male']);
        $dose = ['vaccine_group' => 'Test group', 'dose_label' => 'Dose 1'];
        $privateInfant->vaccineRecords()->create($dose + ['vaccine_name' => 'Private vaccine', 'status' => 'completed']);
        $infant->vaccineRecords()->create($dose + ['vaccine_name' => 'Cancelled dose', 'status' => 'cancelled', 'due_date' => now()->subDays(10)]);
        $infant->vaccineRecords()->create($dose + ['vaccine_name' => 'Past due', 'status' => 'upcoming', 'due_date' => now()->subDays(2)]);
        $infant->vaccineRecords()->create($dose + ['vaccine_name' => 'Future dose', 'status' => 'upcoming', 'due_date' => now()->addDays(10)]);
        $statsResponse = $this->withSession($session)
            ->get(route('staff.dynamic-reports', ['tab' => 'statistics', 'maternal_q' => 'Private', 'newborn' => $privateInfant->id]))
            ->assertOk()->assertViewHas('activeTab', 'statistics')
            ->assertDontSee('Private Barangay')->assertDontSee('Private Patient');
        $stats = $statsResponse->viewData('statistics');
        $this->assertSame(1, $stats['summary']['Total Assigned Mothers']);
        $this->assertSame(1, $stats['summary']['Total 4Ps Beneficiaries']);
        $this->assertSame(1, $stats['summary']['Active Pregnancies']);
        $this->assertSame(1, $stats['summary']['For Review Screenings']);
        $this->assertSame(2, $stats['summary']['Linked Newborns']);
        $this->assertSame(1, $stats['summary']['Completed Vaccine Doses']);
        $this->assertSame(['Concepcion' => 1], $stats['sections'][2]['rows']);
        $this->assertSame(['Completed' => 1, 'Pending / upcoming' => 1, 'Overdue / missed' => 1, 'Cancelled' => 1], $stats['sections'][5]['rows']);
        preg_match('/<template id="statistics-clinical-report">(.*?)<\/template>/s', $statsResponse->getContent(), $statsMatch);
        $statsPrint = preg_replace('/data:image\/jpeg;base64,[^"]+/', 'branding-image', $statsMatch[1] ?? '');
        $this->assertStringContainsString('PROGRAM STAFF CLINICAL STATISTICS REPORT', $statsPrint);
        $this->assertStringContainsString('4Ps Distribution', $statsPrint);
        $this->assertStringContainsString('Vaccine Statistics', $statsPrint);
        foreach (['Maternal Screening Registry', 'Newborn Growth Monitoring Logs', 'Regional Clinical Surveillance', 'Pending Staff Approval', 'Verified Staff IDs', '<button', '<nav'] as $excluded) {
            $this->assertStringNotContainsString($excluded, $statsPrint);
        }
        $staff->casefileMothers()->detach();
        $empty = $this->withSession($session)->get(route('staff.dynamic-reports', ['tab' => 'statistics']))->assertOk();
        $this->assertSame(0, $empty->viewData('statistics')['summary']['Total Assigned Mothers']);
        $this->assertSame(0, $empty->viewData('statistics')['fourPsPercentage']);
        $this->flushSession();
        $this->get(route('staff.dynamic-reports', ['tab' => 'statistics']))->assertRedirect(route('login'));
    }
}

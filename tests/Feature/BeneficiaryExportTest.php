<?php

namespace Tests\Feature;

use App\Models\Infant;
use App\Models\Mother;
use App\Models\ProgramStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeneficiaryExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_requires_staff_session(): void
    {
        $this->get('/staff/mothers?export=all_csv')->assertRedirect(route('login'));
    }

    public function test_export_includes_all_mothers_children_growth_and_vaccines(): void
    {
        $staff = ProgramStaff::create(['first_name' => 'Test', 'last_name' => 'Staff', 'email' => 'staff@example.test', 'password' => 'secret', 'contact_number' => '09990001111', 'staff_id' => 'EXPORT-1', 'position' => 'Program Staff']);
        $mother = Mother::create(['first_name' => '=FORMULA', 'last_name' => 'Export', 'email' => 'export@example.test', 'password' => 'never-export-this', 'barangay' => 'Test', 'contact_number' => '09123456789']);
        $child = Infant::create(['mother_id' => $mother->id, 'full_name' => 'Export Child', 'sex' => 'female', 'birth_date' => now()->subMonths(6)]);
        $child->growthRecords()->create(['measured_at' => now(), 'weight' => 7.5, 'height' => 65]);
        $child->vaccineRecords()->create(['vaccine_group' => 'BCG', 'vaccine_name' => 'BCG Export', 'dose_label' => 'Birth dose', 'status' => 'completed', 'due_date' => now(), 'administered_at' => now()]);
        $response = $this->withSession(['auth_role' => 'staff', 'auth_id' => $staff->id])
            ->get('/staff/mothers?export=all_csv&q=does-not-match');
        $response->assertOk()->assertDownload();
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString("'=FORMULA", $csv);
        $this->assertStringContainsString("'09123456789", $csv);
        $this->assertStringContainsString('Export Child', $csv);
        $this->assertStringContainsString('BCG Export', $csv);
        $this->assertStringContainsString('7.50', $csv);
        $this->assertStringNotContainsString('never-export-this', $csv);
        $rows = array_map(fn ($line) => str_getcsv($line, ',', '"', ''), explode("\n", trim(substr($csv, 3))));
        $this->assertCount(5, $rows);
        foreach ($rows as $row) {
            $this->assertCount(count($rows[0]), $row);
        }
    }
}


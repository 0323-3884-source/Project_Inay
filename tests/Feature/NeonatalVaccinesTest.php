<?php

namespace Tests\Feature;

use App\Models\Infant;
use App\Models\InfantGrowthRecord;
use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Models\StaffMotherCasefile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NeonatalVaccinesTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_open_neonatal_vaccines_page_for_assigned_mother(): void
    {
        $staff = ProgramStaff::create([
            'first_name' => 'Miguel',
            'last_name' => 'Ponce Isles',
            'email' => 'staff-neonatal@example.test',
            'password' => Hash::make('password123'),
            'staff_id' => 'STAFF-NEONATAL',
            'position' => 'Program Staff',
            'contact_number' => '09990001111',
        ]);

        $mother = Mother::create([
            'first_name' => 'Miguel',
            'last_name' => 'Ponce Isles',
            'email' => 'mother-neonatal@example.test',
            'password' => Hash::make('password123'),
            'barangay' => 'Concepcion',
            'contact_number' => '09923245009',
            'age' => 21,
            'blood_type' => 'Unknown',
            'pregnancy_status' => 'not_pregnant',
            'is_4ps_beneficiary' => false,
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
            ->get('/staff/neonatal-vaccines')
            ->assertOk()
            ->assertSee('Neonatal &amp; Vaccines', false)
            ->assertDontSee('Neonatal &amp;amp; Vaccines', false)
            ->assertSee('Neonatal & Vaccine Monitoring', false)
            ->assertSee("Mother's Child", false)
            ->assertDontSee('Linked Child Case Records', false)
            ->assertDontSee('data-neo-open="add-child"', false)
            ->assertDontSee('data-neo-modal="add-child"', false)
            ->assertDontSee('Add Child Case Record')
            ->assertSee($mother->full_name)
            ->assertDontSee('4Ps Beneficiary / F1KD Monitoring');

        $mother->update(['is_4ps_beneficiary' => true]);
        $child = Infant::create([
            'mother_id' => $mother->id,
            'full_name' => 'Baby INAY',
            'sex' => 'female',
            'birth_date' => now()->subMonths(2)->toDateString(),
        ]);
        $this->get('/staff/neonatal-vaccines?child='.$child->id)
            ->assertOk()
            ->assertSee('4Ps Beneficiary / F1KD Monitoring')
            ->assertSee(route('staff.f1kd.edit', ['subject' => 'child-'.$child->id, 'month' => now()->format('Y-m')]));

        $child->update(['birth_date' => now()->subYears(3)->toDateString()]);
        $this->get('/staff/neonatal-vaccines?child='.$child->id)
            ->assertOk()
            ->assertDontSee('4Ps Beneficiary / F1KD Monitoring');
    }

    public function test_staff_selects_mother_then_switches_between_that_mothers_children(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-09-26'));
        $staff = ProgramStaff::create([
            'first_name' => 'Miguel',
            'last_name' => 'Ponce Isles',
            'email' => 'staff-neonatal-mother-select@example.test',
            'password' => Hash::make('password123'),
            'staff_id' => 'STAFF-NEONATAL-MOTHER',
            'position' => 'Program Staff',
            'contact_number' => '09990001111',
        ]);

        $mother = Mother::create([
            'first_name' => 'Maria',
            'last_name' => 'Reyes',
            'email' => 'mother-neonatal-mother-select@example.test',
            'password' => Hash::make('password123'),
            'barangay' => 'Concepcion',
            'contact_number' => '09923245009',
            'age' => 25,
            'blood_type' => 'Unknown',
            'pregnancy_status' => 'not_pregnant',
            'is_4ps_beneficiary' => false,
        ]);

        StaffMotherCasefile::create([
            'staff_id' => $staff->id,
            'mother_id' => $mother->id,
        ]);

        $olderChild = Infant::create([
            'mother_id' => $mother->id,
            'full_name' => 'Baby Alpha',
            'sex' => 'female',
            'birth_date' => '2026-06-01',
        ]);

        $youngerChild = Infant::create([
            'mother_id' => $mother->id,
            'full_name' => 'Baby Beta',
            'sex' => 'male',
            'birth_date' => '2026-07-01',
        ]);

        InfantGrowthRecord::create([
            'infant_id' => $youngerChild->id,
            'recorded_by_staff_id' => $staff->id,
            'measured_at' => '2026-08-01',
            'age_months' => 50,
            'weight' => 5.1,
            'height' => 53,
        ]);

        $session = [
            'auth_role' => 'staff',
            'auth_id' => $staff->id,
            'auth_name' => $staff->full_name,
            'auth_email' => $staff->email,
        ];
        $olderChildUrl = str_replace('&', '&amp;', route('staff.neonatal', ['mother' => $mother->id, 'child' => $olderChild->id]));
        $youngerChildUrl = str_replace('&', '&amp;', route('staff.neonatal', ['mother' => $mother->id, 'child' => $youngerChild->id]));

        $this->withSession($session)
            ->get('/staff/neonatal-vaccines?mother='.$mother->id)
            ->assertOk()
            ->assertSee($mother->full_name)
            ->assertSee('Baby Alpha')
            ->assertSee('Baby Beta')
            ->assertSee('name="mother_id"', false)
            ->assertSee("Mother's Child", false)
            ->assertDontSee('data-neo-open="add-child"', false)
            ->assertDontSee('data-neo-modal="add-child"', false)
            ->assertDontSee('Add Child Case Record')
            ->assertSee($olderChildUrl, false)
            ->assertSee($youngerChildUrl, false);

        $this->withSession($session)
            ->get('/staff/neonatal-vaccines?mother='.$mother->id.'&child='.$youngerChild->id)
            ->assertOk()
            ->assertSeeInOrder([$mother->full_name, 'Baby Beta'])
            ->assertSee('Baby Beta (2 months)')
            ->assertSee('<strong>2 months</strong>', false)
            ->assertSee('data-child-growth-chart', false)
            ->assertSee('&quot;age&quot;:1', false)
            ->assertSee('value="'.$youngerChildUrl.'" selected', false);

        $this->withSession([
            'auth_role' => 'mother', 'auth_id' => $mother->id,
            'auth_name' => $mother->full_name, 'auth_email' => $mother->email,
        ])->get('/child-health?child='.$youngerChild->id)
            ->assertOk()
            ->assertSee('Baby Beta (2 months)')
            ->assertSee('<strong>2 months</strong>', false)
            ->assertSee('data-child-growth-chart', false)
            ->assertSee('&quot;age&quot;:1', false);
    }

    public function test_staff_can_update_child_profile_and_photo(): void
    {
        Storage::fake('public');

        $staff = ProgramStaff::create([
            'first_name' => 'Miguel',
            'last_name' => 'Ponce Isles',
            'email' => 'staff-neonatal-update@example.test',
            'password' => Hash::make('password123'),
            'staff_id' => 'STAFF-NEONATAL-UPD',
            'position' => 'Program Staff',
            'contact_number' => '09990001111',
        ]);

        $mother = Mother::create([
            'first_name' => 'Miguel',
            'last_name' => 'Ponce Isles',
            'email' => 'mother-neonatal-update@example.test',
            'password' => Hash::make('password123'),
            'barangay' => 'Concepcion',
            'contact_number' => '09923245009',
            'age' => 21,
            'blood_type' => 'Unknown',
            'pregnancy_status' => 'not_pregnant',
            'is_4ps_beneficiary' => false,
        ]);

        StaffMotherCasefile::create([
            'staff_id' => $staff->id,
            'mother_id' => $mother->id,
        ]);

        $infant = Infant::create([
            'mother_id' => $mother->id,
            'full_name' => 'Child Original',
            'sex' => 'female',
            'birth_date' => '2026-07-01',
        ]);

        $session = [
            'auth_role' => 'staff',
            'auth_id' => $staff->id,
            'auth_name' => $staff->full_name,
            'auth_email' => $staff->email,
        ];

        $this->withSession($session)
            ->patch('/staff/neonatal-vaccines/infants/'.$infant->id, [
                'mother_id' => $mother->id,
                'full_name' => 'Child Updated',
                'sex' => 'female',
                'birth_date' => '2026-07-01',
                'birth_weight' => 3.2,
                'birth_height' => 50,
                'blood_type' => 'A+',
                'facility' => 'RHU - Concepcion',
                'notes' => 'Updated clinical profile.',
            ])
            ->assertRedirect('/staff/neonatal-vaccines?child='.$infant->id);

        $this->assertDatabaseHas('infants', [
            'id' => $infant->id,
            'full_name' => 'Child Updated',
            'blood_type' => 'A+',
        ]);

        $this->withSession($session)
            ->patch('/staff/neonatal-vaccines/infants/'.$infant->id.'/photo', [
                'child_photo' => $this->fakePng('staff-child-profile.png'),
            ])
            ->assertRedirect('/staff/neonatal-vaccines?child='.$infant->id);

        $infant->refresh();
        $this->assertNotNull($infant->photo_path);
        Storage::disk('public')->assertExists($infant->photo_path);
    }

    public function test_staff_can_edit_growth_history_from_neonatal_growth_table(): void
    {
        $staff = ProgramStaff::create([
            'first_name' => 'Miguel',
            'last_name' => 'Ponce Isles',
            'email' => 'staff-growth-edit@example.test',
            'password' => Hash::make('password123'),
            'staff_id' => 'STAFF-GROWTH-EDIT',
            'position' => 'Program Staff',
            'contact_number' => '09990001111',
        ]);

        $mother = Mother::create([
            'first_name' => 'Maria',
            'last_name' => 'Reyes',
            'email' => 'mother-growth-edit@example.test',
            'password' => Hash::make('password123'),
            'barangay' => 'Concepcion',
            'contact_number' => '09923245009',
            'age' => 21,
            'blood_type' => 'Unknown',
            'pregnancy_status' => 'not_pregnant',
            'is_4ps_beneficiary' => false,
        ]);

        StaffMotherCasefile::create([
            'staff_id' => $staff->id,
            'mother_id' => $mother->id,
        ]);

        $infant = Infant::create([
            'mother_id' => $mother->id,
            'full_name' => 'Baby Reyes',
            'sex' => 'female',
            'birth_date' => '2026-06-10',
        ]);

        $growth = InfantGrowthRecord::create([
            'infant_id' => $infant->id,
            'recorded_by_staff_id' => $staff->id,
            'measured_at' => '2026-07-14',
            'age_months' => 1,
            'weight' => 5.10,
            'height' => 53.00,
            'head_circumference' => 35.00,
            'temperature' => 36.7,
            'remarks' => 'Initial measurement.',
        ]);

        $session = [
            'auth_role' => 'staff',
            'auth_id' => $staff->id,
            'auth_name' => $staff->full_name,
            'auth_email' => $staff->email,
        ];

        $this->withSession($session)
            ->get('/staff/neonatal-vaccines?child='.$infant->id)
            ->assertOk()
            ->assertSeeInOrder(['Recorded By', 'Edit Growth'], false)
            ->assertSee('data-neo-growth', false)
            ->assertSee(route('staff.neonatal.growth.update', $growth), false);

        $this->withSession($session)
            ->patch(route('staff.neonatal.growth.update', $growth), [
                'measured_at' => '2026-07-15',
                'age_months' => 1,
                'weight' => 5.45,
                'height' => 54.2,
                'head_circumference' => 35.8,
                'temperature' => 36.9,
                'remarks' => 'Updated from growth history.',
            ])
            ->assertRedirect('/staff/neonatal-vaccines?child='.$infant->id);

        $this->assertDatabaseHas('infant_growth_records', [
            'id' => $growth->id,
            'recorded_by_staff_id' => $staff->id,
            'measured_at' => '2026-07-15 00:00:00',
            'age_months' => 1,
            'remarks' => 'Updated from growth history.',
        ]);

        $this->assertSame('5.45', $growth->fresh()->weight);
    }

    private function fakePng(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}

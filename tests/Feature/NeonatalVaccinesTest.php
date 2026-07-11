<?php

namespace Tests\Feature;

use App\Models\Infant;
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
            ->assertSee('Neonatal & Vaccine Command', false)
            ->assertSee('Linked Child Case Records', false)
            ->assertSee($mother->full_name);
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

    private function fakePng(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}

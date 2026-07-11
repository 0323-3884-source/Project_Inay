<?php

namespace Tests\Feature;

use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Models\StaffMotherCasefile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
}

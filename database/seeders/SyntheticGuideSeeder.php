<?php

namespace Database\Seeders;

use App\Models\Mother;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SyntheticGuideSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Synthetic guide records are only available locally or in tests.');
        }

        // Fictional identities and contact numbers, for demonstrations only.
        $profiles = [
            ['Maria Elena', 'Bautista', 'Santos', 27, '09171280436', 'San Gabriel', 'Married', 'pregnant'],
            ['Angelica', 'Ramos', 'Dela Cruz', 24, '09284510672', 'San Gregorio', 'Single', 'pregnant'],
            ['Christine Mae', 'Flores', 'Reyes', 31, '09952378014', 'San Vicente', 'Married', 'postpartum'],
            ['Jessa Camille', 'Navarro', 'Mendoza', 22, '09613820475', 'San Jose', 'Single', 'pregnant'],
            ['Patricia Anne', 'Garcia', 'Villanueva', 35, '09186472903', 'Concepcion', 'Married', 'pregnant'],
            ['Rosalie', 'Mercado', 'Torres', 29, '09271360584', 'Del Remedio', 'Married', 'postpartum'],
            ['Aileen Joy', 'Castillo', 'Ramos', 26, '09485720136', 'San Roque', 'Single', 'pregnant'],
            ['Carmela', 'Aquino', 'Bautista', 33, '09763540812', 'San Francisco', 'Married', 'planning'],
            ['Michelle', 'Rivera', 'Gonzales', 28, '09193578204', 'San Isidro', 'Married', 'pregnant'],
            ['Katrina Louise', 'Pascual', 'Flores', 21, '09562481307', 'San Nicolas', 'Single', 'pregnant'],
            ['Andrea', 'Valdez', 'Mercado', 30, '09384710265', 'Santa Cruz', 'Married', 'postpartum'],
            ['Grace Ann', 'Del Rosario', 'Navarro', 38, '09914653028', 'San Pedro', 'Married', 'pregnant'],
            ['Janine', 'Santos', 'Castillo', 25, '09172860349', 'San Juan', 'Married', 'pregnant'],
            ['Hazel Marie', 'Reyes', 'Aquino', 32, '09293671408', 'San Lucas 1', 'Married', 'postpartum'],
            ['Rhea Patricia', 'Torres', 'Rivera', 23, '09654720831', 'San Ignacio', 'Single', 'pregnant'],
            ['Maricel', 'Mendoza', 'Pascual', 36, '09987214063', 'Santa Elena', 'Married', 'pregnant'],
            ['Joyce Anne', 'Gonzales', 'Valdez', 28, '09184630972', 'San Rafael', 'Married', 'planning'],
            ['Dianne', 'Bautista', 'Garcia', 30, '09473918206', 'San Miguel', 'Married', 'postpartum'],
            ['Clarissa Mae', 'Flores', 'Del Rosario', 26, '09762805413', 'Santa Maria', 'Single', 'pregnant'],
            ['Rowena', 'Navarro', 'Cruz', 39, '09258163047', 'San Marcos', 'Married', 'pregnant'],
            ['Mikaela Joy', 'Rivera', 'Ramos', 22, '09573690284', 'Santa Catalina', 'Single', 'pregnant'],
            ['Melanie', 'Castillo', 'Mercado', 34, '09386412059', 'San Mateo', 'Married', 'postpartum'],
            ['Irene Camille', 'Aquino', 'Santos', 29, '09195740826', 'Santa Filomena', 'Married', 'pregnant'],
            ['Jonalyn', 'Pascual', 'Torres', 27, '09934628170', 'San Lucas 2', 'Single', 'planning'],
            ['Nerissa Anne', 'Valdez', 'Reyes', 37, '09621870435', 'Santa Isabel', 'Married', 'pregnant'],
            ['Arlene', 'Cruz', 'Villanueva', 31, '09274853016', 'Santa Monica', 'Married', 'postpartum'],
            ['Lovely Mae', 'Del Rosario', 'Mendoza', 24, '09485617302', 'Santa Veronica', 'Single', 'pregnant'],
        ];

        DB::transaction(function () use ($profiles): void {
            foreach ($profiles as $index => $profile) {
                [$first, $middle, $last, $age, $contact, $barangay, $civil, $status] = $profile;
                Mother::firstOrCreate(
                    ['email' => sprintf('guide.mother.%03d@example.test', $index + 1)],
                    [
                        'first_name' => $first,
                        'middle_name' => $middle,
                        'last_name' => $last,
                        'age' => $age,
                        'contact_number' => $contact,
                        'barangay' => $barangay,
                        'municipality_city' => 'San Pablo City',
                        'civil_status' => $civil,
                        'pregnancy_status' => $status,
                        'is_4ps_beneficiary' => $index % 3 !== 1,
                        'password' => Hash::make(Str::random(40)),
                    ],
                );
            }
        });

        $this->command?->info(count($profiles).' fictional guide profiles are ready. Existing profiles were preserved.');
    }
}

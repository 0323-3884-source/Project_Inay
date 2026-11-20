<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Hash};

class MotherChildBulkDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Mother-child demo data is available only locally or in tests.');
        }

        $firstNames = ['Alina', 'Bianca', 'Camille', 'Dalia', 'Elena', 'Faye', 'Giselle', 'Hana', 'Isabel', 'Julia'];
        $lastNames = ['Rivera', 'Mendoza', 'Reyes', 'Garcia', 'Bautista'];
        $girls = ['Mia', 'Sofia', 'Ella', 'Chloe', 'Isla'];
        $boys = ['Noah', 'Liam', 'Lucas', 'Ethan', 'Leo'];
        $barangays = ['San Roque', 'San Vicente', 'San Jose', 'San Gabriel', 'San Isidro'];
        $password = Hash::make('DemoInay2026!');
        $familySeeder = new MotherChildDemoSeeder;

        DB::transaction(function () use ($firstNames, $lastNames, $girls, $boys, $barangays, $password, $familySeeder) {
            for ($index = 1; $index <= 50; $index++) {
                $last = $lastNames[intdiv($index - 1, 10)];
                $female = $index % 2 === 1;
                $childFirst = ($female ? $girls : $boys)[($index - 1) % 5];
                $familySeeder->seedFamily([
                    'email' => sprintf('demo.mother.%03d@example.test', $index),
                    'first_name' => $firstNames[($index - 1) % 10], 'middle_name' => 'Demo', 'last_name' => $last,
                    'password' => $password, 'age' => 22 + ($index % 14),
                    'barangay' => $barangays[($index - 1) % 5],
                ], [
                    'full_name' => $childFirst.' Demo '.$last,
                    'sex' => $female ? 'female' : 'male',
                    'birth_date' => today()->subMonthsNoOverflow(3)->subDays($index - 1),
                ]);
            }
        });

        $this->command?->info('50 fictional mother-child pairs are ready, with growth, vaccination and prenatal records. Existing records/passwords were preserved.');
    }
}

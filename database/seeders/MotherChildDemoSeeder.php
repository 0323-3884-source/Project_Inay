<?php

namespace Database\Seeders;

use App\Models\{Mother, Infant, InfantGrowthRecord, InfantVaccineRecord, MaternalMonitoringRecord};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Hash};

class MotherChildDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFamily();
        $this->command?->info('Fictional mother Amara Demo Santos and child Lia Demo Santos are ready. Existing records/passwords were preserved.');
    }

    public function seedFamily(array $motherProfile = [], array $childProfile = []): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Mother-child demo data is available only locally or in tests.');
        }

        DB::transaction(function () use ($motherProfile, $childProfile) {
            $mother = Mother::firstOrCreate(['email' => $motherProfile['email'] ?? 'demo.mother@example.test'], $motherProfile + [
                'first_name' => 'Amara', 'middle_name' => 'Demo', 'last_name' => 'Santos',
                'password' => $motherProfile['password'] ?? Hash::make('DemoInay2026!'), 'age' => 27,
                'barangay' => 'San Roque', 'municipality_city' => 'San Pablo City',
                'contact_number' => '09000000000', 'civil_status' => 'Married',
                'gravidity' => 1, 'parity' => 1, 'blood_type' => 'O+',
                'pregnancy_status' => 'postpartum', 'is_4ps_beneficiary' => false,
            ]);
            $infant = Infant::firstOrCreate(['mother_id' => $mother->id, 'full_name' => $childProfile['full_name'] ?? 'Lia Demo Santos'], $childProfile + [
                'sex' => 'female', 'birth_date' => today()->subMonthsNoOverflow(3),
                'birth_weight' => 3.1, 'birth_height' => 49, 'blood_type' => 'O+',
                'facility' => 'Fictional Demo Health Center',
                'notes' => 'SYNTHETIC DEMO: fictional child and measurements for system testing only.',
            ]);
            $birth = $infant->birth_date->toImmutable();
            foreach ([[0, 3.1, 49, 34], [1, 4.1, 53, 36], [2, 5.1, 57, 38], [3, 5.9, 60, 39.5]] as [$month, $weight, $height, $head]) {
                InfantGrowthRecord::firstOrCreate([
                    'infant_id' => $infant->id, 'age_months' => $month,
                ], [
                    'measured_at' => $birth->addMonthsNoOverflow($month)->toDateString(), 'weight' => $weight, 'height' => $height,
                    'head_circumference' => $head, 'temperature' => 36.6,
                    'remarks' => 'SYNTHETIC DEMO: fictional growth measurement.',
                ]);
            }
            foreach (['BCG', 'Hepatitis B'] as $vaccine) {
                InfantVaccineRecord::firstOrCreate([
                    'infant_id' => $infant->id, 'vaccine_name' => $vaccine, 'dose_label' => 'Birth dose',
                ], [
                    'vaccine_group' => 'At Birth', 'due_date' => $birth, 'status' => 'completed',
                    'administered_at' => $birth, 'facility' => 'Fictional Demo Health Center',
                    'remarks' => 'SYNTHETIC DEMO: fictional vaccination record, not proof of vaccination.',
                ]);
            }
            foreach ([28, 32, 36] as $week) {
                MaternalMonitoringRecord::firstOrCreate([
                    'mother_id' => $mother->id, 'notes' => 'SYNTHETIC DEMO: fictional prenatal visit, week '.$week.'.',
                ], [
                    'pregnancy_week' => $week, 'pregnancy_month' => (int) ceil($week / 4),
                    'bp_systolic' => 112, 'bp_diastolic' => 74, 'weight' => 60 + ($week - 28) / 2,
                    'blood_sugar' => 88, 'blood_sugar_test_type' => 'fasting_plasma_glucose',
                    'temperature' => 36.6, 'heart_rate' => 80,
                    'recorded_at' => $birth->subWeeks(40 - $week)->setTime(9, 0),
                ]);
            }
        });

    }
}

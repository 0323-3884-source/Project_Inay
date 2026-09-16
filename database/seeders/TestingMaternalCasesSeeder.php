<?php

namespace Database\Seeders;

use App\Models\MaternalMonitoringRecord;
use App\Models\Mother;
use App\Support\MaternalVitalScreening;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class TestingMaternalCasesSeeder extends Seeder
{
    /**
     * Seed unassigned mother accounts for program staff testing.
     */
    public function run(): void
    {
        $barangays = [
            'Bagong Bayan',
            'Concepcion',
            'Del Remedio',
            'San Francisco',
            'San Gabriel',
            'San Gregorio',
            'San Ignacio',
            'San Isidro',
            'San Joaquin',
            'San Jose',
            'San Juan',
            'San Lucas 1',
            'San Lucas 2',
            'San Marcos',
            'San Mateo',
            'San Miguel',
            'San Nicolas',
            'San Pedro',
            'San Rafael',
            'San Roque',
            'San Vicente',
            'Santa Ana',
            'Santa Catalina',
            'Santa Cruz',
            'Santa Elena',
            'Santa Filomena',
            'Santa Isabel',
            'Santa Maria',
            'Santa Monica',
            'Santa Veronica',
        ];
        $barangayWeights = [
            'Bagong Bayan' => 7,
            'Concepcion' => 6,
            'Del Remedio' => 5,
            'San Francisco' => 5,
            'San Gabriel' => 4,
            'San Gregorio' => 4,
            'San Ignacio' => 4,
            'San Isidro' => 3,
            'San Joaquin' => 3,
            'San Jose' => 3,
            'San Juan' => 3,
            'San Lucas 1' => 2,
            'San Lucas 2' => 2,
            'San Marcos' => 2,
            'San Mateo' => 2,
            'San Miguel' => 2,
            'San Nicolas' => 2,
            'San Pedro' => 2,
            'San Rafael' => 2,
            'San Roque' => 2,
            'San Vicente' => 2,
            'Santa Ana' => 2,
            'Santa Catalina' => 1,
            'Santa Cruz' => 1,
            'Santa Elena' => 1,
            'Santa Filomena' => 1,
            'Santa Isabel' => 1,
            'Santa Maria' => 1,
            'Santa Monica' => 1,
            'Santa Veronica' => 1,
        ];
        $weightedBarangays = [];

        foreach ($barangayWeights as $barangayName => $weight) {
            for ($copy = 0; $copy < $weight; $copy++) {
                $weightedBarangays[] = $barangayName;
            }
        }

        $firstNames = [
            'Maria', 'Lorna', 'Angelica', 'Rhea', 'Mikaela', 'Janine', 'Carmela', 'Joyce', 'Patricia', 'Aileen',
            'Clarissa', 'Jessa', 'Marites', 'Rosalie', 'Dianne', 'Lovely', 'Andrea', 'Hazel', 'Katrina', 'Michelle',
            'Rowena', 'Christine', 'Maricel', 'Jonalyn', 'Arlene', 'Princess', 'Nerissa', 'Melanie', 'Grace', 'Irene',
        ];

        $lastNames = [
            'Santos', 'Reyes', 'Garcia', 'Dela Cruz', 'Mendoza', 'Ramos', 'Torres', 'Castillo', 'Flores', 'Gonzales',
            'Bautista', 'Villanueva', 'Rivera', 'Aquino', 'Navarro', 'Mercado', 'Cruz', 'Del Rosario', 'Pascual', 'Valdez',
        ];

        $scenarios = [
            ['label' => 'Routine prenatal intake', 'status' => 'pregnant', 'risk' => 'low', 'age' => 26, 'week' => 10, 'bp' => [112, 72], 'sugar' => 88, 'weight' => 54.2, 'hemoglobin' => 12.4, 'temperature' => 36.6, 'heart_rate' => 78],
            ['label' => 'High blood pressure review', 'status' => 'pregnant', 'risk' => 'high', 'age' => 34, 'week' => 28, 'bp' => [146, 94], 'sugar' => 106, 'weight' => 69.4, 'hemoglobin' => 11.8, 'temperature' => 36.8, 'heart_rate' => 96],
            ['label' => 'Anemia follow-up', 'status' => 'pregnant', 'risk' => 'high', 'age' => 22, 'week' => 18, 'bp' => [106, 68], 'sugar' => 92, 'weight' => 49.7, 'hemoglobin' => 9.7, 'temperature' => 36.4, 'heart_rate' => 88],
            ['label' => 'Blood sugar needs review', 'status' => 'pregnant', 'risk' => 'medium', 'age' => 31, 'week' => 24, 'bp' => [126, 82], 'sugar' => 148, 'weight' => 66.5, 'hemoglobin' => 12.1, 'temperature' => 36.7, 'heart_rate' => 86],
            ['label' => 'Teen pregnancy nutrition support', 'status' => 'pregnant', 'risk' => 'medium', 'age' => 18, 'week' => 16, 'bp' => [104, 66], 'sugar' => 76, 'weight' => 45.3, 'hemoglobin' => 11.2, 'temperature' => 36.5, 'heart_rate' => 92],
            ['label' => 'Advanced maternal age monitoring', 'status' => 'pregnant', 'risk' => 'medium', 'age' => 39, 'week' => 20, 'bp' => [132, 84], 'sugar' => 116, 'weight' => 63.1, 'hemoglobin' => 11.6, 'temperature' => 36.6, 'heart_rate' => 84],
            ['label' => 'Postpartum recovery baseline', 'status' => 'postpartum', 'risk' => 'low', 'age' => 29, 'week' => null, 'bp' => [116, 76], 'sugar' => 94, 'weight' => 58.6, 'hemoglobin' => 12.0, 'temperature' => 36.8, 'heart_rate' => 80],
            ['label' => 'Postpartum danger sign review', 'status' => 'postpartum', 'risk' => 'high', 'age' => 27, 'week' => null, 'bp' => [138, 88], 'sugar' => 104, 'weight' => 61.0, 'hemoglobin' => 10.2, 'temperature' => 38.1, 'heart_rate' => 116],
            ['label' => 'Planning pregnancy counseling', 'status' => 'planning', 'risk' => 'low', 'age' => 24, 'week' => null, 'bp' => [110, 70], 'sugar' => 86, 'weight' => 52.8, 'hemoglobin' => 12.9, 'temperature' => 36.4, 'heart_rate' => 76],
            ['label' => 'Planning with diabetes risk', 'status' => 'planning', 'risk' => 'medium', 'age' => 35, 'week' => null, 'bp' => [128, 82], 'sugar' => 142, 'weight' => 72.0, 'hemoglobin' => 12.2, 'temperature' => 36.7, 'heart_rate' => 88],
            ['label' => 'Not pregnant wellness intake', 'status' => 'not_pregnant', 'risk' => 'low', 'age' => 32, 'week' => null, 'bp' => [114, 74], 'sugar' => 90, 'weight' => 57.4, 'hemoglobin' => 12.6, 'temperature' => 36.5, 'heart_rate' => 74],
            ['label' => 'Low blood sugar observation', 'status' => 'pregnant', 'risk' => 'medium', 'age' => 25, 'week' => 12, 'bp' => [108, 68], 'sugar' => 66, 'weight' => 50.1, 'hemoglobin' => 11.9, 'temperature' => 36.1, 'heart_rate' => 82],
            ['label' => 'Possible infection review', 'status' => 'pregnant', 'risk' => 'high', 'age' => 30, 'week' => 30, 'bp' => [120, 78], 'sugar' => 98, 'weight' => 67.8, 'hemoglobin' => 11.4, 'temperature' => 38.2, 'heart_rate' => 122],
            ['label' => 'Underweight gain monitoring', 'status' => 'pregnant', 'risk' => 'medium', 'age' => 21, 'week' => 22, 'bp' => [102, 64], 'sugar' => 84, 'weight' => 43.9, 'hemoglobin' => 11.5, 'temperature' => 36.3, 'heart_rate' => 90],
            ['label' => 'Healthy third trimester', 'status' => 'pregnant', 'risk' => 'low', 'age' => 28, 'week' => 34, 'bp' => [118, 76], 'sugar' => 96, 'weight' => 64.6, 'hemoglobin' => 12.0, 'temperature' => 36.7, 'heart_rate' => 82],
        ];

        $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'O+', 'O-', 'Unknown'];
        $password = Hash::make('password123');
        $baseDate = Carbon::create(2026, 7, 16)->startOfDay();
        mt_srand(20260716);

        for ($index = 1; $index <= 60; $index++) {
            $scenario = $scenarios[($index - 1) % count($scenarios)];
            $firstName = $firstNames[($index - 1) % count($firstNames)];
            $lastName = $lastNames[(int) floor(($index - 1) / 3) % count($lastNames)];
            $barangay = $weightedBarangays[mt_rand(0, count($weightedBarangays) - 1)] ?? $barangays[($index - 1) % count($barangays)];
            $age = min(44, max(16, $scenario['age'] + (($index % 5) - 2)));
            $email = sprintf('testing.maternal.%03d@project-inay.test', $index);
            $phone = '09'.str_pad((string) (170000000 + $index), 9, '0', STR_PAD_LEFT);
            $registeredAt = $baseDate->copy()->subDays(90 - $index);

            $mother = Mother::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $firstName,
                    'middle_name' => $index % 4 === 0 ? 'Mae' : null,
                    'last_name' => $lastName,
                    'password' => $password,
                    'barangay' => $barangay,
                    'contact_number' => $phone,
                    'age' => $age,
                    'blood_type' => $bloodTypes[($index - 1) % count($bloodTypes)],
                    'pregnancy_status' => $scenario['status'],
                    'location_latitude' => 14.0640 + (($index % 15) * 0.0021),
                    'location_longitude' => 121.3210 + (($index % 12) * 0.0024),
                    'location_accuracy' => 15 + ($index % 35),
                    'is_4ps_beneficiary' => $index % 3 === 0,
                    'created_at' => $registeredAt,
                    'updated_at' => $registeredAt,
                ]
            );

            $week = $scenario['week'] === null ? null : min(40, max(4, $scenario['week'] + ($index % 3)));
            $recordedAt = Carbon::parse($registeredAt)->addDays(2 + ($index % 7));
            $vitals = [
                'recorded_at' => $recordedAt,
                'pregnancy_week' => $week,
                'bp_systolic' => $scenario['bp'][0] + ($index % 2),
                'bp_diastolic' => $scenario['bp'][1],
                'blood_sugar_test_type' => 'fasting_plasma_glucose',
                'blood_sugar' => $scenario['sugar'] + ($index % 4),
                'weight' => $scenario['weight'] + (($index % 6) * 0.35),
                'temperature' => $scenario['temperature'],
                'heart_rate' => $scenario['heart_rate'],
            ];
            $screening = MaternalVitalScreening::screen($vitals, $mother);

            MaternalMonitoringRecord::updateOrCreate(
                [
                    'mother_id' => $mother->id,
                    'recorded_at' => $recordedAt,
                ],
                [
                    'staff_mother_casefile_id' => null,
                    'recorded_by_staff_id' => null,
                    'pregnancy_week' => $week,
                    'pregnancy_month' => $week ? min(10, (int) ceil($week / 4)) : null,
                    'bp_systolic' => $vitals['bp_systolic'],
                    'bp_diastolic' => $vitals['bp_diastolic'],
                    'blood_sugar_test_type' => $vitals['blood_sugar_test_type'],
                    'blood_sugar' => $vitals['blood_sugar'],
                    'weight' => $vitals['weight'],
                    'temperature' => $vitals['temperature'],
                    'heart_rate' => $vitals['heart_rate'],
                    'bp_status' => $screening['statuses']['blood_pressure'],
                    'blood_sugar_status' => $screening['statuses']['blood_sugar'],
                    'weight_status' => $screening['statuses']['weight'],
                    'temperature_status' => $screening['statuses']['temperature'],
                    'heart_rate_status' => $screening['statuses']['heart_rate'],
                    'screening_summary_status' => $screening['summary_status'],
                    'measurement_units' => $screening['units'],
                    'screening_explanations' => $screening['explanations'],
                    'screening_guidelines' => $screening['guidelines'],
                    'weight_change_from_previous' => $screening['weight_change_from_previous'],
                    'risk_level' => $screening['summary_status'],
                    'notes' => $scenario['label'].' seeded for unassigned casefile testing.',
                    'created_at' => $recordedAt,
                    'updated_at' => $recordedAt,
                ]
            );
        }
    }
}

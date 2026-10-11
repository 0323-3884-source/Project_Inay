<?php

namespace Database\Seeders;

use App\Models\Infant;
use App\Models\InfantGrowthRecord;
use App\Models\Mother;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SyntheticChildProfilesSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Synthetic child profiles are only available locally or in tests.');
        }

        // Fictional children for the local testing dataset, never real patient information.
        $names = [
            ['Gabriel Luis', 'male'], ['Sofia Isabel', 'female'],
            ['Lucas Mateo', 'male'], ['Amelia Grace', 'female'],
            ['Nathaniel James', 'male'], ['Chloe Andrea', 'female'],
            ['Ethan Miguel', 'male'], ['Isabella Rose', 'female'],
            ['Liam Rafael', 'male'], ['Mia Gabrielle', 'female'],
            ['Noah Sebastian', 'male'], ['Ava Camille', 'female'],
            ['Elijah Daniel', 'male'], ['Hannah Elise', 'female'],
            ['Joshua Adrian', 'male'], ['Zoe Patricia', 'female'],
            ['Caleb Antonio', 'male'], ['Aria Celeste', 'female'],
            ['Jacob Emmanuel', 'male'], ['Emma Louise', 'female'],
            ['Aaron Gabriel', 'male'], ['Samantha Claire', 'female'],
            ['Dylan Lorenzo', 'male'], ['Leah Nicole', 'female'],
            ['David Joaquin', 'male'], ['Abigail Faith', 'female'],
            ['Isaac Julian', 'male'], ['Natalie Joy', 'female'],
            ['Samuel Vincent', 'male'], ['Olivia Beatrice', 'female'],
        ];
        $referenceDate = Carbon::create(2026, 10, 9)->min(now())->startOfDay();
        $created = 0;

        DB::transaction(function () use ($names, $referenceDate, &$created): void {
            foreach (Mother::orderBy('id')->get() as $index => $mother) {
                // Preserve any children already registered; reruns do not add duplicates.
                if (Infant::where('mother_id', $mother->id)->exists()) {
                    continue;
                }

                [$firstName, $sex] = $names[$index % count($names)];
                $ageMonths = $mother->pregnancy_status === 'postpartum'
                    ? 1 + $index % 5
                    : 9 + $index % 28;
                $birthDate = $referenceDate->copy()->subMonthsNoOverflow($ageMonths)->subDays($index % 20);
                $birthWeight = round(2.8 + ($index % 9) * 0.1, 2);
                $birthHeight = 47 + $index % 6;
                $child = Infant::create([
                    'mother_id' => $mother->id,
                    'full_name' => trim($firstName.' '.$mother->middle_name.' '.$mother->last_name),
                    'sex' => $sex,
                    'birth_date' => $birthDate->toDateString(),
                    'birth_weight' => $birthWeight,
                    'birth_height' => $birthHeight,
                    'blood_type' => ['O+', 'A+', 'B+', 'AB+', 'Unknown'][$index % 5],
                    'facility' => ['San Pablo City General Hospital', 'Laguna Provincial Hospital', 'San Pablo City Health Center'][$index % 3],
                    'notes' => 'SYNTHETIC TEST DATA: Fictional child identity and measurements for interface testing.',
                ]);

                InfantGrowthRecord::create([
                    'infant_id' => $child->id,
                    'measured_at' => $birthDate->toDateString(),
                    'age_months' => 0,
                    'weight' => $birthWeight,
                    'height' => $birthHeight,
                    'head_circumference' => 33 + ($index % 4) * 0.5,
                    'temperature' => 36.6,
                    'remarks' => 'Synthetic birth baseline for testing.',
                ]);
                InfantGrowthRecord::create([
                    'infant_id' => $child->id,
                    'measured_at' => $referenceDate->toDateString(),
                    'age_months' => $ageMonths,
                    'weight' => round($birthWeight + min($ageMonths, 12) * 0.48 + max(0, $ageMonths - 12) * 0.18, 2),
                    'height' => round($birthHeight + min($ageMonths, 12) * 1.8 + max(0, $ageMonths - 12) * 0.7, 2),
                    'head_circumference' => round(34 + min($ageMonths, 12) * 0.8 + max(0, $ageMonths - 12) * 0.1, 2),
                    'temperature' => 36.5 + ($index % 4) * 0.1,
                    'remarks' => 'Synthetic follow-up measurement for testing; not a clinical reference.',
                ]);
                $created++;
            }
        });

        $this->command?->info($created.' synthetic child profiles added. Existing child profiles preserved.');
    }
}

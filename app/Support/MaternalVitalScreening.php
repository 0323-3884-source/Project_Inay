<?php

namespace App\Support;

use App\Models\MaternalMonitoringRecord;
use App\Models\MaternalVitalThreshold;
use App\Models\Mother;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class MaternalVitalScreening
{
    public const STATUS_LOGGED = 'Logged';
    public const STATUS_WITHIN = 'Within Reference Range';
    public const STATUS_REVIEW = 'For Review';
    public const STATUS_INTERPRETATION = 'For Professional Interpretation';
    public const STATUS_URGENT = 'Urgent Referral Recommended';

    private static ?Collection $thresholds = null;

    public static function bloodSugarTestTypes(): array
    {
        return config('maternal_vitals.blood_sugar_test_types', []);
    }

    public static function references(): array
    {
        return config('maternal_vitals.references', []);
    }

    public static function clearCache(): void
    {
        self::$thresholds = null;
    }

    public static function statusSlug(?string $status): string
    {
        return str_replace('_', '-', self::statusKey($status));
    }

    public static function statusKey(?string $status): string
    {
        return match (strtolower(trim((string) $status))) {
            'within reference range', 'normal', 'low' => 'within_reference_range',
            'for review', 'review', 'needs review', 'medium', 'high' => 'for_review',
            'for professional interpretation', 'professional interpretation' => 'for_professional_interpretation',
            'urgent referral recommended', 'urgent' => 'urgent_referral_recommended',
            default => 'logged',
        };
    }

    public static function normalizeStatus(?string $status): string
    {
        return match (self::statusKey($status)) {
            'within_reference_range' => self::STATUS_WITHIN,
            'for_review' => self::STATUS_REVIEW,
            'for_professional_interpretation' => self::STATUS_INTERPRETATION,
            'urgent_referral_recommended' => self::STATUS_URGENT,
            default => self::STATUS_LOGGED,
        };
    }

    public static function screen(array $values, Mother $mother, ?int $ignoreRecordId = null): array
    {
        $date = Carbon::parse($values['recorded_at'] ?? now())->startOfDay();
        $hasSystolic = self::hasValue($values['bp_systolic'] ?? null);
        $hasDiastolic = self::hasValue($values['bp_diastolic'] ?? null);
        $hasBloodSugar = self::hasValue($values['blood_sugar'] ?? null);
        $hasWeight = self::hasValue($values['weight'] ?? null);
        $hasTemperature = self::hasValue($values['temperature'] ?? null);
        $hasHeartRate = self::hasValue($values['heart_rate'] ?? null);
        $systolic = $hasSystolic ? (int) $values['bp_systolic'] : null;
        $diastolic = $hasDiastolic ? (int) $values['bp_diastolic'] : null;
        $bloodSugar = $hasBloodSugar ? (float) $values['blood_sugar'] : null;
        $bloodSugarTestType = (string) ($values['blood_sugar_test_type'] ?? '');
        $weight = $hasWeight ? (float) $values['weight'] : null;
        $temperature = $hasTemperature ? (float) $values['temperature'] : null;
        $heartRate = $hasHeartRate ? (int) $values['heart_rate'] : null;

        $previousWeight = $hasWeight ? self::previousWeight($mother, $date, $ignoreRecordId) : null;
        $weightChange = $previousWeight === null || $weight === null ? null : round($weight - (float) $previousWeight->weight, 2);

        $statuses = [
            'blood_pressure' => $hasSystolic && $hasDiastolic ? self::bloodPressureStatus($systolic, $diastolic) : self::STATUS_LOGGED,
            'blood_sugar' => $hasBloodSugar ? self::bloodSugarStatus($bloodSugar, $bloodSugarTestType) : self::STATUS_LOGGED,
            'weight' => self::STATUS_LOGGED,
            'temperature' => $hasTemperature ? self::temperatureStatus($temperature) : self::STATUS_LOGGED,
            'heart_rate' => $hasHeartRate ? self::heartRateStatus($heartRate) : self::STATUS_LOGGED,
        ];

        $explanations = [
            'blood_pressure' => $hasSystolic && $hasDiastolic ? self::bloodPressureExplanation($statuses['blood_pressure']) : 'Blood pressure measurement is not logged yet.',
            'blood_sugar' => $hasBloodSugar ? self::bloodSugarExplanation($bloodSugar, $bloodSugarTestType, $statuses['blood_sugar']) : 'Blood sugar measurement is not logged yet.',
            'weight' => self::weightExplanation($weightChange, $values['pre_pregnancy_weight'] ?? null, $values['pre_pregnancy_bmi'] ?? null),
            'temperature' => $hasTemperature ? self::temperatureExplanation($statuses['temperature']) : 'Temperature measurement is not logged yet.',
            'heart_rate' => $hasHeartRate ? self::heartRateExplanation($statuses['heart_rate']) : 'Heart rate measurement is not logged yet.',
        ];

        $guidelines = [
            'blood_pressure' => self::guideline('blood_pressure.systolic.review_min'),
            'blood_sugar' => self::bloodSugarGuideline($bloodSugarTestType),
            'weight' => self::guideline('weight.unusual_min'),
            'temperature' => self::guideline('temperature.review_min'),
            'heart_rate' => self::guideline('heart_rate.review_min'),
        ];

        $confirmationWarnings = self::confirmationWarnings($statuses, $explanations);
        $confirmationWarnings = array_merge(
            $confirmationWarnings,
            self::unusualValueWarnings($systolic, $diastolic, $bloodSugar, $weight, $temperature, $heartRate)
        );

        return [
            'statuses' => $statuses,
            'summary_status' => self::summaryStatus($statuses),
            'explanations' => $explanations,
            'guidelines' => $guidelines,
            'units' => [
                'bp_systolic' => 'mmHg',
                'bp_diastolic' => 'mmHg',
                'blood_sugar' => 'mg/dL',
                'weight' => 'kg',
                'height_cm' => 'cm',
                'pre_pregnancy_weight' => 'kg',
                'pre_pregnancy_bmi' => 'kg/m2',
                'temperature' => 'C',
                'heart_rate' => 'bpm',
            ],
            'weight_change_from_previous' => $weightChange,
            'previous_weight' => $previousWeight === null ? null : (float) $previousWeight->weight,
            'confirmation_warnings' => array_values(array_unique(array_filter($confirmationWarnings))),
        ];
    }

    public static function thresholdValue(string $key, ?float $default = null): ?float
    {
        $threshold = self::thresholds()->get($key);

        return $threshold && $threshold['value'] !== null ? (float) $threshold['value'] : $default;
    }

    public static function guideline(string $key): array
    {
        $threshold = self::thresholds()->get($key);

        if (! $threshold) {
            return [
                'name' => 'Facility-configurable maternal vital screening rule',
                'version' => 'Pending partner OB-GYN/facility validation',
                'source_url' => null,
                'threshold' => null,
                'unit' => null,
            ];
        }

        return [
            'name' => $threshold['guideline_name'],
            'version' => $threshold['guideline_version'],
            'source_url' => $threshold['source_url'],
            'threshold' => $threshold['value'] === null ? null : (float) $threshold['value'],
            'unit' => $threshold['unit'],
        ];
    }

    private static function thresholds(): Collection
    {
        if (self::$thresholds !== null) {
            return self::$thresholds;
        }

        $defaults = collect(config('maternal_vitals.thresholds', []))
            ->keyBy('key')
            ->map(fn (array $threshold): array => [
                ...$threshold,
                'is_active' => true,
            ]);

        try {
            $rows = MaternalVitalThreshold::query()
                ->get()
                ->keyBy('key')
                ->map(fn (MaternalVitalThreshold $threshold): array => [
                    'key' => $threshold->key,
                    'measurement' => $threshold->measurement,
                    'test_type' => $threshold->test_type,
                    'threshold_type' => $threshold->threshold_type,
                    'label' => $threshold->label,
                    'value' => $threshold->value === null ? null : (float) $threshold->value,
                    'unit' => $threshold->unit,
                    'guideline_name' => $threshold->guideline_name,
                    'guideline_version' => $threshold->guideline_version,
                    'source_url' => $threshold->source_url,
                    'notes' => $threshold->notes,
                    'is_active' => $threshold->is_active,
                ]);

            self::$thresholds = $defaults
                ->merge($rows)
                ->filter(fn (array $threshold): bool => (bool) ($threshold['is_active'] ?? true));
        } catch (Throwable) {
            self::$thresholds = $defaults;
        }

        return self::$thresholds;
    }

    private static function hasValue(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }

    private static function previousWeight(Mother $mother, Carbon $date, ?int $ignoreRecordId): ?MaternalMonitoringRecord
    {
        return MaternalMonitoringRecord::query()
            ->where('mother_id', $mother->id)
            ->whereNotNull('weight')
            ->when($ignoreRecordId, fn ($query) => $query->where('id', '<>', $ignoreRecordId))
            ->where(function ($query) use ($date): void {
                $query->whereDate('recorded_at', '<=', $date->toDateString())
                    ->orWhereNull('recorded_at');
            })
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->first();
    }

    private static function bloodPressureStatus(int $systolic, int $diastolic): string
    {
        $needsReview = $systolic >= self::thresholdValue('blood_pressure.systolic.review_min', 140)
            || $diastolic >= self::thresholdValue('blood_pressure.diastolic.review_min', 90);

        return $needsReview ? self::STATUS_REVIEW : self::STATUS_WITHIN;
    }

    private static function bloodPressureExplanation(string $status): string
    {
        if ($status === self::STATUS_REVIEW) {
            return 'Elevated blood pressure detected. Repeat measurement and healthcare-worker assessment are recommended.';
        }

        return 'Blood pressure is below the configured pregnancy screening review threshold.';
    }

    private static function bloodSugarStatus(float $value, string $testType): string
    {
        if ($testType === 'random_blood_glucose' || ! array_key_exists($testType, self::bloodSugarTestTypes())) {
            return self::STATUS_INTERPRETATION;
        }

        if ($value < self::thresholdValue('blood_sugar.low.review_below', 60)) {
            return self::STATUS_REVIEW;
        }

        if ($testType === 'fasting_plasma_glucose') {
            if ($value >= self::thresholdValue('blood_sugar.fasting_plasma_glucose.urgent_min', 126)) {
                return self::STATUS_URGENT;
            }

            $needsReview = $value >= self::thresholdValue('blood_sugar.fasting_plasma_glucose.review_min', 92)
                && $value <= self::thresholdValue('blood_sugar.fasting_plasma_glucose.review_max', 125);

            return $needsReview ? self::STATUS_REVIEW : self::STATUS_WITHIN;
        }

        if ($testType === 'ogtt_1_hour') {
            return $value >= self::thresholdValue('blood_sugar.ogtt_1_hour.review_min', 180)
                ? self::STATUS_REVIEW
                : self::STATUS_WITHIN;
        }

        if ($testType === 'ogtt_2_hour') {
            if ($value >= self::thresholdValue('blood_sugar.ogtt_2_hour.urgent_min', 200)) {
                return self::STATUS_URGENT;
            }

            $needsReview = $value >= self::thresholdValue('blood_sugar.ogtt_2_hour.review_min', 153)
                && $value <= self::thresholdValue('blood_sugar.ogtt_2_hour.review_max', 199);

            return $needsReview ? self::STATUS_REVIEW : self::STATUS_WITHIN;
        }

        return self::STATUS_INTERPRETATION;
    }

    private static function bloodSugarExplanation(float $value, string $testType, string $status): string
    {
        $testLabel = self::bloodSugarTestTypes()[$testType] ?? 'Blood sugar test type not recorded';

        if ($testType === 'random_blood_glucose') {
            return $testLabel.' requires professional interpretation with timing, symptoms, and clinical context.';
        }

        if ($status === self::STATUS_URGENT) {
            return $testLabel.' is at or above the configured referral-support threshold. Healthcare-worker assessment is recommended.';
        }

        if ($status === self::STATUS_REVIEW) {
            if ($value < self::thresholdValue('blood_sugar.low.review_below', 60)) {
                return $testLabel.' is below the configured low-value review threshold. Repeat measurement and professional assessment are recommended.';
            }

            return $testLabel.' meets the WHO pregnancy-specific screening threshold for review. This is not a diagnosis.';
        }

        if ($status === self::STATUS_INTERPRETATION) {
            return 'Blood sugar value needs professional interpretation because the test type is missing or not recognized.';
        }

        return $testLabel.' is below the configured pregnancy screening review threshold.';
    }

    private static function bloodSugarGuideline(string $testType): array
    {
        return match ($testType) {
            'fasting_plasma_glucose' => self::guideline('blood_sugar.fasting_plasma_glucose.review_min'),
            'ogtt_1_hour' => self::guideline('blood_sugar.ogtt_1_hour.review_min'),
            'ogtt_2_hour' => self::guideline('blood_sugar.ogtt_2_hour.review_min'),
            default => self::guideline('blood_sugar.low.review_below'),
        };
    }

    private static function weightExplanation(?float $weightChange, mixed $prePregnancyWeight, mixed $prePregnancyBmi): string
    {
        $parts = ['Review weight change based on pre-pregnancy BMI and pregnancy week.'];

        if ($weightChange !== null) {
            $direction = $weightChange > 0 ? '+' : '';
            $parts[] = 'Change from previous entry: '.$direction.rtrim(rtrim(number_format($weightChange, 2), '0'), '.').' kg.';
        }

        if ($prePregnancyWeight !== null && $prePregnancyWeight !== '') {
            $parts[] = 'Pre-pregnancy weight logged.';
        }

        if ($prePregnancyBmi !== null && $prePregnancyBmi !== '') {
            $parts[] = 'Pre-pregnancy BMI logged.';
        }

        return implode(' ', $parts);
    }

    private static function temperatureStatus(float $value): string
    {
        if ($value >= self::thresholdValue('temperature.urgent_min', 39)) {
            return self::STATUS_URGENT;
        }

        $needsReview = $value < self::thresholdValue('temperature.review_below', 36)
            || $value >= self::thresholdValue('temperature.review_min', 38);

        return $needsReview ? self::STATUS_REVIEW : self::STATUS_WITHIN;
    }

    private static function temperatureExplanation(string $status): string
    {
        if ($status === self::STATUS_URGENT) {
            return 'Temperature is above the configured urgent referral-support threshold. Healthcare-worker assessment is recommended.';
        }

        if ($status === self::STATUS_REVIEW) {
            return 'Temperature is outside the configured review thresholds and should be assessed with symptoms and clinical context.';
        }

        return 'Temperature is within the currently configured review thresholds.';
    }

    private static function heartRateStatus(int $value): string
    {
        if ($value <= self::thresholdValue('heart_rate.urgent_below', 45) || $value >= self::thresholdValue('heart_rate.urgent_min', 130)) {
            return self::STATUS_URGENT;
        }

        $needsReview = $value < self::thresholdValue('heart_rate.review_below', 60)
            || $value > self::thresholdValue('heart_rate.review_min', 110);

        return $needsReview ? self::STATUS_REVIEW : self::STATUS_WITHIN;
    }

    private static function heartRateExplanation(string $status): string
    {
        if ($status === self::STATUS_URGENT) {
            return 'Heart rate is outside the configured urgent referral-support threshold. Healthcare-worker assessment is recommended.';
        }

        if ($status === self::STATUS_REVIEW) {
            return 'Heart rate is outside the configured review thresholds and should be assessed with symptoms and clinical context.';
        }

        return 'Heart rate is within the currently configured review thresholds.';
    }

    public static function summaryStatus(array $statuses): string
    {
        $priority = [
            self::STATUS_URGENT,
            self::STATUS_REVIEW,
            self::STATUS_INTERPRETATION,
            self::STATUS_WITHIN,
            self::STATUS_LOGGED,
        ];

        foreach ($priority as $status) {
            if (in_array($status, $statuses, true)) {
                return $status;
            }
        }

        return self::STATUS_LOGGED;
    }

    private static function confirmationWarnings(array $statuses, array $explanations): array
    {
        return collect($statuses)
            ->reject(fn (string $status): bool => in_array($status, [self::STATUS_LOGGED, self::STATUS_WITHIN], true))
            ->map(fn (string $status, string $key): string => ucfirst(str_replace('_', ' ', $key)).': '.$explanations[$key])
            ->values()
            ->all();
    }

    private static function unusualValueWarnings(
        ?int $systolic,
        ?int $diastolic,
        ?float $bloodSugar,
        ?float $weight,
        ?float $temperature,
        ?int $heartRate
    ): array {
        $warnings = [];

        if (($systolic !== null && $systolic >= self::thresholdValue('blood_pressure.systolic.confirm_min', 160))
            || ($diastolic !== null && $diastolic >= self::thresholdValue('blood_pressure.diastolic.confirm_min', 110))) {
            $warnings[] = 'Blood pressure is in an unusually elevated range. Confirm the measurement before saving.';
        }

        if ($weight !== null && ($weight < self::thresholdValue('weight.unusual_below', 35)
            || $weight >= self::thresholdValue('weight.unusual_min', 180))) {
            $warnings[] = 'Weight is unusually far from expected adult pregnancy entries. Confirm the value and unit before saving.';
        }

        if ($bloodSugar !== null && $bloodSugar >= 300) {
            $warnings[] = 'Blood sugar is unusually high. Confirm the value, test type, and unit before saving.';
        }

        if ($temperature !== null && ($temperature >= 39 || $temperature < 35)) {
            $warnings[] = 'Temperature is unusually outside routine monitoring values. Confirm the value before saving.';
        }

        if ($heartRate !== null && ($heartRate >= 130 || $heartRate <= 45)) {
            $warnings[] = 'Heart rate is unusually outside routine monitoring values. Confirm the value before saving.';
        }

        return $warnings;
    }
}

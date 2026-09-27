<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class ChildProfileDisplay
{
    public static function date(mixed $value): ?CarbonImmutable
    {
        try {
            $text = $value instanceof DateTimeInterface ? $value->format('Y-m-d') : substr((string) $value, 0, 10);
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $text);

            return $date && $date->format('Y-m-d') === $text ? $date : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function months(mixed $birthDate, mixed $at = null): ?int
    {
        $birth = self::date($birthDate);
        $date = $at === null ? CarbonImmutable::today() : self::date($at);
        if (! $birth || ! $date || $birth->isAfter($date) || $date->isAfter(CarbonImmutable::today())) {
            return null;
        }

        return (int) $birth->diffInMonths($date);
    }

    public static function age(mixed $birthDate): string
    {
        $months = self::months($birthDate);
        if ($months === null) return 'Age unavailable';
        if ($months < 24) return $months.' month'.($months === 1 ? '' : 's');
        $years = intdiv($months, 12);
        $remaining = $months % 12;

        return $years.' years'.($remaining ? ' '.$remaining.' month'.($remaining === 1 ? '' : 's') : '');
    }

    public static function chart($records, string $field, mixed $birthDate): array
    {
        $birth = self::date($birthDate);
        $points = collect();
        foreach ($records as $record) {
            $date = self::date($record->getRawOriginal('measured_at'));
            $value = $record->{$field};
            if (! $birth || ! $date || self::months($birth, $date) === null || ! is_numeric($value) || (float) $value <= 0) continue;
            $points->push([
                'age' => round($birth->diffInMonths($date), 3),
                'value' => (float) $value,
                'date' => $date->format('M j, Y'),
                'sort' => $date->toDateString(),
            ]);
        }
        $points = $points->sortBy('sort')->values();
        if ($points->isEmpty()) return ['has' => false, 'points' => [], 'latest' => null];
        $padding = max(0.5, ($points->max('value') - $points->min('value')) * 0.2);
        $minAge = $points->min('age');
        $maxAge = $points->max('age');
        $agePadding = max(0.5, ($maxAge - $minAge) * 0.1);

        return [
            'has' => true, 'points' => $points->all(), 'latest' => $points->last()['value'],
            'min' => max(0, floor(($points->min('value') - $padding) * 10) / 10),
            'max' => ceil(($points->max('value') + $padding) * 10) / 10,
            'min_age' => max(0, $minAge - $agePadding), 'max_age' => $maxAge + $agePadding,
        ];
    }
}

<?php

namespace Tests\Unit;

use App\Models\InfantGrowthRecord;
use App\Support\ChildProfileDisplay;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class ChildProfileDisplayTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_age_uses_birth_date_and_handles_boundaries_and_invalid_dates(): void
    {
        CarbonImmutable::setTestNow('2026-09-26');
        $this->assertSame('24 years 10 months', ChildProfileDisplay::age('2001-11-09'));
        $this->assertSame('23 months', ChildProfileDisplay::age('2024-09-27'));
        $this->assertSame('2 years', ChildProfileDisplay::age('2024-09-26'));
        $this->assertSame('0 months', ChildProfileDisplay::age('2026-09-26'));
        foreach ([null, '', 'invalid', '2026-02-30', '2026-09-27'] as $date) {
            $this->assertSame('Age unavailable', ChildProfileDisplay::age($date));
        }
    }

    public function test_chart_uses_measurement_dates_instead_of_saved_age_and_does_not_fill_gaps(): void
    {
        CarbonImmutable::setTestNow('2026-09-26');
        $records = collect([
            new InfantGrowthRecord(['measured_at' => '2026-03-01', 'age_months' => 1, 'weight' => 6]),
            new InfantGrowthRecord(['measured_at' => '2026-02-01', 'age_months' => 50, 'weight' => 5]),
            new InfantGrowthRecord(['measured_at' => '2025-12-01', 'weight' => 4]),
            new InfantGrowthRecord(['measured_at' => '2026-10-01', 'weight' => 7]),
            new InfantGrowthRecord(['weight' => 8]),
        ]);
        // Persisted raw values are what the display reads, without touching a database.
        $records->each->syncOriginal();
        $chart = ChildProfileDisplay::chart($records, 'weight', '2026-01-01');
        $this->assertCount(2, $chart['points']);
        $this->assertEquals([1, 2], array_column($chart['points'], 'age'));
        $this->assertSame(6.0, $chart['latest']);
        $this->assertLessThan(5, $chart['min']);
        $this->assertGreaterThan(6, $chart['max']);
        $single = ChildProfileDisplay::chart($records->take(1), 'weight', '2026-01-01');
        $this->assertCount(1, $single['points']);
        $this->assertEquals(2, ($single['min_age'] + $single['max_age']) / 2);
        $this->assertFalse(ChildProfileDisplay::chart($records, 'height', 'invalid')['has']);
    }
}

<?php

namespace Tests\Unit;

use App\Models\Mother;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MotherAgeRiskTest extends TestCase
{
    #[DataProvider('maternalAgeRiskCases')]
    public function test_maternal_age_risk_is_derived_from_age(int $age, string $expected): void
    {
        $mother = Mother::make(['age' => $age]);

        $this->assertSame($expected, $mother->maternal_age_risk);
    }

    public static function maternalAgeRiskCases(): array
    {
        return [
            'below 18' => [17, 'Young Maternal Age Risk'],
            'standard lower boundary' => [18, 'Standard Maternal Age'],
            'standard upper boundary' => [34, 'Standard Maternal Age'],
            '35 and above' => [35, 'Advanced Maternal Age Risk'],
        ];
    }
}

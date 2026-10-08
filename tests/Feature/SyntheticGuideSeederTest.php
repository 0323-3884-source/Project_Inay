<?php

namespace Tests\Feature;

use App\Models\Mother;
use Database\Seeders\SyntheticGuideSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyntheticGuideSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_guide_profiles_are_complete_and_reseeding_preserves_edits(): void
    {
        $this->seed(SyntheticGuideSeeder::class);
        $this->assertSame(27, Mother::count());
        foreach (Mother::all() as $mother) {
            $this->assertMatchesRegularExpression('/^09[0-9]{9}$/', $mother->contact_number);
            $this->assertGreaterThanOrEqual(18, $mother->age);
            $this->assertSame('San Pablo City', $mother->municipality_city);
            $this->assertStringEndsWith('@example.test', $mother->email);
        }
        $mother = Mother::first();
        $mother->update(['contact_number' => '09170000000']);
        $this->seed(SyntheticGuideSeeder::class);
        $this->assertSame(27, Mother::count());
        $this->assertSame('09170000000', $mother->fresh()->contact_number);
    }
}

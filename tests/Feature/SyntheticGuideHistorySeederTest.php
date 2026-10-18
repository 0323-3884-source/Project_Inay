<?php

namespace Tests\Feature;

use App\Models\Mother;
use Database\Seeders\SyntheticGuideSeeder;
use Database\Seeders\SyntheticGuideHistorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SyntheticGuideHistorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_profile_has_history_learning_and_real_documents_without_duplicates(): void
    {
        Storage::fake('public');
        $this->seed(SyntheticGuideSeeder::class);
        $this->seed(SyntheticGuideHistorySeeder::class);
        foreach (Mother::all() as $mother) {
            $this->assertSame(3, $mother->maternalMonitoringRecords()->count());
            $weeks = $mother->maternalMonitoringRecords()->orderBy('recorded_at')->pluck('pregnancy_week')->all();
            $this->assertNotContains(null, $weeks);
            $this->assertSame(4, $weeks[1] - $weeks[0]);
            $this->assertSame(4, $weeks[2] - $weeks[1]);
            $this->assertSame(3, $mother->inayKaalamanCheckups()->count());
            $this->assertSame(3, $mother->inayKaalamanUploads()->count());
            $this->assertGreaterThan(0, $mother->inayKaalamanProgress()->whereNotNull('completed_at')->count());
            foreach ($mother->inayKaalamanUploads as $upload) {
                $this->assertStringStartsWith('%PDF-', Storage::disk('public')->get($upload->path));
            }
        }
        $mother = Mother::first();
        $visit = $mother->maternalMonitoringRecords()->first();
        $expectedWeek = $visit->pregnancy_week;
        $visit->update(['notes'=>'Edited sample visit', 'pregnancy_week' => null]);
        $count = $mother->inayKaalamanProgress()->count();
        $this->seed(SyntheticGuideHistorySeeder::class);
        $this->assertSame('Edited sample visit', $visit->fresh()->notes);
        $this->assertSame($expectedWeek, $visit->fresh()->pregnancy_week);
        $this->assertSame(3, $mother->maternalMonitoringRecords()->count());
        $this->assertSame($count, $mother->inayKaalamanProgress()->count());
        $this->assertSame(3, $mother->inayKaalamanUploads()->count());
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ResponsivePaginationTest extends TestCase
{
    public function test_default_pagination_uses_compact_controls_and_preserves_filters(): void
    {
        $paginator = new LengthAwarePaginator(range(1, 15), 30, 15, 1, ['path'=>'/dswd/f1kd/reports']);
        $paginator->appends(['month'=>'2026-10', 'barangay'=>'San Jose']);
        $html = (string) $paginator->links();
        $this->assertStringContainsString('site-pagination', $html);
        $this->assertStringContainsString('month=2026-10', $html);
        $this->assertStringContainsString('barangay=San', $html);
        $this->assertStringContainsString('aria-disabled="true"', $html);
        $this->assertStringNotContainsString('<svg', $html);
        $this->assertStringContainsString('Page 1', $html);
    }
}

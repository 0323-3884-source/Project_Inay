<?php

namespace Tests\Feature;

use App\Models\EducationalContent;
use App\Models\InayKaalamanProgress;
use App\Models\Mother;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfographicLearningTest extends TestCase
{
    use RefreshDatabase;

    private function mother(string $email = 'infographic@example.test'): Mother
    {
        return Mother::create([
            'first_name' => 'Maria', 'last_name' => 'Santos', 'email' => $email,
            'password' => bcrypt('test-password'), 'barangay' => 'Concepcion',
            'contact_number' => '09170000000', 'is_4ps_beneficiary' => false,
        ]);
    }

    private function payload(string $status = 'in_progress'): array
    {
        return ['month' => 1, 'activity_type' => 'infographic', 'item_key' => 'month-1-infographic', 'status' => $status];
    }

    public function test_library_renders_published_content_and_preserves_legacy_progress(): void
    {
        $mother = $this->mother();
        InayKaalamanProgress::create([
            'mother_id' => $mother->id, ...$this->payload('reviewed'),
            'started_at' => now(), 'completed_at' => now(),
        ]);
        EducationalContent::create([
            'stage_key' => 'first-trimester', 'month' => 1, 'title' => 'Hidden draft infographic',
            'infographic_sections' => [['Draft', 'Not published']], 'is_published' => false,
        ]);
        $response = $this->withSession(['auth_role' => 'mother', 'auth_id' => $mother->id])
            ->get('/inay-kaalaman')->assertOk()
            ->assertSee('Paglilihi at Bagong Simula Checklist')
            ->assertSee('data-key="month-1-infographic"', false)
            ->assertSee('Natapos')->assertSee('data-infographic-end', false)
            ->assertSee('Hindi ito kapalit')->assertDontSee('Hidden draft infographic')
            ->assertSee('data-upload-form', false)->assertSee('data-youtube-video', false);

        // Optional rendered fixture for the browser interaction checks, using only in-memory test data.
        if ($path = getenv('INAY_BROWSER_FIXTURE')) {
            file_put_contents($path, $response->getContent());
        }
    }

    public function test_start_completion_and_repeated_requests_keep_one_record_and_never_regress(): void
    {
        $mother = $this->mother();
        $this->withSession(['auth_role' => 'mother', 'auth_id' => $mother->id]);
        $this->postJson('/inay-kaalaman/progress', $this->payload())->assertOk()->assertJsonPath('progress.status', 'in_progress');
        $record = InayKaalamanProgress::sole();
        $this->assertNotNull($record->started_at);
        $this->assertNull($record->completed_at);
        $this->postJson('/inay-kaalaman/progress', $this->payload('reviewed'))->assertOk()
            ->assertJsonPath('month_summary.infographic.status', 'reviewed');
        $completedAt = $record->fresh()->completed_at;
        $this->travel(5)->minutes();
        $this->postJson('/inay-kaalaman/progress', $this->payload())->assertOk()->assertJsonPath('progress.status', 'reviewed');
        $this->postJson('/inay-kaalaman/progress', $this->payload('reviewed'))->assertOk();
        $this->assertDatabaseCount('inay_kaalaman_progress', 1);
        $this->assertTrue($record->fresh()->completed_at->equalTo($completedAt));
        $this->assertSame('Paglilihi at Bagong Simula Checklist', $record->fresh()->item_title);
        $this->withSession(['auth_role' => 'mother', 'auth_id' => $mother->id])->get('/inay-kaalaman')
            ->assertOk()->assertSee('data-status="reviewed"', false);
    }

    public function test_progress_is_mother_scoped_and_rejects_unavailable_or_wrong_month_content(): void
    {
        $this->postJson('/inay-kaalaman/progress', $this->payload())->assertUnauthorized();
        $mother = $this->mother();
        $other = $this->mother('other@example.test');
        $this->withSession(['auth_role' => 'staff', 'auth_id' => $mother->id])
            ->postJson('/inay-kaalaman/progress', $this->payload())->assertUnauthorized();
        $this->withSession(['auth_role' => 'mother', 'auth_id' => $mother->id])
            ->postJson('/inay-kaalaman/progress', [...$this->payload(), 'mother_id' => $other->id])->assertOk();
        $this->assertDatabaseMissing('inay_kaalaman_progress', ['mother_id' => $other->id]);
        $this->postJson('/inay-kaalaman/progress', [...$this->payload(), 'month' => 2])->assertUnprocessable();
        $this->postJson('/inay-kaalaman/progress', [...$this->payload(), 'item_key' => 'fake-infographic'])->assertUnprocessable();
        $this->postJson('/inay-kaalaman/progress', $this->payload('watched'))->assertUnprocessable();
        EducationalContent::where('infographic_key', 'month-1-infographic')->update(['is_published' => false]);
        $this->postJson('/inay-kaalaman/progress', $this->payload('reviewed'))->assertUnprocessable();
        $this->assertDatabaseCount('inay_kaalaman_progress', 1);
    }

    public function test_uploaded_and_stage_infographics_have_independent_persistent_progress(): void
    {
        $mother = $this->mother();
        $content = EducationalContent::create([
            'stage_key' => 'neonatal-care', 'month' => null, 'calendar_month' => 11,
            'title' => 'Uploaded vaccination guide', 'category' => 'Vaccination',
            'infographic_path' => 'educational-content/infographics/example.png',
            'is_published' => true, 'published_at' => now(),
        ]);
        $this->withSession(['auth_role' => 'mother', 'auth_id' => $mother->id])
            ->get('/inay-kaalaman')->assertOk()->assertDontSee('Uploaded vaccination guide')
            ->assertDontSee('Paggaling at Suporta sa Pagpapasuso')
            ->assertDontSee('Unang mga Araw ni Baby')
            ->assertDontSee('Bakuna at Child Health Records');
        foreach (['in_progress', 'reviewed', 'reviewed'] as $status) {
            $this->postJson('/inay-kaalaman/progress', [
                'month' => $content->infographic_progress_month, 'activity_type' => 'infographic',
                'item_key' => $content->infographic_progress_key, 'status' => $status,
            ])->assertOk()->assertJsonPath('month_summary.infographic.status', 'not_started');
        }
        $this->assertDatabaseCount('inay_kaalaman_progress', 1);
        $this->assertDatabaseHas('inay_kaalaman_progress', ['item_key' => $content->infographic_progress_key, 'status' => 'reviewed']);
    }
}

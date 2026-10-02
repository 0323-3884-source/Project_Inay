<?php

namespace Tests\Feature;

use App\Models\InayKaalamanUpload;
use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Support\MotherCareRecordPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MotherDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private function mother(string $email = 'documents@example.test'): Mother
    {
        return Mother::create(['first_name' => 'Ana', 'last_name' => 'Cruz', 'email' => $email, 'password' => 'unused', 'barangay' => 'San Jose', 'contact_number' => '09170000000']);
    }

    public function test_uploads_from_both_entry_points_share_one_library_and_staff_tabs(): void
    {
        Storage::fake('public');
        $mother = $this->mother();
        $this->withSession(['auth_role' => 'mother', 'auth_id' => $mother->id]);
        foreach (['/mother/documents' => '/mother/documents', '/inay-kaalaman/uploads' => '/inay-kaalaman'] as $url => $redirect) {
            $this->post($url, ['month' => 2, 'record_type' => 'Receipts', 'document' => UploadedFile::fake()->create('receipt.pdf', 12, 'application/pdf')])->assertRedirect($redirect);
        }
        $this->assertDatabaseCount('inay_kaalaman_uploads', 1);
        $this->get('/mother/documents')->assertOk()->assertSee('receipt.pdf')->assertSee('data-upload-open', false);
        $this->get('/inay-kaalaman')->assertOk()->assertSee('receipt.pdf');
        $this->post('/inay-kaalaman/uploads', ['month' => 3, 'record_type' => 'Checkup Records', 'document' => UploadedFile::fake()->create('checkup.pdf', 12, 'application/pdf')])->assertRedirect('/inay-kaalaman');
        $motherPage = $this->get('/mother/documents')->assertOk()->assertSee('checkup.pdf')->assertSee('receipt.pdf');
        if ($directory = getenv('INAY_DOCUMENT_FIXTURES')) {
            file_put_contents($directory.'/mother.html', $motherPage->getContent());
        }

        $staff = ProgramStaff::create(['first_name' => 'Staff', 'last_name' => 'Member', 'email' => 'documents-staff@example.test', 'password' => 'unused', 'staff_id' => 'STAFF-DOCS', 'position' => 'Program Staff', 'role' => 'Midwife', 'contact_number' => '09170000000', 'approval_status' => 'approved']);
        $staff->casefileMothers()->attach($mother);
        $this->withSession(['auth_role' => 'staff', 'auth_id' => $staff->id]);
        $html = $this->get(route('staff.mothers.show', $mother))->assertOk()->assertSee('data-casefile-tab="documents"', false)->getContent();
        if ($directory = getenv('INAY_DOCUMENT_FIXTURES')) {
            file_put_contents($directory.'/staff.html', $html);
        }
        foreach (['learning-documents', 'documents'] as $panel) {
            $start = strpos($html, 'data-casefile-panel="'.$panel.'"');
            $end = strpos($html, '</section>', $start);
            // The learning panel contains nested sections; its file cards follow that first section.
            $segment = $panel === 'documents' ? substr($html, $start, $end - $start) : substr($html, $start, strpos($html, 'data-casefile-panel="documents"') - $start);
            $this->assertStringContainsString('receipt.pdf', $segment);
            $this->assertStringContainsString('checkup.pdf', $segment);
        }
        $this->mock(MotherCareRecordPdf::class, function ($mock) {
            $mock->shouldReceive('render')->once()->withArgs(fn ($html) => str_contains($html, 'receipt.pdf') && str_contains($html, 'Submitted Documents'))->andReturn('%PDF-1.4 test');
        });
        $this->get(route('staff.mothers.pdf', [$mother, 'section' => 'documents']))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_mothers_can_only_view_download_and_remove_their_own_documents(): void
    {
        Storage::fake('public');
        $mother = $this->mother();
        $other = $this->mother('other-documents@example.test');
        $upload = InayKaalamanUpload::create(['mother_id' => $mother->id, 'month' => 1, 'record_type' => 'Receipts', 'original_name' => 'private-receipt.pdf', 'path' => 'records/private.pdf', 'mime_type' => 'application/pdf', 'size' => 40]);
        Storage::disk('public')->put($upload->path, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
        $this->get('/mother/documents')->assertForbidden();
        $this->withSession(['auth_role' => 'mother', 'auth_id' => $other->id]);
        $this->get('/mother/documents')->assertOk()->assertDontSee('private-receipt.pdf');
        foreach (['preview', 'download'] as $action) $this->get(route('mother.documents.'.$action, $upload))->assertForbidden();
        $this->delete(route('mother.documents.destroy', $upload))->assertForbidden();
        $this->withSession(['auth_role' => 'mother', 'auth_id' => $mother->id]);
        $this->get(route('mother.documents.preview', $upload))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('mother.documents.download', $upload))->assertDownload('private-receipt.pdf');
        Storage::disk('public')->put($upload->path, '<html>Unsupported preview</html>');
        $this->get(route('mother.documents.preview', $upload))->assertStatus(415);
        $this->delete(route('mother.documents.destroy', $upload))->assertRedirect('/mother/documents');
        Storage::disk('public')->assertMissing($upload->path);
        $this->assertDatabaseMissing('inay_kaalaman_uploads', ['id' => $upload->id]);
        $this->get('/inay-kaalaman')->assertOk()->assertDontSee('private-receipt.pdf');
    }

    public function test_upload_validation_and_dialog_recovery(): void
    {
        Storage::fake('public');
        $mother = $this->mother();
        $this->withSession(['auth_role' => 'mother', 'auth_id' => $mother->id]);
        $this->from('/mother/documents')->post('/mother/documents', ['month' => 11, 'record_type' => 'Invalid', 'document' => UploadedFile::fake()->create('large.pdf', 6000, 'application/pdf')])->assertSessionHasErrors(['month', 'record_type', 'document']);
        $this->get('/mother/documents')->assertOk()->assertSee('data-has-errors="true"', false);
        $this->assertDatabaseCount('inay_kaalaman_uploads', 0);
    }
}

<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\EducationalContent;
use App\Models\Mother;
use App\Models\ProgramStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EducationalContentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_and_unpublish_youtube_content_for_the_mother_care_path(): void
    {
        $admin = $this->createAdmin('education-admin');
        $mother = $this->createMother('education-mother@example.test');

        $this->withAdminSession($admin)
            ->get('/admin/educational-content')
            ->assertOk()
            ->assertSee('Educational Content Management')
            ->assertSee('Create Content');

        $this->withAdminSession($admin)
            ->post('/admin/educational-content', [
                'stage_key' => 'first-trimester',
                'month' => 1,
                'title' => 'Month 1 Prenatal Basics',
                'description' => 'Early care reminders for the first month.',
                'output_description' => 'Pregnancy foundation saved.',
                'display_order' => 3,
                'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
            ])
            ->assertRedirect('/admin/educational-content');

        $content = EducationalContent::first();

        $this->assertNotNull($content);
        $this->assertFalse($content->is_published);
        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ', $content->youtube_embed_url);

        $this->withMotherSession($mother)
            ->get('/inay-kaalaman')
            ->assertOk()
            ->assertDontSee('Month 1 Prenatal Basics');

        $this->withAdminSession($admin)
            ->patch('/admin/educational-content/'.$content->id.'/publish')
            ->assertRedirect('/admin/educational-content');

        $this->withMotherSession($mother)
            ->get('/inay-kaalaman')
            ->assertOk()
            ->assertSee('Month 1 Prenatal Basics')
            ->assertSee('Pregnancy foundation saved.')
            ->assertSee('https://www.youtube.com/embed/dQw4w9WgXcQ', false);

        $this->withAdminSession($admin)
            ->patch('/admin/educational-content/'.$content->id.'/unpublish')
            ->assertRedirect('/admin/educational-content');

        $this->withMotherSession($mother)
            ->get('/inay-kaalaman')
            ->assertOk()
            ->assertDontSee('Month 1 Prenatal Basics');

        $this->withAdminSession($admin)
            ->post('/admin/educational-content', [
                'stage_key' => 'neonatal-care',
                'title' => 'Newborn First Days Guide',
                'description' => 'Whole-stage newborn education for the first 28 days.',
                'output_description' => 'Newborn care readiness improved.',
                'display_order' => 1,
                'is_published' => '1',
            ])
            ->assertRedirect('/admin/educational-content');

        $this->withMotherSession($mother)
            ->get('/inay-kaalaman')
            ->assertOk()
            ->assertSee('Newborn First Days Guide')
            ->assertSee('Whole-stage newborn education for the first 28 days.');
    }

    public function test_uploaded_videos_and_infographics_use_storage_and_are_removed_from_mother_page_after_delete(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin('media-admin');
        $mother = $this->createMother('education-media-mother@example.test');

        $this->withAdminSession($admin)
            ->post('/admin/educational-content', [
                'stage_key' => 'second-trimester',
                'month' => 4,
                'title' => 'Second Trimester Movement Guide',
                'description' => 'Watch for healthy movement patterns.',
                'output_description' => 'Movement awareness completed.',
                'display_order' => 1,
                'video_file' => UploadedFile::fake()->create('movement-guide.mp4', 128, 'video/mp4'),
                'infographic_file' => $this->fakePng('movement-guide.png'),
                'is_published' => '1',
            ])
            ->assertRedirect('/admin/educational-content');

        $content = EducationalContent::first();

        $this->assertNotNull($content);
        $this->assertTrue($content->is_published);
        $this->assertStringStartsWith('educational-content/videos/', $content->uploaded_video_path);
        $this->assertStringStartsWith('educational-content/infographics/', $content->infographic_path);
        $this->assertStringNotContainsString('movement-guide.mp4', $content->uploaded_video_path);
        $this->assertStringNotContainsString('movement-guide.png', $content->infographic_path);
        Storage::disk('public')->assertExists($content->uploaded_video_path);
        Storage::disk('public')->assertExists($content->infographic_path);

        $this->withMotherSession($mother)
            ->get('/inay-kaalaman')
            ->assertOk()
            ->assertSee('Second Trimester Movement Guide')
            ->assertSee('<video controls', false)
            ->assertSee('<img src="/storage/educational-content/infographics/', false);

        $videoPath = $content->uploaded_video_path;
        $infographicPath = $content->infographic_path;

        $this->withAdminSession($admin)
            ->delete('/admin/educational-content/'.$content->id)
            ->assertRedirect('/admin/educational-content');

        $this->assertDatabaseMissing('educational_contents', ['id' => $content->id]);
        Storage::disk('public')->assertMissing($videoPath);
        Storage::disk('public')->assertMissing($infographicPath);

        $this->withMotherSession($mother)
            ->get('/inay-kaalaman')
            ->assertOk()
            ->assertDontSee('Second Trimester Movement Guide');
    }

    public function test_only_admins_manage_educational_content_and_youtube_links_are_validated(): void
    {
        $admin = $this->createAdmin('validation-admin');
        $mother = $this->createMother('education-permission-mother@example.test');
        $staff = ProgramStaff::create([
            'first_name' => 'Nora',
            'middle_name' => null,
            'last_name' => 'Dela Cruz',
            'email' => 'education-staff@example.test',
            'password' => Hash::make('password123'),
            'staff_id' => 'STAFF-EDU-001',
            'position' => 'Program Staff',
            'contact_number' => '09170000001',
        ]);

        $this->get('/admin/educational-content')->assertRedirect('/admin/login');
        $this->withMotherSession($mother)->get('/admin/educational-content')->assertRedirect('/admin/login');
        $this->withStaffSession($staff)->get('/admin/educational-content')->assertRedirect('/admin/login');

        $this->withAdminSession($admin)
            ->from('/admin/educational-content')
            ->post('/admin/educational-content', [
                'stage_key' => 'third-trimester',
                'month' => 7,
                'title' => 'Invalid Video Lesson',
                'display_order' => 0,
                'youtube_url' => 'https://example.com/not-youtube',
            ])
            ->assertRedirect('/admin/educational-content')
            ->assertSessionHasErrors('youtube_url');

        $this->assertDatabaseMissing('educational_contents', [
            'title' => 'Invalid Video Lesson',
        ]);
    }

    private function createAdmin(string $username): AdminUser
    {
        return AdminUser::create([
            'username' => $username,
            'password' => Hash::make('password123'),
        ]);
    }

    private function createMother(string $email): Mother
    {
        return Mother::create([
            'first_name' => 'Maria',
            'middle_name' => null,
            'last_name' => 'Santos',
            'email' => $email,
            'password' => Hash::make('password123'),
            'barangay' => 'Concepcion',
            'contact_number' => '09923245009',
            'is_4ps_beneficiary' => false,
        ]);
    }

    private function withAdminSession(AdminUser $admin): self
    {
        return $this->withSession([
            'admin_authenticated' => true,
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
        ]);
    }

    private function withMotherSession(Mother $mother): self
    {
        return $this->withSession([
            'auth_role' => 'mother',
            'auth_id' => $mother->id,
            'auth_name' => $mother->full_name,
            'auth_email' => $mother->email,
        ]);
    }

    private function withStaffSession(ProgramStaff $staff): self
    {
        return $this->withSession([
            'auth_role' => 'staff',
            'auth_id' => $staff->id,
            'auth_name' => $staff->full_name,
            'auth_email' => $staff->email,
        ]);
    }

    private function fakePng(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}

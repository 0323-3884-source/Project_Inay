<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\ProgramStaff;
use App\Mail\ProgramStaffApprovalStatusMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProgramStaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_registration_saves_contact_role_and_id_photo(): void
    {
        Storage::fake('public');

        $this->get(route('staff.register'))
            ->assertOk()
            ->assertSee('id="staffSubmit"', false)
            ->assertSee('auth-submit requires-consent', false)
            ->assertSee('syncStaffSubmit', false);

        $this->post(route('staff.register.store'), [
            'full_name' => 'Ana Dela Cruz',
            'email' => 'ana.midwife@example.test',
            'password' => 'password123',
            'staff_id' => 'HW-1001',
            'role' => 'Midwife',
            'contact_number' => '09171112222',
            'healthcare_worker_id_photo' => $this->fakePngUpload('ana-id.png'),
            'privacy_policy' => '1',
        ])
            ->assertRedirect('/login')
            ->assertSessionHasNoErrors();

        $staff = ProgramStaff::where('email', 'ana.midwife@example.test')->firstOrFail();

        $this->assertSame('Midwife', $staff->role);
        $this->assertSame('Midwife', $staff->position);
        $this->assertSame('09171112222', $staff->contact_number);
        $this->assertSame('HW-1001', $staff->staff_id);
        $this->assertNotNull($staff->healthcare_worker_id_photo_path);
        $this->assertStringStartsWith('healthcare-worker-ids/', $staff->healthcare_worker_id_photo_path);
        $this->assertStringNotContainsString('\\', $staff->healthcare_worker_id_photo_path);
        $this->assertStringNotContainsString(storage_path(), $staff->healthcare_worker_id_photo_path);
        $this->assertSame('/storage/'.$staff->healthcare_worker_id_photo_path, $staff->healthcare_worker_id_photo_url);
        $this->assertNull($staff->healthcare_worker_id_verified_at);
        $this->assertSame('pending', $staff->approval_status);
        Storage::disk('public')->assertExists($staff->healthcare_worker_id_photo_path);

        $this->assertFalse(session()->has('auth_role'));
    }

    public function test_pending_program_staff_cannot_login_until_admin_approves_and_email_is_sent(): void
    {
        Mail::fake();

        $admin = $this->createAdmin('approval-admin', 'secret-pass');
        $staff = $this->createStaff([
            'email' => 'pending.staff@example.test',
            'approval_status' => 'pending',
        ]);

        $this->from('/login')->post('/login', [
            'role' => 'staff',
            'email' => $staff->email,
            'password' => 'password123',
        ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email')
            ->assertSessionMissing('auth_role');

        $this->withSession([
            'admin_authenticated' => true,
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
        ])
            ->patch(route('admin.program-staff.approve', $staff))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $staff->refresh();

        $this->assertSame('approved', $staff->approval_status);
        $this->assertNotNull($staff->approved_at);
        $this->assertSame($admin->id, $staff->approved_by_admin_id);

        Mail::assertSent(ProgramStaffApprovalStatusMail::class, function (ProgramStaffApprovalStatusMail $mail) use ($staff): bool {
            return $mail->hasTo($staff->email) && $mail->status === 'approved';
        });

        $this->post('/login', [
            'role' => 'staff',
            'email' => $staff->email,
            'password' => 'password123',
        ])
            ->assertRedirect('/staff/dashboard')
            ->assertSessionHas('auth_role', 'staff');
    }

    public function test_program_staff_approval_email_uses_project_inay_sender_name(): void
    {
        config([
            'mail.from.address' => 'noreply@project-inay.test',
            'mail.from.name' => 'Project INAY',
        ]);

        $staff = $this->createStaff([
            'email' => 'sender-name.staff@example.test',
            'approval_status' => 'approved',
        ]);

        $mail = (new ProgramStaffApprovalStatusMail($staff, 'approved'))->build();

        $this->assertSame([
            [
                'name' => 'Project INAY',
                'address' => 'noreply@project-inay.test',
            ],
        ], $mail->from);
    }

    public function test_admin_can_reject_program_staff_and_email_notification(): void
    {
        Mail::fake();

        $admin = $this->createAdmin('reject-admin', 'secret-pass');
        $staff = $this->createStaff([
            'email' => 'reject.staff@example.test',
            'approval_status' => 'pending',
        ]);

        $this->withSession([
            'admin_authenticated' => true,
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
        ])
            ->patch(route('admin.program-staff.reject', $staff), [
                'rejection_reason' => 'The submitted ID could not be confirmed.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $staff->refresh();

        $this->assertSame('rejected', $staff->approval_status);
        $this->assertNotNull($staff->rejected_at);
        $this->assertSame('The submitted ID could not be confirmed.', $staff->rejection_reason);

        Mail::assertSent(ProgramStaffApprovalStatusMail::class, function (ProgramStaffApprovalStatusMail $mail) use ($staff): bool {
            return $mail->hasTo($staff->email) && $mail->status === 'rejected';
        });

        $this->from('/login')->post('/login', [
            'role' => 'staff',
            'email' => $staff->email,
            'password' => 'password123',
        ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email')
            ->assertSessionMissing('auth_role');
    }

    public function test_admin_can_view_update_verify_and_unverify_program_staff_identity(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin('staff-admin', 'secret-pass');
        $staff = $this->createStaff();
        $adminSession = [
            'admin_authenticated' => true,
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
        ];

        $this->withSession($adminSession)
            ->get(route('admin.program-staff.index'))
            ->assertOk()
            ->assertSee('Program Staff Management')
            ->assertSee($staff->full_name)
            ->assertSee($staff->contact_number)
            ->assertSee('No ID');

        $this->withSession($adminSession)
            ->patch(route('admin.program-staff.update', $staff), [
                'first_name' => 'Ana',
                'middle_name' => 'Mae',
                'last_name' => 'Santos',
                'email' => 'ana.santos@example.test',
                'staff_id' => 'HW-UPDATED',
                'role' => 'Nurse',
                'contact_number' => '09175556666',
                'healthcare_worker_id_photo' => $this->fakePngUpload('updated-id.png'),
            ])
            ->assertRedirect(route('admin.program-staff.show', $staff))
            ->assertSessionHasNoErrors();

        $staff->refresh();

        $this->assertSame('Ana Mae Santos', $staff->full_name);
        $this->assertSame('Nurse', $staff->role);
        $this->assertSame('Nurse', $staff->position);
        $this->assertSame('09175556666', $staff->contact_number);
        $this->assertNotNull($staff->healthcare_worker_id_photo_path);
        $this->assertNull($staff->healthcare_worker_id_verified_at);
        Storage::disk('public')->assertExists($staff->healthcare_worker_id_photo_path);

        $this->withSession($adminSession)
            ->patch(route('admin.program-staff.verify', $staff))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $staff->refresh();

        $this->assertNotNull($staff->healthcare_worker_id_verified_at);
        $this->assertSame($admin->id, $staff->healthcare_worker_id_verified_by_admin_id);

        $this->withSession($adminSession)
            ->get(route('admin.program-staff.show', $staff))
            ->assertOk()
            ->assertSee('Verified')
            ->assertSee('staff-admin')
            ->assertSee('09175556666')
            ->assertSee('Nurse');

        $this->withSession($adminSession)
            ->patch(route('admin.program-staff.unverify', $staff))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNull($staff->fresh()->healthcare_worker_id_verified_at);
    }

    public function test_admin_id_preview_uses_public_storage_url_and_missing_file_placeholder(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin('image-admin', 'secret-pass');
        $photoPath = 'healthcare-worker-ids/existing-id.png';
        Storage::disk('public')->put($photoPath, 'fake-image');

        $staff = $this->createStaff([
            'email' => 'existing-image@example.test',
            'healthcare_worker_id_photo_path' => Storage::disk('public')->path($photoPath),
        ]);

        $adminSession = [
            'admin_authenticated' => true,
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
        ];

        $this->assertSame($photoPath, $staff->healthcare_worker_id_photo_path);

        $this->withSession($adminSession)
            ->get(route('admin.program-staff.show', $staff))
            ->assertOk()
            ->assertSee('src="/storage/'.$photoPath.'"', false)
            ->assertDontSee('ID image unavailable');

        Storage::disk('public')->delete($photoPath);

        $this->withSession($adminSession)
            ->get(route('admin.program-staff.show', $staff))
            ->assertOk()
            ->assertSee('ID image unavailable')
            ->assertDontSee('src="/storage/'.$photoPath.'"', false);
    }

    public function test_program_staff_id_photo_path_rejects_temporary_upload_paths(): void
    {
        Storage::fake('public');

        $staff = $this->createStaff([
            'email' => 'temp-path@example.test',
            'healthcare_worker_id_photo_path' => 'C:\\xampp\\tmp\\php1234.tmp',
        ]);

        $this->assertNull($staff->healthcare_worker_id_photo_path);
        $this->assertNull($staff->healthcare_worker_id_photo_url);
    }

    public function test_admin_cannot_verify_staff_without_an_id_photo(): void
    {
        $admin = $this->createAdmin('staff-admin-no-photo', 'secret-pass');
        $staff = $this->createStaff(['email' => 'no-photo@example.test']);

        $this->withSession([
            'admin_authenticated' => true,
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
        ])
            ->from(route('admin.program-staff.show', $staff))
            ->patch(route('admin.program-staff.verify', $staff))
            ->assertRedirect(route('admin.program-staff.show', $staff))
            ->assertSessionHasErrors('healthcare_worker_id_photo');

        $this->assertNull($staff->fresh()->healthcare_worker_id_verified_at);
    }

    public function test_admin_can_delete_program_staff_and_stored_id_photo(): void
    {
        Storage::fake('public');

        $photoPath = 'healthcare-worker-ids/delete-me.png';
        Storage::disk('public')->put($photoPath, 'fake-image');

        $admin = $this->createAdmin('delete-admin', 'secret-pass');
        $staff = $this->createStaff([
            'email' => 'delete.staff@example.test',
            'healthcare_worker_id_photo_path' => $photoPath,
        ]);

        $this->withSession([
            'admin_authenticated' => true,
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
        ])
            ->delete(route('admin.program-staff.destroy', $staff))
            ->assertRedirect(route('admin.program-staff.index'));

        $this->assertDatabaseMissing('program_staff', ['id' => $staff->id]);
        Storage::disk('public')->assertMissing($photoPath);
    }

    private function createAdmin(string $username, string $password): AdminUser
    {
        return AdminUser::create([
            'username' => $username,
            'password' => Hash::make($password),
        ]);
    }

    private function createStaff(array $overrides = []): ProgramStaff
    {
        return ProgramStaff::create(array_merge([
            'first_name' => 'Ana',
            'middle_name' => null,
            'last_name' => 'Cruz',
            'email' => 'ana.cruz@example.test',
            'password' => Hash::make('password123'),
            'staff_id' => 'HW-0001',
            'position' => 'Program Staff',
            'role' => 'Program Staff',
            'contact_number' => '09171111111',
            'approval_status' => 'approved',
        ], $overrides));
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'staff-id-').'.png';
        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='
        ));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }
}

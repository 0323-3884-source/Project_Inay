<?php

namespace Tests\Feature;

use App\Models\AdminStaffMessage;
use App\Models\AdminStaffThread;
use App\Models\AdminUser;
use App\Models\ProgramStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminStaffMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_messages_page_creates_program_staff_threads_and_management_buttons(): void
    {
        $admin = $this->createAdmin('message-admin');
        $staff = $this->createStaff('Ana', 'Cruz', 'ana.admin-message@example.test', 'STAFF-ADMIN-MESSAGE');

        $this->withSession($this->adminSession($admin))
            ->get(route('admin.program-staff.index'))
            ->assertOk()
            ->assertSee('Program Staff Management')
            ->assertSee('Chat');

        $this->withSession($this->adminSession($admin))
            ->get(route('admin.program-staff.show', $staff))
            ->assertOk()
            ->assertSee('Chat Staff');

        $this->withSession($this->adminSession($admin))
            ->get(route('admin.staff-messages.index', ['staff' => $staff->id]))
            ->assertOk()
            ->assertSee('Admin Messages')
            ->assertSee('Admin Messages are limited to Admin and Program Staff accounts only.');

        $this->assertDatabaseHas('admin_staff_threads', [
            'admin_user_id' => $admin->id,
            'program_staff_id' => $staff->id,
        ]);

        $thread = AdminStaffThread::firstOrFail();
        $payload = $this->withSession($this->adminSession($admin))
            ->getJson(route('admin-staff-messages.threads.index', ['selected' => $thread->id]))
            ->assertOk()
            ->json('threads');

        $this->assertCount(1, $payload);
        $this->assertSame($staff->full_name, $payload[0]['participant']['name']);
        $this->assertSame($staff->contact_number, $payload[0]['participant']['contact_number']);

        config(['contacts.admin_phone' => '+639171234567']);
        $this->withSession($this->staffSession($staff))
            ->getJson(route('admin-staff-messages.threads.index', ['selected' => $thread->id]))
            ->assertOk()
            ->assertJsonPath('threads.0.participant.contact_number', '+639171234567');
    }

    public function test_admin_and_program_staff_can_send_read_reply_and_unsend_messages(): void
    {
        $admin = $this->createAdmin('conversation-admin');
        $staff = $this->createStaff('Miguel', 'Isles', 'miguel.admin-message@example.test', 'STAFF-MIGUEL-MESSAGE');

        $this->withSession($this->adminSession($admin))
            ->get(route('admin.staff-messages.index', ['staff' => $staff->id]))
            ->assertOk();

        $thread = AdminStaffThread::firstOrFail();

        $this->withSession($this->adminSession($admin))
            ->postJson(route('admin-staff-messages.threads.messages.store', $thread), [
                'message' => 'Please review the account note.',
            ])
            ->assertCreated()
            ->assertJsonPath('message.sender_role', AdminStaffMessage::ROLE_ADMIN)
            ->assertJsonPath('message.is_own', true);

        $adminMessage = AdminStaffMessage::firstOrFail();

        $messagesForStaff = $this->withSession($this->staffSession($staff))
            ->getJson(route('admin-staff-messages.threads.messages.index', $thread))
            ->assertOk()
            ->json('messages');

        $this->assertFalse($messagesForStaff[0]['is_own']);
        $this->assertSame('Please review the account note.', $messagesForStaff[0]['message']);
        $this->assertDatabaseHas('admin_staff_messages', [
            'id' => $adminMessage->id,
            'is_read' => true,
        ]);

        $this->withSession($this->staffSession($staff))
            ->postJson(route('admin-staff-messages.messages.unsend', $adminMessage))
            ->assertForbidden();

        $this->withSession($this->staffSession($staff))
            ->postJson(route('admin-staff-messages.threads.messages.store', $thread), [
                'message' => 'Noted, admin. I will update it.',
            ])
            ->assertCreated()
            ->assertJsonPath('message.sender_role', AdminStaffMessage::ROLE_PROGRAM_STAFF)
            ->assertJsonPath('message.is_own', true);

        $this->flushSession();

        $this->withSession($this->adminSession($admin))
            ->postJson(route('admin-staff-messages.messages.unsend', $adminMessage))
            ->assertOk()
            ->assertJsonPath('message.message', 'This message was unsent.');

        $this->assertDatabaseHas('admin_staff_messages', [
            'id' => $adminMessage->id,
            'is_unsent' => true,
        ]);
    }

    public function test_admin_staff_messages_block_mothers_unrelated_staff_and_other_admins(): void
    {
        $admin = $this->createAdmin('owner-admin');
        $otherAdmin = $this->createAdmin('other-admin');
        $staff = $this->createStaff('Ana', 'Private', 'ana.private-admin-message@example.test', 'STAFF-PRIVATE');
        $otherStaff = $this->createStaff('Other', 'Staff', 'other.private-admin-message@example.test', 'STAFF-OTHER-PRIVATE');

        $this->withSession($this->adminSession($admin))
            ->get(route('admin.staff-messages.index', ['staff' => $staff->id]))
            ->assertOk();

        $thread = AdminStaffThread::where('admin_user_id', $admin->id)
            ->where('program_staff_id', $staff->id)
            ->firstOrFail();

        $this->withSession([
            'auth_role' => 'mother',
            'auth_id' => 1,
            'auth_name' => 'Mother User',
        ])
            ->getJson(route('admin-staff-messages.threads.index'))
            ->assertUnauthorized();

        $this->withSession($this->staffSession($otherStaff))
            ->getJson(route('admin-staff-messages.threads.messages.index', $thread))
            ->assertForbidden();

        $this->flushSession();

        $this->withSession($this->adminSession($otherAdmin))
            ->getJson(route('admin-staff-messages.threads.messages.index', $thread))
            ->assertForbidden();
    }

    private function createAdmin(string $username): AdminUser
    {
        return AdminUser::create([
            'username' => $username,
            'password' => Hash::make('password123'),
        ]);
    }

    private function createStaff(string $firstName, string $lastName, string $email, string $staffId): ProgramStaff
    {
        return ProgramStaff::create([
            'first_name' => $firstName,
            'middle_name' => null,
            'last_name' => $lastName,
            'email' => $email,
            'password' => Hash::make('password123'),
            'staff_id' => $staffId,
            'position' => 'Program Staff',
            'role' => 'Program Staff',
            'contact_number' => '09171111111',
            'approval_status' => 'approved',
        ]);
    }

    private function adminSession(AdminUser $admin): array
    {
        return [
            'admin_authenticated' => true,
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
        ];
    }

    private function staffSession(ProgramStaff $staff): array
    {
        return [
            'auth_role' => 'staff',
            'auth_id' => $staff->id,
            'auth_name' => $staff->full_name,
            'auth_email' => $staff->email,
        ];
    }
}

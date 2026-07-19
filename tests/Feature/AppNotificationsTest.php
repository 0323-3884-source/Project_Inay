<?php

namespace Tests\Feature;

use App\Models\AdminStaffThread;
use App\Models\AdminUser;
use App\Models\AppNotification;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Models\StaffCoordinationThread;
use App\Models\StaffMotherCasefile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AppNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_api_is_scoped_to_mother_staff_and_admin_roles(): void
    {
        $mother = $this->createMother('maria.notifications@example.test');
        $staff = $this->createStaff('Ana', 'Cruz', 'ana.notifications@example.test', 'STAFF-NOTIFY');
        $admin = $this->createAdmin('notify-admin');

        $motherNotification = AppNotification::create([
            'recipient_id' => $mother->id,
            'recipient_role' => Message::ROLE_MOTHER,
            'type' => 'test',
            'title' => 'Mother notice',
            'body' => 'Mother body',
            'data' => ['url' => route('mother.dashboard')],
        ]);
        $staffNotification = AppNotification::create([
            'recipient_id' => $staff->id,
            'recipient_role' => Message::ROLE_PROGRAM_STAFF,
            'type' => 'test',
            'title' => 'Staff notice',
            'body' => 'Staff body',
            'data' => ['url' => route('staff.dashboard')],
        ]);
        AppNotification::create([
            'recipient_id' => $admin->id,
            'recipient_role' => 'admin',
            'type' => 'test',
            'title' => 'Admin notice',
            'body' => 'Admin body',
            'data' => ['url' => route('admin.statistics')],
        ]);

        $this->withSession($this->motherSession($mother))
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.title', 'Mother notice');

        $this->withSession($this->motherSession($mother))
            ->postJson(route('notifications.read', $staffNotification))
            ->assertForbidden();

        $this->withSession($this->staffSession($staff))
            ->postJson(route('notifications.read', $staffNotification))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($staffNotification->fresh()->read_at);

        $this->flushSession();

        $this->withSession($this->adminSession($admin))
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('recipient_role', 'admin')
            ->assertJsonPath('notifications.0.title', 'Admin notice');

        $this->flushSession();

        $this->withSession($this->motherSession($mother))
            ->postJson(route('notifications.read', $motherNotification))
            ->assertOk();
    }

    public function test_message_flows_create_notifications_for_receivers(): void
    {
        $mother = $this->createMother('maria.message-notify@example.test');
        $staff = $this->createStaff('Ana', 'Cruz', 'ana.message-notify@example.test', 'STAFF-MESSAGE-NOTIFY');
        $otherStaff = $this->createStaff('Miguel', 'Isles', 'miguel.message-notify@example.test', 'STAFF-OTHER-NOTIFY');
        $admin = $this->createAdmin('message-notify-admin');

        StaffMotherCasefile::create([
            'staff_id' => $staff->id,
            'mother_id' => $mother->id,
        ]);

        $this->withSession($this->staffSession($staff))->get(route('staff.consultation'))->assertOk();
        $conversation = Conversation::firstOrFail();

        $this->withSession($this->staffSession($staff))
            ->postJson(route('consultation.conversations.messages.store', $conversation), [
                'message' => 'Please confirm your next checkup.',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('app_notifications', [
            'recipient_id' => $mother->id,
            'recipient_role' => Message::ROLE_MOTHER,
            'type' => 'consultation_message',
            'title' => 'New consultation message',
        ]);

        $this->withSession($this->staffSession($staff))->get(route('staff.coordination'))->assertOk();
        $coordinationThread = StaffCoordinationThread::where('staff_one_id', min($staff->id, $otherStaff->id))
            ->where('staff_two_id', max($staff->id, $otherStaff->id))
            ->firstOrFail();

        $this->withSession($this->staffSession($staff))
            ->postJson(route('staff-coordination.threads.messages.store', $coordinationThread), [
                'message' => 'Please review the endorsement note.',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('app_notifications', [
            'recipient_id' => $otherStaff->id,
            'recipient_role' => Message::ROLE_PROGRAM_STAFF,
            'type' => 'staff_coordination_message',
            'title' => 'New staff coordination message',
        ]);

        $this->flushSession();

        $this->withSession($this->adminSession($admin))
            ->get(route('admin.staff-messages.index', ['staff' => $staff->id]))
            ->assertOk();
        $adminThread = AdminStaffThread::where('admin_user_id', $admin->id)
            ->where('program_staff_id', $staff->id)
            ->firstOrFail();

        $this->withSession($this->adminSession($admin))
            ->postJson(route('admin-staff-messages.threads.messages.store', $adminThread), [
                'message' => 'Please update your staff details.',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('app_notifications', [
            'recipient_id' => $staff->id,
            'recipient_role' => Message::ROLE_PROGRAM_STAFF,
            'type' => 'admin_staff_message',
            'title' => 'New admin message',
        ]);

        $this->flushSession();

        $this->withSession($this->staffSession($staff))
            ->postJson(route('admin-staff-messages.threads.messages.store', $adminThread), [
                'message' => 'Updated, admin.',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('app_notifications', [
            'recipient_id' => $admin->id,
            'recipient_role' => 'admin',
            'type' => 'admin_staff_message',
            'title' => 'New Program Staff reply',
        ]);
    }

    public function test_message_links_render_as_sidebar_dropdown_and_admin_has_notifications(): void
    {
        $mother = $this->createMother('maria.responsive-shell@example.test');
        $staff = $this->createStaff('Ana', 'Dropdown', 'ana.dropdown@example.test', 'STAFF-DROPDOWN');
        $admin = $this->createAdmin('dropdown-admin');

        $this->withSession($this->motherSession($mother))
            ->get(route('mother.dashboard'))
            ->assertOk()
            ->assertSee('id="portal-drawer-toggle"', false)
            ->assertSee('js/portal-responsive.js', false)
            ->assertSee('data-notification-root', false)
            ->assertSee('Messages');

        $this->withSession($this->staffSession($staff))
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('id="portal-drawer-toggle"', false)
            ->assertSee('js/portal-responsive.js', false)
            ->assertSee('data-notification-root', false)
            ->assertSee('<details class="portal-nav-group"', false)
            ->assertSee('Messages')
            ->assertSee('Consultation')
            ->assertSee('Staff Coordination')
            ->assertSee('Admin Messages');

        $this->withSession($this->adminSession($admin))
            ->get(route('admin.statistics'))
            ->assertOk()
            ->assertSee('data-notification-root', false)
            ->assertSee('Notifications');
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
            'last_name' => 'Reyes',
            'email' => $email,
            'password' => Hash::make('password123'),
            'barangay' => 'Barangay San Francisco',
            'contact_number' => '09170000000',
            'pregnancy_status' => 'pregnant',
            'is_4ps_beneficiary' => false,
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

    private function motherSession(Mother $mother): array
    {
        return [
            'auth_role' => 'mother',
            'auth_id' => $mother->id,
            'auth_name' => $mother->full_name,
            'auth_email' => $mother->email,
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

    private function adminSession(AdminUser $admin): array
    {
        return [
            'admin_authenticated' => true,
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
        ];
    }
}

<?php

namespace Tests\Feature;

use App\Models\ProgramStaff;
use App\Models\StaffCoordinationMessage;
use App\Models\StaffCoordinationThread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffCoordinationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_coordination_page_creates_staff_only_threads(): void
    {
        $ana = $this->createStaff('Ana', 'Cruz', 'ana@example.test', 'STAFF-ANA');
        $miguel = $this->createStaff('Miguel', 'Isles', 'miguel@example.test', 'STAFF-MIGUEL');

        $this->withSession($this->staffSession($ana))
            ->get(route('staff.coordination'))
            ->assertOk()
            ->assertSee('Staff Coordination')
            ->assertSee('For coordination notes, endorsements, and internal follow-up information only.');

        $this->assertDatabaseHas('staff_coordination_threads', [
            'staff_one_id' => min($ana->id, $miguel->id),
            'staff_two_id' => max($ana->id, $miguel->id),
        ]);

        $payload = $this->withSession($this->staffSession($ana))
            ->getJson(route('staff-coordination.threads.index'))
            ->assertOk()
            ->json('threads');

        $this->assertCount(1, $payload);
        $this->assertSame($miguel->full_name, $payload[0]['participant']['name']);
    }

    public function test_staff_can_send_read_and_unsend_internal_coordination_messages(): void
    {
        $ana = $this->createStaff('Ana', 'Cruz', 'ana-send@example.test', 'STAFF-ANA-SEND');
        $miguel = $this->createStaff('Miguel', 'Isles', 'miguel-send@example.test', 'STAFF-MIGUEL-SEND');

        $this->withSession($this->staffSession($ana))->get(route('staff.coordination'))->assertOk();
        $thread = StaffCoordinationThread::first();

        $this->withSession($this->staffSession($ana))
            ->postJson(route('staff-coordination.threads.messages.store', $thread), [
                'message' => 'Please review the endorsement note.',
            ])
            ->assertCreated()
            ->assertJsonPath('message.sender_staff_id', $ana->id)
            ->assertJsonPath('message.receiver_staff_id', $miguel->id)
            ->assertJsonPath('message.is_own', true);

        $message = StaffCoordinationMessage::first();

        $messages = $this->withSession($this->staffSession($miguel))
            ->getJson(route('staff-coordination.threads.messages.index', $thread))
            ->assertOk()
            ->json('messages');

        $this->assertFalse($messages[0]['is_own']);
        $this->assertSame('Please review the endorsement note.', $messages[0]['message']);
        $this->assertDatabaseHas('staff_coordination_messages', [
            'id' => $message->id,
            'is_read' => true,
        ]);

        $this->withSession($this->staffSession($ana))
            ->postJson(route('staff-coordination.messages.unsend', $message))
            ->assertOk()
            ->assertJsonPath('message.message', 'This message was unsent.');

        $this->assertDatabaseHas('staff_coordination_messages', [
            'id' => $message->id,
            'is_unsent' => true,
        ]);
    }

    public function test_unrelated_staff_cannot_open_private_staff_coordination_thread(): void
    {
        $ana = $this->createStaff('Ana', 'Cruz', 'ana-private@example.test', 'STAFF-ANA-PRIVATE');
        $miguel = $this->createStaff('Miguel', 'Isles', 'miguel-private@example.test', 'STAFF-MIGUEL-PRIVATE');
        $other = $this->createStaff('Other', 'Worker', 'other-private@example.test', 'STAFF-OTHER-PRIVATE');

        $this->withSession($this->staffSession($ana))->get(route('staff.coordination'))->assertOk();

        $thread = StaffCoordinationThread::where(function ($query) use ($ana, $miguel): void {
            $query->where('staff_one_id', min($ana->id, $miguel->id))
                ->where('staff_two_id', max($ana->id, $miguel->id));
        })->firstOrFail();

        $this->withSession($this->staffSession($other))
            ->getJson(route('staff-coordination.threads.messages.index', $thread))
            ->assertForbidden();
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
            'contact_number' => '09171111111',
        ]);
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

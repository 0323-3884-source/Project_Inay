<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Models\StaffMotherCasefile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ConsultationModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_consultation_keeps_same_email_mother_and_staff_accounts_separate(): void
    {
        [$mother, $staff] = $this->makeAssignedPair('shared@example.test');

        $this->withSession($this->motherSession($mother))
            ->get('/mother/consultation')
            ->assertOk()
            ->assertSee('MOTHER PORTAL')
            ->assertSee('Chat securely with your assigned Program Staff.');

        $this->withSession($this->staffSession($staff))
            ->get('/staff/consultation')
            ->assertOk()
            ->assertSee('PROGRAM STAFF PORTAL')
            ->assertSee('Respond to assigned mothers in one secure chat workspace.');

        $this->assertDatabaseCount('conversations', 1);
        $conversation = Conversation::first();

        $this->withSession($this->motherSession($mother))
            ->postJson(route('consultation.conversations.messages.store', $conversation), [
                'message' => 'Hello staff',
            ])
            ->assertCreated()
            ->assertJsonPath('message.sender_id', $mother->id)
            ->assertJsonPath('message.sender_role', 'mother')
            ->assertJsonPath('message.receiver_id', $staff->id)
            ->assertJsonPath('message.receiver_role', 'program_staff');

        $staffMessages = $this->withSession($this->staffSession($staff))
            ->getJson(route('consultation.conversations.messages.index', $conversation))
            ->assertOk()
            ->json('messages');

        $this->assertFalse($staffMessages[0]['is_own']);
        $this->assertSame('Hello staff', $staffMessages[0]['message']);

        $this->withSession($this->staffSession($staff))
            ->postJson(route('consultation.conversations.messages.store', $conversation), [
                'message' => 'Hello mother',
            ])
            ->assertCreated()
            ->assertJsonPath('message.sender_id', $staff->id)
            ->assertJsonPath('message.sender_role', 'program_staff')
            ->assertJsonPath('message.receiver_id', $mother->id)
            ->assertJsonPath('message.receiver_role', 'mother');

        $motherMessages = $this->withSession($this->motherSession($mother))
            ->getJson(route('consultation.conversations.messages.index', $conversation))
            ->assertOk()
            ->json('messages');

        $this->assertTrue($motherMessages[0]['is_own']);
        $this->assertFalse($motherMessages[1]['is_own']);
        $this->assertSame('Hello mother', $motherMessages[1]['message']);
    }

    public function test_staff_conversation_list_only_contains_assigned_mothers_and_does_not_duplicate_conversations(): void
    {
        [$assignedMother, $staff] = $this->makeAssignedPair();
        $otherMother = Mother::create($this->motherAttributes('Other', 'Mother', 'other@example.test'));
        $otherStaff = ProgramStaff::create($this->staffAttributes('Other', 'Staff', 'other-staff@example.test', 'STAFF-OTHER'));
        StaffMotherCasefile::create(['staff_id' => $otherStaff->id, 'mother_id' => $otherMother->id]);

        $this->withSession($this->staffSession($staff))->get('/staff/consultation')->assertOk();
        $this->withSession($this->staffSession($staff))->get('/staff/consultation')->assertOk();

        $this->assertDatabaseCount('conversations', 1);

        $payload = $this->withSession($this->staffSession($staff))
            ->getJson(route('consultation.conversations.index'))
            ->assertOk()
            ->json('conversations');

        $this->assertCount(1, $payload);
        $this->assertSame($assignedMother->full_name, $payload[0]['participant']['name']);
    }

    public function test_messages_accept_text_only_file_only_and_reject_empty_submissions(): void
    {
        [$mother, $staff] = $this->makeAssignedPair();

        $this->withSession($this->motherSession($mother))->get('/mother/consultation')->assertOk();
        $conversation = Conversation::first();

        $this->withSession($this->motherSession($mother))
            ->postJson(route('consultation.conversations.messages.store', $conversation), [
                'message' => '   Text only message   ',
            ])
            ->assertCreated()
            ->assertJsonPath('message.message', 'Text only message')
            ->assertJsonPath('message.sender_role', 'mother')
            ->assertJsonPath('message.receiver_role', 'program_staff');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $mother->id,
            'sender_role' => 'mother',
            'receiver_id' => $staff->id,
            'receiver_role' => 'program_staff',
            'message' => 'Text only message',
            'message_type' => 'text',
        ]);

        $this->withSession($this->staffSession($staff))
            ->post(route('consultation.conversations.messages.store', $conversation), [
                'attachment' => UploadedFile::fake()->create('care-plan.pdf', 8, 'application/pdf'),
            ], [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertCreated()
            ->assertJsonPath('message.message', null)
            ->assertJsonPath('message.message_type', 'file')
            ->assertJsonPath('message.attachment_name', 'care-plan.pdf')
            ->assertJsonPath('conversation.latest_message', 'Sent care-plan.pdf');

        $this->withSession($this->motherSession($mother))
            ->postJson(route('consultation.conversations.messages.store', $conversation), [
                'message' => '   ',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Type a message or attach a file before sending.');
    }

    public function test_unauthorized_users_cannot_open_or_modify_another_conversation(): void
    {
        [$mother, $staff] = $this->makeAssignedPair();
        $otherStaff = ProgramStaff::create($this->staffAttributes('Intruder', 'Staff', 'intruder@example.test', 'STAFF-INTRUDER'));

        $this->withSession($this->staffSession($staff))->get('/staff/consultation')->assertOk();
        $conversation = Conversation::first();

        $this->withSession($this->staffSession($otherStaff))
            ->getJson(route('consultation.conversations.messages.index', $conversation))
            ->assertForbidden();

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $mother->id,
            'sender_role' => 'mother',
            'receiver_id' => $staff->id,
            'receiver_role' => 'program_staff',
            'message_type' => 'text',
            'message' => 'Private message',
        ]);

        $this->withSession($this->staffSession($staff))
            ->postJson(route('consultation.messages.unsend', $message))
            ->assertForbidden();

        $this->withSession($this->motherSession($mother))
            ->postJson(route('consultation.messages.unsend', $message))
            ->assertOk()
            ->assertJsonPath('message.message', 'This message was unsent.');

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'is_unsent' => true,
        ]);
    }

    public function test_call_buttons_create_ringing_call_for_the_other_role(): void
    {
        [$mother, $staff] = $this->makeAssignedPair();

        $this->withSession($this->staffSession($staff))->get('/staff/consultation')->assertOk();
        $conversation = Conversation::first();

        $this->withSession($this->staffSession($staff))
            ->postJson(route('consultation.conversations.calls.store', $conversation), [
                'call_type' => 'voice',
            ])
            ->assertCreated()
            ->assertJsonPath('call.status', 'ringing')
            ->assertJsonPath('call.caller.id', $staff->id)
            ->assertJsonPath('call.caller.role', 'program_staff')
            ->assertJsonPath('call.receiver.id', $mother->id)
            ->assertJsonPath('call.receiver.role', 'mother');

        $this->assertDatabaseHas('calls', [
            'conversation_id' => $conversation->id,
            'caller_id' => $staff->id,
            'caller_role' => 'program_staff',
            'receiver_id' => $mother->id,
            'receiver_role' => 'mother',
            'call_type' => 'voice',
            'status' => 'ringing',
        ]);

        $call = Call::first();

        $this->withSession($this->motherSession($mother))
            ->getJson(route('consultation.calls.incoming'))
            ->assertOk()
            ->assertJsonPath('calls.0.id', $call->id);
    }

    public function test_video_call_signaling_is_role_scoped_and_prevents_duplicate_active_calls(): void
    {
        [$mother, $staff] = $this->makeAssignedPair();

        $this->withSession($this->motherSession($mother))->get('/mother/consultation')->assertOk();
        $conversation = Conversation::first();

        $this->withSession($this->motherSession($mother))
            ->postJson(route('consultation.conversations.calls.store', $conversation), [
                'call_type' => 'video',
            ])
            ->assertCreated()
            ->assertJsonPath('call.status', 'ringing')
            ->assertJsonPath('call.caller.role', 'mother')
            ->assertJsonPath('call.receiver.role', 'program_staff');

        $call = Call::first();

        $this->withSession($this->motherSession($mother))
            ->postJson(route('consultation.conversations.calls.store', $conversation), [
                'call_type' => 'video',
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'User is already in another call.');

        $offer = ['type' => 'offer', 'sdp' => "v=0\r\nm=video 9 UDP/TLS/RTP/SAVPF 96\r\na=sendrecv\r\n"];
        $answer = ['type' => 'answer', 'sdp' => "v=0\r\nm=video 9 UDP/TLS/RTP/SAVPF 96\r\na=sendrecv\r\n"];

        $this->withSession($this->staffSession($staff))
            ->postJson(route('consultation.calls.signal', $call), [
                'offer' => $offer,
            ])
            ->assertForbidden();

        $this->withSession($this->motherSession($mother))
            ->postJson(route('consultation.calls.signal', $call), [
                'offer' => ['type' => 'offer', 'sdp' => 'broken-sdp'],
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'The video call offer is invalid.');

        $this->withSession($this->motherSession($mother))
            ->postJson(route('consultation.calls.signal', $call), [
                'offer' => $offer,
            ])
            ->assertOk()
            ->assertJsonPath('call.signaling.offer.sdp', $offer['sdp']);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'offer_type' => 'offer',
            'offer_sdp' => $offer['sdp'],
        ]);

        $this->withSession($this->staffSession($staff))
            ->patchJson(route('consultation.calls.update', $call), [
                'action' => 'accept',
            ])
            ->assertOk()
            ->assertJsonPath('call.status', 'accepted');

        $this->withSession($this->motherSession($mother))
            ->postJson(route('consultation.calls.signal', $call), [
                'answer' => $answer,
            ])
            ->assertForbidden();

        $this->withSession($this->staffSession($staff))
            ->postJson(route('consultation.calls.signal', $call), [
                'answer' => $answer,
                'candidate' => ['call_id' => $call->id, 'candidate' => 'fake-candidate', 'sdpMid' => '0', 'sdpMLineIndex' => 0],
            ])
            ->assertOk()
            ->assertJsonPath('call.signaling.answer.sdp', $answer['sdp'])
            ->assertJsonPath('call.signaling.ice_candidates.0.sender_role', 'program_staff')
            ->assertJsonPath('call.signaling.ice_candidates.0.is_own', true);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'answer_type' => 'answer',
            'answer_sdp' => $answer['sdp'],
        ]);

        $this->withSession($this->staffSession($staff))
            ->postJson(route('consultation.calls.signal', $call), [
                'candidate' => ['call_id' => $call->id + 1, 'candidate' => 'wrong-call-candidate', 'sdpMid' => '0', 'sdpMLineIndex' => 0],
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This ICE candidate belongs to another call.');

        $this->withSession($this->staffSession($staff))
            ->postJson(route('consultation.calls.signal', $call), [
                'candidate' => ['call_id' => $call->id, 'candidate' => 'fake-candidate', 'sdpMid' => '0', 'sdpMLineIndex' => 0],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'call.signaling.ice_candidates');

        $this->withSession($this->motherSession($mother))
            ->getJson(route('consultation.calls.show', $call))
            ->assertOk()
            ->assertJsonPath('call.signaling.answer.sdp', $answer['sdp'])
            ->assertJsonPath('call.signaling.ice_candidates.0.is_own', false);
    }

    private function makeAssignedPair(string $email = 'pair@example.test'): array
    {
        $mother = Mother::create($this->motherAttributes('Maria', 'Reyes', $email));
        $staff = ProgramStaff::create($this->staffAttributes('Ana', 'Cruz', $email, 'STAFF-PAIR'));

        StaffMotherCasefile::create([
            'staff_id' => $staff->id,
            'mother_id' => $mother->id,
        ]);

        return [$mother, $staff];
    }

    private function motherAttributes(string $firstName, string $lastName, string $email): array
    {
        return [
            'first_name' => $firstName,
            'middle_name' => null,
            'last_name' => $lastName,
            'email' => $email,
            'password' => Hash::make('password123'),
            'barangay' => 'San Gabriel',
            'contact_number' => '09170000000',
            'is_4ps_beneficiary' => false,
        ];
    }

    private function staffAttributes(string $firstName, string $lastName, string $email, string $staffId): array
    {
        return [
            'first_name' => $firstName,
            'middle_name' => null,
            'last_name' => $lastName,
            'email' => $email,
            'password' => Hash::make('password123'),
            'staff_id' => $staffId,
            'position' => 'Program Staff',
            'contact_number' => '09171111111',
        ];
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
}

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

    public function test_message_sidebar_sections_filter_contacts_and_hide_dswd_from_non_beneficiaries(): void
    {
        [$mother, $staff] = $this->makeAssignedPair();
        \App\Models\DswdStaff::create(['name' => '4Ps Officer', 'email' => 'sections@example.test', 'password' => 'password123', 'is_active' => true]);
        $dswdLink = route('mother.consultation', ['contacts' => 'dswd_staff']);
        $this->withSession($this->motherSession($mother))->get('/mother/consultation')
            ->assertOk()->assertSee(route('mother.consultation', ['contacts' => 'program_staff']), false)->assertDontSee($dswdLink, false);
        $this->get($dswdLink)->assertForbidden();
        $mother->update(['is_4ps_beneficiary' => true]);
        $this->get($dswdLink)->assertOk()->assertSee('data-contact-role="dswd_staff"', false)->assertSee($dswdLink, false);
        $this->getJson('/consultation/conversations?contacts=dswd_staff')
            ->assertOk()->assertJsonCount(1, 'conversations')->assertJsonPath('conversations.0.participant.role', 'dswd_staff');
        $this->getJson('/consultation/conversations?contacts=program_staff')
            ->assertOk()->assertJsonCount(1, 'conversations')->assertJsonPath('conversations.0.participant.role', 'program_staff');
        $this->withSession($this->staffSession($staff))->get('/staff/consultation?contacts=dswd_staff')
            ->assertOk()->assertSee(route('staff.coordination'), false)->assertSee('data-contact-role="dswd_staff"', false);
        $this->getJson('/consultation/conversations?contacts=dswd_staff')
            ->assertOk()->assertJsonCount(1, 'conversations')->assertJsonPath('conversations.0.participant.role', 'dswd_staff');
        $dswd = \App\Models\DswdStaff::firstOrFail();
        $this->withSession(['auth_role' => 'dswd_staff', 'auth_id' => $dswd->id]);
        foreach (['mother', 'program_staff'] as $role) {
            $this->get('/dswd/messaging?contacts='.$role)->assertOk()->assertSee('data-contact-role="'.$role.'"', false);
            $this->getJson('/dswd/messaging/conversations?contacts='.$role)
                ->assertOk()->assertJsonCount(1, 'conversations')->assertJsonPath('conversations.0.participant.role', $role);
        }
    }

    public function test_four_ps_contacts_are_only_available_to_beneficiaries_and_program_staff(): void
    {
        [$mother, $staff] = $this->makeAssignedPair();
        $dswd = \App\Models\DswdStaff::create(['name' => '4Ps Officer', 'email' => 'dswd@example.test', 'password' => 'password123', 'is_active' => true]);
        $this->withSession($this->motherSession($mother))->getJson('/consultation/conversations')
            ->assertOk()->assertJsonCount(1, 'conversations')->assertJsonMissing(['role' => 'dswd_staff']);

        $mother->update(['is_4ps_beneficiary' => true]);
        $response = $this->getJson('/consultation/conversations')->assertOk()->assertJsonCount(2, 'conversations');
        $contact = collect($response->json('conversations'))->firstWhere('participant.role', 'dswd_staff');
        $this->assertSame('4Ps Officer', $contact['participant']['name']);
        $this->assertNull($contact['risk']);
        $this->getJson('/consultation/conversations')->assertJsonCount(2, 'conversations');
        $this->assertDatabaseCount('conversations', 2);

        $this->withSession($this->staffSession($staff))->getJson('/consultation/conversations')
            ->assertOk()->assertJsonCount(2, 'conversations')->assertJsonFragment(['role' => 'dswd_staff']);

        $mother->update(['is_4ps_beneficiary' => false]);
        $this->withSession($this->motherSession($mother))->getJson('/consultation/conversations')
            ->assertJsonMissing(['role' => 'dswd_staff']);
        $this->getJson('/consultation/conversations/'.$contact['id'].'/messages')->assertForbidden();
        $this->postJson('/consultation/conversations/'.$contact['id'].'/messages', ['message' => 'Blocked'])->assertForbidden();
        $this->postJson('/consultation/conversations/'.$contact['id'].'/read')->assertForbidden();

        $dswd->update(['is_active' => false]);
        $this->withSession($this->staffSession($staff))->getJson('/consultation/conversations')
            ->assertJsonMissing(['role' => 'dswd_staff']);
    }

    public function test_dswd_can_message_beneficiaries_and_program_staff_without_access_to_clinical_chats(): void
    {
        [$mother, $staff] = $this->makeAssignedPair();
        $mother->update(['is_4ps_beneficiary' => true]);
        $otherMother = Mother::create($this->motherAttributes('Non', 'Beneficiary', 'non@example.test'));
        $dswd = \App\Models\DswdStaff::create(['name' => '4Ps Officer', 'email' => 'dswd@example.test', 'password' => 'password123', 'is_active' => true]);
        $this->withSession($this->motherSession($mother))->getJson('/consultation/conversations')->assertOk();
        $clinical = Conversation::whereNull('dswd_staff_id')->firstOrFail();
        $this->withSession(['auth_role' => 'dswd_staff', 'auth_id' => $dswd->id, 'auth_name' => $dswd->name]);
        $this->get('/dswd/messaging')->assertOk()->assertSee('Messaging')->assertSee('data-current-role="dswd_staff"', false);
        $contacts = $this->getJson('/dswd/messaging/conversations')->assertOk()->assertJsonCount(2, 'conversations')->json('conversations');
        $this->assertEqualsCanonicalizing(['mother', 'program_staff'], array_column(array_column($contacts, 'participant'), 'role'));
        $this->getJson('/dswd/messaging/conversations/'.$clinical->id.'/messages')->assertForbidden();
        $this->getJson('/consultation/conversations')->assertForbidden();

        foreach ($contacts as $contact) {
            $this->postJson('/dswd/messaging/conversations/'.$contact['id'].'/messages', ['message' => 'Hello from DSWD'])
                ->assertCreated()->assertJsonPath('message.sender_role', 'dswd_staff')
                ->assertJsonPath('message.receiver_role', $contact['participant']['role'])
                ->assertJsonPath('message.sender_name', '4Ps Officer');
            $session = $contact['participant']['role'] === 'mother' ? $this->motherSession($mother) : $this->staffSession($staff);
            $this->withSession($session)->getJson('/consultation/conversations/'.$contact['id'].'/messages')
                ->assertOk()->assertJsonPath('messages.0.is_own', false)->assertJsonPath('messages.0.is_read', true);
            $reply = $this->postJson('/consultation/conversations/'.$contact['id'].'/messages', ['message' => 'Reply'])
                ->assertCreated()->assertJsonPath('message.receiver_role', 'dswd_staff')->json('message.id');
            $this->withSession(['auth_role' => 'dswd_staff', 'auth_id' => $dswd->id]);
            $this->getJson('/dswd/messaging/conversations/'.$contact['id'].'/messages')->assertOk()->assertJsonCount(2, 'messages');
            $this->postJson('/dswd/messaging/messages/'.$reply.'/unsend')->assertForbidden();
        }
        $this->assertDatabaseMissing('conversations', ['mother_id' => $otherMother->id, 'dswd_staff_id' => $dswd->id]);
        $secondDswd = \App\Models\DswdStaff::create(['name' => 'Other Officer', 'email' => 'other-dswd@example.test', 'password' => 'password123', 'is_active' => true]);
        $this->withSession(['auth_role' => 'dswd_staff', 'auth_id' => $secondDswd->id])
            ->getJson('/dswd/messaging/conversations/'.$contacts[0]['id'].'/messages')->assertForbidden();
    }

    public function test_dswd_message_attachments_and_unsend_obey_beneficiary_access(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        [$mother] = $this->makeAssignedPair();
        $mother->update(['is_4ps_beneficiary' => true]);
        $dswd = \App\Models\DswdStaff::create(['name' => '4Ps Officer', 'email' => 'dswd@example.test', 'password' => 'password123', 'is_active' => true]);
        $this->withSession(['auth_role' => 'dswd_staff', 'auth_id' => $dswd->id]);
        $contacts = $this->getJson('/dswd/messaging/conversations')->assertOk()->json('conversations');
        $contact = collect($contacts)->firstWhere('participant.role', 'mother');
        $message = $this->postJson('/dswd/messaging/conversations/'.$contact['id'].'/messages', [
            'attachment' => UploadedFile::fake()->create('information.pdf', 10, 'application/pdf'),
        ])->assertCreated()->json('message');
        $this->assertStringContainsString('/dswd/messaging/messages/', $message['attachment_url']);
        $this->get($message['attachment_url'])->assertOk();
        $this->withSession($this->motherSession($mother))->get('/consultation/messages/'.$message['id'].'/attachment')->assertOk();
        $mother->update(['is_4ps_beneficiary' => false]);
        $this->get('/consultation/messages/'.$message['id'].'/attachment')->assertNotFound();
        $this->withSession(['auth_role' => 'dswd_staff', 'auth_id' => $dswd->id])
            ->get($message['attachment_url'])->assertNotFound();
        $mother->update(['is_4ps_beneficiary' => true]);
        $this->postJson('/dswd/messaging/messages/'.$message['id'].'/unsend')->assertOk()->assertJsonPath('message.is_unsent', true);
        $this->get($message['attachment_url'])->assertNotFound();
    }

    public function test_consultation_keeps_same_email_mother_and_staff_accounts_separate(): void
    {
        [$mother, $staff] = $this->makeAssignedPair('shared@example.test');

        $this->withSession($this->motherSession($mother))
            ->get('/mother/consultation')
            ->assertOk()
            ->assertSee('MOTHER PORTAL')
            ->assertSee('Chat securely with your assigned Program Staff.')
            ->assertSee('data-sms-button', false);

        $this->withSession($this->staffSession($staff))
            ->get('/staff/consultation')
            ->assertOk()
            ->assertSee('PROGRAM STAFF PORTAL')
            ->assertSee('Respond to assigned mothers in one secure chat workspace.')
            ->assertSee('data-sms-button', false);

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

    public function test_consultation_sms_contact_uses_the_other_role_account_for_same_email_users(): void
    {
        [$mother, $staff] = $this->makeAssignedPair('shared-sms@example.test');

        $motherPayload = $this->withSession($this->motherSession($mother))
            ->getJson(route('consultation.conversations.index'))
            ->assertOk()
            ->json('conversations.0.participant');

        $this->assertSame('program_staff', $motherPayload['role']);
        $this->assertSame($staff->id, $motherPayload['id']);
        $this->assertSame($staff->contact_number, $motherPayload['contact_number']);
        $this->assertSame('sms:09171111111', $motherPayload['sms_url']);

        $staffPayload = $this->withSession($this->staffSession($staff))
            ->getJson(route('consultation.conversations.index'))
            ->assertOk()
            ->json('conversations.0.participant');

        $this->assertSame('mother', $staffPayload['role']);
        $this->assertSame($mother->id, $staffPayload['id']);
        $this->assertSame($mother->contact_number, $staffPayload['contact_number']);
        $this->assertSame('sms:09170000000', $staffPayload['sms_url']);
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

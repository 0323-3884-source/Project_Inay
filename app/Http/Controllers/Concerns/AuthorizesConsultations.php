<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Call;
use App\Models\Conversation;
use App\Models\MaternalMonitoringRecord;
use App\Models\Message;
use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Models\StaffMotherCasefile;
use App\Support\MaternalVitalScreening;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

trait AuthorizesConsultations
{
    protected function normalizedConsultationRole(Request $request): ?string
    {
        return match ($request->session()->get('auth_role')) {
            Message::ROLE_MOTHER => Message::ROLE_MOTHER,
            'staff', Message::ROLE_PROGRAM_STAFF => Message::ROLE_PROGRAM_STAFF,
            default => null,
        };
    }

    protected function currentConsultationParticipant(Request $request): ?array
    {
        $role = $this->normalizedConsultationRole($request);
        $id = (int) $request->session()->get('auth_id');

        if (! $role || $id <= 0) {
            return null;
        }

        $exists = $role === Message::ROLE_MOTHER
            ? Mother::whereKey($id)->exists()
            : ProgramStaff::whereKey($id)->exists();

        if (! $exists) {
            return null;
        }

        return ['id' => $id, 'role' => $role];
    }

    protected function currentMother(Request $request): ?Mother
    {
        if ($this->normalizedConsultationRole($request) !== Message::ROLE_MOTHER) {
            return null;
        }

        return Mother::find($request->session()->get('auth_id'));
    }

    protected function currentProgramStaff(Request $request): ?ProgramStaff
    {
        if ($this->normalizedConsultationRole($request) !== Message::ROLE_PROGRAM_STAFF) {
            return null;
        }

        return ProgramStaff::find($request->session()->get('auth_id'));
    }

    protected function ensureConversationForPair(Mother $mother, ProgramStaff $staff): Conversation
    {
        return Conversation::firstOrCreate([
            'mother_id' => $mother->id,
            'program_staff_id' => $staff->id,
        ]);
    }

    protected function ensureConversationsForStaff(ProgramStaff $staff): void
    {
        $motherIds = StaffMotherCasefile::where('staff_id', $staff->id)->pluck('mother_id');

        foreach ($motherIds as $motherId) {
            Conversation::firstOrCreate([
                'mother_id' => $motherId,
                'program_staff_id' => $staff->id,
            ]);
        }
    }

    protected function ensureConversationsForMother(Mother $mother): void
    {
        $staffIds = StaffMotherCasefile::where('mother_id', $mother->id)->pluck('staff_id');

        foreach ($staffIds as $staffId) {
            Conversation::firstOrCreate([
                'mother_id' => $mother->id,
                'program_staff_id' => $staffId,
            ]);
        }
    }

    protected function authorizeConversation(Request $request, Conversation $conversation): bool
    {
        $participant = $this->currentConsultationParticipant($request);

        if (! $participant) {
            return false;
        }

        $conversation->loadMissing(['mother', 'programStaff']);

        if (! $conversation->mother || ! $conversation->programStaff) {
            return false;
        }

        $isAssigned = StaffMotherCasefile::where('staff_id', $conversation->program_staff_id)
            ->where('mother_id', $conversation->mother_id)
            ->exists();

        if (! $isAssigned) {
            return false;
        }

        return $participant['role'] === Message::ROLE_MOTHER
            ? $conversation->mother_id === $participant['id']
            : $conversation->program_staff_id === $participant['id'];
    }

    protected function receiverForConversation(Conversation $conversation, string $senderRole): array
    {
        if ($senderRole === Message::ROLE_MOTHER) {
            return ['id' => $conversation->program_staff_id, 'role' => Message::ROLE_PROGRAM_STAFF];
        }

        return ['id' => $conversation->mother_id, 'role' => Message::ROLE_MOTHER];
    }

    protected function conversationPayload(Conversation $conversation, Request $request): array
    {
        $participant = $this->currentConsultationParticipant($request);
        $conversation->loadMissing([
            'mother.maternalMonitoringRecords' => fn ($query) => $query
                ->orderByDesc('recorded_at')
                ->orderByDesc('created_at'),
            'programStaff',
            'lastMessage',
        ]);

        $viewerRole = $participant['role'] ?? Message::ROLE_MOTHER;
        $otherRole = $viewerRole === Message::ROLE_MOTHER
            ? Message::ROLE_PROGRAM_STAFF
            : Message::ROLE_MOTHER;
        $otherModel = $otherRole === Message::ROLE_MOTHER
            ? $conversation->mother
            : $conversation->programStaff;
        $lastMessage = $conversation->lastMessage;

        return [
            'id' => $conversation->id,
            'participant' => $this->profilePayload($otherModel, $otherRole),
            'mother' => $this->profilePayload($conversation->mother, Message::ROLE_MOTHER),
            'program_staff' => $this->profilePayload($conversation->programStaff, Message::ROLE_PROGRAM_STAFF),
            'risk' => $this->riskPayload($conversation->mother),
            'latest_message' => $lastMessage ? $this->messagePreview($lastMessage) : 'No messages yet',
            'latest_message_time' => $lastMessage?->created_at?->format('g:i A') ?? '',
            'latest_message_iso' => $lastMessage?->created_at?->toIso8601String(),
            'unread_count' => $participant ? Message::where('conversation_id', $conversation->id)
                ->where('receiver_id', $participant['id'])
                ->where('receiver_role', $participant['role'])
                ->where('is_read', false)
                ->where('is_unsent', false)
                ->count() : 0,
            'updated_at' => ($conversation->last_message_at ?? $conversation->updated_at)?->toIso8601String(),
        ];
    }

    protected function messagePayload(Message $message, Request $request): array
    {
        $participant = $this->currentConsultationParticipant($request);
        $message->loadMissing(['conversation.mother', 'conversation.programStaff']);
        $conversation = $message->conversation;
        $senderName = $this->participantName($conversation, $message->sender_id, $message->sender_role);

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'sender_role' => $message->sender_role,
            'sender_role_label' => $this->roleLabel($message->sender_role),
            'sender_name' => $senderName,
            'receiver_id' => $message->receiver_id,
            'receiver_role' => $message->receiver_role,
            'message_type' => $message->message_type,
            'message' => $message->is_unsent ? 'This message was unsent.' : $message->message,
            'attachment_name' => $message->is_unsent ? null : $message->attachment_name,
            'attachment_mime' => $message->is_unsent ? null : $message->attachment_mime,
            'attachment_size' => $message->is_unsent ? null : $message->attachment_size,
            'attachment_duration' => $message->is_unsent ? null : $message->attachment_duration,
            'attachment_url' => $message->is_unsent || ! $message->attachment_path
                ? null
                : route('consultation.messages.attachment', $message),
            'is_read' => $message->is_read,
            'read_at' => $message->read_at?->toIso8601String(),
            'is_unsent' => $message->is_unsent,
            'created_at' => $message->created_at?->toIso8601String(),
            'created_time' => $message->created_at?->format('g:i A') ?? '',
            'created_label' => $message->created_at?->format('M j, Y g:i A') ?? '',
            'is_own' => $participant
                && $message->sender_id === $participant['id']
                && $message->sender_role === $participant['role'],
            'can_unsend' => $participant
                && ! $message->is_unsent
                && $message->message_type !== Message::TYPE_SYSTEM
                && $message->sender_id === $participant['id']
                && $message->sender_role === $participant['role'],
        ];
    }

    protected function callPayload(Call $call, Request $request): array
    {
        $participant = $this->currentConsultationParticipant($request);
        $call->loadMissing(['conversation.mother', 'conversation.programStaff']);

        return [
            'id' => $call->id,
            'conversation_id' => $call->conversation_id,
            'call_type' => $call->call_type,
            'status' => $call->status,
            'caller' => [
                'id' => $call->caller_id,
                'role' => $call->caller_role,
                'role_label' => $this->roleLabel($call->caller_role),
                'name' => $this->participantName($call->conversation, $call->caller_id, $call->caller_role),
                'initials' => $this->initials($this->participantName($call->conversation, $call->caller_id, $call->caller_role)),
                'avatar_url' => null,
            ],
            'receiver' => [
                'id' => $call->receiver_id,
                'role' => $call->receiver_role,
                'role_label' => $this->roleLabel($call->receiver_role),
                'name' => $this->participantName($call->conversation, $call->receiver_id, $call->receiver_role),
                'initials' => $this->initials($this->participantName($call->conversation, $call->receiver_id, $call->receiver_role)),
                'avatar_url' => null,
            ],
            'signaling' => [
                'offer' => $call->offer_type && $call->offer_sdp
                    ? ['type' => $call->offer_type, 'sdp' => $call->offer_sdp]
                    : $call->offer_payload,
                'answer' => $call->answer_type && $call->answer_sdp
                    ? ['type' => $call->answer_type, 'sdp' => $call->answer_sdp]
                    : $call->answer_payload,
                'ice_candidates' => collect($call->ice_candidates ?? [])
                    ->map(function (array $candidate) use ($participant): array {
                        $candidate['is_own'] = $participant
                            && (int) ($candidate['sender_id'] ?? 0) === $participant['id']
                            && ($candidate['sender_role'] ?? null) === $participant['role'];

                        return $candidate;
                    })
                    ->values(),
            ],
            'started_at' => $call->started_at?->toIso8601String(),
            'answered_at' => $call->answered_at?->toIso8601String(),
            'ended_at' => $call->ended_at?->toIso8601String(),
            'is_caller' => $participant
                && $call->caller_id === $participant['id']
                && $call->caller_role === $participant['role'],
            'is_receiver' => $participant
                && $call->receiver_id === $participant['id']
                && $call->receiver_role === $participant['role'],
        ];
    }

    protected function profilePayload(?object $model, string $role): array
    {
        $name = $model?->full_name ?: $this->roleLabel($role);
        $contactNumber = trim((string) ($model?->contact_number ?? ''));
        $smsUrl = $this->smsUrlForContact($contactNumber);

        return [
            'id' => $model?->id,
            'role' => $role,
            'role_label' => $this->roleLabel($role),
            'name' => $name,
            'initials' => $this->initials($name),
            'avatar_url' => null,
            'contact_number' => $smsUrl ? $contactNumber : null,
            'sms_url' => $smsUrl,
            'online' => $this->isOnline($model),
            'status_text' => $this->isOnline($model) ? 'Online' : 'Offline',
        ];
    }

    protected function smsUrlForContact(?string $contactNumber): ?string
    {
        $contactNumber = trim((string) $contactNumber);

        if ($contactNumber === '' || Str::lower($contactNumber) === 'not provided') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $contactNumber) ?? '';

        if (strlen($digits) < 7) {
            return null;
        }

        return 'sms:'.(Str::startsWith($contactNumber, '+') ? '+' : '').$digits;
    }

    protected function riskPayload(?Mother $mother): array
    {
        $latest = $mother?->relationLoaded('maternalMonitoringRecords')
            ? $mother->maternalMonitoringRecords->first()
            : ($mother ? MaternalMonitoringRecord::where('mother_id', $mother->id)
                ->orderByDesc('recorded_at')
                ->orderByDesc('created_at')
                ->first() : null);
        $status = MaternalVitalScreening::normalizeStatus($latest?->screening_summary_status ?? $latest?->risk_level);

        return [
            'level' => $latest ? MaternalVitalScreening::statusKey($status) : 'logged',
            'label' => $latest ? $status : MaternalVitalScreening::STATUS_LOGGED,
        ];
    }

    protected function messagePreview(Message $message): string
    {
        if ($message->is_unsent) {
            return 'This message was unsent.';
        }

        $text = trim((string) $message->message);

        if ($text !== '') {
            return Str::limit($text, 80);
        }

        if ($message->attachment_name) {
            return match ($message->message_type) {
                Message::TYPE_IMAGE => 'Sent an image',
                Message::TYPE_VIDEO => 'Sent a video',
                default => 'Sent '.$message->attachment_name,
            };
        }

        return 'No message preview';
    }

    protected function participantName(Conversation $conversation, int $id, string $role): string
    {
        if ($role === Message::ROLE_MOTHER && $conversation->mother_id === $id) {
            return $conversation->mother?->full_name ?: 'Mother';
        }

        if ($role === Message::ROLE_PROGRAM_STAFF && $conversation->program_staff_id === $id) {
            return $conversation->programStaff?->full_name ?: 'Program Staff';
        }

        return $this->roleLabel($role);
    }

    protected function roleLabel(string $role): string
    {
        return $role === Message::ROLE_PROGRAM_STAFF ? 'Program Staff' : 'Mother';
    }

    protected function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return $initials ?: 'IN';
    }

    protected function isOnline(?object $model): bool
    {
        $updatedAt = $model?->updated_at;

        return $updatedAt instanceof Carbon && $updatedAt->greaterThan(now()->subMinutes(10));
    }
}

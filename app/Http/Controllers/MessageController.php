<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesConsultations;
use App\Models\Conversation;
use App\Models\Message;
use App\Support\AppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MessageController extends Controller
{
    use AuthorizesConsultations;

    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        if (! $this->authorizeConversation($request, $conversation)) {
            return response()->json(['message' => 'You cannot open this conversation.'], 403);
        }

        $participant = $this->currentConsultationParticipant($request);

        Message::where('conversation_id', $conversation->id)
            ->where('receiver_id', $participant['id'])
            ->where('receiver_role', $participant['role'])
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        $messages = Message::with(['conversation.mother', 'conversation.programStaff'])
            ->where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $conversation->refresh();

        return response()->json([
            'conversation' => $this->conversationPayload($conversation, $request),
            'messages' => $messages
                ->map(fn (Message $message): array => $this->messagePayload($message, $request))
                ->values(),
        ]);
    }

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        if (! $this->authorizeConversation($request, $conversation)) {
            return response()->json(['message' => 'You cannot send a message to this conversation.'], 403);
        }

        $trimmedMessage = trim((string) $request->input('message', ''));
        $request->merge(['message' => $trimmedMessage]);

        if ($trimmedMessage === '' && ! $request->hasFile('attachment')) {
            return response()->json(['message' => 'Type a message or attach a file before sending.'], 422);
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:1000', 'required_without:attachment'],
            'message_type' => ['nullable', Rule::in([
                Message::TYPE_TEXT,
                Message::TYPE_IEC_MATERIAL,
                Message::TYPE_MEDICAL_TEMPLATE,
            ])],
            'attachment' => [
                'nullable',
                'file',
                'required_without:message',
                'max:12288',
                'mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,webm,pdf,doc,docx,xls,xlsx,txt,csv',
            ],
            'attachment_duration' => ['nullable', 'integer', 'min:0', 'max:7200'],
        ], [
            'message.required_without' => 'Type a message or attach a file before sending.',
            'attachment.required_without' => 'Type a message or attach a file before sending.',
            'message.max' => 'Messages may not be longer than 1000 characters.',
            'attachment.max' => 'The attachment is too large.',
            'attachment.mimes' => 'This attachment type is not supported.',
        ]);

        $body = trim((string) ($validated['message'] ?? ''));
        $attachment = $request->file('attachment');

        if ($body === '' && ! $attachment) {
            return response()->json(['message' => 'Type a message or attach a file before sending.'], 422);
        }

        $participant = $this->currentConsultationParticipant($request);
        $receiver = $this->receiverForConversation($conversation, $participant['role']);
        $messageType = $this->messageTypeForRequest($participant['role'], $validated['message_type'] ?? Message::TYPE_TEXT, $attachment);
        $attachmentPath = null;
        $attachmentName = null;
        $attachmentMime = null;
        $attachmentSize = null;
        $attachmentDuration = $validated['attachment_duration'] ?? null;

        try {
            if ($attachment) {
                $attachmentPath = $attachment->store('consultation-attachments/'.now()->format('Y/m'), 'local');
                $attachmentName = Str::limit(str_replace(['\\', '/', '"'], '', $attachment->getClientOriginalName()), 160, '');
                $attachmentMime = $attachment->getMimeType();
                $attachmentSize = $attachment->getSize() ?: 0;
            }

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $participant['id'],
                'sender_role' => $participant['role'],
                'receiver_id' => $receiver['id'],
                'receiver_role' => $receiver['role'],
                'message_type' => $messageType,
                'message' => $body !== '' ? $body : null,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
                'attachment_mime' => $attachmentMime,
                'attachment_size' => $attachmentSize,
                'attachment_duration' => $attachmentDuration,
            ]);

            $conversation->forceFill([
                'last_message_id' => $message->id,
                'last_message_at' => $message->created_at,
            ])->save();

            $conversation->loadMissing(['mother', 'programStaff']);
            $senderName = $this->participantName($conversation, $participant['id'], $participant['role']);
            $fallback = $attachment ? 'Sent an attachment.' : 'Sent a new consultation message.';

            AppNotificationService::create(
                (int) $receiver['id'],
                $receiver['role'],
                'consultation_message',
                'New consultation message',
                $senderName.': '.AppNotificationService::preview($body, $fallback),
                [
                    'conversation_id' => $conversation->id,
                    'url' => $receiver['role'] === Message::ROLE_MOTHER
                        ? route('mother.consultation', ['conversation' => $conversation->id])
                        : route('staff.consultation', ['conversation' => $conversation->id]),
                ]
            );
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Message could not be sent. Please try again.'], 500);
        }

        return response()->json([
            'message' => $this->messagePayload($message, $request),
            'conversation' => $this->conversationPayload($conversation->refresh(), $request),
        ], 201);
    }

    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        if (! $this->authorizeConversation($request, $conversation)) {
            return response()->json(['message' => 'You cannot update this conversation.'], 403);
        }

        $participant = $this->currentConsultationParticipant($request);

        Message::where('conversation_id', $conversation->id)
            ->where('receiver_id', $participant['id'])
            ->where('receiver_role', $participant['role'])
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['conversation' => $this->conversationPayload($conversation->refresh(), $request)]);
    }

    public function unsend(Request $request, Message $message): JsonResponse
    {
        $message->loadMissing('conversation');

        if (! $this->authorizeConversation($request, $message->conversation)) {
            return response()->json(['message' => 'You cannot update this message.'], 403);
        }

        $participant = $this->currentConsultationParticipant($request);

        if ($message->sender_id !== $participant['id'] || $message->sender_role !== $participant['role']) {
            return response()->json(['message' => 'Only the original sender can unsend this message.'], 403);
        }

        if ($message->attachment_path) {
            Storage::disk('local')->delete($message->attachment_path);
        }

        $message->forceFill([
            'message' => null,
            'attachment_path' => null,
            'attachment_name' => null,
            'attachment_size' => null,
            'is_unsent' => true,
            'unsent_at' => now(),
        ])->save();

        return response()->json([
            'message' => $this->messagePayload($message->refresh(), $request),
            'conversation' => $this->conversationPayload($message->conversation->refresh(), $request),
        ]);
    }

    public function attachment(Request $request, Message $message): BinaryFileResponse
    {
        $message->loadMissing('conversation');

        if (! $this->authorizeConversation($request, $message->conversation) || $message->is_unsent || ! $message->attachment_path) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($message->attachment_path)) {
            abort(404);
        }

        $fileName = str_replace(['\\', '"'], '', $message->attachment_name ?: 'attachment');

        return response()->file(Storage::disk('local')->path($message->attachment_path), [
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    private function messageTypeForRequest(string $senderRole, string $requestedType, mixed $attachment): string
    {
        if ($attachment) {
            $mime = (string) $attachment->getMimeType();

            if (str_starts_with($mime, 'image/')) {
                return Message::TYPE_IMAGE;
            }

            if (str_starts_with($mime, 'video/')) {
                return Message::TYPE_VIDEO;
            }

            return Message::TYPE_FILE;
        }

        if ($senderRole === Message::ROLE_PROGRAM_STAFF && in_array($requestedType, [
            Message::TYPE_IEC_MATERIAL,
            Message::TYPE_MEDICAL_TEMPLATE,
        ], true)) {
            return $requestedType;
        }

        return Message::TYPE_TEXT;
    }
}

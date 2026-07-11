<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesConsultations;
use App\Models\Call;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CallController extends Controller
{
    use AuthorizesConsultations;

    private const ACTIVE_STATUSES = ['ringing', 'accepted'];
    private const RINGING_TIMEOUT_SECONDS = 90;

    public function incoming(Request $request): JsonResponse
    {
        $this->expireStaleRingingCalls();

        $participant = $this->currentConsultationParticipant($request);

        if (! $participant) {
            return response()->json(['message' => 'Please login before opening calls.'], 401);
        }

        $calls = Call::with(['conversation.mother', 'conversation.programStaff'])
            ->where('receiver_id', $participant['id'])
            ->where('receiver_role', $participant['role'])
            ->where('status', 'ringing')
            ->latest()
            ->limit(3)
            ->get()
            ->filter(fn (Call $call): bool => $this->authorizeConversation($request, $call->conversation))
            ->values();

        return response()->json([
            'calls' => $calls->map(fn (Call $call): array => $this->callPayload($call, $request))->values(),
        ]);
    }

    public function show(Request $request, Call $call): JsonResponse
    {
        $this->expireStaleRingingCalls();
        $call->refresh()->loadMissing('conversation');

        if (! $this->authorizeConversation($request, $call->conversation)) {
            return response()->json(['message' => 'You cannot open this call.'], 403);
        }

        return response()->json(['call' => $this->callPayload($call, $request)]);
    }

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $this->expireStaleRingingCalls();

        if (! $this->authorizeConversation($request, $conversation)) {
            return response()->json(['message' => 'You cannot start a call in this conversation.'], 403);
        }

        $validated = $request->validate([
            'call_type' => ['required', Rule::in(['voice', 'video'])],
        ]);
        $participant = $this->currentConsultationParticipant($request);
        $receiver = $this->receiverForConversation($conversation, $participant['role']);

        if ($this->hasActiveCall($participant) || $this->hasActiveCall($receiver)) {
            return response()->json(['message' => 'User is already in another call.'], 409);
        }

        $call = Call::create([
            'conversation_id' => $conversation->id,
            'caller_id' => $participant['id'],
            'caller_role' => $participant['role'],
            'receiver_id' => $receiver['id'],
            'receiver_role' => $receiver['role'],
            'call_type' => $validated['call_type'],
            'status' => 'ringing',
            'started_at' => now(),
        ]);

        return response()->json(['call' => $this->callPayload($call, $request)], 201);
    }

    public function update(Request $request, Call $call): JsonResponse
    {
        $this->expireStaleRingingCalls();
        $call->refresh()->loadMissing('conversation');

        if (! $this->authorizeConversation($request, $call->conversation)) {
            return response()->json(['message' => 'You cannot update this call.'], 403);
        }

        $validated = $request->validate([
            'action' => ['required', Rule::in(['accept', 'decline', 'cancel', 'end'])],
        ]);
        $participant = $this->currentConsultationParticipant($request);
        $isCaller = $call->caller_id === $participant['id'] && $call->caller_role === $participant['role'];
        $isReceiver = $call->receiver_id === $participant['id'] && $call->receiver_role === $participant['role'];

        if ($validated['action'] === 'accept') {
            if (! $isReceiver || $call->status !== 'ringing') {
                return response()->json(['message' => 'This call cannot be accepted.'], 422);
            }

            $call->forceFill(['status' => 'accepted', 'answered_at' => now()])->save();
        }

        if ($validated['action'] === 'decline') {
            if (! $isReceiver || $call->status !== 'ringing') {
                return response()->json(['message' => 'This call cannot be declined.'], 422);
            }

            $call->forceFill(['status' => 'declined', 'ended_at' => now()])->save();
        }

        if ($validated['action'] === 'cancel') {
            if (! $isCaller || $call->status !== 'ringing') {
                return response()->json(['message' => 'This call cannot be cancelled.'], 422);
            }

            $call->forceFill(['status' => 'cancelled', 'ended_at' => now()])->save();
        }

        if ($validated['action'] === 'end') {
            if (! ($isCaller || $isReceiver) || ! in_array($call->status, ['ringing', 'accepted'], true)) {
                return response()->json(['message' => 'This call cannot be ended.'], 422);
            }

            $call->forceFill(['status' => 'ended', 'ended_at' => now()])->save();
        }

        return response()->json(['call' => $this->callPayload($call->refresh(), $request)]);
    }

    public function signal(Request $request, Call $call): JsonResponse
    {
        $this->expireStaleRingingCalls();
        $call->refresh()->loadMissing('conversation');

        if (! $this->authorizeConversation($request, $call->conversation)) {
            return response()->json(['message' => 'You cannot update this call.'], 403);
        }

        if (! in_array($call->status, self::ACTIVE_STATUSES, true)) {
            return response()->json(['message' => 'This call is no longer active.'], 422);
        }

        $validated = $request->validate([
            'offer' => ['nullable', 'array'],
            'offer.type' => ['required_with:offer', Rule::in(['offer'])],
            'offer.sdp' => ['required_with:offer', 'string'],
            'answer' => ['nullable', 'array'],
            'answer.type' => ['required_with:answer', Rule::in(['answer'])],
            'answer.sdp' => ['required_with:answer', 'string'],
            'candidate' => ['nullable', 'array'],
            'candidate.call_id' => ['nullable', 'integer'],
            'candidate.candidate' => ['required_with:candidate', 'string'],
            'candidate.sdpMid' => ['nullable', 'string'],
            'candidate.sdpMLineIndex' => ['nullable', 'integer'],
        ]);
        $rawPayload = json_decode($request->getContent(), true);

        if (is_array($rawPayload)) {
            if (isset($rawPayload['offer']['sdp']) && is_string($rawPayload['offer']['sdp'])) {
                $validated['offer']['sdp'] = $rawPayload['offer']['sdp'];
            }

            if (isset($rawPayload['answer']['sdp']) && is_string($rawPayload['answer']['sdp'])) {
                $validated['answer']['sdp'] = $rawPayload['answer']['sdp'];
            }
        }

        $participant = $this->currentConsultationParticipant($request);
        $isCaller = $call->caller_id === $participant['id'] && $call->caller_role === $participant['role'];
        $isReceiver = $call->receiver_id === $participant['id'] && $call->receiver_role === $participant['role'];

        if (isset($validated['offer']) && ! $isCaller) {
            return response()->json(['message' => 'Only the caller can create the call offer.'], 403);
        }

        if (isset($validated['answer']) && ! $isReceiver) {
            return response()->json(['message' => 'Only the receiver can answer this call.'], 403);
        }

        if (isset($validated['offer']) && ! $this->looksLikeSdp($validated['offer']['sdp'])) {
            report(new \UnexpectedValueException('Invalid WebRTC offer SDP for call '.$call->id));

            return response()->json(['message' => 'The video call offer is invalid.'], 422);
        }

        if (isset($validated['answer']) && ! $this->looksLikeSdp($validated['answer']['sdp'])) {
            report(new \UnexpectedValueException('Invalid WebRTC answer SDP for call '.$call->id));

            return response()->json(['message' => 'The video call answer is invalid.'], 422);
        }

        if (isset($validated['candidate']['call_id']) && (int) $validated['candidate']['call_id'] !== $call->id) {
            return response()->json(['message' => 'This ICE candidate belongs to another call.'], 422);
        }

        $updates = [];

        if (isset($validated['offer'])) {
            if ($call->offer_sdp && $call->offer_sdp !== $validated['offer']['sdp']) {
                return response()->json(['message' => 'This call already has an offer.'], 409);
            }

            $updates['offer_type'] = $validated['offer']['type'];
            $updates['offer_sdp'] = $validated['offer']['sdp'];
            $updates['offer_payload'] = $validated['offer'];
        }

        if (isset($validated['answer'])) {
            if (! $call->offer_sdp) {
                return response()->json(['message' => 'This call is missing an offer.'], 422);
            }

            if ($call->answer_sdp && $call->answer_sdp !== $validated['answer']['sdp']) {
                return response()->json(['message' => 'This call already has an answer.'], 409);
            }

            $updates['answer_type'] = $validated['answer']['type'];
            $updates['answer_sdp'] = $validated['answer']['sdp'];
            $updates['answer_payload'] = $validated['answer'];
        }

        if (isset($validated['candidate'])) {
            $candidatePayload = $validated['candidate'];
            unset($candidatePayload['call_id']);

            $candidate = [
                'id' => (string) Str::uuid(),
                'call_id' => $call->id,
                'sender_id' => $participant['id'],
                'sender_role' => $participant['role'],
                'candidate' => $candidatePayload,
                'fingerprint' => sha1(($candidatePayload['candidate'] ?? '').'|'.($candidatePayload['sdpMid'] ?? '').'|'.($candidatePayload['sdpMLineIndex'] ?? '')),
                'created_at' => now()->toIso8601String(),
            ];

            $candidates = $call->ice_candidates ?? [];
            $exists = collect($candidates)->contains(fn (array $existing): bool => ($existing['fingerprint'] ?? null) === $candidate['fingerprint']);

            if (! $exists) {
                $candidates[] = $candidate;
            }

            $updates['ice_candidates'] = array_slice($candidates, -80);
        }

        if ($updates === []) {
            return response()->json(['message' => 'No call signal was provided.'], 422);
        }

        $call->forceFill($updates)->save();

        return response()->json(['call' => $this->callPayload($call->refresh(), $request)]);
    }

    private function expireStaleRingingCalls(): void
    {
        Call::where('status', 'ringing')
            ->whereNotNull('started_at')
            ->where('started_at', '<', now()->subSeconds(self::RINGING_TIMEOUT_SECONDS))
            ->update([
                'status' => 'missed',
                'ended_at' => now(),
            ]);
    }

    private function hasActiveCall(array $participant): bool
    {
        return Call::whereIn('status', self::ACTIVE_STATUSES)
            ->where(function ($query) use ($participant): void {
                $query->where(function ($query) use ($participant): void {
                    $query->where('caller_id', $participant['id'])
                        ->where('caller_role', $participant['role']);
                })->orWhere(function ($query) use ($participant): void {
                    $query->where('receiver_id', $participant['id'])
                        ->where('receiver_role', $participant['role']);
                });
            })
            ->exists();
    }

    private function looksLikeSdp(string $sdp): bool
    {
        return str_starts_with($sdp, 'v=0') && str_contains($sdp, 'm=');
    }
}

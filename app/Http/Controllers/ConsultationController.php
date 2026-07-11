<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesConsultations;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\StaffMotherCasefile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    use AuthorizesConsultations;

    public function staff(Request $request): View|RedirectResponse
    {
        $staff = $this->currentProgramStaff($request);

        if (! $staff) {
            return redirect()->route('login')->with('status', 'Please login as Program Staff first.');
        }

        $this->ensureConversationsForStaff($staff);

        return view('program-staff.consultation', compact('staff'));
    }

    public function mother(Request $request): View|RedirectResponse
    {
        $mother = $this->currentMother($request);

        if (! $mother) {
            return redirect()->route('login')->with('status', 'Please login as Mother first.');
        }

        $this->ensureConversationsForMother($mother);

        return view('mother.consultation', compact('mother'));
    }

    public function conversations(Request $request): JsonResponse
    {
        $participant = $this->currentConsultationParticipant($request);

        if (! $participant) {
            return response()->json(['message' => 'Please login before opening consultation.'], 401);
        }

        if ($participant['role'] === Message::ROLE_PROGRAM_STAFF) {
            $staff = $this->currentProgramStaff($request);
            $this->ensureConversationsForStaff($staff);
            $motherIds = StaffMotherCasefile::where('staff_id', $staff->id)->pluck('mother_id');

            $conversations = Conversation::with([
                'mother.maternalMonitoringRecords' => fn ($query) => $query
                    ->orderByDesc('recorded_at')
                    ->orderByDesc('created_at'),
                'programStaff',
                'lastMessage',
            ])
                ->where('program_staff_id', $staff->id)
                ->whereIn('mother_id', $motherIds)
                ->get();
        } else {
            $mother = $this->currentMother($request);
            $this->ensureConversationsForMother($mother);
            $staffIds = StaffMotherCasefile::where('mother_id', $mother->id)->pluck('staff_id');

            $conversations = Conversation::with([
                'mother.maternalMonitoringRecords' => fn ($query) => $query
                    ->orderByDesc('recorded_at')
                    ->orderByDesc('created_at'),
                'programStaff',
                'lastMessage',
            ])
                ->where('mother_id', $mother->id)
                ->whereIn('program_staff_id', $staffIds)
                ->get();
        }

        $conversations = $conversations
            ->sortByDesc(fn (Conversation $conversation): int => ($conversation->last_message_at ?? $conversation->updated_at)?->timestamp ?? 0)
            ->values();
        $selectedId = (int) $request->query('selected', 0);
        $selectedConversation = $selectedId > 0
            ? $conversations->firstWhere('id', $selectedId)
            : $conversations->first();

        return response()->json([
            'role' => $participant['role'],
            'conversations' => $conversations
                ->map(fn (Conversation $conversation): array => $this->conversationPayload($conversation, $request))
                ->values(),
            'selected_conversation_id' => $selectedConversation?->id,
        ]);
    }
}

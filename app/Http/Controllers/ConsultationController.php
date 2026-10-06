<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesConsultations;
use App\Models\Conversation;
use App\Models\DswdStaff;
use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Models\Message;
use App\Models\StaffMotherCasefile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    use AuthorizesConsultations;

    public function dswd(Request $request): View|RedirectResponse
    {
        if ($request->filled('staff')) {
            $staff = ProgramStaff::findOrFail($request->integer('staff'));
            $participant = $this->currentConsultationParticipant($request);
            abort_unless($participant && $participant['role'] === Message::ROLE_DSWD_STAFF, 403);
            $conversation = Conversation::firstOrCreate([
                'dswd_staff_id' => $participant['id'], 'program_staff_id' => $staff->id, 'mother_id' => null,
            ]);
            return redirect()->route('dswd.messaging', ['conversation' => $conversation->id, 'contacts' => 'program_staff']);
        }
        return view('mother.consultation', ['dswdMessaging' => true] + $this->contactSection($request));
    }

    private function contactSection(Request $request): array
    {
        $data = $request->validate(['contacts' => ['nullable', 'in:mother,program_staff,dswd_staff']]);
        $contactRole = $data['contacts'] ?? '';
        $contactTitle = match ($contactRole) {
            'mother' => '4Ps Beneficiaries',
            'program_staff' => 'Program Staff',
            'dswd_staff' => 'DSWD Staff',
            default => null,
        };
        return compact('contactRole', 'contactTitle');
    }

    private function ensureDswdConversations(array $participant): void
    {
        if ($participant['role'] === Message::ROLE_DSWD_STAFF) {
            foreach (Mother::where('is_4ps_beneficiary', true)->pluck('id') as $id) {
                Conversation::firstOrCreate(['dswd_staff_id' => $participant['id'], 'mother_id' => $id, 'program_staff_id' => null]);
            }
            foreach (ProgramStaff::pluck('id') as $id) {
                Conversation::firstOrCreate(['dswd_staff_id' => $participant['id'], 'program_staff_id' => $id, 'mother_id' => null]);
            }
            return;
        }
        if ($participant['role'] === Message::ROLE_MOTHER && ! Mother::find($participant['id'])?->is_4ps_beneficiary) {
            return;
        }
        foreach (DswdStaff::where('is_active', true)->pluck('id') as $id) {
            Conversation::firstOrCreate([
                'dswd_staff_id' => $id,
                'mother_id' => $participant['role'] === Message::ROLE_MOTHER ? $participant['id'] : null,
                'program_staff_id' => $participant['role'] === Message::ROLE_PROGRAM_STAFF ? $participant['id'] : null,
            ]);
        }
    }

    public function staff(Request $request): View|RedirectResponse
    {
        $staff = $this->currentProgramStaff($request);

        if (! $staff) {
            return redirect()->route('login')->with('status', 'Please login as Program Staff first.');
        }

        $this->ensureConversationsForStaff($staff);

        if ($request->filled('dswd_staff')) {
            $officer = DswdStaff::where('is_active', true)->findOrFail($request->integer('dswd_staff'));
            $conversation = Conversation::firstOrCreate([
                'dswd_staff_id' => $officer->id, 'program_staff_id' => $staff->id, 'mother_id' => null,
            ]);
            return redirect()->route('staff.consultation', ['conversation' => $conversation->id, 'contacts' => 'dswd_staff']);
        }

        return view('program-staff.consultation', compact('staff') + $this->contactSection($request));
    }

    public function mother(Request $request): View|RedirectResponse
    {
        $mother = $this->currentMother($request);

        if (! $mother) {
            return redirect()->route('login')->with('status', 'Please login as Mother first.');
        }

        abort_if($request->query('contacts') === 'dswd_staff' && ! $mother->is_4ps_beneficiary, 403);

        $this->ensureConversationsForMother($mother);

        if ($request->filled('staff')) {
            $conversation = Conversation::where('mother_id', $mother->id)
                ->where('program_staff_id', $request->integer('staff'))->first();
            if (! $conversation) {
                return redirect()->route('mother.clinic-schedule.index')
                    ->with('status', 'Book an appointment with this healthcare worker first to start your conversation.');
            }

            return redirect()->route('mother.consultation', ['conversation' => $conversation->id]);
        }

        return view('mother.consultation', compact('mother') + $this->contactSection($request));
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
        } elseif ($participant['role'] === Message::ROLE_MOTHER) {
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
        } else {
            $conversations = collect();
        }

        $this->ensureDswdConversations($participant);
        $participantColumn = match ($participant['role']) {
            Message::ROLE_DSWD_STAFF => 'dswd_staff_id',
            Message::ROLE_MOTHER => 'mother_id',
            default => 'program_staff_id',
        };
        $dswdConversations = Conversation::with(['mother', 'programStaff', 'dswdStaff', 'lastMessage'])
            ->whereNotNull('dswd_staff_id')->where($participantColumn, $participant['id'])->get()
            ->filter(fn (Conversation $conversation) => $this->authorizeConversation($request, $conversation));

        $conversations = $conversations->concat($dswdConversations)
            ->sortByDesc(fn (Conversation $conversation): int => ($conversation->last_message_at ?? $conversation->updated_at)?->timestamp ?? 0)
            ->values();
        $contactRole = $this->contactSection($request)['contactRole'];
        if ($contactRole !== '') {
            $conversations = $conversations->filter(fn (Conversation $conversation) =>
                $this->receiverForConversation($conversation, $participant['role'])['role'] === $contactRole
            )->values();
        }
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

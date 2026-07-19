<?php

namespace App\Http\Controllers;

use App\Models\ProgramStaff;
use App\Models\StaffCoordinationMessage;
use App\Models\StaffCoordinationThread;
use App\Support\AppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StaffCoordinationController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $staff = $this->currentStaff($request);

        if (! $staff) {
            return redirect()->route('login')->with('status', 'Please login as Program Staff first.');
        }

        $this->ensureThreadsForStaff($staff);

        return view('program-staff.staff-coordination', compact('staff'));
    }

    public function threads(Request $request): JsonResponse
    {
        $staff = $this->currentStaff($request);

        if (! $staff) {
            return response()->json(['message' => 'Please login as Program Staff first.'], 401);
        }

        $this->ensureThreadsForStaff($staff);

        $threads = StaffCoordinationThread::with(['staffOne', 'staffTwo', 'lastMessage'])
            ->where(function ($query) use ($staff): void {
                $query->where('staff_one_id', $staff->id)
                    ->orWhere('staff_two_id', $staff->id);
            })
            ->get()
            ->sortByDesc(fn (StaffCoordinationThread $thread): int => ($thread->last_message_at ?? $thread->updated_at)?->timestamp ?? 0)
            ->values();

        $selectedId = (int) $request->query('selected', 0);
        $selectedThread = $selectedId > 0
            ? $threads->firstWhere('id', $selectedId)
            : $threads->first();

        return response()->json([
            'threads' => $threads->map(fn (StaffCoordinationThread $thread): array => $this->threadPayload($thread, $staff))->values(),
            'selected_thread_id' => $selectedThread?->id,
        ]);
    }

    public function messages(Request $request, StaffCoordinationThread $thread): JsonResponse
    {
        $staff = $this->currentStaff($request);

        if (! $staff || ! $this->authorizesThread($staff, $thread)) {
            return response()->json(['message' => 'You cannot open this staff coordination thread.'], 403);
        }

        StaffCoordinationMessage::where('thread_id', $thread->id)
            ->where('receiver_staff_id', $staff->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        $messages = StaffCoordinationMessage::with(['sender', 'receiver'])
            ->where('thread_id', $thread->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return response()->json([
            'thread' => $this->threadPayload($thread->refresh(), $staff),
            'messages' => $messages->map(fn (StaffCoordinationMessage $message): array => $this->messagePayload($message, $staff))->values(),
        ]);
    }

    public function store(Request $request, StaffCoordinationThread $thread): JsonResponse
    {
        $staff = $this->currentStaff($request);

        if (! $staff || ! $this->authorizesThread($staff, $thread)) {
            return response()->json(['message' => 'You cannot send a message to this staff coordination thread.'], 403);
        }

        $trimmedMessage = trim((string) $request->input('message', ''));
        $request->merge(['message' => $trimmedMessage]);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ], [
            'message.required' => 'Type a staff coordination message before sending.',
            'message.max' => 'Messages may not be longer than 1000 characters.',
        ]);

        $receiver = $this->otherStaff($thread, $staff);

        if (! $receiver) {
            return response()->json(['message' => 'The receiving Program Staff account could not be found.'], 422);
        }

        $message = StaffCoordinationMessage::create([
            'thread_id' => $thread->id,
            'sender_staff_id' => $staff->id,
            'receiver_staff_id' => $receiver->id,
            'message' => $validated['message'],
        ]);

        $thread->forceFill([
            'last_message_id' => $message->id,
            'last_message_at' => $message->created_at,
        ])->save();

        AppNotificationService::create(
            (int) $receiver->id,
            'program_staff',
            'staff_coordination_message',
            'New staff coordination message',
            $staff->full_name.': '.AppNotificationService::preview($validated['message']),
            [
                'thread_id' => $thread->id,
                'url' => route('staff.coordination', ['thread' => $thread->id]),
            ]
        );

        return response()->json([
            'message' => $this->messagePayload($message->load(['sender', 'receiver']), $staff),
            'thread' => $this->threadPayload($thread->refresh(), $staff),
        ], 201);
    }

    public function markRead(Request $request, StaffCoordinationThread $thread): JsonResponse
    {
        $staff = $this->currentStaff($request);

        if (! $staff || ! $this->authorizesThread($staff, $thread)) {
            return response()->json(['message' => 'You cannot update this staff coordination thread.'], 403);
        }

        StaffCoordinationMessage::where('thread_id', $thread->id)
            ->where('receiver_staff_id', $staff->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['thread' => $this->threadPayload($thread->refresh(), $staff)]);
    }

    public function unsend(Request $request, StaffCoordinationMessage $message): JsonResponse
    {
        $staff = $this->currentStaff($request);
        $message->loadMissing('thread');

        if (! $staff || ! $this->authorizesThread($staff, $message->thread)) {
            return response()->json(['message' => 'You cannot update this staff coordination message.'], 403);
        }

        if ($message->sender_staff_id !== $staff->id) {
            return response()->json(['message' => 'Only the original sender can unsend this message.'], 403);
        }

        $message->forceFill([
            'message' => null,
            'is_unsent' => true,
            'unsent_at' => now(),
        ])->save();

        return response()->json([
            'message' => $this->messagePayload($message->refresh()->load(['sender', 'receiver']), $staff),
            'thread' => $this->threadPayload($message->thread->refresh(), $staff),
        ]);
    }

    private function currentStaff(Request $request): ?ProgramStaff
    {
        if ($request->session()->get('auth_role') !== 'staff') {
            return null;
        }

        $id = (int) $request->session()->get('auth_id');

        return $id > 0 ? ProgramStaff::find($id) : null;
    }

    private function ensureThreadsForStaff(ProgramStaff $staff): void
    {
        ProgramStaff::where('id', '!=', $staff->id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id'])
            ->each(function (ProgramStaff $otherStaff) use ($staff): void {
                [$staffOneId, $staffTwoId] = $this->orderedStaffIds($staff->id, $otherStaff->id);

                StaffCoordinationThread::firstOrCreate([
                    'staff_one_id' => $staffOneId,
                    'staff_two_id' => $staffTwoId,
                ]);
            });
    }

    private function orderedStaffIds(int $firstStaffId, int $secondStaffId): array
    {
        return $firstStaffId < $secondStaffId
            ? [$firstStaffId, $secondStaffId]
            : [$secondStaffId, $firstStaffId];
    }

    private function authorizesThread(ProgramStaff $staff, StaffCoordinationThread $thread): bool
    {
        return $thread->staff_one_id === $staff->id || $thread->staff_two_id === $staff->id;
    }

    private function otherStaff(StaffCoordinationThread $thread, ProgramStaff $staff): ?ProgramStaff
    {
        $thread->loadMissing(['staffOne', 'staffTwo']);

        return $thread->staff_one_id === $staff->id ? $thread->staffTwo : $thread->staffOne;
    }

    private function threadPayload(StaffCoordinationThread $thread, ProgramStaff $viewer): array
    {
        $thread->loadMissing(['staffOne', 'staffTwo', 'lastMessage']);
        $otherStaff = $this->otherStaff($thread, $viewer);
        $lastMessage = $thread->lastMessage;

        return [
            'id' => $thread->id,
            'participant' => $this->staffProfilePayload($otherStaff),
            'latest_message' => $lastMessage ? $this->messagePreview($lastMessage) : 'No staff update yet',
            'latest_message_time' => $lastMessage?->created_at?->format('g:i A') ?? '',
            'latest_message_iso' => $lastMessage?->created_at?->toIso8601String(),
            'unread_count' => StaffCoordinationMessage::where('thread_id', $thread->id)
                ->where('receiver_staff_id', $viewer->id)
                ->where('is_read', false)
                ->where('is_unsent', false)
                ->count(),
            'updated_at' => ($thread->last_message_at ?? $thread->updated_at)?->toIso8601String(),
        ];
    }

    private function messagePayload(StaffCoordinationMessage $message, ProgramStaff $viewer): array
    {
        $message->loadMissing(['sender', 'receiver']);
        $isOwn = $message->sender_staff_id === $viewer->id;

        return [
            'id' => $message->id,
            'thread_id' => $message->thread_id,
            'sender_staff_id' => $message->sender_staff_id,
            'sender_name' => $message->sender?->full_name ?: 'Program Staff',
            'receiver_staff_id' => $message->receiver_staff_id,
            'receiver_name' => $message->receiver?->full_name ?: 'Program Staff',
            'message' => $message->is_unsent ? 'This message was unsent.' : $message->message,
            'is_read' => $message->is_read,
            'read_at' => $message->read_at?->toIso8601String(),
            'is_unsent' => $message->is_unsent,
            'created_at' => $message->created_at?->toIso8601String(),
            'created_time' => $message->created_at?->format('g:i A') ?? '',
            'created_label' => $message->created_at?->format('M j, Y g:i A') ?? '',
            'is_own' => $isOwn,
            'can_unsend' => $isOwn && ! $message->is_unsent,
        ];
    }

    private function staffProfilePayload(?ProgramStaff $staff): array
    {
        $name = $staff?->full_name ?: 'Program Staff';

        return [
            'id' => $staff?->id,
            'role' => 'program_staff',
            'role_label' => $staff?->role_label ?: 'Program Staff',
            'name' => $name,
            'initials' => $this->initials($name),
            'staff_id' => $staff?->staff_id,
            'online' => $this->isOnline($staff),
            'status_text' => $this->isOnline($staff) ? 'Online' : 'Offline',
        ];
    }

    private function messagePreview(StaffCoordinationMessage $message): string
    {
        if ($message->is_unsent) {
            return 'This message was unsent.';
        }

        return Str::limit(trim((string) $message->message), 80) ?: 'No message preview';
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return $initials ?: 'PS';
    }

    private function isOnline(?ProgramStaff $staff): bool
    {
        return $staff?->updated_at instanceof Carbon
            && $staff->updated_at->greaterThan(now()->subMinutes(10));
    }
}

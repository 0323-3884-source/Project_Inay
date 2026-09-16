<?php

namespace App\Http\Controllers;

use App\Models\AdminStaffMessage;
use App\Models\AdminStaffThread;
use App\Models\AdminUser;
use App\Models\ProgramStaff;
use App\Support\AppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminStaffMessageController extends Controller
{
    public function adminIndex(Request $request): View|RedirectResponse
    {
        $admin = $this->currentAdmin($request);

        if (! $admin) {
            return redirect()->route('admin.login');
        }

        $this->ensureThreadsForAdmin($admin);

        $selectedThreadId = (int) $request->query('thread', 0);
        $selectedStaffId = (int) $request->query('staff', 0);

        if ($selectedStaffId > 0) {
            $selectedStaff = ProgramStaff::find($selectedStaffId);
            $selectedThreadId = $selectedStaff
                ? $this->ensureThreadForPair($admin, $selectedStaff)->id
                : 0;
        }

        return view('admin.staff-messages.index', [
            'admin' => $admin,
            'adminUsername' => session('admin_username', $admin->username),
            'selectedThreadId' => $selectedThreadId,
        ]);
    }

    public function staffIndex(Request $request): View|RedirectResponse
    {
        $staff = $this->currentStaff($request);

        if (! $staff) {
            return redirect()->route('login')->with('status', 'Please login as Program Staff first.');
        }

        $this->ensureThreadsForStaff($staff);

        return view('program-staff.admin-messages', [
            'staff' => $staff,
            'selectedThreadId' => (int) $request->query('thread', 0),
        ]);
    }

    public function threads(Request $request): JsonResponse
    {
        $viewer = $this->currentViewer($request);

        if (! $viewer) {
            return response()->json(['message' => 'Please login before opening admin messages.'], 401);
        }

        if ($viewer['role'] === AdminStaffMessage::ROLE_ADMIN) {
            $admin = $viewer['model'];
            $this->ensureThreadsForAdmin($admin);

            $threads = AdminStaffThread::with(['adminUser', 'programStaff', 'lastMessage'])
                ->where('admin_user_id', $admin->id)
                ->get();
        } else {
            $staff = $viewer['model'];
            $this->ensureThreadsForStaff($staff);

            $threads = AdminStaffThread::with(['adminUser', 'programStaff', 'lastMessage'])
                ->where('program_staff_id', $staff->id)
                ->get();
        }

        $threads = $threads
            ->sortByDesc(fn (AdminStaffThread $thread): int => ($thread->last_message_at ?? $thread->updated_at)?->timestamp ?? 0)
            ->values();
        $selectedId = (int) $request->query('selected', 0);
        $selectedThread = $selectedId > 0
            ? $threads->firstWhere('id', $selectedId)
            : $threads->first();

        return response()->json([
            'role' => $viewer['role'],
            'threads' => $threads->map(fn (AdminStaffThread $thread): array => $this->threadPayload($thread, $viewer['role']))->values(),
            'selected_thread_id' => $selectedThread?->id,
        ]);
    }

    public function messages(Request $request, AdminStaffThread $thread): JsonResponse
    {
        $viewer = $this->currentViewer($request);

        if (! $viewer || ! $this->authorizesThread($viewer, $thread)) {
            return response()->json(['message' => 'You cannot open this admin message thread.'], 403);
        }

        AdminStaffMessage::where('thread_id', $thread->id)
            ->where('sender_role', $this->counterpartRole($viewer['role']))
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        $messages = AdminStaffMessage::with(['thread.adminUser', 'thread.programStaff'])
            ->where('thread_id', $thread->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return response()->json([
            'thread' => $this->threadPayload($thread->refresh(), $viewer['role']),
            'messages' => $messages->map(fn (AdminStaffMessage $message): array => $this->messagePayload($message, $viewer['role']))->values(),
        ]);
    }

    public function store(Request $request, AdminStaffThread $thread): JsonResponse
    {
        $viewer = $this->currentViewer($request);

        if (! $viewer || ! $this->authorizesThread($viewer, $thread)) {
            return response()->json(['message' => 'You cannot send a message to this admin thread.'], 403);
        }

        $trimmedMessage = trim((string) $request->input('message', ''));
        $request->merge(['message' => $trimmedMessage]);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ], [
            'message.required' => 'Type an admin message before sending.',
            'message.max' => 'Messages may not be longer than 1000 characters.',
        ]);

        $message = AdminStaffMessage::create([
            'thread_id' => $thread->id,
            'sender_role' => $viewer['role'],
            'message' => $validated['message'],
        ]);

        $thread->forceFill([
            'last_message_id' => $message->id,
            'last_message_at' => $message->created_at,
        ])->save();

        $thread->loadMissing(['adminUser', 'programStaff']);
        $this->notifyAdminStaffMessage($thread, $viewer['role'], $validated['message']);

        return response()->json([
            'message' => $this->messagePayload($message->load(['thread.adminUser', 'thread.programStaff']), $viewer['role']),
            'thread' => $this->threadPayload($thread->refresh(), $viewer['role']),
        ], 201);
    }

    public function markRead(Request $request, AdminStaffThread $thread): JsonResponse
    {
        $viewer = $this->currentViewer($request);

        if (! $viewer || ! $this->authorizesThread($viewer, $thread)) {
            return response()->json(['message' => 'You cannot update this admin message thread.'], 403);
        }

        AdminStaffMessage::where('thread_id', $thread->id)
            ->where('sender_role', $this->counterpartRole($viewer['role']))
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['thread' => $this->threadPayload($thread->refresh(), $viewer['role'])]);
    }

    public function unsend(Request $request, AdminStaffMessage $message): JsonResponse
    {
        $viewer = $this->currentViewer($request);
        $message->loadMissing('thread');

        if (! $viewer || ! $this->authorizesThread($viewer, $message->thread)) {
            return response()->json(['message' => 'You cannot update this admin message.'], 403);
        }

        if ($message->sender_role !== $viewer['role']) {
            return response()->json(['message' => 'Only the original sender can unsend this message.'], 403);
        }

        $message->forceFill([
            'message' => null,
            'is_unsent' => true,
            'unsent_at' => now(),
        ])->save();

        return response()->json([
            'message' => $this->messagePayload($message->refresh()->load(['thread.adminUser', 'thread.programStaff']), $viewer['role']),
            'thread' => $this->threadPayload($message->thread->refresh(), $viewer['role']),
        ]);
    }

    private function currentViewer(Request $request): ?array
    {
        if ($request->session()->has('auth_role')) {
            $staff = $this->currentStaff($request);

            return $staff ? ['role' => AdminStaffMessage::ROLE_PROGRAM_STAFF, 'model' => $staff] : null;
        }

        $admin = $this->currentAdmin($request);

        return $admin ? ['role' => AdminStaffMessage::ROLE_ADMIN, 'model' => $admin] : null;
    }

    private function currentAdmin(Request $request): ?AdminUser
    {
        if ($request->session()->get('admin_authenticated') !== true) {
            return null;
        }

        $id = (int) $request->session()->get('admin_id');

        return $id > 0 ? AdminUser::find($id) : null;
    }

    private function currentStaff(Request $request): ?ProgramStaff
    {
        if ($request->session()->get('auth_role') !== 'staff') {
            return null;
        }

        $id = (int) $request->session()->get('auth_id');

        return $id > 0 ? ProgramStaff::find($id) : null;
    }

    private function ensureThreadsForAdmin(AdminUser $admin): void
    {
        ProgramStaff::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id'])
            ->each(fn (ProgramStaff $staff): AdminStaffThread => $this->ensureThreadForPair($admin, $staff));
    }

    private function ensureThreadsForStaff(ProgramStaff $staff): void
    {
        AdminUser::query()
            ->orderBy('username')
            ->get(['id'])
            ->each(fn (AdminUser $admin): AdminStaffThread => $this->ensureThreadForPair($admin, $staff));
    }

    private function ensureThreadForPair(AdminUser $admin, ProgramStaff $staff): AdminStaffThread
    {
        return AdminStaffThread::firstOrCreate([
            'admin_user_id' => $admin->id,
            'program_staff_id' => $staff->id,
        ]);
    }

    private function authorizesThread(array $viewer, AdminStaffThread $thread): bool
    {
        return $viewer['role'] === AdminStaffMessage::ROLE_ADMIN
            ? $thread->admin_user_id === $viewer['model']->id
            : $thread->program_staff_id === $viewer['model']->id;
    }

    private function counterpartRole(string $role): string
    {
        return $role === AdminStaffMessage::ROLE_ADMIN
            ? AdminStaffMessage::ROLE_PROGRAM_STAFF
            : AdminStaffMessage::ROLE_ADMIN;
    }

    private function threadPayload(AdminStaffThread $thread, string $viewerRole): array
    {
        $thread->loadMissing(['adminUser', 'programStaff', 'lastMessage']);
        $lastMessage = $thread->lastMessage;
        $participant = $viewerRole === AdminStaffMessage::ROLE_ADMIN
            ? $this->staffProfilePayload($thread->programStaff)
            : $this->adminProfilePayload($thread->adminUser);

        return [
            'id' => $thread->id,
            'participant' => $participant,
            'latest_message' => $lastMessage ? $this->messagePreview($lastMessage) : 'No admin message yet',
            'latest_message_time' => $lastMessage?->created_at?->format('g:i A') ?? '',
            'latest_message_iso' => $lastMessage?->created_at?->toIso8601String(),
            'unread_count' => AdminStaffMessage::where('thread_id', $thread->id)
                ->where('sender_role', $this->counterpartRole($viewerRole))
                ->where('is_read', false)
                ->where('is_unsent', false)
                ->count(),
            'updated_at' => ($thread->last_message_at ?? $thread->updated_at)?->toIso8601String(),
        ];
    }

    private function notifyAdminStaffMessage(AdminStaffThread $thread, string $senderRole, string $message): void
    {
        if ($senderRole === AdminStaffMessage::ROLE_ADMIN) {
            AppNotificationService::create(
                (int) $thread->program_staff_id,
                AdminStaffMessage::ROLE_PROGRAM_STAFF,
                'admin_staff_message',
                'New admin message',
                ($thread->adminUser?->username ?: 'Admin').': '.AppNotificationService::preview($message),
                [
                    'thread_id' => $thread->id,
                    'url' => route('staff.admin-messages', ['thread' => $thread->id]),
                ]
            );

            return;
        }

        AppNotificationService::create(
            (int) $thread->admin_user_id,
            AdminStaffMessage::ROLE_ADMIN,
            'admin_staff_message',
            'New Program Staff reply',
            ($thread->programStaff?->full_name ?: 'Program Staff').': '.AppNotificationService::preview($message),
            [
                'thread_id' => $thread->id,
                'url' => route('admin.staff-messages.index', ['thread' => $thread->id]),
            ]
        );
    }

    private function messagePayload(AdminStaffMessage $message, string $viewerRole): array
    {
        $message->loadMissing(['thread.adminUser', 'thread.programStaff']);
        $senderName = $message->sender_role === AdminStaffMessage::ROLE_ADMIN
            ? ($message->thread->adminUser?->username ?: 'Admin')
            : ($message->thread->programStaff?->full_name ?: 'Program Staff');

        return [
            'id' => $message->id,
            'thread_id' => $message->thread_id,
            'sender_role' => $message->sender_role,
            'sender_role_label' => $this->roleLabel($message->sender_role),
            'sender_name' => $senderName,
            'message' => $message->is_unsent ? 'This message was unsent.' : $message->message,
            'is_read' => $message->is_read,
            'read_at' => $message->read_at?->toIso8601String(),
            'is_unsent' => $message->is_unsent,
            'created_at' => $message->created_at?->toIso8601String(),
            'created_time' => $message->created_at?->format('g:i A') ?? '',
            'created_label' => $message->created_at?->format('M j, Y g:i A') ?? '',
            'is_own' => $message->sender_role === $viewerRole,
            'can_unsend' => $message->sender_role === $viewerRole && ! $message->is_unsent,
        ];
    }

    private function staffProfilePayload(?ProgramStaff $staff): array
    {
        $name = $staff?->full_name ?: 'Program Staff';

        return [
            'id' => $staff?->id,
            'role' => AdminStaffMessage::ROLE_PROGRAM_STAFF,
            'role_label' => $staff?->role_label ?: 'Program Staff',
            'name' => $name,
            'initials' => $this->initials($name),
            'staff_id' => $staff?->staff_id,
            'contact_number' => $staff?->contact_number,
            'online' => $this->isOnline($staff),
            'status_text' => $this->isOnline($staff) ? 'Online' : 'Offline',
        ];
    }

    private function adminProfilePayload(?AdminUser $admin): array
    {
        $name = $admin?->username ?: 'Admin';

        return [
            'id' => $admin?->id,
            'role' => AdminStaffMessage::ROLE_ADMIN,
            'role_label' => 'Admin',
            'name' => $name,
            'initials' => $this->initials($name),
            'staff_id' => null,
            'contact_number' => config('contacts.admin_phone'),
            'online' => $this->isOnline($admin),
            'status_text' => $this->isOnline($admin) ? 'Online' : 'Offline',
        ];
    }

    private function roleLabel(string $role): string
    {
        return $role === AdminStaffMessage::ROLE_ADMIN ? 'Admin' : 'Program Staff';
    }

    private function messagePreview(AdminStaffMessage $message): string
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

        return $initials ?: 'AM';
    }

    private function isOnline(?object $model): bool
    {
        return $model?->updated_at instanceof Carbon
            && $model->updated_at->greaterThan(now()->subMinutes(10));
    }
}

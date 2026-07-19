<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\AppNotification;
use App\Models\Message;
use App\Models\Mother;
use App\Models\ProgramStaff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $recipient = $this->currentRecipient($request);

        if (! $recipient) {
            return response()->json(['message' => 'Please login before opening notifications.'], 401);
        }

        $notifications = $this->queryForRecipient($recipient)
            ->latest()
            ->limit(15)
            ->get();

        return response()->json([
            'recipient_role' => $recipient['role'],
            'recipient_id' => $recipient['id'],
            'unread_count' => $this->queryForRecipient($recipient)
                ->whereNull('read_at')
                ->count(),
            'notifications' => $notifications
                ->map(fn (AppNotification $notification): array => $this->payload($notification))
                ->values(),
        ]);
    }

    public function markRead(Request $request, AppNotification $notification): JsonResponse
    {
        $recipient = $this->currentRecipient($request);

        if (! $recipient || ! $this->ownsNotification($notification, $recipient)) {
            return response()->json(['message' => 'You cannot update this notification.'], 403);
        }

        if (! $notification->read_at) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return response()->json([
            'notification' => $this->payload($notification->refresh()),
            'unread_count' => $this->queryForRecipient($recipient)
                ->whereNull('read_at')
                ->count(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $recipient = $this->currentRecipient($request);

        if (! $recipient) {
            return response()->json(['message' => 'Please login before opening notifications.'], 401);
        }

        $this->queryForRecipient($recipient)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'unread_count' => 0,
        ]);
    }

    private function currentRecipient(Request $request): ?array
    {
        if ($request->session()->has('auth_role')) {
            $role = $request->session()->get('auth_role');
            $id = (int) $request->session()->get('auth_id');

            if ($role === Message::ROLE_MOTHER && $id > 0 && Mother::whereKey($id)->exists()) {
                return ['id' => $id, 'role' => Message::ROLE_MOTHER];
            }

            if (in_array($role, ['staff', Message::ROLE_PROGRAM_STAFF], true) && $id > 0 && ProgramStaff::whereKey($id)->exists()) {
                return ['id' => $id, 'role' => Message::ROLE_PROGRAM_STAFF];
            }

            return null;
        }

        $adminId = (int) $request->session()->get('admin_id');

        if ($request->session()->get('admin_authenticated') === true && $adminId > 0 && AdminUser::whereKey($adminId)->exists()) {
            return ['id' => $adminId, 'role' => 'admin'];
        }

        return null;
    }

    private function queryForRecipient(array $recipient)
    {
        return AppNotification::query()
            ->where('recipient_id', $recipient['id'])
            ->where('recipient_role', $recipient['role']);
    }

    private function ownsNotification(AppNotification $notification, array $recipient): bool
    {
        return (int) $notification->recipient_id === (int) $recipient['id']
            && $notification->recipient_role === $recipient['role'];
    }

    private function payload(AppNotification $notification): array
    {
        $data = $notification->data ?? [];

        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'url' => is_array($data) ? ($data['url'] ?? null) : null,
            'data' => $data,
            'is_read' => $notification->read_at !== null,
            'created_at' => $notification->created_at?->toIso8601String(),
            'created_label' => $notification->created_at?->diffForHumans() ?? '',
        ];
    }
}

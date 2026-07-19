<?php

namespace App\Support;

use App\Models\AppNotification;
use Illuminate\Support\Str;

class AppNotificationService
{
    public static function create(
        int $recipientId,
        string $recipientRole,
        string $type,
        string $title,
        ?string $body = null,
        array $data = [],
        ?int $appointmentId = null,
    ): AppNotification {
        return AppNotification::create([
            'recipient_id' => $recipientId,
            'recipient_role' => $recipientRole,
            'appointment_id' => $appointmentId,
            'type' => $type,
            'title' => Str::limit($title, 255, ''),
            'body' => $body ? Str::limit($body, 500, '') : null,
            'data' => $data,
            'read_at' => null,
        ]);
    }

    public static function preview(?string $message, string $fallback = 'Sent a new message.'): string
    {
        $message = trim((string) $message);

        return $message !== '' ? Str::limit($message, 140) : $fallback;
    }
}

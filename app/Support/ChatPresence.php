<?php

namespace App\Support;

use App\Models\ProgramStaff;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class ChatPresence
{
    public const PRESENCE_TTL_MINUTES = 5;

    public static function isOnline(?object $model): bool
    {
        if (! $model) {
            return false;
        }

        if (app()->environment('local') && $model instanceof ProgramStaff
            && Cache::get('local-chat-online:program-staff:'.$model->id, false)) {
            return true;
        }

        if ($model instanceof ProgramStaff) {
            if (Cache::has('user-presence:program_staff:'.$model->id) || Cache::has('user-presence:staff:'.$model->id)) {
                return true;
            }
        } elseif (isset($model->id)) {
            $class = class_basename($model);
            $roleKey = $model instanceof \App\Models\DswdStaff ? 'dswd_staff' : strtolower($class);
            if (Cache::has("user-presence:{$roleKey}:{$model->id}")) {
                return true;
            }
        }

        return $model->updated_at instanceof Carbon
            && $model->updated_at->greaterThan(now()->subMinutes(10));
    }

    public static function recordPresence(string $role, int $id): void
    {
        if ($id <= 0) {
            return;
        }

        $ttl = now()->addMinutes(self::PRESENCE_TTL_MINUTES);
        Cache::put("user-presence:{$role}:{$id}", now()->timestamp, $ttl);

        if ($role === 'staff') {
            Cache::put("user-presence:program_staff:{$id}", now()->timestamp, $ttl);
        } elseif ($role === 'program_staff') {
            Cache::put("user-presence:staff:{$id}", now()->timestamp, $ttl);
        }
    }

    public static function recordOffline(string $role, int $id): void
    {
        if ($id <= 0) {
            return;
        }

        Cache::forget("user-presence:{$role}:{$id}");

        if ($role === 'staff') {
            Cache::forget("user-presence:program_staff:{$id}");
            Cache::forget("local-chat-online:program-staff:{$id}");
        } elseif ($role === 'program_staff') {
            Cache::forget("user-presence:staff:{$id}");
            Cache::forget("local-chat-online:program-staff:{$id}");
        }
    }
}

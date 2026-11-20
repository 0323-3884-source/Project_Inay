<?php

namespace App\Support;

use App\Models\{DswdStaff, Mother, ProgramStaff};
use Illuminate\Support\Facades\Cache;

class StaffActivity
{
    public static function role(object $staff): string
    {
        return $staff instanceof Mother ? 'mother' : ($staff instanceof DswdStaff ? 'dswd_staff' : 'program_staff');
    }

    public static function record(string $role, int $id, bool $login = false): void
    {
        $role = $role === 'staff' ? 'program_staff' : $role;
        if (! in_array($role, ['program_staff', 'dswd_staff', 'mother'], true) || $id <= 0) return;
        Cache::put("staff-activity:{$role}:{$id}:seen", now()->timestamp, now()->addDays(30));
        if ($login) Cache::put("staff-activity:{$role}:{$id}:login", now()->timestamp, now()->addDays(30));
    }

    public static function summary(object $staff): array
    {
        $role = self::role($staff);
        $eligible = $staff instanceof Mother || ($staff instanceof DswdStaff ? $staff->is_active : $staff->is_approved);
        return [
            'id' => $staff->id,
            'active' => $eligible && Cache::has("user-presence:{$role}:{$staff->id}"),
            'last_seen' => self::time(Cache::get("staff-activity:{$role}:{$staff->id}:seen")),
            'last_login' => self::time(Cache::get("staff-activity:{$role}:{$staff->id}:login")),
        ];
    }

    private static function time(?int $timestamp): ?string
    {
        return $timestamp ? \Illuminate\Support\Carbon::createFromTimestamp($timestamp)->setTimezone('Asia/Manila')->format('M d, Y h:i A').' PHT' : null;
    }
}

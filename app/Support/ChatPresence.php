<?php

namespace App\Support;

use App\Models\ProgramStaff;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class ChatPresence
{
    public static function isOnline(?object $model): bool
    {
        if (app()->environment('local') && $model instanceof ProgramStaff
            && Cache::get('local-chat-online:program-staff:'.$model->id, false)) {
            return true;
        }

        return $model?->updated_at instanceof Carbon
            && $model->updated_at->greaterThan(now()->subMinutes(10));
    }
}

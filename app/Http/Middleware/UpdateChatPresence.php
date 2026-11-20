<?php

namespace App\Http\Middleware;

use App\Support\ChatPresence;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateChatPresence
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession()) {
            $role = (string) $request->session()->get('auth_role');
            $id = (int) $request->session()->get('auth_id');

            $eligible = match ($role) {
                'staff', 'program_staff' => \App\Models\ProgramStaff::whereKey($id)->where('approval_status', 'approved')->exists(),
                'dswd_staff' => \App\Models\DswdStaff::whereKey($id)->where('is_active', true)->exists(),
                'mother' => \App\Models\Mother::whereKey($id)->exists(),
                default => false,
            };
            if ($eligible && $id > 0) {
                ChatPresence::recordPresence($role, $id);
                \App\Support\StaffActivity::record($role, $id);
            }
        }

        return $next($request);
    }
}

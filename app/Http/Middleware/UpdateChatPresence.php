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

            if (in_array($role, ['mother', 'staff', 'program_staff'], true) && $id > 0) {
                ChatPresence::recordPresence($role, $id);
            }
        }

        return $next($request);
    }
}

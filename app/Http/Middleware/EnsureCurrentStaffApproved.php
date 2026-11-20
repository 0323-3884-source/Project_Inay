<?php

namespace App\Http\Middleware;

use App\Models\ProgramStaff;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentStaffApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->session()->get('auth_role'), ['staff', 'program_staff'], true)
            && ! ProgramStaff::whereKey($request->session()->get('auth_id'))->where('approval_status', 'approved')->exists()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Your staff account is unavailable. Contact your administrator.');
        }

        return $next($request);
    }
}

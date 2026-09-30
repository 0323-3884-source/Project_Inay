<?php

namespace App\Http\Middleware;

use App\Models\DswdStaff;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDswdAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('auth_role')) {
            return redirect()->route('login');
        }
        abort_unless($request->session()->get('auth_role') === 'dswd_staff', 403);
        $staff = DswdStaff::find($request->session()->get('auth_id'));
        if (! $staff || ! $staff->is_active) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email' => 'Your DSWD account is unavailable. Contact your administrator.']);
        }
        $request->attributes->set('dswd_staff', $staff);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        return $response;
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DatabaseRecoveryGuard
{
    public function handle(Request $request, Closure $next)
    {
        if (Cache::get('database-recovery-in-progress') && ! $request->routeIs('admin.backups.*')) {
            return response('Database recovery is in progress. Please try again shortly.', 503)->header('Retry-After', '60');
        }
        if ($request->session()->has('auth_role') || $request->session()->get('admin_authenticated') === true) {
            if ($request->session()->get('database_security_epoch', 'initial') !== Cache::get('database-security-epoch', 'initial')) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return $request->expectsJson() ? response()->json(['message' => 'Sign in again after database recovery.'], 401) : redirect()->route('login');
            }
        }
        return $next($request);
    }
}

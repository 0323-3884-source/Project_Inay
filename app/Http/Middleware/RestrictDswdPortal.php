<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictDswdPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        // Fail closed for this role, including shared APIs, downloads and future modules.
        if ($request->session()->get('auth_role') === 'dswd_staff') {
            abort_unless($request->routeIs('dswd.*', 'login', 'logout', 'home'), 403);
        }
        return $next($request);
    }
}

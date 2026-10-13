<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('admin_authenticated') !== true || ! \App\Models\AdminUser::whereKey($request->session()->get('admin_id'))->exists()) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}

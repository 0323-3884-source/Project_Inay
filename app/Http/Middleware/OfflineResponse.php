<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OfflineResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $role = $request->session()->get('auth_role');
        $id = $request->session()->get('auth_id');

        if ($request->isMethod('GET') && in_array($role, ['mother', 'staff'], true) && $id && $response->isSuccessful()) {
            $response->headers->set('X-INAY-Offline-User', hash_hmac('sha256', "$role:$id", config('app.key')));
        }

        return $response;
    }
}

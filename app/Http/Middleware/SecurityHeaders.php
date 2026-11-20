<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'same-origin');
        if ($request->session()->has('auth_role') || $request->session()->get('admin_authenticated') === true) {
            // The explicit offline feature manages its own browser cache.
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        return $response;
    }
}

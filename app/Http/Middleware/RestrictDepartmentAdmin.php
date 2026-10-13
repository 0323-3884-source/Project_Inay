<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictDepartmentAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('admin_authenticated') === true) {
            $admin = AdminUser::find($request->session()->get('admin_id'));
            if (! $admin || ! $admin->canAuthenticate()) {
                $request->session()->forget(['admin_authenticated', 'admin_id', 'admin_username', 'admin_role']);
                return redirect()->route('admin.login');
            }
            // Resolve permission from the database on every request, never from a client-supplied role.
            abort_unless($admin->allowsRoute($request->route()?->getName()), 403);
            $request->attributes->set('department_admin', $admin);
            $request->session()->put('admin_role', $admin->role);
        }
        return $next($request);
    }
}

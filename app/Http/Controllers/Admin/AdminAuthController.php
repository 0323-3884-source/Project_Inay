<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('admin_authenticated') === true) {
            $admin = AdminUser::find($request->session()->get('admin_id'));
            if ($admin) return redirect()->route($admin->dashboardRoute());
        }

        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $admin = AdminUser::where('username', $validated['username'])->first();

        if (! $admin || ! $admin->canAuthenticate() || ! Hash::check($validated['password'], $admin->password)) {
            throw ValidationException::withMessages([
                'username' => 'The username or password is incorrect.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->forget(['auth_role', 'auth_id', 'auth_name', 'auth_email']);
        $request->session()->put([
            'admin_authenticated' => true,
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
            'admin_role' => $admin->role,
        ]);

        $admin->forceFill(['last_login_at' => now()])->save();

        return redirect()->route($admin->dashboardRoute());
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

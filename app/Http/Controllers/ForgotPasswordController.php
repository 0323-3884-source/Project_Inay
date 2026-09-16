<?php

namespace App\Http\Controllers;

use App\Mail\AccountPasswordReset;
use App\Models\Mother;
use App\Models\ProgramStaff;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    private function key(string $role, string $email): string
    {
        // Separate tokens even when the two roles share the same email address.
        return $role.':'.hash('sha256', Str::lower($email));
    }

    private function account(string $role, string $email): Mother|ProgramStaff|null
    {
        return ($role === 'mother' ? Mother::query() : ProgramStaff::query())
            ->where('email', $email)->first();
    }

    public function requestForm(Request $request): View
    {
        return view('auth.forgot-password', ['role' => $request->query('role') === 'staff' ? 'staff' : 'mother']);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate(['role' => 'required|in:mother,staff', 'email' => 'required|email|max:255']);
        $key = $this->key($data['role'], $data['email']);
        $response = back()->withInput($request->only('role', 'email'))
            ->with('status', 'If an account matches that email and role, a password reset link will be sent. Please check your inbox and spam folder.');
        if (RateLimiter::tooManyAttempts('password-reset:'.$key, 1)) {
            return $response;
        }
        RateLimiter::hit('password-reset:'.$key, 60);
        $account = $this->account($data['role'], $data['email']);
        if (! $account) {
            return $response;
        }
        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(['email' => $key], [
            'token' => hash('sha256', $token), 'created_at' => now(),
        ]);
        $url = rtrim(config('app.url'), '/').route('password.reset', [
            'token' => $token, 'role' => $data['role'], 'email' => $account->email,
        ], false);
        try {
            Mail::to($account->email)->send(new AccountPasswordReset($url, $data['role']));
        } catch (\Throwable $exception) {
            DB::table('password_reset_tokens')->where('email', $key)->where('token', hash('sha256', $token))->delete();
            report($exception);
        }

        return $response;
    }

    public function resetForm(Request $request, string $token): View
    {
        $data = $request->validate(['role' => 'required|in:mother,staff', 'email' => 'required|email|max:255']);

        return view('auth.reset-password', $data + ['token' => $token]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'role' => 'required|in:mother,staff', 'email' => 'required|email|max:255',
            'token' => 'required|string|size:64', 'password' => 'required|string|min:8|max:255|confirmed',
        ]);
        $changed = DB::transaction(function () use ($data) {
            $key = $this->key($data['role'], $data['email']);
            $row = DB::table('password_reset_tokens')->where('email', $key)->lockForUpdate()->first();
            if (! $row || ! $row->created_at || Carbon::parse($row->created_at)->addHour()->isPast()
                || ! hash_equals($row->token, hash('sha256', $data['token']))) {
                return false;
            }
            $account = $this->account($data['role'], $data['email']);
            if (! $account) {
                return false;
            }
            $account->update(['password' => Hash::make($data['password'])]);
            DB::table('password_reset_tokens')->where('email', $key)->delete();

            return true;
        });
        if (! $changed) {
            return back()->withErrors(['token' => 'This reset link is invalid or expired. Please request a new link.']);
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Your password has been reset. Select your account role and log in with your new password.');
    }
}

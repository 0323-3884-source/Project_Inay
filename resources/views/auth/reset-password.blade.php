@extends('layouts.app')
@section('title', 'Reset Password - Project INAY')
@section('body_class', 'auth-body')
@section('auth_screen', 'true')
@include('auth.partials.recovery-button')
@include('auth.partials.password-visibility')
@section('content')
<header class="auth-header"><h1 class="auth-title">Project INAY</h1><p class="auth-subtitle">Account Recovery</p></header>
<section class="auth-card">
    <h2 class="auth-card-title">Set a New Password</h2><p class="auth-card-subtitle">Reset your {{ $role === 'staff' ? 'Program Staff' : 'Mother/User' }} password. Use at least 8 characters.</p>
    @if ($errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('password.update') }}" data-recovery-form>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}"><input type="hidden" name="role" value="{{ $role }}"><input type="hidden" name="email" value="{{ $email }}">
        <div class="auth-field"><label class="auth-label" for="new-password">New password</label><input class="auth-input" id="new-password" type="password" name="password" autocomplete="new-password" minlength="8" maxlength="255" required></div>
        <div class="auth-field"><label class="auth-label" for="confirm-password">Confirm new password</label><input class="auth-input" id="confirm-password" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" maxlength="255" required></div>
        <button class="auth-submit" type="submit">Reset Password</button>
    </form>
    <div class="auth-footer-link"><a href="{{ route('password.request', ['role' => $role]) }}">Request a New Reset Link</a></div>
    <div class="auth-footer-link"><a href="{{ route('login') }}">Back to Login</a></div>
</section>
@endsection

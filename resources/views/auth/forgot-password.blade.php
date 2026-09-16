@extends('layouts.app')
@section('title', 'Forgot Password - Project INAY')
@section('body_class', 'auth-body')
@section('auth_screen', 'true')
@include('auth.partials.recovery-button')
@section('content')
<header class="auth-header"><h1 class="auth-title">Project INAY</h1><p class="auth-subtitle">Account Recovery</p></header>
<section class="auth-card">
    <h2 class="auth-card-title">Forgot Password?</h2><p class="auth-card-subtitle">Select your account role and enter your registered email. We will send you a reset link.</p>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('password.email') }}" data-recovery-form>
        @csrf
        <div class="auth-field"><label class="auth-label" for="reset-role">Account role</label><select class="auth-input" id="reset-role" name="role" required><option value="mother" @selected(old('role', $role) === 'mother')>Mother/User</option><option value="staff" @selected(old('role', $role) === 'staff')>Program Staff</option></select></div>
        <div class="auth-field"><label class="auth-label" for="reset-email">Email address</label><input class="auth-input" id="reset-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required></div>
        <button class="auth-submit" type="submit">Send Reset Link</button>
    </form>
    <div class="auth-footer-link"><a href="{{ route('login') }}">Back to Login</a></div>
</section>
@endsection

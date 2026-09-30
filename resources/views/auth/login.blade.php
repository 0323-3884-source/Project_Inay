@extends('layouts.app')

@section('title', 'Login - Project INAY')
@section('body_class', 'auth-body')
@section('auth_screen', 'true')
@include('auth.partials.password-visibility')

@php
    $selectedRole = old('role', 'mother');
@endphp

@push('styles')
    <style>
        .auth-inline-status { margin: 0 0 18px; color: #704814; background: #fff8e1; border-color: #e8c56d; font-size: 13px; line-height: 1.45; }
        #loginForm .role-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        #loginForm .role-option { min-width: 0; }
        @media (max-width: 420px) { #loginForm .role-grid { grid-template-columns: 1fr; gap: 8px; } #loginForm .role-option { display: flex; justify-content: center; min-height: 52px; } }
    </style>
@endpush

@section('content')
    <header class="auth-header">
        <span class="auth-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24">
                <path d="M12 21s-7.5-4.8-9.6-9.1C.7 8.4 2.8 4.5 6.7 4.5c2 0 3.6 1 4.5 2.5.9-1.5 2.5-2.5 4.5-2.5 3.9 0 6 3.9 4.3 7.4C19.5 16.2 12 21 12 21z" />
            </svg>
        </span>
        <h1 class="auth-title">Project INAY</h1>
        <div class="auth-kicker">MNCH INNOVATIVE MATERNAL PROGRAM</div>
        <p class="auth-subtitle">Innovative Nanay Building Strengthening Maternal, Neonatal and Child Health Program</p>
    </header>

    <section class="auth-card">
        <h2 class="auth-card-title">Mag-login sa platform</h2>
        <p class="auth-card-subtitle">Pumili ng tungkulin (Role-based Portal)</p>

        @if ($errors->any())
            <div class="alert error auth-error">Please check your login details.</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" id="loginForm">
            @csrf
            <input id="roleInput" type="hidden" name="role" value="{{ $selectedRole }}">

            <div class="role-grid" aria-label="Choose account role">
                <button
                    type="button"
                    class="role-option {{ $selectedRole === 'mother' ? 'is-active' : '' }}"
                    data-role="mother"
                    data-button-label="Login as Mother/User"
                    data-register-url="{{ route('mother.register') }}"
                    aria-pressed="{{ $selectedRole === 'mother' ? 'true' : 'false' }}"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20.8 5.8a5.5 5.5 0 0 0-7.8 0L12 6.8l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 22l7.8-7.4 1-1a5.5 5.5 0 0 0 0-7.8z" />
                    </svg>
                    <span>Mother/User Portal</span>
                </button>

                <button
                    type="button"
                    class="role-option {{ $selectedRole === 'staff' ? 'is-active' : '' }}"
                    data-role="staff"
                    data-button-label="Login as Program Staff"
                    data-register-url="{{ route('staff.register') }}"
                    aria-pressed="{{ $selectedRole === 'staff' ? 'true' : 'false' }}"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    </svg>
                    <span>Program Staff</span>
                </button>
                <button type="button" class="role-option {{ $selectedRole === 'dswd_staff' ? 'is-active' : '' }}"
                    data-role="dswd_staff" data-button-label="Login as DSWD / 4Ps Staff"
                    aria-pressed="{{ $selectedRole === 'dswd_staff' ? 'true' : 'false' }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M6 16c1-3 5-3 6 0M15 9h3M15 13h3M15 17h3"/></svg>
                    <span>DSWD / 4Ps Staff</span>
                </button>
            </div>

            @error('role')
                <span class="field-error">{{ $message }}</span>
            @enderror

            @if (session('status'))
                <div class="alert auth-inline-status">{{ session('status') }}</div>
            @endif

            <div class="auth-field">
                <label class="auth-label" for="email">Email Address / Admin Username</label>
                <input class="auth-input" id="email" type="text" name="email" value="{{ old('email') }}" placeholder="maria.santos@inayhealth.org or admin" autocomplete="username" inputmode="email" required autofocus>
                @error('email')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="auth-field">
                <label class="auth-label" for="password">Password</label>
                <input class="auth-input" id="password" type="password" name="password" placeholder="Password" autocomplete="current-password" required>
                @error('password')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="auth-footer-link" style="margin: 0 0 18px; text-align: right;">
                <a id="forgotPasswordLink" href="{{ route('password.request', ['role' => $selectedRole]) }}">Forgot password?</a>
            </div>
            <button class="auth-submit login-submit" id="loginSubmit" type="submit">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="5" y="11" width="14" height="10" rx="2" />
                    <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                </svg>
                <span>{{ match ($selectedRole) { 'staff' => 'Login as Program Staff', 'dswd_staff' => 'Login as DSWD / 4Ps Staff', default => 'Login as Mother/User' } }}</span>
            </button>
        </form>

        <div class="auth-footer-link">
            <a id="registerLink" @if($selectedRole === 'dswd_staff') hidden @endif href="{{ $selectedRole === 'staff' ? route('staff.register') : route('mother.register') }}">Wala pang account? Mag-register</a>
            <span id="dswdAccountHelp" @if($selectedRole !== 'dswd_staff') hidden @endif>Contact your administrator for a DSWD / 4Ps Staff account.</span>
        </div>
    </section>

    <script>
        const roleInput = document.getElementById('roleInput');
        const roleButtons = document.querySelectorAll('[data-role]');
        const loginSubmitLabel = document.querySelector('#loginSubmit span');
        const registerLink = document.getElementById('registerLink');
        const loginIdentifier = document.getElementById('email');

        const refreshSubmitLabel = () => {
            const activeRole = document.querySelector('[data-role].is-active');

            if (roleInput.value !== 'dswd_staff' && loginIdentifier.value.trim().toLowerCase() === 'admin') {
                loginSubmitLabel.textContent = 'Login as Admin';
                return;
            }

            loginSubmitLabel.textContent = activeRole ? activeRole.dataset.buttonLabel : 'Login';
        };

        roleButtons.forEach((button) => {
            button.addEventListener('click', () => {
                roleInput.value = button.dataset.role;
                registerLink.hidden = button.dataset.role === 'dswd_staff';
                document.getElementById('dswdAccountHelp').hidden = button.dataset.role !== 'dswd_staff';
                if (button.dataset.registerUrl) registerLink.href = button.dataset.registerUrl;
                const forgotUrl = new URL(document.getElementById('forgotPasswordLink').href);
                forgotUrl.searchParams.set('role', button.dataset.role);
                document.getElementById('forgotPasswordLink').href = forgotUrl;

                roleButtons.forEach((item) => {
                    const isActive = item === button;
                    item.classList.toggle('is-active', isActive);
                    item.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                refreshSubmitLabel();
            });
        });

        loginIdentifier.addEventListener('input', refreshSubmitLabel);
        refreshSubmitLabel();
    </script>
@endsection

@extends('layouts.app')

@section('title', 'Program Staff Registration - Project INAY')
@section('body_class', 'auth-body')
@section('auth_screen', 'true')

@php
    $fullName = old('full_name', trim(implode(' ', array_filter([
        old('first_name'),
        old('middle_name'),
        old('last_name'),
    ]))));
@endphp

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
        <h2 class="auth-card-title">Mag-sign up sa platform</h2>
        <p class="auth-card-subtitle">Pumili ng tungkulin (Role-based Portal)</p>

        @if ($errors->any())
            <div class="alert error auth-error">Please fix the highlighted fields.</div>
        @endif

        <form method="POST" action="{{ route('staff.register.store') }}">
            @csrf

            <div class="role-grid" aria-label="Choose account role">
                <a class="role-option" href="{{ route('mother.register') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20.8 5.8a5.5 5.5 0 0 0-7.8 0L12 6.8l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 22l7.8-7.4 1-1a5.5 5.5 0 0 0 0-7.8z" />
                    </svg>
                    <span>Mother/User Portal</span>
                </a>

                <a class="role-option is-active" href="{{ route('staff.register') }}" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    </svg>
                    <span>Program Staff</span>
                </a>
            </div>

            <div class="auth-field">
                <label class="auth-label" for="full_name">Buong Pangalan (Full Name)</label>
                <input class="auth-input" id="full_name" name="full_name" value="{{ $fullName }}" placeholder="e.g. Juan dela Cruz" required>
                @error('full_name')<span class="field-error">{{ $message }}</span>@enderror
                @error('first_name')<span class="field-error">{{ $message }}</span>@enderror
                @error('last_name')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="auth-field">
                <label class="auth-label" for="email">Email Address</label>
                <input class="auth-input" id="email" type="email" name="email" value="{{ old('email') }}" placeholder="maria.santos@inayhealth.org" required>
                @error('email')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="auth-field">
                <label class="auth-label" for="password">Password</label>
                <input class="auth-input" id="password" type="password" name="password" placeholder="Password" required>
                @error('password')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <label class="consent-box" for="privacy_policy">
                <input id="privacy_policy" type="checkbox" name="privacy_policy" value="1" @checked(old('privacy_policy')) required>
                <span>I have read and agree to the <a href="#">Privacy Policy</a>.</span>
            </label>
            @error('privacy_policy')<span class="field-error">{{ $message }}</span>@enderror

            <button class="auth-submit" type="submit">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M19 8v6M22 11h-6" />
                </svg>
                Register / Add Program Staff Portal
            </button>
        </form>

        <div class="auth-footer-link">
            <a href="{{ route('login') }}">Mayroon nang account? Log in</a>
        </div>
    </section>
@endsection

@extends('layouts.admin')

@section('title', 'Admin Login - Project INAY')
@section('admin_auth_screen', 'true')

@section('content')
    @php
        $loginIcons = [
            'mark' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7.5-4.8-9.6-9.1C.7 8.4 2.8 4.5 6.7 4.5c2 0 3.6 1 4.5 2.5.9-1.5 2.5-2.5 4.5-2.5 3.9 0 6 3.9 4.3 7.4C19.5 16.2 12 21 12 21z"/></svg>',
            'lock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>',
        ];
    @endphp

    <section class="admin-login-shell" aria-label="Admin Login">
        <header class="admin-auth-brand">
            <span class="admin-auth-mark">{!! $loginIcons['mark'] !!}</span>
            <h1 class="admin-auth-title">Project INAY Admin</h1>
            <p class="admin-auth-subtitle">Statistics and system management console</p>
        </header>

        <div class="admin-auth-card">
            <h2 class="admin-card-title">Admin Login</h2>
            <p class="admin-card-subtitle">Use your administrator account to open the statistics dashboard.</p>

            @if (session('status'))
                <div class="admin-alert is-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="admin-alert is-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}">
                @csrf
                <label class="admin-field">
                    <span class="admin-label">Username</span>
                    <input class="admin-input" type="text" name="username" value="{{ old('username') }}" autocomplete="username" required autofocus>
                    @error('username')<span class="admin-field-error">{{ $message }}</span>@enderror
                </label>

                <label class="admin-field">
                    <span class="admin-label">Password</span>
                    <input class="admin-input" type="password" name="password" autocomplete="current-password" required>
                    @error('password')<span class="admin-field-error">{{ $message }}</span>@enderror
                </label>

                <button class="admin-submit" type="submit">
                    {!! $loginIcons['lock'] !!}
                    Login to Admin
                </button>
            </form>
        </div>
    </section>
@endsection

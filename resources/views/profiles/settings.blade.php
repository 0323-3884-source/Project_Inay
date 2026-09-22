@extends('layouts.app')
@section('title', 'Settings - Project INAY')
@section('portal_title', 'Settings')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/account-pages.css') }}?v={{ filemtime(public_path('css/account-pages.css')) }}">
@endpush
@section('content')
<div class="account-page">
    <header class="account-heading">
        <div><p class="account-kicker">{{ $isMother ? 'MOTHER PORTAL' : 'PROGRAM STAFF PORTAL' }}</p><h1>Account Settings</h1><p>Manage your account and password.</p></div>
        <a class="account-button" href="{{ route($isMother ? 'mother.dashboard' : 'staff.dashboard') }}">Back to Dashboard</a>
    </header>
    <div class="account-columns">
        <section class="account-panel">
            <h2>Your Account</h2>
            <dl class="account-facts">
                <div><dt>Name</dt><dd>{{ $account->full_name }}</dd></div>
                <div><dt>Email</dt><dd>{{ $account->email }}</dd></div>
                <div><dt>Account type</dt><dd>{{ $isMother ? 'Mother' : 'Program Staff' }}</dd></div>
            </dl>
            <p class="account-muted">Update your name and contact information in your profile.</p>
            <a class="account-button" href="{{ route($isMother ? 'mother.profile.show' : 'staff.profile.show') }}">Edit My Profile</a>
        </section>
        <section class="account-panel">
            <h2>Change Password</h2>
            <p class="account-muted" id="password-help">Choose a new password with at least 8 characters.</p>
            @if ($errors->any())
                <div class="account-error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form method="POST" action="{{ route($isMother ? 'mother.settings.password' : 'staff.settings.password') }}">
                @csrf
                @method('PATCH')
                <div class="account-form-grid">
                    <label>Current password<input type="password" name="current_password" autocomplete="current-password" required></label>
                    <label>New password<input type="password" name="password" autocomplete="new-password" minlength="8" maxlength="255" aria-describedby="password-help" required></label>
                    <label>Confirm new password<input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" maxlength="255" required></label>
                </div>
                <button class="account-button is-primary" type="submit">Update Password</button>
            </form>
        </section>
    </div>
</div>
@endsection

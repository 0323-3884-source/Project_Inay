@extends('layouts.app')
@section('title', 'My Profile - Project INAY')
@section('portal_title', 'My Profile')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/account-pages.css') }}?v={{ filemtime(public_path('css/account-pages.css')) }}">
@endpush
@section('content')
<div class="account-page">
    <header class="account-heading"><div><p class="account-kicker">{{ $isMother ? 'MOTHER PORTAL' : 'PROGRAM STAFF PORTAL' }}</p><h1>My Profile</h1><p>Keep your personal and contact information up to date.</p></div><a class="account-button" href="{{ route($isMother ? 'mother.dashboard' : 'staff.dashboard') }}">Back to Dashboard</a></header>
    <div class="account-columns">
        <aside class="account-panel">
            <div class="account-identity">
                @if ($isMother && $account->profile_photo_path)
                    <img class="account-avatar" src="{{ asset('storage/'.$account->profile_photo_path) }}" alt="Your profile photo">
                @else
                    <span class="account-avatar">{{ mb_substr($account->first_name, 0, 1).mb_substr($account->last_name, 0, 1) }}</span>
                @endif
                <h2>{{ $account->full_name }}</h2><p>{{ $isMother ? 'Mother' : $account->role_label }}</p>
            </div>
            <dl class="account-facts"><div><dt>Email</dt><dd>{{ $account->email }}</dd></div>
            @if ($isMother)
                <div><dt>4Ps beneficiary</dt><dd>{{ $account->is_4ps_beneficiary ? 'Yes' : 'No' }}</dd></div>
                <div><dt>Blood type</dt><dd>{{ $account->blood_type ?: 'Not recorded' }}</dd></div>
            @else
                <div><dt>Healthcare Worker ID</dt><dd>{{ $account->staff_id }}</dd></div>
                <div><dt>Assigned facility</dt><dd>{{ $account->assigned_facility ?: 'Not assigned' }}</dd></div>
                <div><dt>ID verification</dt><dd>{{ $account->healthcare_worker_id_verified_at ? 'Verified' : 'Not yet verified' }}</dd></div>
            @endif
            </dl>
            @if ($isMother)
                <form class="account-photo-form" method="POST" action="{{ route('mother.profile-photo.update') }}" enctype="multipart/form-data">
                    @csrf @method('PATCH')
                    <label for="profile-photo">Profile photo</label><input id="profile-photo" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" required>
                    <small>JPG, PNG or WebP. Maximum 4 MB.</small><button class="account-button" type="submit">Update Photo</button>
                </form>
            @elseif ($account->healthcare_worker_id_photo_url)
                <img class="account-id-image" src="{{ $account->healthcare_worker_id_photo_url }}" alt="Your healthcare worker ID">
            @endif
        </aside>
        <section class="account-panel">
            <h2>Personal Information</h2><p class="account-muted">Your contact number is used by your care team for calls and SMS.</p>
            @if ($errors->any())<div class="account-error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form method="POST" action="{{ route($isMother ? 'mother.profile.update' : 'staff.profile.update') }}">
                @csrf @method('PATCH')
                <div class="account-form-grid">
                    @foreach (['first_name' => 'First name', 'middle_name' => 'Middle name (optional)', 'last_name' => 'Last name'] as $field => $label)
                        <label>{{ $label }}<input name="{{ $field }}" value="{{ old($field, $account->$field) }}" maxlength="100" @if ($field !== 'middle_name') required @endif autocomplete="{{ ['first_name' => 'given-name', 'middle_name' => 'additional-name', 'last_name' => 'family-name'][$field] }}"></label>
                    @endforeach
                    <label>Contact number<input type="tel" name="contact_number" value="{{ old('contact_number', $account->contact_number) }}" maxlength="25" autocomplete="tel" required></label>
                    @if ($isMother)<label>Barangay<input name="barangay" value="{{ old('barangay', $account->barangay) }}" maxlength="255" required></label>@endif
                </div>
                <button class="account-button is-primary" type="submit">Save Changes</button>
            </form>
        </section>
    </div>
</div>
@endsection

@extends('layouts.dswd')
@section('heading', 'Profile')
@section('content')
<section class="admin-card"><p class="dswd-note">{{ $staff->email }} · DSWD / 4Ps Staff</p>
    <form class="dswd-form" method="POST" action="{{ route('dswd.profile.update') }}">@csrf @method('PATCH')
        <label>Full name<input name="name" value="{{ old('name', $staff->name) }}" maxlength="255" required autocomplete="name"></label>
        <label>Office<input name="office" value="{{ old('office', $staff->office) }}" maxlength="255" autocomplete="organization"></label>
        <h2>Change password (optional)</h2>
        <label>Current password<input type="password" name="current_password" autocomplete="current-password"></label>
        <label>New password<input type="password" name="password" minlength="8" maxlength="255" autocomplete="new-password"></label>
        <label>Confirm new password<input type="password" name="password_confirmation" minlength="8" maxlength="255" autocomplete="new-password"></label>
        <button class="dswd-button" type="submit">Save profile</button>
    </form>
</section>
@endsection

@extends('layouts.admin')
@section('title', 'DSWD Staff Accounts - Project INAY')
@push('styles')@include('dswd.partials.styles')@endpush
@section('content')
<header class="admin-topbar"><div><h1>DSWD / 4Ps Staff</h1><p>Create and manage access to the 4Ps portal.</p></div></header>
@if(session('status'))<div class="admin-alert is-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="admin-alert is-error">{{ $errors->first() }}</div>@endif
<div class="dswd-stack"><section class="admin-card"><h2>Create staff account</h2>
    <form class="dswd-form" method="POST" action="{{ route('admin.dswd-staff.store') }}">@csrf
        <label>Full name<input name="name" value="{{ old('name') }}" maxlength="255" required></label>
        <label>Email<input type="email" name="email" value="{{ old('email') }}" maxlength="255" required></label>
        <label>Office<input name="office" value="{{ old('office') }}" maxlength="255"></label>
        <label>Password<input type="password" name="password" minlength="8" maxlength="255" autocomplete="new-password" required></label>
        <label>Confirm password<input type="password" name="password_confirmation" minlength="8" maxlength="255" autocomplete="new-password" required></label>
        <button class="dswd-button" type="submit">Create DSWD account</button>
    </form>
</section><section class="admin-card"><h2>Staff accounts</h2><div class="dswd-table-wrap"><table class="dswd-table"><thead><tr><th>Name</th><th>Email</th><th>Office</th><th>Status</th><th>Action</th></tr></thead><tbody>@forelse($accounts as $account)<tr><td>{{ $account->name }}</td><td>{{ $account->email }}</td><td>{{ $account->office ?: 'Not recorded' }}</td><td>{{ $account->is_active ? 'Active' : 'Inactive' }}</td><td><form method="POST" action="{{ route('admin.dswd-staff.update', $account) }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $account->is_active ? '0' : '1' }}"><button class="dswd-button secondary" type="submit">{{ $account->is_active ? 'Deactivate' : 'Activate' }}</button></form></td></tr>@empty<tr><td colspan="5">No DSWD staff accounts yet.</td></tr>@endforelse</tbody></table></div>@include('dswd.partials.pagination', ['paginator' => $accounts])</section></div>
@endsection

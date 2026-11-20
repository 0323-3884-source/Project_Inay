@extends('layouts.admin')
@section('title', 'Login Activity - Project INAY')
@push('styles')@include('dswd.partials.styles')@endpush
@section('content')
<header class="admin-topbar"><div><h1>Login Activity</h1><p>Active means authenticated activity within the last five minutes. Status and times refresh automatically every 10 seconds while this page is visible.</p><p>Staff and mothers are listed separately. A mother signing in does not mark a staff account active.</p><p data-activity-connection aria-live="polite">Connecting to activity updates...</p></div></header>
<div class="dswd-stack">
@foreach(['CHO Program Staff' => $programStaff, 'DSWD Staff' => $dswdStaff, 'Mothers' => $mothers] as $department => $members)
    @if($members !== null)
    <section class="admin-card"><h2>{{ $department }}</h2>
        @if($department === 'Mothers')
            <form method="GET" action="{{ route('admin.staff-activity') }}" class="dswd-form">
                <label>Find a mother<input type="search" name="mother_q" value="{{ $motherSearch }}" placeholder="Name or email"></label>
                <button class="dswd-button" type="submit">Search</button>
                @if($motherSearch !== '')<a href="{{ route('admin.staff-activity') }}">Clear search</a>@endif
            </form>
        @endif
        <div class="dswd-table-wrap"><table class="dswd-table">
            <thead><tr><th>Name</th><th>Login activity</th><th>Account access</th><th>Last login</th><th>Last activity</th></tr></thead>
            <tbody>@forelse($members as $member)
                @php($activity = \App\Support\StaffActivity::summary($member))
                <tr><td>{{ $member instanceof \App\Models\DswdStaff ? $member->name : $member->full_name }}</td>
                    <td><span data-staff-activity="{{ \App\Support\StaffActivity::role($member) }}" data-staff-id="{{ $member->id }}">{{ $activity['active'] ? 'Active' : 'Inactive' }}</span></td>
                    <td>{{ $member instanceof \App\Models\Mother ? 'Registered mother' : ($member instanceof \App\Models\ProgramStaff ? $member->approval_status_label : ($member->is_active ? 'Enabled' : 'Disabled')) }}</td>
                    <td data-last-login>{{ $activity['last_login'] ?? 'Not recorded' }}</td><td data-last-seen>{{ $activity['last_seen'] ?? 'Not recorded' }}</td></tr>
            @empty<tr><td colspan="5">No matching accounts.</td></tr>@endforelse</tbody>
        </table></div>
        @include('dswd.partials.pagination', ['paginator' => $members])
    </section>
    @endif
@endforeach
</div>
@endsection
@push('scripts')<script src="{{ asset('js/staff-activity.js') }}?v={{ filemtime(public_path('js/staff-activity.js')) }}" data-activity-url="{{ route('admin.staff-activity') }}" defer></script>@endpush

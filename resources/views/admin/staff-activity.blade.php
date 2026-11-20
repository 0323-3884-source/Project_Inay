@extends('layouts.admin')
@section('title', 'Staff Login Activity - Project INAY')
@push('styles')@include('dswd.partials.styles')@endpush
@section('content')
<header class="admin-topbar"><div><h1>Staff Login Activity</h1><p>Active means authenticated activity within the last five minutes. Status refreshes automatically every 10 seconds while this page is visible.</p></div></header>
<div class="dswd-stack">
@foreach(['CHO Program Staff' => $programStaff, 'DSWD Staff' => $dswdStaff] as $department => $members)
    @if($members !== null)
    <section class="admin-card"><h2>{{ $department }}</h2>
        <div class="dswd-table-wrap"><table class="dswd-table">
            <thead><tr><th>Name</th><th>Login activity</th><th>Account access</th><th>Last login</th><th>Last activity</th></tr></thead>
            <tbody>@forelse($members as $member)
                @php($activity = \App\Support\StaffActivity::summary($member))
                <tr><td>{{ $member instanceof \App\Models\ProgramStaff ? $member->full_name : $member->name }}</td>
                    <td><span data-staff-activity="{{ \App\Support\StaffActivity::role($member) }}" data-staff-id="{{ $member->id }}">{{ $activity['active'] ? 'Active' : 'Inactive' }}</span></td>
                    <td>{{ $member instanceof \App\Models\ProgramStaff ? $member->approval_status_label : ($member->is_active ? 'Enabled' : 'Disabled') }}</td>
                    <td>{{ $activity['last_login'] ?? 'Not recorded' }}</td><td>{{ $activity['last_seen'] ?? 'Not recorded' }}</td></tr>
            @empty<tr><td colspan="5">No staff accounts.</td></tr>@endforelse</tbody>
        </table></div>
        @include('dswd.partials.pagination', ['paginator' => $members])
    </section>
    @endif
@endforeach
</div>
@endsection
@push('scripts')<script src="{{ asset('js/staff-activity.js') }}" data-activity-url="{{ route('admin.staff-activity') }}" defer></script>@endpush

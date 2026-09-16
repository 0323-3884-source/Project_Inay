@extends('layouts.app')

@section('title', 'Mother Dashboard - Project INAY')
@section('portal_title', 'My Care Dashboard')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/account-pages.css') }}?v={{ filemtime(public_path('css/account-pages.css')) }}">
@endpush

@section('content')
<div class="account-page">
    <header class="account-heading"><div><p class="account-kicker">MOTHER PORTAL</p><h1>Welcome, {{ $mother->first_name }}.</h1><p>Your care, records, and support in one place.</p></div><a class="account-button" href="{{ route('mother.profile.show') }}">My Profile</a></header>
    <section class="account-welcome"><div><h2>Stay connected to your care team</h2><p>Review your monitoring records, explore learning materials, and message your assigned healthcare worker.</p></div><a class="account-button is-primary" href="{{ route('mother.consultation') }}">Message Your Care Team</a></section>
    <div class="account-metrics">
        <a href="{{ route('maternal-monitoring') }}"><span>Monitoring Records</span><strong>{{ $monitoringCount }}</strong><small>View your recorded checkups &rarr;</small></a>
        <a href="{{ route('child-health') }}"><span>My Children</span><strong>{{ $childCount }}</strong><small>Open child health records &rarr;</small></a>
        <a href="{{ route('mother.consultation') }}"><span>Unread Messages</span><strong>{{ $unreadMessageCount }}</strong><small>Open your conversations &rarr;</small></a>
    </div>
    <div class="account-columns is-dashboard">
        <section class="account-panel"><h2>Latest Monitoring Record</h2>
            @if ($latestRecord)
                <p class="account-muted">Recorded {{ ($latestRecord->recorded_at ?? $latestRecord->created_at)?->format('F j, Y') }}. These are the values saved at your last checkup.</p>
                <dl class="account-facts account-record-grid">
                    <div><dt>Pregnancy week at checkup</dt><dd>{{ $latestRecord->pregnancy_week ?? 'Not recorded' }}</dd></div>
                    <div><dt>Blood pressure</dt><dd>{{ $latestRecord->bp_systolic && $latestRecord->bp_diastolic ? $latestRecord->bp_systolic.'/'.$latestRecord->bp_diastolic.' mmHg' : 'Not recorded' }}</dd></div>
                    <div><dt>Weight</dt><dd>{{ $latestRecord->weight !== null ? $latestRecord->weight.' kg' : 'Not recorded' }}</dd></div>
                    <div><dt>Temperature</dt><dd>{{ $latestRecord->temperature !== null ? $latestRecord->temperature.' °C' : 'Not recorded' }}</dd></div>
                </dl>
            @else
                <div class="account-empty"><strong>No monitoring records yet</strong><p>Your recorded checkups will appear here once your healthcare worker adds them.</p></div>
            @endif
            <a class="account-button" href="{{ route('maternal-monitoring') }}">View Monitoring Records</a>
        </section>
        <section class="account-panel"><h2>Your Care Team</h2><p class="account-muted">Healthcare workers assigned to your casefile.</p>
            @forelse ($careTeam as $worker)
                <div class="account-team-row"><strong>{{ $worker->full_name }}</strong><small>{{ $worker->role_label }} · {{ $worker->assigned_facility ?: 'Facility not listed' }}</small></div>
            @empty
                <div class="account-empty"><strong>No healthcare worker assigned yet</strong><p>Your care team will appear here once your assignment is recorded.</p></div>
            @endforelse
            <a class="account-button" href="{{ route('mother.consultation') }}">Open Messages</a>
        </section>
    </div>
    <section class="account-panel"><h2>Explore Your Care Resources</h2><div class="account-resources">
        <a href="{{ route('inay-kaalaman') }}"><strong>INAY Kaalaman</strong><span>Browse learning materials for your pregnancy journey.</span><b>Explore materials &rarr;</b></a>
        <a href="{{ route('child-health') }}"><strong>Child Health</strong><span>View your children's growth and vaccination records.</span><b>View child records &rarr;</b></a>
        <a href="{{ route('health-services') }}"><strong>Health Services</strong><span>Find available health facilities and services.</span><b>Browse services &rarr;</b></a>
    </div></section>
</div>
@endsection

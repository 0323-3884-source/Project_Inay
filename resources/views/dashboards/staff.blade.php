@extends('layouts.app')

@section('title', 'Monitoring Desk - Project INAY')
@section('portal_title', 'Monitoring Desk')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/staff-monitoring-desk.css') }}?v={{ filemtime(public_path('css/staff-monitoring-desk.css')) }}">
    <style>
        .staff-dashboard-profile { display: grid; grid-template-columns: minmax(0, 1fr) 280px; gap: 18px; align-items: start; }
        .staff-dashboard-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin: 22px 0; }
        .staff-dashboard-stat { display: grid; gap: 8px; padding: 16px; background: #f8fafc; border: 1px solid #dbe5f1; border-radius: 8px; }
        .staff-dashboard-stat span { color: #64748b; font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .staff-dashboard-stat strong { color: #ec0a78; font-size: 30px; font-weight: 900; line-height: 1; }
        .staff-dashboard-stat a { width: fit-content; color: #10213f; font-size: 12px; font-weight: 900; }
        .staff-id-card { display: grid; gap: 12px; padding: 14px; background: #f8fafc; border: 1px solid #dbe5f1; border-radius: 8px; }
        .staff-id-card h2 { margin: 0; font-size: 17px; font-weight: 900; }
        .staff-id-preview { display: grid; min-height: 170px; place-items: center; color: #64748b; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 8px; overflow: hidden; text-align: center; }
        .staff-id-preview img { width: 100%; height: 100%; object-fit: contain; }
        .staff-id-status { display: inline-flex; align-items: center; width: fit-content; min-height: 30px; padding: 0 10px; border-radius: 999px; font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .staff-id-status.is-verified { color: #007f5f; background: #ecfdf5; border: 1px solid #86efc2; }
        .staff-id-status.is-pending { color: #c2410c; background: #fff7ed; border: 1px solid #fed7aa; }
        .staff-id-status.is-missing { color: #64748b; background: #f1f5f9; border: 1px solid #cbd5e1; }
        @media (max-width: 860px) { .staff-dashboard-profile { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    <div class="monitor-desk">
        <header class="monitor-desk-heading">
            <div>
                <p class="monitor-eyebrow">PROGRAM STAFF PORTAL</p>
                <h1>Monitoring Desk</h1>
                <p>Welcome, {{ $staff->full_name }}. Here is your care overview for today.</p>
            </div>
            <span class="monitor-date">{{ today()->format('l, F j, Y') }}</span>
        </header>

        <nav class="monitor-shortcuts" aria-label="Staff quick actions">
            <a class="monitor-primary" href="{{ route('staff.mothers') }}">Open Mother Casefiles</a>
            <a href="{{ route('staff.clinic-schedule.index', ['tab' => 'appointments']) }}">Manage Appointments</a>
            <a href="{{ route('staff.consultation') }}">Open Messages</a>
            <a href="{{ route('staff.dynamic-reports') }}">View Reports</a>
        </nav>

        <div class="monitor-metrics">
            <article><span>Registered Mothers</span><strong data-dashboard-registered-mothers>{{ number_format($registeredMotherCount) }}</strong><small>Across the program</small></article>
            <article><span>Assigned to You</span><strong>{{ number_format($assignedMotherCount) }}</strong><small>Your mother casefiles</small></article>
            <article><span>Today's Appointments</span><strong>{{ $todayAppointments->count() }}</strong><small>Confirmed or rescheduled</small></article>
            <article><span>Appointment Requests</span><strong data-dashboard-appointment-requests>{{ number_format($pendingAppointmentCount) }}</strong><small>Pending or requesting a new time</small></article>
        </div>

        <div class="monitor-columns">
            <section class="monitor-panel">
                <header><div><h2>Today's Appointments</h2><p>Your confirmed schedule for {{ today()->format('M j') }}.</p></div><span class="monitor-badge">{{ $todayAppointments->count() }} scheduled</span></header>
                <div class="monitor-list">
                    @forelse ($todayAppointments as $appointment)
                        <a class="monitor-row" href="{{ route('staff.clinic-schedule.index', ['tab' => 'appointments', 'q' => $appointment->mother->full_name]) }}">
                            <span class="monitor-time">{{ \Illuminate\Support\Carbon::parse($appointment->start_time)->format('g:i A') }}</span>
                            <span><strong>{{ $appointment->mother->full_name }}</strong><small>{{ $appointment->typeLabel() }} · {{ $appointment->meetingLabel() }}</small></span>
                            <span class="monitor-link">View &rarr;</span>
                        </a>
                    @empty
                        <div class="monitor-empty"><strong>No confirmed appointments today.</strong><p>Review appointment requests or manage your available hours.</p><a href="{{ route('staff.clinic-schedule.index', ['tab' => 'profile']) }}">Manage availability &rarr;</a></div>
                    @endforelse
                </div>
            </section>

            <section class="monitor-panel">
                <header><div><h2>Needs Your Review</h2><p>Follow-up work for your assigned mothers.</p></div></header>
                <a class="monitor-review" href="{{ route('staff.clinic-schedule.index', ['tab' => 'appointments']) }}"><span><strong>Appointment requests</strong><small>Review pending bookings and reschedule requests.</small></span><b>{{ $pendingAppointmentCount }}</b></a>
                <div class="monitor-section-label"><h3>No Monitoring Record Yet</h3><span class="monitor-badge">{{ $withoutMonitoringCount }}</span></div>
                @forelse ($mothersWithoutMonitoring as $mother)
                    <a class="monitor-row" href="{{ route('staff.mothers.show', $mother) }}"><span><strong>{{ $mother->full_name }}</strong><small>{{ $mother->barangay ?: 'Barangay not provided' }}</small></span><span class="monitor-link">Open casefile &rarr;</span></a>
                @empty
                    <p class="monitor-muted">{{ $assignedMotherCount ? 'All your assigned mothers have a monitoring record.' : 'No mothers are assigned to you yet.' }}</p>
                @endforelse
                @if ($withoutMonitoringCount > 5)<p class="monitor-muted">Showing 5 of {{ $withoutMonitoringCount }} mothers without a monitoring record.</p>@endif
            </section>

            <section class="monitor-panel">
                <header><div><h2>Coming Up Next</h2><p>Your next five appointments after today.</p></div></header>
                @forelse ($upcomingAppointments as $appointment)
                    <a class="monitor-row" href="{{ route('staff.clinic-schedule.index', ['tab' => 'appointments', 'q' => $appointment->mother->full_name]) }}"><span><strong>{{ $appointment->mother->full_name }}</strong><small>{{ $appointment->typeLabel() }}</small></span><span class="monitor-time">{{ $appointment->appointment_date->format('M j, Y') }}<small>{{ \Illuminate\Support\Carbon::parse($appointment->start_time)->format('g:i A') }}</small></span></a>
                @empty
                    <div class="monitor-empty"><strong>No upcoming confirmed appointments.</strong><p>Confirmed bookings will appear here.</p></div>
                @endforelse
            </section>

            <section class="monitor-panel">
                <header><div><h2>Recent Monitoring Activity</h2><p>Latest records for mothers assigned to you.</p></div></header>
                @forelse ($recentMonitoring as $record)
                    <a class="monitor-row" href="{{ route('staff.mothers.show', $record->mother) }}"><span><strong>{{ $record->mother->full_name }}</strong><small>{{ $record->pregnancy_week ? 'Pregnancy week '.$record->pregnancy_week : 'Maternal monitoring record' }}</small></span><span class="monitor-time">{{ ($record->recorded_at ?? $record->created_at)?->format('M j, Y') }}</span></a>
                @empty
                    <div class="monitor-empty"><strong>No monitoring activity yet.</strong><p>Records for your assigned mothers will appear here once recorded.</p></div>
                @endforelse
            </section>
        </div>

    <details class="card monitor-profile">
        <summary>Your Healthcare Worker Profile <span>Account details and ID verification</span></summary>

        @php
            $idPhotoUrl = $staff->healthcare_worker_id_photo_url;
            $idStatusClass = $staff->healthcare_worker_id_verified_at && $idPhotoUrl ? 'is-verified' : ($idPhotoUrl ? 'is-pending' : 'is-missing');
            $idStatusText = $staff->healthcare_worker_id_verified_at && $idPhotoUrl ? 'ID Verified' : ($idPhotoUrl ? 'Pending Admin Verification' : 'ID Image Unavailable');
        @endphp


        <div class="staff-dashboard-profile">
            <div class="details">
                <div class="detail-item">
                    <span class="detail-label">Email</span>
                    {{ $staff->email }}
                </div>
                <div class="detail-item">
                    <span class="detail-label">Healthcare Worker ID</span>
                    {{ $staff->staff_id }}
                </div>
                <div class="detail-item">
                    <span class="detail-label">Role</span>
                    {{ $staff->role_label }}
                </div>
                <div class="detail-item">
                    <span class="detail-label">Contact Number</span>
                    {{ $staff->contact_number }}
                </div>
            </div>

            <aside class="staff-id-card" aria-label="Healthcare worker ID status">
                <h2>Healthcare Worker ID</h2>
                <div class="staff-id-preview">
                    @if($idPhotoUrl)
                        <img src="{{ $idPhotoUrl }}" alt="{{ $staff->full_name }} healthcare worker ID">
                    @else
                        <span>ID image unavailable</span>
                    @endif
                </div>
                <span class="staff-id-status {{ $idStatusClass }}">{{ $idStatusText }}</span>
            </aside>
        </div>

        <div class="actions">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Logout</button>
            </form>
        </div>
    </details>
    </div>
@endsection

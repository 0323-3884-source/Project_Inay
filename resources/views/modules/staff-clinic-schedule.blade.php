@extends('layouts.app')

@section('title', 'Clinic Schedule - Project INAY')
@section('portal_title', 'Clinic Schedule')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/clinic-schedule.css') }}?v={{ filemtime(public_path('css/clinic-schedule.css')) }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/clinic-schedule.js') }}?v={{ filemtime(public_path('js/clinic-schedule.js')) }}" defer></script>
@endpush

@php
    $statusClass = fn (string $statusValue): string => 'is-'.str_replace('_', '_', $statusValue);
    $timeLabel = fn ($time): string => \Illuminate\Support\Carbon::parse((string) $time)->format('g:i A');
    $canJoinVideo = function ($appointment): bool {
        if ($appointment->meeting_type !== \App\Models\Appointment::MEETING_VIDEO || $appointment->status !== \App\Models\Appointment::STATUS_CONFIRMED) {
            return false;
        }

        $start = \Illuminate\Support\Carbon::parse($appointment->appointment_date->toDateString().' '.substr((string) $appointment->start_time, 0, 5));
        $end = \Illuminate\Support\Carbon::parse($appointment->appointment_date->toDateString().' '.substr((string) $appointment->end_time, 0, 5));

        return now()->between($start->copy()->subMinutes(15), $end);
    };
    $conversationRoute = fn ($appointment): string => route('staff.consultation', [
        'conversation' => $appointment->conversation_id,
        'appointment' => $appointment->id,
    ]);
@endphp

@section('content')
    <section class="clinic-schedule" data-clinic-schedule-root>
        <header class="clinic-page-head">
            <div>
                <p class="clinic-kicker">Program Staff Portal</p>
                <h1 class="clinic-title">Clinic Schedule</h1>
                <p class="clinic-copy">Create and manage assigned mothers' appointments, reschedule requests, and video consultation follow-ups from one schedule.</p>
            </div>
            <button class="clinic-primary" type="button" data-open-appointment-modal>Create Appointment</button>
        </header>

        @if (session('status'))
            <div class="clinic-alert">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="clinic-alert is-error">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="clinic-filter-card" aria-label="Schedule filters">
            <form class="clinic-filter-form" method="GET" action="{{ route('staff.clinic-schedule.index') }}">
                <label class="clinic-search">
                    <span class="sr-only">Search by mother's name</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search by mother's name">
                </label>
                <button class="clinic-secondary" type="submit">Search</button>
            </form>
            <div class="clinic-status-tabs" aria-label="Status filters">
                <a class="clinic-tab {{ $status === 'all' ? 'is-active' : '' }}" href="{{ route('staff.clinic-schedule.index', ['q' => $search]) }}">All</a>
                @foreach ($statusLabels as $statusValue => $statusLabel)
                    @if ($statusValue !== 'missed')
                        <a class="clinic-tab {{ $status === $statusValue ? 'is-active' : '' }}" href="{{ route('staff.clinic-schedule.index', ['q' => $search, 'status' => $statusValue]) }}">{{ $statusLabel }}</a>
                    @endif
                @endforeach
            </div>
        </section>

        <div class="clinic-grid">
            <section class="clinic-panel">
                <div class="clinic-panel-head">
                    <h2>Upcoming Appointments</h2>
                    <span class="clinic-count">{{ $upcomingAppointments->count() }} scheduled</span>
                </div>

                <div class="clinic-stack">
                    @forelse ($appointmentsByDate as $date => $dateAppointments)
                        <div class="clinic-date-group">
                            <p class="clinic-date-label">{{ \Illuminate\Support\Carbon::parse($date)->format('l, F j, Y') }}</p>
                            @foreach ($dateAppointments as $appointment)
                                @include('partials.staff-appointment-card', ['appointment' => $appointment, 'isHistory' => false])
                            @endforeach
                        </div>
                    @empty
                        <div class="clinic-empty">No upcoming appointments found.</div>
                    @endforelse
                </div>
            </section>

            <aside class="clinic-stack">
                <section class="clinic-panel">
                    <div class="clinic-panel-head">
                        <h2>Reschedule Requests</h2>
                        <span class="clinic-count">{{ $appointments->where('status', 'reschedule_requested')->count() }}</span>
                    </div>
                    <div class="clinic-stack">
                        @forelse ($appointments->where('status', 'reschedule_requested') as $appointment)
                            <article class="clinic-card">
                                <div>
                                    <h3>{{ $appointment->mother->full_name }}</h3>
                                    <p>{{ $appointment->typeLabel() }} · {{ $appointment->appointment_date->format('M j, Y') }}</p>
                                </div>
                                <div class="clinic-request">
                                    Preferred: {{ $appointment->preferred_date?->format('M j, Y') ?? 'No date' }} at {{ $appointment->preferred_start_time ? $timeLabel($appointment->preferred_start_time) : 'No time' }}<br>
                                    {{ $appointment->reschedule_reason ?: 'No reason provided.' }}
                                </div>
                                @php
                                    $duration = max(15, \Illuminate\Support\Carbon::parse((string) $appointment->start_time)->diffInMinutes(\Illuminate\Support\Carbon::parse((string) $appointment->end_time), false));
                                    $preferredEnd = $appointment->preferred_start_time
                                        ? \Illuminate\Support\Carbon::parse((string) $appointment->preferred_start_time)->addMinutes($duration)->format('H:i')
                                        : substr((string) $appointment->end_time, 0, 5);
                                @endphp
                                @if ($appointment->preferred_date && $appointment->preferred_start_time)
                                    <form method="POST" action="{{ route('staff.clinic-schedule.update', $appointment) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="mother_id" value="{{ $appointment->mother_id }}">
                                        <input type="hidden" name="conversation_id" value="{{ $appointment->conversation_id }}">
                                        <input type="hidden" name="appointment_type" value="{{ $appointment->appointment_type }}">
                                        <input type="hidden" name="meeting_type" value="{{ $appointment->meeting_type }}">
                                        <input type="hidden" name="appointment_date" value="{{ $appointment->preferred_date->toDateString() }}">
                                        <input type="hidden" name="start_time" value="{{ substr((string) $appointment->preferred_start_time, 0, 5) }}">
                                        <input type="hidden" name="end_time" value="{{ $preferredEnd }}">
                                        <input type="hidden" name="location" value="{{ $appointment->location }}">
                                        <input type="hidden" name="notes" value="{{ $appointment->notes }}">
                                        <input type="hidden" name="status" value="confirmed">
                                        <button class="clinic-success" type="submit">Approve Requested Time</button>
                                    </form>
                                @endif
                            </article>
                        @empty
                            <div class="clinic-empty">No pending reschedule requests.</div>
                        @endforelse
                    </div>
                </section>

                <section class="clinic-panel">
                    <div class="clinic-panel-head">
                        <h2>Appointment History</h2>
                        <span class="clinic-count">{{ $historyAppointments->count() }}</span>
                    </div>
                    <div class="clinic-stack">
                        @forelse ($historyAppointments as $appointment)
                            @include('partials.staff-appointment-card', ['appointment' => $appointment, 'isHistory' => true])
                        @empty
                            <div class="clinic-empty">No completed, cancelled, or past appointments yet.</div>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>

        @include('partials.appointment-modal')

        <div class="clinic-modal" data-clinic-modal data-details-modal hidden>
            <button class="clinic-backdrop" type="button" data-clinic-modal-close aria-label="Close appointment details"></button>
            <section class="clinic-dialog" role="dialog" aria-modal="true" aria-labelledby="details-modal-title">
                <header class="clinic-dialog-head">
                    <h2 id="details-modal-title" data-detail="detailTitle">Appointment Details</h2>
                    <button class="clinic-close" type="button" data-clinic-modal-close aria-label="Close appointment details">x</button>
                </header>
                <div class="clinic-form-grid">
                    <div class="clinic-fact"><span>Mother</span><strong data-detail="motherName"></strong></div>
                    <div class="clinic-fact"><span>Status</span><strong data-detail="statusLabel"></strong></div>
                    <div class="clinic-fact"><span>Date</span><strong data-detail="dateLabel"></strong></div>
                    <div class="clinic-fact"><span>Time</span><strong data-detail="timeLabel"></strong></div>
                    <div class="clinic-fact"><span>Meeting</span><strong data-detail="meetingLabel"></strong></div>
                    <div class="clinic-fact"><span>Location</span><strong data-detail="location"></strong></div>
                    <div class="clinic-fact clinic-wide"><span>Notes</span><strong data-detail="notes"></strong></div>
                </div>
            </section>
        </div>
    </section>
@endsection

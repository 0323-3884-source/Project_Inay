@extends('layouts.app')

@section('title', 'My Clinic Schedule - Project INAY')
@section('portal_title', 'My Clinic Schedule')

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
    $conversationRoute = fn ($appointment): string => route('mother.consultation', [
        'conversation' => $appointment->conversation_id,
        'appointment' => $appointment->id,
    ]);
@endphp

@section('content')
    <section class="clinic-schedule" data-clinic-schedule-root>
        <header class="clinic-page-head">
            <div>
                <p class="clinic-kicker">Mother Portal</p>
                <h1 class="clinic-title">My Clinic Schedule</h1>
                <p class="clinic-copy">Review upcoming checkups, confirm your visit, or request a new time from your assigned Program Staff.</p>
            </div>
        </header>

        @if (session('status'))
            <div class="clinic-alert">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="clinic-alert is-error">
                {{ $errors->first() }}
            </div>
        @endif

        @if ($nextAppointment)
            <section class="clinic-next-card">
                <p class="clinic-kicker">Next appointment</p>
                <h2>{{ $nextAppointment->typeLabel() }}</h2>
                <p>{{ $nextAppointment->appointment_date->format('l, F j, Y') }} · {{ $timeLabel($nextAppointment->start_time) }} - {{ $timeLabel($nextAppointment->end_time) }}</p>
                <div class="clinic-meta">
                    <span class="clinic-pill {{ $statusClass($nextAppointment->status) }}">{{ $nextAppointment->statusLabel() }}</span>
                    <span class="clinic-pill">{{ $nextAppointment->meetingLabel() }}</span>
                    <span class="clinic-pill">{{ $nextAppointment->staff->full_name }}</span>
                </div>
            </section>
        @endif

        <div class="clinic-grid">
            <section class="clinic-panel">
                <div class="clinic-panel-head">
                    <h2>Upcoming Appointments</h2>
                    <span class="clinic-count">{{ $upcomingAppointments->count() }} scheduled</span>
                </div>
                <div class="clinic-stack">
                    @forelse ($upcomingAppointments as $appointment)
                        @include('partials.mother-appointment-card', ['appointment' => $appointment, 'isHistory' => false])
                    @empty
                        <div class="clinic-empty">You do not have upcoming clinic appointments yet.</div>
                    @endforelse
                </div>
            </section>

            <aside class="clinic-panel">
                <div class="clinic-panel-head">
                    <h2>Appointment History</h2>
                    <span class="clinic-count">{{ $historyAppointments->count() }}</span>
                </div>
                <div class="clinic-stack">
                    @forelse ($historyAppointments as $appointment)
                        @include('partials.mother-appointment-card', ['appointment' => $appointment, 'isHistory' => true])
                    @empty
                        <div class="clinic-empty">No appointment history yet.</div>
                    @endforelse
                </div>
            </aside>
        </div>

        <div class="clinic-modal" data-clinic-modal data-reschedule-modal hidden>
            <button class="clinic-backdrop" type="button" data-clinic-modal-close aria-label="Close reschedule form"></button>
            <section class="clinic-dialog" role="dialog" aria-modal="true" aria-labelledby="reschedule-modal-title">
                <form method="POST" action="#" data-reschedule-form>
                    @csrf
                    @method('PATCH')
                    <header class="clinic-dialog-head">
                        <h2 id="reschedule-modal-title" data-reschedule-title>Request Reschedule</h2>
                        <button class="clinic-close" type="button" data-clinic-modal-close aria-label="Close reschedule form">x</button>
                    </header>
                    <div class="clinic-form-grid">
                        <label>
                            Preferred date
                            <input type="date" name="preferred_date" min="{{ now()->toDateString() }}" required>
                        </label>
                        <label>
                            Preferred start time
                            <input type="time" name="preferred_start_time" required>
                        </label>
                        <label class="clinic-wide">
                            Reason
                            <textarea name="reschedule_reason" maxlength="1000" required placeholder="Tell your Program Staff why you need a new schedule"></textarea>
                        </label>
                    </div>
                    <footer class="clinic-dialog-footer">
                        <button class="clinic-secondary" type="button" data-clinic-modal-close>Cancel</button>
                        <button class="clinic-primary" type="submit">Send Request</button>
                    </footer>
                </form>
            </section>
        </div>
    </section>
@endsection

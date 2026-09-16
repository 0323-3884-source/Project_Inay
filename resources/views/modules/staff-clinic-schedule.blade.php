@extends('layouts.app')

@section('title', 'Clinic Schedule - Project INAY')
@section('portal_title', 'Clinic Schedule')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/clinic-schedule.css') }}?v={{ filemtime(public_path('css/clinic-schedule.css')) }}">
    <style>
        /* ── Responsive overrides ── */
        :root {
            --clinic-radius: 0.5rem;
            --clinic-gap: 1.25rem;
            --clinic-font-base: 0.9375rem;
        }

        .clinic-schedule {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem;
            font-size: var(--clinic-font-base);
            line-height: 1.5;
        }

        .clinic-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: var(--clinic-gap);
            align-items: start;
        }

        .clinic-page-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: var(--clinic-gap);
        }
        .clinic-page-tab {
            flex: 1 1 0;
            min-width: 0;
            padding: 0.75rem 1rem;
            border: 1px solid #d5e2f5;
            border-radius: var(--clinic-radius);
            background: #fff;
            color: #4b5563;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            overflow-wrap: anywhere;
        }
        .clinic-page-tab[aria-selected="true"] {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }
        .clinic-page-tab:focus-visible {
            outline: 3px solid #2563eb;
            outline-offset: 3px;
        }
        .clinic-schedule [data-clinic-tab-panel] {
            min-width: 0;
            overflow-wrap: anywhere;
        }
        .clinic-schedule [data-clinic-tab-panel][hidden] {
            display: none;
        }

        .clinic-stack {
            display: flex;
            flex-direction: column;
            gap: var(--clinic-gap);
        }

        .clinic-panel {
            background: #fff;
            border-radius: var(--clinic-radius);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 1.25rem;
            transition: box-shadow 0.2s;
        }

        .clinic-panel-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 0.5rem 1rem;
            margin-bottom: 1rem;
        }
        .clinic-panel-head h2 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }
        .clinic-panel-copy {
            font-size: 0.9rem;
            color: #6b7280;
            margin: 0.25rem 0 0 0;
        }
        .clinic-count {
            background: #e5e7eb;
            padding: 0.15rem 0.7rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #1f2937;
            white-space: nowrap;
        }

        /* forms */
        .clinic-profile-form,
        .clinic-availability-form,
        .clinic-block-form,
        .clinic-filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1rem;
            align-items: flex-end;
        }
        .clinic-profile-form label,
        .clinic-availability-form label,
        .clinic-block-form label {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            font-weight: 500;
            font-size: 0.85rem;
            flex: 1 0 140px;
            min-width: 120px;
        }
        .clinic-profile-form input,
        .clinic-profile-form select,
        .clinic-availability-form input,
        .clinic-availability-form select,
        .clinic-block-form input,
        .clinic-block-form select,
        .clinic-filter-form input {
            padding: 0.45rem 0.6rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.9rem;
            width: 100%;
            box-sizing: border-box;
            background: #fafafa;
        }
        .clinic-profile-form input:focus,
        .clinic-availability-form input:focus,
        .clinic-block-form input:focus,
        .clinic-filter-form input:focus {
            border-color: #2563eb;
            outline: none;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
        }

        .clinic-check {
            flex-direction: row !important;
            align-items: center;
            gap: 0.5rem;
            font-weight: 400;
        }
        .clinic-check input[type="checkbox"] {
            width: 1.1rem;
            height: 1.1rem;
            accent-color: #2563eb;
        }

        .clinic-primary,
        .clinic-secondary,
        .clinic-success,
        .clinic-danger {
            padding: 0.5rem 1.2rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: 0.15s;
            white-space: nowrap;
        }
        .clinic-primary { background: #2563eb; color: #fff; }
        .clinic-primary:hover { background: #1d4ed8; }
        .clinic-secondary { background: #e5e7eb; color: #1f2937; }
        .clinic-secondary:hover { background: #d1d5db; }
        .clinic-success { background: #16a34a; color: #fff; }
        .clinic-success:hover { background: #15803d; }
        .clinic-danger { background: #dc2626; color: #fff; }
        .clinic-danger:hover { background: #b91c1c; }

        .clinic-small-action {
            padding: 0.2rem 0.8rem;
            font-size: 0.75rem;
            border-radius: 0.3rem;
        }

        .clinic-actions {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
            margin-top: 0.4rem;
        }

        .clinic-mini-slot,
        .clinic-block-row,
        .clinic-card {
            background: #f9fafb;
            border-radius: 0.375rem;
            padding: 0.75rem 1rem;
            margin-top: 0.6rem;
            border-left: 4px solid #2563eb;
        }
        .clinic-mini-slot.is-disabled {
            border-left-color: #9ca3af;
            opacity: 0.7;
        }
        .clinic-slot-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.3rem 0.8rem;
        }
        .clinic-pill {
            display: inline-block;
            padding: 0.1rem 0.7rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .clinic-pill.is-confirmed { background: #d1fae5; color: #065f46; }
        .clinic-pill.is-cancelled { background: #fee2e2; color: #991b1b; }

        .clinic-block-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            border-left-color: #f59e0b;
        }

        .clinic-card {
            border-left-color: #8b5cf6;
            margin-top: 0.6rem;
        }
        .clinic-card h3 {
            margin: 0 0 0.2rem;
            font-size: 1rem;
        }
        .clinic-card .clinic-request {
            background: #f3f4f6;
            padding: 0.4rem 0.7rem;
            border-radius: 0.3rem;
            font-size: 0.85rem;
            margin: 0.4rem 0;
        }

        .clinic-date-group {
            margin-top: 0.8rem;
        }
        .clinic-date-label {
            font-weight: 600;
            color: #374151;
            margin: 0 0 0.3rem;
            font-size: 0.95rem;
        }

        .clinic-empty {
            padding: 1.5rem 0.5rem;
            text-align: center;
            color: #6b7280;
            font-style: italic;
        }

        .clinic-alert {
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            background: #d1fae5;
            color: #065f46;
            margin-bottom: 1rem;
        }
        .clinic-alert.is-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .clinic-status-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem 0.5rem;
            margin: 0.75rem 0 0.25rem;
        }
        .clinic-tab {
            padding: 0.3rem 1rem;
            border-radius: 20px;
            background: #f3f4f6;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: 0.15s;
        }
        .clinic-tab.is-active {
            background: #2563eb;
            color: #fff;
        }
        .clinic-tab:hover { background: #e5e7eb; }
        .clinic-tab.is-active:hover { background: #1d4ed8; }

        .clinic-filter-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 0.5rem;
        }
        .clinic-filter-form .clinic-search {
            flex: 1 1 200px;
            min-width: 120px;
        }
        .clinic-filter-form .clinic-search input {
            width: 100%;
        }

        /* Modal */
        .clinic-modal {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .clinic-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            cursor: pointer;
            border: none;
        }
        .clinic-dialog {
            background: #fff;
            border-radius: 0.75rem;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 1.5rem;
            position: relative;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .clinic-dialog-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }
        .clinic-dialog-head h2 { margin: 0; font-size: 1.25rem; }
        .clinic-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
            padding: 0 0.3rem;
        }
        .clinic-close:hover { color: #1f2937; }
        .clinic-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem 1rem;
        }
        .clinic-fact {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
        }
        .clinic-fact span {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #6b7280;
        }
        .clinic-fact strong {
            font-weight: 500;
            word-break: break-word;
        }
        .clinic-wide { grid-column: 1 / -1; }

        /* ── Mobile adjustments ── */
        @media (max-width: 768px) {
            .clinic-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .clinic-schedule {
                padding: 0.75rem;
            }
            .clinic-panel {
                padding: 1rem;
            }
            .clinic-panel-head h2 {
                font-size: 1.1rem;
            }
            .clinic-profile-form,
            .clinic-availability-form,
            .clinic-block-form {
                flex-direction: column;
                align-items: stretch;
            }
            .clinic-profile-form label,
            .clinic-availability-form label,
            .clinic-block-form label {
                flex: 1 1 auto;
                min-width: 0;
            }
            .clinic-profile-form button,
            .clinic-availability-form button,
            .clinic-block-form button {
                width: 100%;
                justify-content: center;
            }
            .clinic-status-tabs {
                gap: 0.2rem;
            }
            .clinic-tab {
                font-size: 0.75rem;
                padding: 0.2rem 0.7rem;
            }
            .clinic-form-grid {
                grid-template-columns: 1fr;
            }
            .clinic-dialog {
                padding: 1.2rem;
                margin: 0.5rem;
            }
            .clinic-card {
                padding: 0.6rem 0.8rem;
            }
            .clinic-mini-slot {
                padding: 0.6rem 0.8rem;
            }
            .clinic-block-row {
                padding: 0.6rem 0.8rem;
            }
            .clinic-actions {
                flex-direction: column;
                align-items: flex-start;
            }
            .clinic-actions form {
                width: 100%;
            }
            .clinic-actions button {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .clinic-panel-head {
                flex-direction: column;
                align-items: flex-start;
            }
            .clinic-count {
                align-self: flex-start;
            }
            .clinic-primary,
            .clinic-secondary,
            .clinic-success,
            .clinic-danger {
                padding: 0.4rem 1rem;
                font-size: 0.8rem;
                width: 100%;
                text-align: center;
            }
            .clinic-filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            .clinic-filter-form .clinic-search {
                flex: 1 1 auto;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/clinic-schedule.js') }}?v={{ filemtime(public_path('js/clinic-schedule.js')) }}" defer></script>
@endpush

@php
    $activeTab = request()->query('tab', request()->hasAny(['q', 'status']) ? 'appointments' : 'profile');
    $activeTab = in_array($activeTab, ['profile', 'appointments'], true) ? $activeTab : 'profile';
    $statusClass = fn (string $statusValue): string => 'is-'.$statusValue;
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
    <section class="clinic-schedule" data-clinic-schedule-root data-staff-tabs="{{ $staff->id }}">
        <header class="clinic-page-head">
            <div>
                <p class="clinic-kicker">Healthcare Worker Portal</p>
                <h1 class="clinic-title">Clinic Schedule</h1>
                <p class="clinic-copy">Set your availability and manage appointment requests from mothers assigned to your care.</p>
            </div>
        </header>

        <nav class="clinic-page-tabs" role="tablist" aria-label="Clinic Schedule sections">
            @foreach (['profile' => 'Scheduling Profile', 'appointments' => 'Appointment Requests'] as $tabKey => $tabLabel)
                <a class="clinic-page-tab" id="clinic-tab-{{ $tabKey }}" role="tab"
                   href="{{ route('staff.clinic-schedule.index', array_merge(request()->only(['q', 'status']), ['tab' => $tabKey])) }}"
                   data-clinic-page-tab="{{ $tabKey }}" aria-controls="clinic-panel-{{ $tabKey }}"
                   aria-selected="{{ $activeTab === $tabKey ? 'true' : 'false' }}"
                   tabindex="{{ $activeTab === $tabKey ? '0' : '-1' }}">{{ $tabLabel }}</a>
            @endforeach
        </nav>

        @if (session('status'))
            <div class="clinic-alert">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="clinic-alert is-error">{{ $errors->first() }}</div>
        @endif

        <div class="clinic-grid">
            <div class="clinic-stack" id="clinic-panel-profile" role="tabpanel" aria-labelledby="clinic-tab-profile" tabindex="0" data-clinic-tab-panel="profile" @if ($activeTab !== 'profile') hidden @endif>
                <!-- Scheduling Profile -->
                <section class="clinic-panel">
                    <div class="clinic-panel-head">
                        <div>
                            <h2>Scheduling Profile</h2>
                            <p class="clinic-panel-copy">These details are shown to mothers looking for an appointment.</p>
                        </div>
                    </div>
                    <form class="clinic-profile-form" method="POST" action="{{ route('staff.clinic-schedule.profile.update') }}">
                        @csrf
                        @method('PATCH')
                        <label>
                            Barangay
                            <select name="assigned_barangay">
                                <option value="">Select barangay</option>
                                @foreach ($barangays as $barangay)
                                    <option value="{{ $barangay }}" @selected($staff->assigned_barangay === $barangay)>{{ $barangay }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            Facility
                            <input type="text" name="assigned_facility" value="{{ $staff->assigned_facility }}" maxlength="255" placeholder="Health center or facility">
                        </label>
                        <label>
                            Daily limit
                            <input type="number" name="max_appointments_per_day" value="{{ $staff->max_appointments_per_day ?? 8 }}" min="0" max="50" required>
                        </label>
                        <label class="clinic-check">
                            <input type="hidden" name="accepting_appointments" value="0">
                            <input type="checkbox" name="accepting_appointments" value="1" @checked($staff->accepting_appointments ?? true)>
                            <span>Accept new appointments</span>
                        </label>
                        <button class="clinic-primary" type="submit">Save Profile</button>
                    </form>
                </section>

                <!-- Available Hours -->
                <section class="clinic-panel">
                    <div class="clinic-panel-head">
                        <div>
                            <h2>Available Hours</h2>
                            <p class="clinic-panel-copy">Add the slots mothers can book.</p>
                        </div>
                    </div>
                    <form class="clinic-availability-form" method="POST" action="{{ route('staff.clinic-schedule.availability.store') }}">
                        @csrf
                        <label>
                            Day
                            <select name="day_of_week" required>
                                @foreach ($dayLabels as $dayValue => $dayLabel)
                                    <option value="{{ $dayValue }}">{{ $dayLabel }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Start<input type="time" name="start_time" required></label>
                        <label>End<input type="time" name="end_time" required></label>
                        <label>
                            Appointment type
                            <select name="appointment_type" required>
                                @foreach ($appointmentTypeLabels as $typeValue => $typeLabel)
                                    <option value="{{ $typeValue }}">{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            Meeting type
                            <select name="meeting_type" required>
                                @foreach ($meetingTypeLabels as $meetingValue => $meetingLabel)
                                    <option value="{{ $meetingValue }}">{{ $meetingLabel }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Location<input type="text" name="location" maxlength="255" placeholder="Facility or room"></label>
                        <button class="clinic-primary" type="submit">Add Hours</button>
                    </form>

                    <div class="clinic-availability-list">
                        @forelse ($staffAvailabilities as $availability)
                            <article class="clinic-mini-slot {{ $availability->is_active ? '' : 'is-disabled' }}">
                                <div class="clinic-slot-top">
                                    <strong>{{ $availability->dayLabel() }} · {{ $availability->timeLabel() }}</strong>
                                    <span class="clinic-pill {{ $availability->is_active ? 'is-confirmed' : 'is-cancelled' }}">{{ $availability->is_active ? 'Active' : 'Paused' }}</span>
                                </div>
                                <span>{{ $availability->typeLabel() }} · {{ $availability->meetingLabel() }}</span>
                                <small>{{ $availability->location ?: ($staff->assigned_facility ?: 'Location to be announced') }}</small>
                                <div class="clinic-actions">
                                    <form method="POST" action="{{ route('staff.clinic-schedule.availability.toggle', $availability) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="clinic-secondary clinic-small-action" type="submit">{{ $availability->is_active ? 'Pause' : 'Enable' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('staff.clinic-schedule.availability.destroy', $availability) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="clinic-danger clinic-small-action" type="submit">Remove</button>
                                    </form>
                                </div>
                            </article>
                        @empty
                            <div class="clinic-empty">No available hours yet. Add a slot so mothers can request an appointment.</div>
                        @endforelse
                    </div>
                </section>

                <!-- Blocked Dates -->
                <section class="clinic-panel">
                    <div class="clinic-panel-head">
                        <div>
                            <h2>Blocked Dates</h2>
                            <p class="clinic-panel-copy">Prevent booking on leave days or clinic closures.</p>
                        </div>
                    </div>
                    <form class="clinic-block-form" method="POST" action="{{ route('staff.clinic-schedule.blocks.store') }}">
                        @csrf
                        <label>Date<input type="date" name="blocked_date" min="{{ today()->toDateString() }}" required></label>
                        <label>Reason<input type="text" name="reason" maxlength="255" placeholder="Leave or clinic closure"></label>
                        <label>Notes<input type="text" name="notes" maxlength="1000" placeholder="Optional details"></label>
                        <button class="clinic-secondary" type="submit">Block Date</button>
                    </form>
                    <div class="clinic-block-list">
                        @forelse ($availabilityBlocks as $block)
                            <article class="clinic-block-row">
                                <div>
                                    <strong>{{ $block->blocked_date->format('M j, Y') }}</strong>
                                    <span>{{ $block->reason ?: 'Unavailable' }}</span>
                                    @if ($block->notes)<small>{{ $block->notes }}</small>@endif
                                </div>
                                <form method="POST" action="{{ route('staff.clinic-schedule.blocks.destroy', $block) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="clinic-danger clinic-small-action" type="submit">Remove</button>
                                </form>
                            </article>
                        @empty
                            <div class="clinic-empty">No blocked dates.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="clinic-stack" id="clinic-panel-appointments" role="tabpanel" aria-labelledby="clinic-tab-appointments" tabindex="0" data-clinic-tab-panel="appointments" @if ($activeTab !== 'appointments') hidden @endif>
                <!-- Appointment Requests (filter tabs) -->
                <section class="clinic-panel">
                    <div class="clinic-panel-head">
                        <div>
                            <h2>Appointment Requests</h2>
                            <p class="clinic-panel-copy">Review appointments from your assigned mothers.</p>
                        </div>
                        <span class="clinic-count">{{ $upcomingAppointments->count() }} upcoming</span>
                        <button class="clinic-primary" type="button" data-open-appointment-modal>Create Appointment</button>
                    </div>
                    <form class="clinic-filter-form" method="GET" action="{{ route('staff.clinic-schedule.index') }}">
                        <input type="hidden" name="tab" value="appointments">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <label class="clinic-search">
                            <span class="sr-only">Search by mother's name</span>
                            <input type="search" name="q" value="{{ $search }}" placeholder="Search mother's name">
                        </label>
                        <button class="clinic-secondary" type="submit">Search</button>
                    </form>
                    <div class="clinic-status-tabs" aria-label="Appointment status filters">
                        <a class="clinic-tab {{ $status === 'all' ? 'is-active' : '' }}" href="{{ route('staff.clinic-schedule.index', ['tab' => 'appointments', 'q' => $search]) }}">All</a>
                        @foreach ($statusLabels as $statusValue => $statusLabel)
                            @if ($statusValue !== 'missed')
                                <a class="clinic-tab {{ $status === $statusValue ? 'is-active' : '' }}" href="{{ route('staff.clinic-schedule.index', ['tab' => 'appointments', 'q' => $search, 'status' => $statusValue]) }}">{{ $statusLabel }}</a>
                            @endif
                        @endforeach
                    </div>
                </section>

                <!-- Upcoming Appointments -->
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

                <!-- Reschedule Requests -->
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
                                    $preferredEnd = $appointment->preferred_start_time ? \Illuminate\Support\Carbon::parse((string) $appointment->preferred_start_time)->addMinutes($duration)->format('H:i') : substr((string) $appointment->end_time, 0, 5);
                                @endphp
                                @if ($appointment->preferred_date && $appointment->preferred_start_time)
                                    <form method="POST" action="{{ route('staff.clinic-schedule.update', $appointment) }}">
                                        @csrf @method('PATCH')
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

                <!-- Appointment History -->
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
            </div>
        </div>

        <!-- Create Appointment Modal -->
        @include('partials.appointment-modal')

        <!-- Details Modal -->
        <div class="clinic-modal" data-clinic-modal data-details-modal hidden>
            <button class="clinic-backdrop" type="button" data-clinic-modal-close aria-label="Close appointment details"></button>
            <section class="clinic-dialog" role="dialog" aria-modal="true" aria-labelledby="details-modal-title">
                <header class="clinic-dialog-head">
                    <h2 id="details-modal-title" data-detail="detailTitle">Appointment Details</h2>
                    <button class="clinic-close" type="button" data-clinic-modal-close aria-label="Close appointment details">✕</button>
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

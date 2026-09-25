@extends('layouts.app')

@section('title', 'Doctor List - Project INAY')
@section('portal_title', 'Doctor List')
@section('handles_status', '1')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/mother-appointments.css') }}?v={{ filemtime(public_path('css/mother-appointments.css')) }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/mother-appointments.js') }}?v={{ filemtime(public_path('js/mother-appointments.js')) }}" defer></script>
@endpush

@php
    $iconSearch = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>';
    $iconFilter = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16"/><path d="M7 12h10"/><path d="M10 18h4"/></svg>';
    $iconUser = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21a7 7 0 0 0-14 0"/><circle cx="12" cy="7" r="4"/></svg>';
    $iconLocation = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
    $iconCalendar = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="5" width="18" height="17" rx="2"/><path d="M3 10h18"/></svg>';
    $iconMessage = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/><path d="M8 8h8"/><path d="M8 12h5"/></svg>';
    $iconClose = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';
    $doctorJson = json_encode($doctorCards, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $activeAppointmentNotice = $activeAppointmentNotice ?? null;
    $activeAppointmentJson = json_encode($activeAppointmentNotice, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
@endphp

@section('content')
    <section
        class="mother-appointments"
        data-mother-appointments
        data-booking-url="{{ route('mother.clinic-schedule.store') }}"
        data-calendar-url="{{ route('mother.clinic-schedule.calendar') }}"
        data-today="{{ today()->toDateString() }}"
        data-csrf="{{ csrf_token() }}"
    >
        <script type="application/json" data-doctor-json>{!! $doctorJson !!}</script>
        <script type="application/json" data-active-appointment-json>{!! $activeAppointmentJson !!}</script>

        @if (session('status'))
            <div class="appointment-alert" data-appointment-alert>{{ session('status') }}</div>
        @else
            <div class="appointment-alert" data-appointment-alert hidden></div>
        @endif

        @if ($errors->any())
            <div class="appointment-alert is-error">{{ $errors->first() }}</div>
        @endif

        <section
            class="appointment-active-notice"
            data-active-appointment-notice
            @if(! $activeAppointmentNotice) hidden @endif
        >
            <div class="appointment-active-copy">
                <span
                    class="appointment-status-badge is-{{ $activeAppointmentNotice['status_tone'] ?? 'waiting' }}"
                    data-active-status-badge
                >
                    {{ $activeAppointmentNotice['status_display'] ?? 'Waiting for Confirmation' }}
                </span>
                <p data-active-appointment-copy>
                    You already have an active appointment with
                    <strong data-active-doctor>{{ $activeAppointmentNotice['doctor_name'] ?? 'your healthcare worker' }}</strong>
                    on
                    <strong data-active-date-time>{{ $activeAppointmentNotice['date_time_label'] ?? 'your selected schedule' }}</strong>.
                </p>
            </div>
            <div class="appointment-notice-actions">
                <a
                    class="appointment-notice-button"
                    href="#{{ $activeAppointmentNotice['view_anchor'] ?? '' }}"
                    data-view-active-appointment
                >
                    View Appointment
                </a>
                <form
                    class="appointment-notice-cancel"
                    method="POST"
                    action="{{ $activeAppointmentNotice['cancel_url'] ?? '#' }}"
                    data-active-cancel-form
                    @if(! ($activeAppointmentNotice['can_cancel'] ?? false)) hidden @endif
                >
                    @csrf
                    @method('PATCH')
                    <button type="submit">Cancel Request</button>
                </form>
            </div>
        </section>

        @if ($appointments->isNotEmpty())
            <section class="care-appointments" aria-labelledby="my-appointments-title">
                <h2 id="my-appointments-title">My Appointments</h2>
                @foreach ($upcomingAppointments as $appointment)
                    @include('partials.mother-care-team-appointment', ['isHistory' => false])
                @endforeach
                @if ($historyAppointments->isNotEmpty())
                    <details class="care-appointment-history">
                        <summary>Appointment History ({{ $historyAppointments->count() }})</summary>
                        @foreach ($historyAppointments as $appointment)
                            @include('partials.mother-care-team-appointment', ['isHistory' => true])
                        @endforeach
                    </details>
                @endif
            </section>
        @endif

        <header class="appointment-topbar">
            <h1>Doctor List</h1>
            <form class="appointment-search" method="GET" action="{{ route('mother.clinic-schedule.index') }}">
                <label class="appointment-search-field">
                    <span class="sr-only">Search Doctor</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search Doctor">
                    {!! $iconSearch !!}
                </label>
                <input type="hidden" name="category" value="{{ $selectedCategory }}">
                @if ($selectedBarangay)
                    <input type="hidden" name="barangay" value="{{ $selectedBarangay }}">
                @endif
                <button class="appointment-filter-button @if($selectedBarangay) is-active @endif" type="button" data-filter-open>
                    <span>Filter</span>
                    {!! $iconFilter !!}
                </button>
            </form>
        </header>

        @if ($selectedBarangay)
            <div class="appointment-active-filter">
                <span>Barangay: <strong>{{ $selectedBarangay }}</strong></span>
                <a href="{{ route('mother.clinic-schedule.index', array_filter(['q' => $search, 'category' => $selectedCategory === 'all' ? null : $selectedCategory])) }}">Clear</a>
            </div>
        @endif

        <nav class="appointment-categories" aria-label="Appointment categories">
            @foreach ($appointmentCategories as $categoryKey => $categoryLabel)
                <a
                    class="@if($selectedCategory === $categoryKey) is-active @endif"
                    href="{{ route('mother.clinic-schedule.index', array_filter(['q' => $search, 'barangay' => $selectedBarangay, 'category' => $categoryKey === 'all' ? null : $categoryKey])) }}"
                >
                    {{ $categoryLabel }}
                </a>
            @endforeach
        </nav>

        <div class="filter-modal" data-filter-modal hidden>
            <button class="filter-backdrop" type="button" data-filter-close aria-label="Close barangay filter"></button>
            <section class="filter-dialog" role="dialog" aria-modal="true" aria-labelledby="barangay-filter-title">
                <header class="filter-head">
                    <div>
                        <h2 id="barangay-filter-title">Filter by Barangay</h2>
                        <p>Select a barangay to show doctors assigned nearby.</p>
                    </div>
                    <button class="filter-close" type="button" data-filter-close aria-label="Close barangay filter">{!! $iconClose !!}</button>
                </header>

                <form class="filter-form" method="GET" action="{{ route('mother.clinic-schedule.index') }}" data-filter-form>
                    <input type="hidden" name="q" value="{{ $search }}">
                    <input type="hidden" name="category" value="{{ $selectedCategory }}">
                    <input type="hidden" name="barangay" value="{{ $selectedBarangay }}" data-barangay-value>

                    <label class="filter-search-field">
                        <span class="sr-only">Search barangay</span>
                        <input type="search" placeholder="Search barangay" data-barangay-search>
                        {!! $iconSearch !!}
                    </label>

                    <div class="barangay-list" data-barangay-list>
                        <button
                            class="barangay-option @if($selectedBarangay === '') is-selected @endif"
                            type="button"
                            data-barangay-option=""
                        >
                            All barangays
                        </button>
                        @foreach ($barangays as $barangay)
                            <button
                                class="barangay-option @if($selectedBarangay === $barangay) is-selected @endif"
                                type="button"
                                data-barangay-option="{{ $barangay }}"
                            >
                                {{ $barangay }}
                            </button>
                        @endforeach
                    </div>

                    <p class="filter-empty" data-filter-empty hidden>No barangay matched your search.</p>

                    <footer class="filter-actions">
                        <button class="filter-clear" type="button" data-filter-clear>Clear</button>
                        <button class="filter-apply" type="submit">Apply Filter</button>
                    </footer>
                </form>
            </section>
        </div>

        <div class="doctor-grid">
            @forelse ($doctorCards as $doctor)
                @php
                    $doctorStatus = $doctor['appointment_status'] ?? null;
                    $hasActiveAppointment = (bool) $activeAppointmentNotice;
                    $isActiveAppointmentDoctor = (bool) ($doctor['is_active_appointment_doctor'] ?? false);
                    $hasSlots = ! empty($doctor['availabilities']);
                    $bookButtonClass = '';
                    $bookButtonLabel = 'Book Now';

                    if (! $hasSlots) {
                        $bookButtonLabel = 'No Slots';
                    } elseif ($hasActiveAppointment) {
                        if ($isActiveAppointmentDoctor && ($activeAppointmentNotice['status'] ?? '') === 'pending') {
                            $bookButtonLabel = 'Appointment Pending';
                            $bookButtonClass = 'is-pending';
                        } elseif ($isActiveAppointmentDoctor) {
                            $bookButtonLabel = 'Appointment Confirmed';
                            $bookButtonClass = 'is-confirmed';
                        } else {
                            $bookButtonLabel = 'Active Appointment';
                            $bookButtonClass = 'is-locked';
                        }
                    }
                @endphp
                <article
                    id="doctor-card-{{ $doctor['id'] }}"
                    class="doctor-card @if($isActiveAppointmentDoctor) is-active-appointment @endif"
                    data-doctor-card
                    data-doctor-id="{{ $doctor['id'] }}"
                >
                    <div class="doctor-card-main">
                        <div class="doctor-photo" aria-hidden="true">
                            @if ($doctor['photo_url'])
                                <img src="{{ $doctor['photo_url'] }}" alt="">
                            @else
                                <span>{{ $doctor['initials'] }}</span>
                            @endif
                        </div>

                        <div class="doctor-card-copy">
                            <div class="doctor-name-row">
                                <h2>{{ $doctor['name'] }}</h2>
                                <span
                                    class="appointment-status-badge is-{{ $doctorStatus['tone'] ?? 'neutral' }}"
                                    data-doctor-status-badge
                                    @if(! $doctorStatus) hidden @endif
                                >
                                    {{ $doctorStatus['label'] ?? '' }}
                                </span>
                            </div>
                            <p class="doctor-specialty">{{ $doctor['role'] }}</p>
                            <ul>
                                <li>{!! $iconUser !!}<span>{{ $doctor['category_label'] }}</span></li>
                                <li>{!! $iconLocation !!}<span>{{ $doctor['location'] ?: $doctor['consultation_type'] }}</span></li>
                                <li>{!! $iconCalendar !!}<span>{{ $doctor['schedule'] }}</span></li>
                            </ul>
                        </div>
                    </div>

                    <div class="doctor-actions">
                        <button
                            class="doctor-book {{ $bookButtonClass }}"
                            type="button"
                            data-book-doctor="{{ $doctor['id'] }}"
                            data-has-slots="{{ $hasSlots ? 'true' : 'false' }}"
                            @disabled(! $hasSlots || $hasActiveAppointment)
                        >
                            {{ $bookButtonLabel }}
                        </button>
                        <button class="doctor-detail" type="button" data-open-detail="{{ $doctor['id'] }}">Details</button>
                        <a class="doctor-message" href="{{ $doctor['message_url'] }}" aria-label="Message {{ $doctor['name'] }}">
                            {!! $iconMessage !!}
                        </a>
                    </div>
                </article>
            @empty
                <div class="doctor-empty">
                    <strong>No doctors matched your filters.</strong>
                    <span>Try another name, specialty, barangay, or category.</span>
                </div>
            @endforelse
        </div>

        <div class="detail-modal" data-detail-modal hidden>
            <button class="detail-backdrop" type="button" data-detail-close aria-label="Close doctor details"></button>
            <section class="detail-dialog" role="dialog" aria-modal="true" aria-labelledby="detail-doctor-title">
                <header class="detail-head">
                    <h2 id="detail-doctor-title">Detail Doctor</h2>
                    <button class="detail-close" type="button" data-detail-close aria-label="Close doctor details">{!! $iconClose !!}</button>
                </header>

                <div class="detail-photo">
                    <img data-detail-photo alt="" hidden>
                    <span data-detail-initials>IN</span>
                </div>

                <section class="detail-profile">
                    <h3 data-detail-name>Doctor Name</h3>
                    <div class="detail-meta">
                        <span>{!! $iconUser !!}<b data-detail-role>Specialty</b></span>
                        <span>{!! $iconLocation !!}<b data-detail-location>Location</b></span>
                    </div>
                    <p>{!! $iconCalendar !!}<span data-detail-schedule>Available schedule</span></p>
                </section>

                <section class="detail-section">
                    <h4>Experience</h4>
                    <p data-detail-experience>Clinical care experience.</p>
                </section>

                <section class="detail-section">
                    <h4>Speciality</h4>
                    <ul data-detail-specialties></ul>
                </section>

                <section class="detail-section">
                    <h4>Reviews</h4>
                    <p>No verified reviews available.</p>
                </section>

                <footer class="detail-footer">
                    <button class="detail-book" type="button" data-detail-book @if($activeAppointmentNotice) disabled @endif>{{ $activeAppointmentNotice ? 'Active Appointment' : 'Book Now' }}</button>
                    <a class="detail-chat" href="{{ route('mother.consultation') }}" data-detail-chat>Chat</a>
                </footer>
            </section>
        </div>

        <div class="booking-modal" data-booking-modal hidden>
            <button class="booking-backdrop" type="button" data-booking-close aria-label="Close booking appointment"></button>
            <section class="booking-dialog" role="dialog" aria-modal="true" aria-labelledby="booking-appointment-title">
                <form data-booking-form>
                    <input type="hidden" name="staff_availability_id" data-booking-availability>
                    <input type="hidden" name="appointment_date" data-booking-date>
                    <input type="hidden" name="appointment_type" data-booking-type>
                    <input type="hidden" name="meeting_type" data-booking-meeting>

                    <header class="booking-head">
                        <div>
                            <h2 id="booking-appointment-title">Booking Appointment</h2>
                            <p data-booking-doctor>Choose a doctor and time slot.</p>
                        </div>
                        <button class="booking-close" type="button" data-booking-close aria-label="Close booking appointment">{!! $iconClose !!}</button>
                    </header>

                    <div class="booking-calendar-head">
                        <strong data-calendar-month>Month</strong>
                        <div>
                            <button type="button" data-calendar-prev aria-label="Previous month">&lt;</button>
                            <button type="button" data-calendar-next aria-label="Next month">&gt;</button>
                        </div>
                    </div>

                    <div class="booking-weekdays" aria-hidden="true">
                        <span>Sun</span>
                        <span>Mon</span>
                        <span>Tue</span>
                        <span>Wed</span>
                        <span>Thu</span>
                        <span>Fri</span>
                        <span>Sat</span>
                    </div>
                    <div class="booking-calendar-grid" data-calendar-grid></div>
                    <div class="booking-legend" aria-label="Calendar legend">
                        <span><i class="legend-available"></i>Available</span>
                        <span><i class="legend-selected"></i>Selected</span>
                        <span><i class="legend-booked"></i>Booked / full</span>
                        <span><i class="legend-unavailable"></i>Unavailable</span>
                    </div>
                    <p class="booking-loading" data-calendar-status role="status"></p>
                    <button class="booking-retry" type="button" data-calendar-retry hidden>Retry availability</button>

                    <section class="booking-slots" aria-live="polite">
                        <h3 data-selected-date-label>Select a date</h3>
                        <div class="booking-slot-grid" data-time-slots></div>
                    </section>

                    <label class="booking-concerns">
                        Patient Concerns
                        <textarea name="notes" maxlength="2000" rows="3" placeholder="Describe your concerns, symptoms, or questions"></textarea>
                    </label>

                    <p class="booking-error" data-booking-error hidden></p>

                    <footer class="booking-footer">
                        <button class="booking-cancel" type="button" data-booking-close>Cancel</button>
                        <button class="booking-confirm" type="submit" data-booking-submit disabled>Confirm Appointment</button>
                    </footer>
                </form>
            </section>
        </div>
    </section>
@endsection

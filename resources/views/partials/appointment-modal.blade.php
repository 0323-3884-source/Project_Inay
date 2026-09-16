<div class="clinic-modal" data-clinic-modal data-appointment-modal hidden>
    <button class="clinic-backdrop" type="button" data-clinic-modal-close aria-label="Close appointment form"></button>
    <section class="clinic-dialog" role="dialog" aria-modal="true" aria-labelledby="appointment-modal-title">
        <form method="POST" action="{{ route('staff.clinic-schedule.store') }}" data-appointment-form data-store-url="{{ route('staff.clinic-schedule.store') }}">
            @csrf
            <input type="hidden" name="_method" value="PATCH" data-method-input disabled>
            <input type="hidden" name="conversation_id" value="">
            @isset($fromConsultation)
                <input type="hidden" name="from_consultation" value="1">
            @endisset

            <header class="clinic-dialog-head">
                <h2 id="appointment-modal-title" data-appointment-modal-title>Create Appointment</h2>
                <button class="clinic-close" type="button" data-clinic-modal-close aria-label="Close appointment form">x</button>
            </header>

            <div class="clinic-form-grid">
                <label>
                    Mother
                    <select name="mother_id" required>
                        <option value="">Select mother</option>
                        @foreach ($assignedMothers as $assignedMother)
                            <option value="{{ $assignedMother->id }}">{{ $assignedMother->full_name }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Appointment type
                    <select name="appointment_type" required>
                        @foreach ($appointmentTypeLabels as $typeValue => $typeLabel)
                            <option value="{{ $typeValue }}">{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Date
                    <input type="date" name="appointment_date" min="{{ now()->toDateString() }}" required>
                </label>

                <label>
                    Available slot
                    <select name="staff_availability_id" data-availability-select>
                        <option value="">Select a saved slot</option>
                        @foreach (($staffAvailabilities ?? collect()) as $availability)
                            <option
                                value="{{ $availability->id }}"
                                data-day="{{ $availability->day_of_week }}"
                                data-appointment-type="{{ $availability->appointment_type }}"
                                data-meeting-type="{{ $availability->meeting_type }}"
                                data-start-time="{{ substr((string) $availability->start_time, 0, 5) }}"
                                data-end-time="{{ substr((string) $availability->end_time, 0, 5) }}"
                                data-location="{{ $availability->location }}"
                            >
                                {{ $availability->dayLabel() }} - {{ $availability->timeLabel() }} - {{ $availability->typeLabel() }}
                            </option>
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

                <label>
                    Start time
                    <input type="time" name="start_time" required>
                </label>

                <label>
                    End time
                    <input type="time" name="end_time" required>
                </label>

                <label>
                    Location
                    <input type="text" name="location" maxlength="255" placeholder="Clinic room, health center, or video room">
                </label>

                <label data-status-row hidden>
                    Status
                    <select name="status" disabled>
                        @foreach ($statusLabels as $statusValue => $statusLabel)
                            @if (in_array($statusValue, ['pending', 'confirmed', 'reschedule_requested'], true))
                                <option value="{{ $statusValue }}">{{ $statusLabel }}</option>
                            @endif
                        @endforeach
                    </select>
                </label>

                <label class="clinic-wide">
                    Notes
                    <textarea name="notes" maxlength="2000" placeholder="Add preparation notes, instructions, or follow-up context"></textarea>
                </label>
            </div>

            <footer class="clinic-dialog-footer">
                <button class="clinic-secondary" type="button" data-clinic-modal-close>Cancel</button>
                <button class="clinic-primary" type="submit">Save Appointment</button>
            </footer>
        </form>
    </section>
</div>

@php
    $startValue = substr((string) $appointment->start_time, 0, 5);
    $endValue = substr((string) $appointment->end_time, 0, 5);
    $dateValue = $appointment->appointment_date->toDateString();
    $detailTitle = $appointment->typeLabel().' with '.$appointment->mother->full_name;
@endphp

<article class="clinic-card">
    <div class="clinic-card-top">
        <div>
            <h3>{{ $appointment->typeLabel() }}</h3>
            <p>{{ $appointment->mother->full_name }} · {{ $appointment->appointment_date->format('M j, Y') }} · {{ $timeLabel($appointment->start_time) }} - {{ $timeLabel($appointment->end_time) }}</p>
        </div>
        <span class="clinic-pill {{ $statusClass($appointment->status) }}">{{ $appointment->statusLabel() }}</span>
    </div>

    <div class="clinic-facts">
        <div class="clinic-fact"><span>Meeting</span><strong>{{ $appointment->meetingLabel() }}</strong></div>
        <div class="clinic-fact"><span>Location</span><strong>{{ $appointment->location ?: 'Not provided' }}</strong></div>
        <div class="clinic-fact"><span>Program Staff</span><strong>{{ $appointment->staff->full_name }}</strong></div>
        <div class="clinic-fact"><span>Conversation</span><strong>{{ $appointment->conversation_id ? 'Linked' : 'Not linked' }}</strong></div>
    </div>

    @if ($appointment->notes)
        <p>{{ $appointment->notes }}</p>
    @endif

    <div class="clinic-actions">
        <button
            class="clinic-secondary"
            type="button"
            data-open-details
            data-detail-title="{{ $detailTitle }}"
            data-mother-name="{{ $appointment->mother->full_name }}"
            data-status-label="{{ $appointment->statusLabel() }}"
            data-date-label="{{ $appointment->appointment_date->format('M j, Y') }}"
            data-time-label="{{ $timeLabel($appointment->start_time) }} - {{ $timeLabel($appointment->end_time) }}"
            data-meeting-label="{{ $appointment->meetingLabel() }}"
            data-location="{{ $appointment->location ?: 'Not provided' }}"
            data-notes="{{ $appointment->notes ?: 'No notes.' }}"
        >View Details</button>

        @if (! $isHistory && in_array($appointment->status, \App\Models\Appointment::activeStatuses(), true))
            <button
                class="clinic-secondary"
                type="button"
                data-edit-appointment
                data-update-url="{{ route('staff.clinic-schedule.update', $appointment) }}"
                data-mother-id="{{ $appointment->mother_id }}"
                data-conversation-id="{{ $appointment->conversation_id }}"
                data-appointment-type="{{ $appointment->appointment_type }}"
                data-meeting-type="{{ $appointment->meeting_type }}"
                data-appointment-date="{{ $dateValue }}"
                data-start-time="{{ $startValue }}"
                data-end-time="{{ $endValue }}"
                data-location="{{ $appointment->location }}"
                data-notes="{{ $appointment->notes }}"
                data-status="{{ $appointment->status }}"
            >Edit</button>

            <form method="POST" action="{{ route('staff.clinic-schedule.cancel', $appointment) }}">
                @csrf
                @method('PATCH')
                <button class="clinic-danger" type="submit">Cancel</button>
            </form>

            <form method="POST" action="{{ route('staff.clinic-schedule.complete', $appointment) }}">
                @csrf
                @method('PATCH')
                <button class="clinic-success" type="submit">Mark Completed</button>
            </form>
        @endif

        @if ($appointment->meeting_type === \App\Models\Appointment::MEETING_VIDEO)
            @if ($canJoinVideo($appointment))
                <a class="clinic-primary" href="{{ $conversationRoute($appointment) }}">Join Video Consultation</a>
            @else
                <span class="clinic-link-button is-disabled" aria-disabled="true">Join Video Consultation</span>
            @endif
        @endif
    </div>
</article>

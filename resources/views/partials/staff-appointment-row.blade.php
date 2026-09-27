@php
    $startValue = substr((string) $appointment->start_time, 0, 5);
    $endValue = substr((string) $appointment->end_time, 0, 5);
    $dateValue = $appointment->appointment_date->toDateString();
    $availabilityValue = $appointment->staff_availability_id ?: '';
    $detailTitle = $appointment->typeLabel().' with '.$appointment->mother->full_name;
@endphp

<tr data-appointment-row data-status="{{ $appointment->status }}" data-search="{{ $appointment->mother->full_name }} {{ $appointment->typeLabel() }} {{ $appointment->staff->full_name }} {{ $appointment->reschedule_reason }}">
    <td><strong>{{ $appointment->mother->full_name }}</strong></td>
    @if ($isReschedule)
        <td>{{ $appointment->appointment_date->format('M j, Y') }}<small>{{ $timeLabel($appointment->start_time) }} - {{ $timeLabel($appointment->end_time) }}</small></td>
        <td>{{ $appointment->preferred_date?->format('M j, Y') ?? 'No date requested' }}<small>{{ $appointment->preferred_start_time ? $timeLabel($appointment->preferred_start_time) : 'No time requested' }}</small></td>
        <td><span class="appointment-reason">{{ $appointment->reschedule_reason ?: 'No reason provided.' }}</span></td>
    @else
        <td>{{ $appointment->typeLabel() }}</td>
        <td>{{ $appointment->appointment_date->format('M j, Y') }}<small>{{ $timeLabel($appointment->start_time) }} - {{ $timeLabel($appointment->end_time) }}</small></td>
        <td>{{ $appointment->staff->full_name }}</td>
    @endif
    <td><span class="clinic-pill {{ $statusClass($appointment->status) }}">{{ $appointment->statusLabel() }}</span></td>
    <td><div class="appointment-row-actions">
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
            data-worker="{{ $appointment->staff->full_name }}"
            data-facility="{{ $appointment->care_team_snapshot['facility'] ?? 'Not provided' }}"
            data-barangay="{{ $appointment->care_team_snapshot['barangay'] ?? 'Not provided' }}"
            data-midwife="{{ $appointment->care_team_snapshot['midwife']['name'] ?? 'Not provided' }}"
            data-conversation="{{ $appointment->conversation_id ? 'Linked' : 'Not linked' }}"
            data-requested="{{ $appointment->preferred_date?->format('M j, Y') ?? 'No date' }} {{ $appointment->preferred_start_time ? $timeLabel($appointment->preferred_start_time) : '' }}"
            data-reason="{{ $appointment->reschedule_reason ?: 'No reason provided.' }}"
            data-notes="{{ $appointment->notes ?: 'No notes.' }}"
        >View Details</button>

    @if ((! $isHistory && in_array($appointment->status, \App\Models\Appointment::activeStatuses(), true)) || $appointment->meeting_type === \App\Models\Appointment::MEETING_VIDEO)
        <details class="appointment-more"><summary>Actions</summary><div class="appointment-more-content">
            @if ($isReschedule)
                @include('partials.staff-appointment-approval')
            @endif

        @if (! $isHistory && in_array($appointment->status, \App\Models\Appointment::activeStatuses(), true))
            @if (in_array($appointment->status, [\App\Models\Appointment::STATUS_PENDING, \App\Models\Appointment::STATUS_RESCHEDULE_REQUESTED], true))
                <form method="POST" action="{{ route('staff.clinic-schedule.confirm', $appointment) }}">
                    @csrf
                    @method('PATCH')
                    <button class="clinic-success" type="submit">Confirm</button>
                </form>

                <form method="POST" action="{{ route('staff.clinic-schedule.reject', $appointment) }}" data-reject-form>
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="decline_reason" value="">
                    <button class="clinic-danger" type="submit">Reject</button>
                </form>
            @endif

            <button
                class="clinic-secondary"
                type="button"
                data-edit-appointment
                data-update-url="{{ route('staff.clinic-schedule.update', $appointment) }}"
                data-mother-id="{{ $appointment->mother_id }}"
                data-staff-availability-id="{{ $availabilityValue }}"
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

            <button
                class="clinic-secondary"
                type="button"
                data-open-staff-reschedule
                data-suggest-url="{{ route('staff.clinic-schedule.suggest-schedule', $appointment) }}"
                data-suggest-title="Suggest New Schedule for {{ $appointment->mother->full_name }}"
                data-suggest-appointment-type="{{ $appointment->appointment_type }}"
            >Suggest New Schedule</button>

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

        </div></details>
    @endif
    </div></td>
</tr>

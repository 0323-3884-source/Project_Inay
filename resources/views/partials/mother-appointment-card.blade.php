<article class="clinic-card">
    <div class="clinic-card-top">
        <div>
            <h3>{{ $appointment->typeLabel() }}</h3>
            <p>{{ $appointment->appointment_date->format('M j, Y') }} &middot; {{ $timeLabel($appointment->start_time) }} - {{ $timeLabel($appointment->end_time) }}</p>
        </div>
        <span class="clinic-pill {{ $statusClass($appointment->status) }}">{{ $appointment->statusLabel() }}</span>
    </div>

    <div class="clinic-facts">
        <div class="clinic-fact"><span>Healthcare worker</span><strong>{{ $appointment->staff->full_name }}</strong></div>
        <div class="clinic-fact"><span>Role</span><strong>{{ $appointment->staff->role_label }}</strong></div>
        <div class="clinic-fact"><span>Consultation type</span><strong>{{ $appointment->meetingLabel() }}</strong></div>
        <div class="clinic-fact"><span>Location</span><strong>{{ $appointment->location ?: 'Not provided' }}</strong></div>
        <div class="clinic-fact"><span>Status</span><strong>{{ $appointment->statusLabel() }}</strong></div>
    </div>

    @if ($appointment->notes)
        <p>{{ $appointment->notes }}</p>
    @endif

    @if ($appointment->status === \App\Models\Appointment::STATUS_RESCHEDULE_REQUESTED)
        <div class="clinic-request">
            Reschedule requested for {{ $appointment->preferred_date?->format('M j, Y') ?? 'your preferred date' }}
            @if ($appointment->preferred_start_time)
                at {{ $timeLabel($appointment->preferred_start_time) }}
            @endif
            . {{ $appointment->reschedule_reason }}
        </div>
    @endif

    @if ($appointment->status === \App\Models\Appointment::STATUS_RESCHEDULED && $appointment->reschedule_reason)
        <div class="clinic-request">
            New schedule suggested: {{ $appointment->reschedule_reason }}
        </div>
    @endif

    <div class="clinic-actions">
        @if (! $isHistory && $appointment->status === \App\Models\Appointment::STATUS_RESCHEDULED)
            <form method="POST" action="{{ route('mother.clinic-schedule.confirm', $appointment) }}">
                @csrf
                @method('PATCH')
                <button class="clinic-success" type="submit">Accept New Schedule</button>
            </form>

            <form method="POST" action="{{ route('mother.clinic-schedule.decline', $appointment) }}" data-decline-form>
                @csrf
                @method('PATCH')
                <input type="hidden" name="decline_reason" value="">
                <button class="clinic-danger" type="submit">Reject New Schedule</button>
            </form>
        @endif

        @if (! $isHistory && in_array($appointment->status, [\App\Models\Appointment::STATUS_PENDING, \App\Models\Appointment::STATUS_CONFIRMED], true))
            <form method="POST" action="{{ route('mother.clinic-schedule.cancel', $appointment) }}">
                @csrf
                @method('PATCH')
                <button class="clinic-danger" type="submit">{{ $appointment->status === \App\Models\Appointment::STATUS_PENDING ? 'Cancel Request' : 'Cancel Appointment' }}</button>
            </form>
        @endif

        @if (! $isHistory && $appointment->status === \App\Models\Appointment::STATUS_CONFIRMED)
            <button
                class="clinic-secondary"
                type="button"
                data-open-reschedule
                data-reschedule-url="{{ route('mother.clinic-schedule.reschedule', $appointment) }}"
                data-reschedule-title="Request Reschedule for {{ $appointment->typeLabel() }}"
            >Request Reschedule</button>
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

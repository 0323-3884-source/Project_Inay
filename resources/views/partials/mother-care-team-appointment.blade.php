@php($team = $appointment->care_team_snapshot ?? [])
<article class="care-appointment" id="appointment-{{ $appointment->id }}">
    <header>
        <div>
            <h3>{{ $appointment->typeLabel() }}</h3>
            <p>{{ $appointment->appointment_date->format('F j, Y') }} &middot; {{ \Illuminate\Support\Carbon::parse($appointment->start_time)->format('g:i A') }} – {{ \Illuminate\Support\Carbon::parse($appointment->end_time)->format('g:i A') }}</p>
        </div>
        <span class="care-appointment-status">{{ $appointment->statusLabel() }}</span>
    </header>
    <dl class="care-appointment-facts">
        <div><dt>Facility</dt><dd>{{ $team['facility'] ?? $appointment->location ?: 'Not provided' }}</dd></div>
        <div><dt>Barangay</dt><dd>{{ $team['barangay'] ?? 'Not recorded' }}</dd></div>
        <div><dt>Consultation type</dt><dd>{{ $appointment->meetingLabel() }}</dd></div>
        <div><dt>Assigned Healthcare Worker</dt><dd>{{ $team['worker_name'] ?? $appointment->staff?->full_name }}<small>{{ $team['worker_role'] ?? $appointment->staff?->role_label }}</small></dd></div>
        @if (! empty($team['midwife']))
            <div class="care-appointment-midwife"><dt>Assigned Midwife</dt><dd>{{ $team['midwife']['name'] }}<small>Midwife</small>
                @if (! empty($team['midwife']['contact_number']))
                    <small>Contact: {{ $team['midwife']['contact_number'] }}</small>
                @endif
            </dd></div>
        @endif
    </dl>
    @if (! $isHistory && in_array($appointment->status, [\App\Models\Appointment::STATUS_PENDING, \App\Models\Appointment::STATUS_CONFIRMED], true))
        <form method="POST" action="{{ route('mother.clinic-schedule.cancel', $appointment) }}">
            @csrf
            @method('PATCH')
            <button class="care-appointment-cancel" type="submit">{{ $appointment->status === \App\Models\Appointment::STATUS_PENDING ? 'Cancel Request' : 'Cancel Appointment' }}</button>
        </form>
    @endif
</article>

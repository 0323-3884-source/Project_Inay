@php
    $team = $appointment->care_team_snapshot ?? [];
    $midwifeData = ! empty($team['midwife']) ? $team['midwife'] : null;
    $midwifeModel = $appointment->midwife ?? null;
    $midwifeStaff = $midwifeModel?->programStaff;

    // Use snapshot midwife if present; fallback to database relationship if snapshot did not record it
    $hasMidwife = ! empty($midwifeData) || ! empty($appointment->midwife_profile_id);

    $midwifeName = $midwifeData['name']
        ?? $midwifeModel?->display_name
        ?? null;

    $midwifeRole = $midwifeData['role']
        ?? ($midwifeStaff?->role_label ?: 'Midwife');

    $midwifeFacility = $midwifeData['facility']
        ?? $midwifeModel?->currentFacility()?->name
        ?? $midwifeStaff?->assigned_facility
        ?? $midwifeModel?->facility?->name
        ?? $team['facility']
        ?? $appointment->facility?->name
        ?? $appointment->location
        ?? null;

    $midwifeContact = $midwifeData['contact_number']
        ?? $midwifeData['contact']
        ?? $midwifeStaff?->contact_number
        ?? $midwifeModel?->contact_number
        ?? null;
@endphp
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
        @if ($hasMidwife && $midwifeName)
            <div class="care-appointment-midwife">
                <dt>Assigned Midwife</dt>
                <dd>
                    {{ $midwifeName }}
                    <small>{{ $midwifeRole }}</small>
                    @if ($midwifeFacility)
                        <small>Facility: {{ $midwifeFacility }}</small>
                    @endif
                    @if ($midwifeContact && ! in_array(strtolower(trim($midwifeContact)), ['not provided', 'not set', 'none', ''], true))
                        <small>Contact: {{ $midwifeContact }}</small>
                    @elseif ($midwifeContact && in_array(strtolower(trim($midwifeContact)), ['not provided', 'not set', 'none'], true))
                        <small>Contact: Not provided</small>
                    @endif
                </dd>
            </div>
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

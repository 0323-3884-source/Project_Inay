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

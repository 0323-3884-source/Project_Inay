<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->session()->get('auth_role') === 'staff';
    }

    public function rules(): array
    {
        return [
            'mother_id' => ['required', 'integer', 'exists:mothers,id'],
            'staff_availability_id' => ['nullable', 'integer', 'exists:staff_availabilities,id'],
            'conversation_id' => ['nullable', 'integer', 'exists:conversations,id'],
            'appointment_type' => ['required', Rule::in(array_keys(Appointment::appointmentTypeLabels()))],
            'meeting_type' => ['required', Rule::in(array_keys(Appointment::meetingTypeLabels()))],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in([
                Appointment::STATUS_PENDING,
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_RESCHEDULED,
                Appointment::STATUS_RESCHEDULE_REQUESTED,
            ])],
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_date.after_or_equal' => 'Do not schedule an appointment in the past.',
            'end_time.after' => 'End time must be later than start time.',
        ];
    }
}

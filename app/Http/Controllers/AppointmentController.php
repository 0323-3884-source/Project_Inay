<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesConsultations;
use App\Http\Requests\StaffAppointmentRequest;
use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Models\StaffMotherCasefile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    use AuthorizesConsultations;

    public function staffIndex(Request $request): View|RedirectResponse
    {
        $staff = $this->currentProgramStaff($request);

        if (! $staff) {
            return redirect()->route('login')->with('status', 'Please login as Program Staff first.');
        }

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $assignedMotherIds = StaffMotherCasefile::where('staff_id', $staff->id)->pluck('mother_id');
        $assignedMothers = Mother::whereIn('id', $assignedMotherIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $appointmentsQuery = Appointment::with(['mother', 'staff', 'conversation'])
            ->where('staff_id', $staff->id)
            ->whereIn('mother_id', $assignedMotherIds);

        if ($search !== '') {
            $tokens = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($tokens as $token) {
                $like = '%'.$token.'%';

                $appointmentsQuery->whereHas('mother', function ($query) use ($like) {
                    $query->where('first_name', 'like', $like)
                        ->orWhere('middle_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('contact_number', 'like', $like);
                });
            }
        }

        if (array_key_exists($status, Appointment::statusLabels())) {
            $appointmentsQuery->where('status', $status);
        }

        $appointments = $appointmentsQuery
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get();

        $today = today();
        $upcomingAppointments = $appointments
            ->filter(fn (Appointment $appointment): bool => $this->isUpcomingAppointment($appointment, $today))
            ->values();
        $historyAppointments = $appointments
            ->reject(fn (Appointment $appointment): bool => $this->isUpcomingAppointment($appointment, $today))
            ->values();
        $appointmentsByDate = $upcomingAppointments->groupBy(fn (Appointment $appointment): string => $appointment->appointment_date->toDateString());

        return view('modules.staff-clinic-schedule', [
            'staff' => $staff,
            'assignedMothers' => $assignedMothers,
            'appointments' => $appointments,
            'upcomingAppointments' => $upcomingAppointments,
            'historyAppointments' => $historyAppointments,
            'appointmentsByDate' => $appointmentsByDate,
            'appointmentTypeLabels' => Appointment::appointmentTypeLabels(),
            'meetingTypeLabels' => Appointment::meetingTypeLabels(),
            'statusLabels' => Appointment::statusLabels(),
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function motherIndex(Request $request): View|RedirectResponse
    {
        $mother = $this->currentMother($request);

        if (! $mother) {
            return redirect()->route('login')->with('status', 'Please login as Mother first.');
        }

        $appointments = Appointment::with(['mother', 'staff', 'conversation'])
            ->where('mother_id', $mother->id)
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get();

        $today = today();
        $upcomingAppointments = $appointments
            ->filter(fn (Appointment $appointment): bool => $this->isUpcomingAppointment($appointment, $today))
            ->values();
        $historyAppointments = $appointments
            ->reject(fn (Appointment $appointment): bool => $this->isUpcomingAppointment($appointment, $today))
            ->values();
        $nextAppointment = $upcomingAppointments->first();

        return view('modules.mother-clinic-schedule', [
            'mother' => $mother,
            'appointments' => $appointments,
            'upcomingAppointments' => $upcomingAppointments,
            'historyAppointments' => $historyAppointments,
            'nextAppointment' => $nextAppointment,
            'appointmentTypeLabels' => Appointment::appointmentTypeLabels(),
            'meetingTypeLabels' => Appointment::meetingTypeLabels(),
            'statusLabels' => Appointment::statusLabels(),
        ]);
    }

    public function store(StaffAppointmentRequest $request): JsonResponse|RedirectResponse
    {
        $staff = $this->currentProgramStaff($request);

        if (! $staff) {
            return $this->appointmentError($request, 'Please login as Program Staff first.', 401);
        }

        $validated = $request->validated();
        $mother = Mother::findOrFail($validated['mother_id']);

        if (! $this->staffMayScheduleMother($staff, $mother)) {
            return $this->appointmentError($request, 'You can only schedule assigned mothers.', 403);
        }

        $conversation = $this->conversationForAppointment($mother, $staff, $validated['conversation_id'] ?? null);

        if (! $conversation) {
            return $this->appointmentError($request, 'The selected conversation is invalid.', 403);
        }

        if ($this->hasAppointmentConflict(
            (int) $mother->id,
            (int) $staff->id,
            $validated['appointment_date'],
            $validated['start_time'],
            $validated['end_time'],
        )) {
            return $this->appointmentError($request, 'This schedule conflicts with another appointment.', 422);
        }

        $appointment = Appointment::create([
            'mother_id' => $mother->id,
            'staff_id' => $staff->id,
            'conversation_id' => $conversation->id,
            'appointment_type' => $validated['appointment_type'],
            'meeting_type' => $validated['meeting_type'],
            'appointment_date' => $validated['appointment_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'location' => $validated['location'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => Appointment::STATUS_PENDING,
            'created_by_id' => $staff->id,
            'created_by_role' => Message::ROLE_PROGRAM_STAFF,
        ]);

        $this->notifyAppointment(
            $appointment,
            Message::ROLE_MOTHER,
            (int) $mother->id,
            'appointment_created',
            'New clinic appointment',
            $this->appointmentSummary($appointment).' was scheduled by '.$staff->full_name.'.'
        );

        $messagePayload = null;

        if ($request->boolean('from_consultation') || $request->expectsJson()) {
            $messagePayload = $this->createScheduleSystemMessage($appointment, $request);
        }

        if ($request->expectsJson()) {
            $appointment->loadMissing('conversation');

            return response()->json([
                'appointment' => $this->appointmentPayload($appointment->refresh()),
                'message' => $messagePayload,
                'conversation' => $appointment->conversation
                    ? $this->conversationPayload($appointment->conversation->refresh(), $request)
                    : null,
                'status' => 'Appointment scheduled successfully.',
            ], 201);
        }

        return redirect()
            ->route('staff.clinic-schedule.index')
            ->with('status', 'Appointment scheduled successfully.');
    }

    public function update(StaffAppointmentRequest $request, Appointment $appointment): JsonResponse|RedirectResponse
    {
        $staff = $this->currentProgramStaff($request);

        if (! $staff) {
            return $this->appointmentError($request, 'Please login as Program Staff first.', 401);
        }

        if (! $this->staffMayManageAppointment($staff, $appointment)) {
            return $this->appointmentError($request, 'You cannot update this appointment.', 403);
        }

        $validated = $request->validated();
        $mother = Mother::findOrFail($validated['mother_id']);

        if (! $this->staffMayScheduleMother($staff, $mother)) {
            return $this->appointmentError($request, 'You can only schedule assigned mothers.', 403);
        }

        $conversation = $this->conversationForAppointment($mother, $staff, $validated['conversation_id'] ?? null);

        if (! $conversation) {
            return $this->appointmentError($request, 'The selected conversation is invalid.', 403);
        }

        if ($this->hasAppointmentConflict(
            (int) $mother->id,
            (int) $staff->id,
            $validated['appointment_date'],
            $validated['start_time'],
            $validated['end_time'],
            (int) $appointment->id,
        )) {
            return $this->appointmentError($request, 'This schedule conflicts with another appointment.', 422);
        }

        $oldStatus = $appointment->status;
        $status = $validated['status'] ?? $appointment->status;

        $appointment->fill([
            'mother_id' => $mother->id,
            'staff_id' => $staff->id,
            'conversation_id' => $conversation->id,
            'appointment_type' => $validated['appointment_type'],
            'meeting_type' => $validated['meeting_type'],
            'appointment_date' => $validated['appointment_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'location' => $validated['location'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => $status,
            'confirmed_at' => $status === Appointment::STATUS_CONFIRMED
                ? ($appointment->confirmed_at ?? now())
                : ($status === Appointment::STATUS_PENDING ? null : $appointment->confirmed_at),
            'cancelled_at' => null,
            'completed_at' => null,
        ])->save();

        $notificationType = $oldStatus === Appointment::STATUS_RESCHEDULE_REQUESTED && $status === Appointment::STATUS_CONFIRMED
            ? 'reschedule_approved'
            : 'appointment_updated';
        $notificationTitle = $notificationType === 'reschedule_approved'
            ? 'Reschedule approved'
            : 'Clinic appointment updated';

        $this->notifyAppointment(
            $appointment,
            Message::ROLE_MOTHER,
            (int) $mother->id,
            $notificationType,
            $notificationTitle,
            $this->appointmentSummary($appointment).' was updated by '.$staff->full_name.'.'
        );

        if ($request->expectsJson()) {
            return response()->json([
                'appointment' => $this->appointmentPayload($appointment->refresh()),
                'status' => 'Appointment updated successfully.',
            ]);
        }

        return redirect()
            ->route('staff.clinic-schedule.index')
            ->with('status', 'Appointment updated successfully.');
    }

    public function cancel(Request $request, Appointment $appointment): JsonResponse|RedirectResponse
    {
        $staff = $this->currentProgramStaff($request);

        if (! $staff || ! $this->staffMayManageAppointment($staff, $appointment)) {
            return $this->appointmentError($request, 'You cannot cancel this appointment.', 403);
        }

        $appointment->forceFill([
            'status' => Appointment::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ])->save();

        $this->notifyAppointment(
            $appointment,
            Message::ROLE_MOTHER,
            (int) $appointment->mother_id,
            'appointment_cancelled',
            'Clinic appointment cancelled',
            $this->appointmentSummary($appointment).' was cancelled.'
        );

        return $this->appointmentActionResponse($request, $appointment, 'Appointment cancelled.');
    }

    public function complete(Request $request, Appointment $appointment): JsonResponse|RedirectResponse
    {
        $staff = $this->currentProgramStaff($request);

        if (! $staff || ! $this->staffMayManageAppointment($staff, $appointment)) {
            return $this->appointmentError($request, 'You cannot complete this appointment.', 403);
        }

        $appointment->forceFill([
            'status' => Appointment::STATUS_COMPLETED,
            'completed_at' => now(),
        ])->save();

        $this->notifyAppointment(
            $appointment,
            Message::ROLE_MOTHER,
            (int) $appointment->mother_id,
            'appointment_completed',
            'Clinic appointment completed',
            $this->appointmentSummary($appointment).' was marked completed.'
        );

        return $this->appointmentActionResponse($request, $appointment, 'Appointment marked as completed.');
    }

    public function confirm(Request $request, Appointment $appointment): RedirectResponse
    {
        $mother = $this->currentMother($request);

        if (! $mother || ! $this->motherMayManageAppointment($mother, $appointment)) {
            abort(403);
        }

        $appointment->forceFill([
            'status' => Appointment::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ])->save();

        $this->notifyAppointment(
            $appointment,
            Message::ROLE_PROGRAM_STAFF,
            (int) $appointment->staff_id,
            'appointment_confirmed',
            'Appointment confirmed',
            $mother->full_name.' confirmed '.$this->appointmentSummary($appointment).'.'
        );

        return redirect()
            ->route('mother.clinic-schedule.index')
            ->with('status', 'Appointment confirmed.');
    }

    public function decline(Request $request, Appointment $appointment): RedirectResponse
    {
        $mother = $this->currentMother($request);

        if (! $mother || ! $this->motherMayManageAppointment($mother, $appointment)) {
            abort(403);
        }

        $validated = $request->validate([
            'decline_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $appointment->forceFill([
            'status' => Appointment::STATUS_DECLINED,
            'decline_reason' => $validated['decline_reason'] ?? null,
        ])->save();

        $this->notifyAppointment(
            $appointment,
            Message::ROLE_PROGRAM_STAFF,
            (int) $appointment->staff_id,
            'appointment_declined',
            'Appointment declined',
            $mother->full_name.' declined '.$this->appointmentSummary($appointment).'.'
        );

        return redirect()
            ->route('mother.clinic-schedule.index')
            ->with('status', 'Appointment declined.');
    }

    public function reschedule(Request $request, Appointment $appointment): RedirectResponse
    {
        $mother = $this->currentMother($request);

        if (! $mother || ! $this->motherMayManageAppointment($mother, $appointment)) {
            abort(403);
        }

        $validated = $request->validate([
            'preferred_date' => ['required', 'date', 'after_or_equal:today'],
            'preferred_start_time' => ['required', 'date_format:H:i'],
            'reschedule_reason' => ['required', 'string', 'max:1000'],
        ], [
            'preferred_date.after_or_equal' => 'Do not request a date in the past.',
        ]);

        $appointment->forceFill([
            'status' => Appointment::STATUS_RESCHEDULE_REQUESTED,
            'preferred_date' => $validated['preferred_date'],
            'preferred_start_time' => $validated['preferred_start_time'],
            'reschedule_reason' => $validated['reschedule_reason'],
        ])->save();

        $this->notifyAppointment(
            $appointment,
            Message::ROLE_PROGRAM_STAFF,
            (int) $appointment->staff_id,
            'reschedule_requested',
            'Reschedule requested',
            $mother->full_name.' requested a new time for '.$this->appointmentSummary($appointment).'.'
        );

        return redirect()
            ->route('mother.clinic-schedule.index')
            ->with('status', 'Reschedule request sent.');
    }

    private function isUpcomingAppointment(Appointment $appointment, Carbon $today): bool
    {
        return $appointment->appointment_date->greaterThanOrEqualTo($today)
            && ! in_array($appointment->status, Appointment::terminalStatuses(), true);
    }

    private function staffMayScheduleMother(ProgramStaff $staff, Mother $mother): bool
    {
        return StaffMotherCasefile::where('staff_id', $staff->id)
            ->where('mother_id', $mother->id)
            ->exists();
    }

    private function staffMayManageAppointment(ProgramStaff $staff, Appointment $appointment): bool
    {
        return (int) $appointment->staff_id === (int) $staff->id
            && StaffMotherCasefile::where('staff_id', $staff->id)
                ->where('mother_id', $appointment->mother_id)
                ->exists();
    }

    private function motherMayManageAppointment(Mother $mother, Appointment $appointment): bool
    {
        return (int) $appointment->mother_id === (int) $mother->id;
    }

    private function conversationForAppointment(Mother $mother, ProgramStaff $staff, mixed $conversationId = null): ?Conversation
    {
        if ($conversationId) {
            $conversation = Conversation::find($conversationId);

            if (! $conversation
                || (int) $conversation->mother_id !== (int) $mother->id
                || (int) $conversation->program_staff_id !== (int) $staff->id) {
                return null;
            }

            return $conversation;
        }

        return $this->ensureConversationForPair($mother, $staff);
    }

    private function hasAppointmentConflict(
        int $motherId,
        int $staffId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $ignoreAppointmentId = null,
    ): bool {
        return Appointment::query()
            ->whereDate('appointment_date', $date)
            ->whereIn('status', Appointment::activeStatuses())
            ->when($ignoreAppointmentId, fn ($query) => $query->whereKeyNot($ignoreAppointmentId))
            ->where(function ($query) use ($motherId, $staffId) {
                $query->where('mother_id', $motherId)
                    ->orWhere('staff_id', $staffId);
            })
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();
    }

    private function appointmentError(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return back()
            ->withErrors(['appointment' => $message])
            ->withInput();
    }

    private function appointmentActionResponse(Request $request, Appointment $appointment, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'appointment' => $this->appointmentPayload($appointment->refresh()),
                'status' => $message,
            ]);
        }

        return redirect()
            ->route('staff.clinic-schedule.index')
            ->with('status', $message);
    }

    private function notifyAppointment(
        Appointment $appointment,
        string $recipientRole,
        int $recipientId,
        string $type,
        string $title,
        string $body,
    ): void {
        AppNotification::updateOrCreate([
            'recipient_id' => $recipientId,
            'recipient_role' => $recipientRole,
            'appointment_id' => $appointment->id,
            'type' => $type,
        ], [
            'title' => $title,
            'body' => $body,
            'read_at' => null,
            'data' => [
                'appointment_id' => $appointment->id,
                'appointment_date' => $appointment->appointment_date?->toDateString(),
                'start_time' => $this->formatTime($appointment->start_time),
                'url' => $recipientRole === Message::ROLE_MOTHER
                    ? route('mother.clinic-schedule.index')
                    : route('staff.clinic-schedule.index'),
            ],
        ]);
    }

    private function createScheduleSystemMessage(Appointment $appointment, Request $request): ?array
    {
        $appointment->loadMissing(['conversation.mother', 'conversation.programStaff']);
        $conversation = $appointment->conversation;

        if (! $conversation) {
            return null;
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $appointment->staff_id,
            'sender_role' => Message::ROLE_PROGRAM_STAFF,
            'receiver_id' => $appointment->mother_id,
            'receiver_role' => Message::ROLE_MOTHER,
            'message_type' => Message::TYPE_SYSTEM,
            'message' => $appointment->typeLabel().' scheduled for '
                .$appointment->appointment_date->format('F j, Y')
                .' at '.$this->formatTime($appointment->start_time).'.',
            'is_read' => false,
        ]);

        $conversation->forceFill([
            'last_message_id' => $message->id,
            'last_message_at' => $message->created_at,
        ])->save();

        return $this->messagePayload($message, $request);
    }

    private function appointmentPayload(Appointment $appointment): array
    {
        $appointment->loadMissing(['mother', 'staff', 'conversation']);

        return [
            'id' => $appointment->id,
            'mother_id' => $appointment->mother_id,
            'mother_name' => $appointment->mother?->full_name,
            'staff_id' => $appointment->staff_id,
            'staff_name' => $appointment->staff?->full_name,
            'conversation_id' => $appointment->conversation_id,
            'appointment_type' => $appointment->appointment_type,
            'appointment_type_label' => $appointment->typeLabel(),
            'meeting_type' => $appointment->meeting_type,
            'meeting_type_label' => $appointment->meetingLabel(),
            'appointment_date' => $appointment->appointment_date?->toDateString(),
            'date_label' => $appointment->appointment_date?->format('M j, Y'),
            'start_time' => substr((string) $appointment->start_time, 0, 5),
            'end_time' => substr((string) $appointment->end_time, 0, 5),
            'time_label' => $this->formatTime($appointment->start_time).' - '.$this->formatTime($appointment->end_time),
            'location' => $appointment->location,
            'notes' => $appointment->notes,
            'status' => $appointment->status,
            'status_label' => $appointment->statusLabel(),
            'preferred_date' => $appointment->preferred_date?->toDateString(),
            'preferred_start_time' => $appointment->preferred_start_time ? substr((string) $appointment->preferred_start_time, 0, 5) : null,
            'reschedule_reason' => $appointment->reschedule_reason,
        ];
    }

    private function appointmentSummary(Appointment $appointment): string
    {
        return $appointment->typeLabel().' on '
            .$appointment->appointment_date->format('M j, Y')
            .' at '.$this->formatTime($appointment->start_time);
    }

    private function formatTime(mixed $time): string
    {
        if (! $time) {
            return '';
        }

        return Carbon::parse((string) $time)->format('g:i A');
    }
}

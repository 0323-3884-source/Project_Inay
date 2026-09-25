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
use App\Models\StaffAvailability;
use App\Models\StaffAvailabilityBlock;
use App\Models\StaffMotherCasefile;
use App\Support\AppointmentCareTeam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    use AuthorizesConsultations;

    private const SAN_PABLO_BARANGAYS = [
        'Bagong Bayan II-A',
        'Bagong Pook VI-C',
        'Barangay I-A',
        'Barangay I-B',
        'Barangay II-A',
        'Barangay II-B',
        'Barangay II-C',
        'Barangay II-D',
        'Barangay II-E',
        'Barangay II-F',
        'Barangay III-A',
        'Barangay III-B',
        'Barangay III-C',
        'Barangay III-D',
        'Barangay III-E',
        'Barangay III-F',
        'Barangay IV-A',
        'Barangay IV-B',
        'Barangay IV-C',
        'Barangay V-A',
        'Barangay V-B',
        'Barangay V-C',
        'Barangay V-D',
        'Barangay VI-A',
        'Barangay VI-B',
        'Barangay VI-D',
        'Barangay VI-E',
        'Barangay VII-A',
        'Barangay VII-B',
        'Barangay VII-C',
        'Barangay VII-D',
        'Barangay VII-E',
        'Bautista',
        'Concepcion',
        'Del Remedio',
        'Dolores',
        'San Antonio 1',
        'San Antonio 2',
        'San Bartolome',
        'San Buenaventura',
        'San Crispin',
        'San Cristobal',
        'San Diego',
        'San Francisco',
        'San Gabriel',
        'San Gregorio',
        'San Ignacio',
        'San Isidro',
        'San Joaquin',
        'San Jose',
        'San Juan',
        'San Lorenzo',
        'San Lucas 1',
        'San Lucas 2',
        'San Marcos',
        'San Mateo',
        'San Miguel',
        'San Nicolas',
        'San Pedro',
        'San Rafael',
        'San Roque',
        'San Vicente',
        'Santa Ana',
        'Santa Catalina',
        'Santa Cruz',
        'Santa Felomina',
        'Santa Isabel',
        'Santa Maria Magdalena',
        'Santa Veronica',
        'Santiago I',
        'Santiago II',
        'Santisimo Rosario',
        'Santo Angel',
        'Santo Cristo',
        'Santo Niño',
        'Soledad',
        'Atisan',
        'Santa Elena',
        'Santa Maria',
        'Santa Monica',
    ];

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
        $staffAvailabilities = StaffAvailability::where('staff_id', $staff->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
        $availabilityBlocks = StaffAvailabilityBlock::where('staff_id', $staff->id)
            ->whereDate('blocked_date', '>=', today())
            ->orderBy('blocked_date')
            ->get();

        return view('modules.staff-clinic-schedule', [
            'staff' => $staff,
            'midwifeOptions' => app(AppointmentCareTeam::class)->options(),
            'assignedMothers' => $assignedMothers,
            'staffAvailabilities' => $staffAvailabilities,
            'availabilityBlocks' => $availabilityBlocks,
            'dayLabels' => StaffAvailability::dayLabels(),
            'barangays' => self::SAN_PABLO_BARANGAYS,
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

        $search = trim((string) $request->query('q', ''));
        $selectedBarangay = trim((string) $request->query('barangay', ''));
        $selectedCategory = (string) $request->query('category', 'all');
        $categories = $this->motherAppointmentCategories();
        $selectedCategory = array_key_exists($selectedCategory, $categories) ? $selectedCategory : 'all';
        $selectedBarangay = in_array($selectedBarangay, self::SAN_PABLO_BARANGAYS, true) ? $selectedBarangay : '';
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
        $activeAppointment = $appointments
            ->first(fn (Appointment $appointment): bool => $this->isMotherBookingBlockingAppointment($appointment, $today));
        $latestAppointmentsByStaff = $appointments
            ->groupBy('staff_id')
            ->map(fn (Collection $staffAppointments): ?Appointment => $staffAppointments
                ->sortByDesc(fn (Appointment $appointment): int => (int) $appointment->id)
                ->first());
        $staffQuery = ProgramStaff::query()
            ->where('approval_status', 'approved')
            ->where(function ($query): void {
                $query->where('accepting_appointments', true)
                    ->orWhereNull('accepting_appointments');
            })
            ->with(['availabilities' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('day_of_week')
                ->orderBy('start_time')])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($search !== '') {
            $tokens = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($tokens as $token) {
                $like = '%'.$token.'%';

                $staffQuery->where(function ($query) use ($like): void {
                    $query->where('first_name', 'like', $like)
                        ->orWhere('middle_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('role', 'like', $like)
                        ->orWhere('position', 'like', $like)
                        ->orWhere('assigned_barangay', 'like', $like)
                        ->orWhere('assigned_facility', 'like', $like);
                });
            }
        }

        if ($selectedBarangay !== '') {
            $staffQuery->where(function ($query) use ($selectedBarangay): void {
                $query->where('assigned_barangay', $selectedBarangay)
                    ->orWhere('assigned_barangay', 'like', '%'.$selectedBarangay.'%')
                    ->orWhere('assigned_facility', 'like', '%'.$selectedBarangay.'%');
            });
        }

        $doctorCards = $staffQuery
            ->get()
            ->map(fn (ProgramStaff $staff): array => $this->doctorCardPayload(
                $staff,
                $latestAppointmentsByStaff->get($staff->id),
                $activeAppointment
            ))
            ->filter(fn (array $doctor): bool => $selectedCategory === 'all' || $doctor['category_key'] === $selectedCategory)
            ->values();

        return view('modules.mother-clinic-schedule', [
            'mother' => $mother,
            'appointments' => $appointments,
            'upcomingAppointments' => $upcomingAppointments,
            'historyAppointments' => $historyAppointments,
            'nextAppointment' => $nextAppointment,
            'activeAppointment' => $activeAppointment,
            'activeAppointmentNotice' => $activeAppointment ? $this->motherActiveAppointmentPayload($activeAppointment) : null,
            'appointmentTypeLabels' => Appointment::appointmentTypeLabels(),
            'meetingTypeLabels' => Appointment::meetingTypeLabels(),
            'statusLabels' => Appointment::statusLabels(),
            'barangays' => self::SAN_PABLO_BARANGAYS,
            'doctorCards' => $doctorCards,
            'appointmentCategories' => $categories,
            'search' => $search,
            'selectedCategory' => $selectedCategory,
            'selectedBarangay' => $selectedBarangay,
        ]);
    }

    public function bookingCalendar(Request $request): JsonResponse
    {
        $mother = $this->currentMother($request);
        abort_unless($mother, 403);
        $data = $request->validate([
            'staff_id' => ['required', 'integer', 'exists:program_staff,id'],
            'month' => ['required', 'date_format:Y-m'],
        ]);
        $month = Carbon::createFromFormat('!Y-m', $data['month']);
        if ($month->lt(today()->startOfMonth()) || $month->gt(today()->addMonthsNoOverflow(12)->startOfMonth())) {
            throw ValidationException::withMessages(['month' => 'Choose a month within the next year.']);
        }
        $staff = ProgramStaff::with(['availabilities' => fn ($q) => $q->where('is_active', true), 'availabilityBlocks'])
            ->where('approval_status', 'approved')->findOrFail($data['staff_id']);
        $start = $month->copy()->startOfWeek(Carbon::SUNDAY);
        $end = $start->copy()->addDays(41);
        $appointments = Appointment::whereIn('status', Appointment::activeStatuses())
            ->whereBetween('appointment_date', [$start->toDateString(), $end->toDateString()])
            ->where(fn ($q) => $q->where('staff_id', $staff->id)->orWhere('mother_id', $mother->id))
            ->get(['staff_id', 'mother_id', 'appointment_date', 'start_time', 'end_time'])
            ->groupBy(fn (Appointment $appointment) => $appointment->appointment_date->toDateString());
        $days = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $key = $date->toDateString();
            $dayAppointments = $appointments->get($key, collect());
            $full = $dayAppointments->where('staff_id', $staff->id)->count() >= (int) ($staff->max_appointments_per_day ?? 8);
            $blocked = $this->staffIsBlocked($staff, $date) || $staff->accepting_appointments === false;
            $slots = $staff->availabilities->where('day_of_week', $date->isoWeekday())->sortBy('start_time')
                ->map(function (StaffAvailability $slot) use ($date, $dayAppointments, $full, $blocked): array {
                    $past = Carbon::parse($date->toDateString().' '.$slot->start_time)->lte(now());
                    $overlap = $dayAppointments->contains(fn (Appointment $a) =>
                        substr($a->start_time, 0, 5) < substr($slot->end_time, 0, 5)
                        && substr($a->end_time, 0, 5) > substr($slot->start_time, 0, 5));
                    $status = $past ? 'past' : ($overlap ? 'booked' : ($blocked ? 'unavailable' : ($full ? 'full' : 'available')));
                    return [
                        'id' => $slot->id, 'start_time' => substr($slot->start_time, 0, 5),
                        'end_time' => substr($slot->end_time, 0, 5), 'time_label' => $slot->timeLabel(),
                        'appointment_type' => $slot->appointment_type, 'appointment_type_label' => $slot->typeLabel(),
                        'meeting_type' => $slot->meeting_type, 'meeting_type_label' => $slot->meetingLabel(),
                        'status' => $status,
                    ];
                })->values();
            $status = $date->lt(today()) ? 'past' : ($slots->contains('status', 'available') ? 'available'
                : ($slots->contains(fn ($s) => in_array($s['status'], ['booked', 'full'], true)) ? 'booked' : 'unavailable'));
            $days[$key] = ['status' => $status, 'slots' => $slots];
        }
        return response()->json(['days' => $days, 'today' => today()->toDateString()])->header('Cache-Control', 'no-store');
    }

    private function motherAppointmentCategories(): array
    {
        return [
            'all' => 'All',
            'ob_gyn' => 'OB-GYN',
            'pediatrician' => 'Pediatrician',
            'midwife' => 'Midwife',
            'nurse' => 'Nurse',
            'general_doctor' => 'General Doctor',
            'program_staff' => 'DSWD/Program Staff',
            'barangay_health_worker' => 'Barangay Health Worker',
        ];
    }

    private function doctorCardPayload(
        ProgramStaff $staff,
        ?Appointment $latestAppointment = null,
        ?Appointment $activeAppointment = null,
    ): array
    {
        $availabilities = $staff->availabilities
            ->where('is_active', true)
            ->sortBy(fn (StaffAvailability $availability): string => $availability->day_of_week.'-'.$availability->start_time)
            ->values();
        [$categoryKey, $categoryLabel] = $this->doctorCategory($staff);
        $location = $staff->assigned_facility
            ?: ($staff->assigned_barangay ? $staff->assigned_barangay.' Health Service Area' : 'Community health service');

        return [
            'id' => $staff->id,
            'name' => $staff->full_name,
            'initials' => $this->staffInitials($staff),
            'role' => $staff->role_label,
            'category_key' => $categoryKey,
            'category_label' => $categoryLabel,
            'photo_url' => $staff->healthcare_worker_id_photo_url,
            'location' => $location,
            'consultation_type' => $this->doctorConsultationSummary($availabilities),
            'schedule' => $this->doctorScheduleSummary($availabilities, $location),
            'available_days' => $availabilities->pluck('day_of_week')->unique()->values()->all(),
            'appointment_status' => $this->doctorAppointmentStatusPayload($latestAppointment),
            'is_active_appointment_doctor' => $activeAppointment
                ? (int) $activeAppointment->staff_id === (int) $staff->id
                : false,
            'message_url' => route('mother.consultation', ['staff' => $staff->id]),
            'availabilities' => $availabilities
                ->map(fn (StaffAvailability $availability): array => [
                    'id' => $availability->id,
                    'day_of_week' => $availability->day_of_week,
                    'day_label' => $availability->dayLabel(),
                    'appointment_type' => $availability->appointment_type,
                    'appointment_type_label' => $availability->typeLabel(),
                    'meeting_type' => $availability->meeting_type,
                    'meeting_type_label' => $availability->meetingLabel(),
                    'start_time' => substr((string) $availability->start_time, 0, 5),
                    'end_time' => substr((string) $availability->end_time, 0, 5),
                    'time_label' => $availability->timeLabel(),
                    'location' => $availability->location ?: $location,
                ])
                ->values()
                ->all(),
        ];
    }

    private function doctorCategory(ProgramStaff $staff): array
    {
        $haystack = strtolower(trim(($staff->role ?? '').' '.($staff->position ?? '')));
        $categories = $this->motherAppointmentCategories();

        if (str_contains($haystack, 'ob') || str_contains($haystack, 'gyne') || str_contains($haystack, 'obstetric')) {
            return ['ob_gyn', $categories['ob_gyn']];
        }

        if (str_contains($haystack, 'pediatric') || str_contains($haystack, 'pedia')) {
            return ['pediatrician', $categories['pediatrician']];
        }

        if (str_contains($haystack, 'midwife')) {
            return ['midwife', $categories['midwife']];
        }

        if (str_contains($haystack, 'nurse')) {
            return ['nurse', $categories['nurse']];
        }

        if (str_contains($haystack, 'bhw') || str_contains($haystack, 'barangay health')) {
            return ['barangay_health_worker', $categories['barangay_health_worker']];
        }

        if (str_contains($haystack, 'dswd') || str_contains($haystack, 'program') || str_contains($haystack, 'social')) {
            return ['program_staff', $categories['program_staff']];
        }

        return ['general_doctor', $categories['general_doctor']];
    }

    private function doctorConsultationSummary(Collection $availabilities): string
    {
        $labels = $availabilities
            ->map(fn (StaffAvailability $availability): string => $availability->meetingLabel())
            ->unique()
            ->take(2)
            ->values();

        return $labels->isNotEmpty() ? $labels->implode(' / ') : 'Consultation schedule pending';
    }

    private function doctorScheduleSummary(Collection $availabilities, string $fallbackLocation): string
    {
        if ($availabilities->isEmpty()) {
            return $fallbackLocation;
        }

        $days = $availabilities
            ->pluck('day_of_week')
            ->unique()
            ->take(3)
            ->map(fn (int $day): string => substr(StaffAvailability::dayLabels()[$day] ?? 'Day', 0, 3))
            ->implode(', ');
        $firstSlot = $availabilities->first();
        $extraDays = $availabilities->pluck('day_of_week')->unique()->count() > 3 ? ' +' : '';

        return trim($days.$extraDays.' - '.$this->formatTime($firstSlot->start_time).' to '.$this->formatTime($firstSlot->end_time));
    }

    private function staffInitials(ProgramStaff $staff): string
    {
        $first = (string) ($staff->first_name ?? '');
        $last = (string) ($staff->last_name ?? '');

        return strtoupper(substr($first, 0, 1).substr($last, 0, 1)) ?: 'IN';
    }

    public function availableSlots(Request $request): JsonResponse
    {
        $mother = $this->currentMother($request);

        if (! $mother) {
            return response()->json(['message' => 'Please login as Mother first.'], 401);
        }

        $validated = $request->validate([
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_type' => ['nullable', Rule::in(array_keys(Appointment::appointmentTypeLabels()))],
            'meeting_type' => ['nullable', Rule::in(array_keys(Appointment::meetingTypeLabels()))],
            'preferred_start_time' => ['nullable', 'date_format:H:i'],
            'staff_id' => ['nullable', 'integer', 'exists:program_staff,id'],
            'q' => ['nullable', 'string', 'max:120'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:120'],
            'accepting_only' => ['nullable', 'boolean'],
            'calendar_month' => ['nullable', 'date_format:Y-m'],
        ], [
            'appointment_date.after_or_equal' => 'Choose today or a future date.',
        ]);

        $date = Carbon::parse($validated['appointment_date'])->startOfDay();
        $appointmentType = $validated['appointment_type'] ?? $this->defaultAppointmentType();
        $preferredStartTime = $validated['preferred_start_time'] ?? null;
        $filters = $this->availabilityFilters($validated);
        $slots = $this->openAvailabilitySlots($date, $appointmentType, $preferredStartTime, $mother, null, $filters);
        $nearestDates = $slots->isEmpty()
            ? $this->nearestAvailableDates($date, $appointmentType, $mother, $preferredStartTime, $filters)
            : collect();
        $calendarMonth = isset($validated['calendar_month'])
            ? Carbon::createFromFormat('Y-m', $validated['calendar_month'])->startOfMonth()
            : $date->copy()->startOfMonth();

        return response()->json([
            'date' => $date->toDateString(),
            'date_label' => $date->format('l, F j, Y'),
            'slots' => $slots->map(fn (StaffAvailability $availability): array => $this->availabilityPayload($availability, $date))->values(),
            'nearest_dates' => $nearestDates->values(),
            'available_dates' => $this->availableDatesForMonth($calendarMonth, $appointmentType, $mother, $filters)->values(),
            'other_workers' => $slots->isEmpty()
                ? $this->otherAvailableWorkers($date, $appointmentType, $mother, $filters)->values()
                : [],
            'nearby_barangays' => $slots->isEmpty()
                ? $this->nearbyAvailableBarangays($date, $appointmentType, $mother, $filters)->values()
                : [],
            'message' => $slots->isEmpty()
                ? 'No healthcare worker is available on this date or in the selected barangay. Please choose another date or nearby barangay.'
                : 'Available consultation slots found.',
        ]);
    }

    public function searchWorkers(Request $request): JsonResponse
    {
        $mother = $this->currentMother($request);

        if (! $mother) {
            return response()->json(['message' => 'Please login as Mother first.'], 401);
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:120'],
            'meeting_type' => ['nullable', Rule::in(array_keys(Appointment::meetingTypeLabels()))],
            'appointment_type' => ['nullable', Rule::in(array_keys(Appointment::appointmentTypeLabels()))],
            'appointment_date' => ['nullable', 'date', 'after_or_equal:today'],
            'accepting_only' => ['nullable', 'boolean'],
        ], [
            'appointment_date.after_or_equal' => 'Choose today or a future date.',
        ]);

        $appointmentType = $validated['appointment_type'] ?? $this->defaultAppointmentType();
        $filters = $this->availabilityFilters($validated);
        $fromDate = isset($validated['appointment_date'])
            ? Carbon::parse($validated['appointment_date'])->startOfDay()
            : today();

        $workers = $this->matchingWorkerQuery($filters)
            ->with(['availabilities' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('day_of_week')
                ->orderBy('start_time')])
            ->limit(24)
            ->get()
            ->map(fn (ProgramStaff $staff): array => $this->workerPayload($staff, $mother, $fromDate, $appointmentType, $filters))
            ->filter(function (array $worker) use ($validated): bool {
                return empty($validated['appointment_date']) || $worker['has_selected_date_slots'];
            })
            ->values();

        return response()->json([
            'workers' => $workers,
            'message' => $workers->isEmpty()
                ? 'No healthcare workers matched your filters yet.'
                : 'Healthcare workers found.',
        ]);
    }

    public function bookFromMother(Request $request): JsonResponse|RedirectResponse
    {
        $mother = $this->currentMother($request);

        if (! $mother) {
            return $this->appointmentError($request, 'Please login as Mother first.', 401);
        }

        $validated = $request->validate([
            'staff_availability_id' => ['required', 'integer', 'exists:staff_availabilities,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_type' => ['required', Rule::in(array_keys(Appointment::appointmentTypeLabels()))],
            'meeting_type' => ['nullable', Rule::in(array_keys(Appointment::meetingTypeLabels()))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'appointment_date.after_or_equal' => 'Choose today or a future date.',
        ]);

        $result = DB::transaction(function () use ($mother, $validated): array {
            // Serialize bookings for a mother and worker, including initially empty schedules.
            Mother::whereKey($mother->id)->lockForUpdate()->firstOrFail();
            $slotStaffId = StaffAvailability::whereKey($validated['staff_availability_id'])->value('staff_id');
            ProgramStaff::whereKey($slotStaffId)->lockForUpdate()->firstOrFail();
            $activeAppointment = $this->activeMotherAppointmentQuery((int) $mother->id)
                ->lockForUpdate()
                ->first();

            if ($activeAppointment) {
                return ['active_appointment' => $activeAppointment];
            }

            /** @var StaffAvailability|null $availability */
            $availability = StaffAvailability::with('staff')
                ->whereKey($validated['staff_availability_id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $availability || ! $availability->staff) {
                throw ValidationException::withMessages([
                    'staff_availability_id' => 'This time slot is no longer available.',
                ]);
            }

            $this->ensureStaffIsApproved($availability->staff);
            $this->ensureStaffIsAcceptingAppointments($availability->staff);
            $this->ensureAvailabilityMatchesDateAndType(
                $availability,
                $validated['appointment_date'],
                $validated['appointment_type'],
                $validated['meeting_type'] ?? null
            );
            $this->ensureFutureAppointmentStart($validated['appointment_date'], $availability->start_time);
            $this->ensureStaffCanBookDate($availability->staff, $validated['appointment_date']);

            if ($this->hasAppointmentConflict(
                (int) $mother->id,
                (int) $availability->staff_id,
                $validated['appointment_date'],
                substr((string) $availability->start_time, 0, 5),
                substr((string) $availability->end_time, 0, 5),
            )) {
                throw ValidationException::withMessages([
                    'staff_availability_id' => 'This time slot was just booked. Please choose another open time.',
                ]);
            }

            StaffMotherCasefile::firstOrCreate([
                'staff_id' => $availability->staff_id,
                'mother_id' => $mother->id,
            ]);

            $conversation = $this->ensureConversationForPair($mother, $availability->staff);

            return ['appointment' => Appointment::create([
                ...app(AppointmentCareTeam::class)->forBooking($availability->staff),
                'mother_id' => $mother->id,
                'staff_id' => $availability->staff_id,
                'staff_availability_id' => $availability->id,
                'conversation_id' => $conversation->id,
                'appointment_type' => $availability->appointment_type,
                'meeting_type' => $availability->meeting_type,
                'appointment_date' => $validated['appointment_date'],
                'start_time' => substr((string) $availability->start_time, 0, 5),
                'end_time' => substr((string) $availability->end_time, 0, 5),
                'location' => $availability->location,
                'notes' => $validated['notes'] ?? null,
                'status' => Appointment::STATUS_PENDING,
                'created_by_id' => $mother->id,
                'created_by_role' => Message::ROLE_MOTHER,
            ])];
        });

        if (isset($result['active_appointment'])) {
            return $this->activeAppointmentConflictResponse($request, $result['active_appointment']);
        }

        /** @var Appointment $appointment */
        $appointment = $result['appointment'];
        $appointment->loadMissing(['mother', 'staff']);

        $this->notifyAppointment(
            $appointment,
            Message::ROLE_PROGRAM_STAFF,
            (int) $appointment->staff_id,
            'appointment_requested',
            'New appointment request',
            $mother->full_name.' requested '.$this->appointmentSummary($appointment).'.'
        );

        $this->notifyAppointment(
            $appointment,
            Message::ROLE_MOTHER,
            (int) $appointment->mother_id,
            'appointment_booked',
            'Appointment request sent',
            'Your appointment request '.$this->appointmentReference($appointment).' is waiting for confirmation.'
        );

        if ($request->expectsJson()) {
            $appointment = $appointment->refresh();

            return response()->json([
                'appointment' => $this->appointmentPayload($appointment),
                'active_appointment' => $this->motherActiveAppointmentPayload($appointment),
                'reference_number' => $this->appointmentReference($appointment),
                'redirect_url' => route('mother.clinic-schedule.index'),
                'status' => 'Appointment request sent. Please wait for the healthcare worker to confirm your appointment.',
            ], 201);
        }

        return redirect()
            ->route('mother.clinic-schedule.index')
            ->with('status', 'Appointment request sent. Please wait for the healthcare worker to confirm your appointment.');
    }

    public function storeAvailability(Request $request): RedirectResponse
    {
        $staff = $this->currentProgramStaff($request);

        if (! $staff) {
            return redirect()->route('login')->with('status', 'Please login as Program Staff first.');
        }

        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', Rule::in(array_keys(StaffAvailability::dayLabels()))],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'appointment_type' => ['required', Rule::in(array_keys(Appointment::appointmentTypeLabels()))],
            'meeting_type' => ['required', Rule::in(array_keys(Appointment::meetingTypeLabels()))],
            'location' => ['nullable', 'string', 'max:255'],
        ], [
            'end_time.after' => 'End time must be later than start time.',
        ]);

        if ($this->hasAvailabilityConflict(
            $staff,
            (int) $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time'],
        )) {
            return back()
                ->withErrors(['availability' => 'This availability overlaps another time slot.'])
                ->withInput();
        }

        StaffAvailability::create([
            'staff_id' => $staff->id,
            ...$validated,
            'is_active' => true,
        ]);

        return redirect()
            ->route('staff.clinic-schedule.index')
            ->with('status', 'Availability slot saved.');
    }

    public function updateSchedulingProfile(Request $request): RedirectResponse
    {
        $staff = $this->currentProgramStaff($request);

        if (! $staff) {
            return redirect()->route('login')->with('status', 'Please login as Program Staff first.');
        }

        $validated = $request->validate([
            'assigned_barangay' => ['nullable', 'string', 'max:255'],
            'assigned_facility' => ['nullable', 'string', 'max:255'],
            'accepting_appointments' => ['nullable', 'boolean'],
            'max_appointments_per_day' => ['required', 'integer', 'min:0', 'max:50'],
            'midwife_selection' => ['nullable', 'string', 'regex:/^(new|staff:[1-9][0-9]*|profile:[1-9][0-9]*)$/'],
            'midwife_full_name' => ['exclude_unless:midwife_selection,new', 'required', 'string', 'max:255'],
            'midwife_barangay' => ['exclude_unless:midwife_selection,new', 'required', Rule::in(self::SAN_PABLO_BARANGAYS)],
            'midwife_facility' => ['exclude_unless:midwife_selection,new', 'required', 'string', 'max:255'],
            'midwife_contact_number' => ['exclude_unless:midwife_selection,new', 'nullable', 'string', 'max:30'],
            'midwife_availability_status' => ['exclude_unless:midwife_selection,new', 'required', Rule::in(['available', 'unavailable'])],
            'existing_midwife_availability_status' => ['nullable', Rule::in(['available', 'unavailable'])],
        ]);

        DB::transaction(function () use ($staff, $validated, $request): void {
            $staff->forceFill([
                'assigned_barangay' => $validated['assigned_barangay'] ?: null,
                'assigned_facility' => $validated['assigned_facility'] ?: null,
                'accepting_appointments' => $request->boolean('accepting_appointments'),
                'max_appointments_per_day' => (int) $validated['max_appointments_per_day'],
            ])->save();
            app(AppointmentCareTeam::class)->assign($staff, $validated);
            $staff->save();
        });

        return redirect()
            ->route('staff.clinic-schedule.index')
            ->with('status', 'Scheduling profile updated.');
    }

    public function updateAvailability(Request $request, StaffAvailability $availability): RedirectResponse
    {
        $staff = $this->currentProgramStaff($request);

        if (! $staff || (int) $availability->staff_id !== (int) $staff->id) {
            abort(403);
        }

        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', Rule::in(array_keys(StaffAvailability::dayLabels()))],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'appointment_type' => ['required', Rule::in(array_keys(Appointment::appointmentTypeLabels()))],
            'meeting_type' => ['required', Rule::in(array_keys(Appointment::meetingTypeLabels()))],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'end_time.after' => 'End time must be later than start time.',
        ]);

        $willBeActive = $request->boolean('is_active', true);

        if ($willBeActive && $this->hasAvailabilityConflict(
            $staff,
            (int) $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time'],
            (int) $availability->id,
        )) {
            return back()
                ->withErrors(['availability' => 'This availability overlaps another time slot.'])
                ->withInput();
        }

        $availability->forceFill([
            ...$validated,
            'location' => $validated['location'] ?: null,
            'is_active' => $willBeActive,
        ])->save();

        return redirect()
            ->route('staff.clinic-schedule.index')
            ->with('status', 'Availability slot updated.');
    }

    public function toggleAvailability(Request $request, StaffAvailability $availability): RedirectResponse
    {
        $staff = $this->currentProgramStaff($request);

        if (! $staff || (int) $availability->staff_id !== (int) $staff->id) {
            abort(403);
        }

        $availability->forceFill([
            'is_active' => ! $availability->is_active,
        ])->save();

        return redirect()
            ->route('staff.clinic-schedule.index')
            ->with('status', $availability->is_active ? 'Availability slot enabled.' : 'Availability slot disabled.');
    }

    public function storeAvailabilityBlock(Request $request): RedirectResponse
    {
        $staff = $this->currentProgramStaff($request);

        if (! $staff) {
            return redirect()->route('login')->with('status', 'Please login as Program Staff first.');
        }

        $validated = $request->validate([
            'blocked_date' => ['required', 'date', 'after_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'blocked_date.after_or_equal' => 'Choose today or a future date to block.',
        ]);

        StaffAvailabilityBlock::updateOrCreate([
            'staff_id' => $staff->id,
            'blocked_date' => $validated['blocked_date'],
        ], [
            'reason' => $validated['reason'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ]);

        return redirect()
            ->route('staff.clinic-schedule.index')
            ->with('status', 'Blocked date saved.');
    }

    public function destroyAvailability(Request $request, StaffAvailability $availability): RedirectResponse
    {
        $staff = $this->currentProgramStaff($request);

        if (! $staff || (int) $availability->staff_id !== (int) $staff->id) {
            abort(403);
        }

        $availability->delete();

        return redirect()
            ->route('staff.clinic-schedule.index')
            ->with('status', 'Availability slot removed.');
    }

    public function destroyAvailabilityBlock(Request $request, StaffAvailabilityBlock $block): RedirectResponse
    {
        $staff = $this->currentProgramStaff($request);

        if (! $staff || (int) $block->staff_id !== (int) $staff->id) {
            abort(403);
        }

        $block->delete();

        return redirect()
            ->route('staff.clinic-schedule.index')
            ->with('status', 'Blocked date removed.');
    }

    public function store(StaffAppointmentRequest $request): JsonResponse|RedirectResponse
    {
        $staff = $this->currentProgramStaff($request);

        if (! $staff) {
            return $this->appointmentError($request, 'Please login as Program Staff first.', 401);
        }

        $validated = $request->validated();
        $availability = $this->availabilityForStaffAppointment($staff, $validated);
        $validated = $this->applyAvailabilityToAppointmentData($validated, $availability);
        $this->ensureFutureAppointmentStart($validated['appointment_date'], $validated['start_time']);
        $this->ensureStaffCanBookDate($staff, $validated['appointment_date']);
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
            ...app(AppointmentCareTeam::class)->forBooking($staff),
            'mother_id' => $mother->id,
            'staff_id' => $staff->id,
            'staff_availability_id' => $availability?->id,
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
        $availability = $this->availabilityForStaffAppointment($staff, $validated);
        $validated = $this->applyAvailabilityToAppointmentData($validated, $availability);
        $this->ensureFutureAppointmentStart($validated['appointment_date'], $validated['start_time']);
        $this->ensureStaffCanBookDate($staff, $validated['appointment_date'], (int) $appointment->id);
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
            'staff_availability_id' => $availability?->id,
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

    public function staffConfirm(Request $request, Appointment $appointment): JsonResponse|RedirectResponse
    {
        $staff = $this->currentProgramStaff($request);

        if (! $staff || ! $this->staffMayManageAppointment($staff, $appointment)) {
            return $this->appointmentError($request, 'You cannot confirm this appointment.', 403);
        }

        $this->ensureFutureAppointmentStart($appointment->appointment_date->toDateString(), $appointment->start_time);

        if ($this->hasAppointmentConflict(
            (int) $appointment->mother_id,
            (int) $appointment->staff_id,
            $appointment->appointment_date->toDateString(),
            substr((string) $appointment->start_time, 0, 5),
            substr((string) $appointment->end_time, 0, 5),
            (int) $appointment->id,
        )) {
            return $this->appointmentError($request, 'This schedule conflicts with another appointment.', 422);
        }

        $appointment->forceFill([
            'status' => Appointment::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'cancelled_at' => null,
        ])->save();

        $this->notifyAppointment(
            $appointment,
            Message::ROLE_MOTHER,
            (int) $appointment->mother_id,
            'appointment_confirmed_by_staff',
            'Appointment confirmed',
            $this->appointmentSummary($appointment).' was confirmed by '.$staff->full_name.'.'
        );

        return $this->appointmentActionResponse($request, $appointment, 'Appointment confirmed.');
    }

    public function reject(Request $request, Appointment $appointment): JsonResponse|RedirectResponse
    {
        $staff = $this->currentProgramStaff($request);

        if (! $staff || ! $this->staffMayManageAppointment($staff, $appointment)) {
            return $this->appointmentError($request, 'You cannot reject this appointment.', 403);
        }

        $validated = $request->validate([
            'decline_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $appointment->forceFill([
            'status' => Appointment::STATUS_REJECTED,
            'decline_reason' => $validated['decline_reason'] ?? null,
            'cancelled_at' => now(),
        ])->save();

        $this->notifyAppointment(
            $appointment,
            Message::ROLE_MOTHER,
            (int) $appointment->mother_id,
            'appointment_rejected',
            'Appointment request rejected',
            $this->appointmentSummary($appointment).' was rejected by '.$staff->full_name.'.'
        );

        return $this->appointmentActionResponse($request, $appointment, 'Appointment rejected.');
    }

    public function suggestSchedule(Request $request, Appointment $appointment): JsonResponse|RedirectResponse
    {
        $staff = $this->currentProgramStaff($request);

        if (! $staff || ! $this->staffMayManageAppointment($staff, $appointment)) {
            return $this->appointmentError($request, 'You cannot suggest a new schedule for this appointment.', 403);
        }

        $validated = $request->validate([
            'staff_availability_id' => ['required', 'integer', 'exists:staff_availabilities,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'reschedule_reason' => ['nullable', 'string', 'max:1000'],
        ], [
            'appointment_date.after_or_equal' => 'Choose today or a future date.',
        ]);

        DB::transaction(function () use ($appointment, $staff, $validated): void {
            /** @var StaffAvailability|null $availability */
            $availability = StaffAvailability::whereKey($validated['staff_availability_id'])
                ->where('staff_id', $staff->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $availability) {
                throw ValidationException::withMessages([
                    'staff_availability_id' => 'Choose one of your available time slots.',
                ]);
            }

            $this->ensureAvailabilityMatchesDateAndType($availability, $validated['appointment_date'], $appointment->appointment_type);
            $this->ensureFutureAppointmentStart($validated['appointment_date'], $availability->start_time);
            $this->ensureStaffCanBookDate($staff, $validated['appointment_date'], (int) $appointment->id);

            if ($this->hasAppointmentConflict(
                (int) $appointment->mother_id,
                (int) $staff->id,
                $validated['appointment_date'],
                substr((string) $availability->start_time, 0, 5),
                substr((string) $availability->end_time, 0, 5),
                (int) $appointment->id,
            )) {
                throw ValidationException::withMessages([
                    'staff_availability_id' => 'This suggested time conflicts with another appointment.',
                ]);
            }

            $appointment->forceFill([
                'staff_availability_id' => $availability->id,
                'appointment_date' => $validated['appointment_date'],
                'start_time' => substr((string) $availability->start_time, 0, 5),
                'end_time' => substr((string) $availability->end_time, 0, 5),
                'meeting_type' => $availability->meeting_type,
                'location' => $availability->location,
                'status' => Appointment::STATUS_RESCHEDULED,
                'reschedule_reason' => $validated['reschedule_reason'] ?? null,
                'confirmed_at' => null,
                'cancelled_at' => null,
            ])->save();
        });

        $this->notifyAppointment(
            $appointment,
            Message::ROLE_MOTHER,
            (int) $appointment->mother_id,
            'appointment_rescheduled',
            'New schedule suggested',
            $this->appointmentSummary($appointment->refresh()).' was suggested by '.$staff->full_name.'.'
        );

        return $this->appointmentActionResponse($request, $appointment, 'New schedule suggested.');
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

    public function motherCancel(Request $request, Appointment $appointment): JsonResponse|RedirectResponse
    {
        $mother = $this->currentMother($request);

        if (! $mother || ! $this->motherMayManageAppointment($mother, $appointment)) {
            return $this->appointmentError($request, 'You cannot cancel this appointment.', 403);
        }

        if (! in_array($appointment->status, [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED], true)) {
            return $this->appointmentError($request, 'Only pending or confirmed appointments can be cancelled.', 422);
        }

        $appointment->forceFill([
            'status' => Appointment::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ])->save();

        $this->notifyAppointment(
            $appointment,
            Message::ROLE_PROGRAM_STAFF,
            (int) $appointment->staff_id,
            'appointment_cancelled_by_mother',
            'Appointment cancelled',
            $mother->full_name.' cancelled '.$this->appointmentSummary($appointment).'.'
        );

        if ($request->expectsJson()) {
            return response()->json([
                'appointment' => $this->appointmentPayload($appointment->refresh()),
                'active_appointment' => null,
                'status' => 'Appointment request cancelled.',
            ]);
        }

        return redirect()
            ->route('mother.clinic-schedule.index')
            ->with('status', 'Appointment request cancelled.');
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

    private function motherBookingBlockingStatuses(): array
    {
        return [
            Appointment::STATUS_PENDING,
            Appointment::STATUS_CONFIRMED,
            Appointment::STATUS_RESCHEDULED,
            Appointment::STATUS_RESCHEDULE_REQUESTED,
        ];
    }

    private function isMotherBookingBlockingAppointment(Appointment $appointment, Carbon $today): bool
    {
        return $appointment->appointment_date->greaterThanOrEqualTo($today)
            && in_array($appointment->status, $this->motherBookingBlockingStatuses(), true);
    }

    private function activeMotherAppointmentQuery(int $motherId)
    {
        return Appointment::with(['staff', 'conversation'])
            ->where('mother_id', $motherId)
            ->whereIn('status', $this->motherBookingBlockingStatuses())
            ->whereDate('appointment_date', '>=', today()->toDateString())
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->orderBy('id');
    }

    private function activeAppointmentConflictResponse(Request $request, Appointment $activeAppointment): JsonResponse|RedirectResponse
    {
        $activeAppointment->loadMissing(['staff', 'conversation']);
        $message = $this->activeAppointmentMessage($activeAppointment);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'active_appointment' => $this->motherActiveAppointmentPayload($activeAppointment),
            ], 409);
        }

        return back()
            ->withErrors(['appointment' => $message])
            ->withInput();
    }

    private function doctorAppointmentStatusPayload(?Appointment $appointment): ?array
    {
        if (! $appointment) {
            return null;
        }

        $status = $this->appointmentStatusDisplay($appointment->status);

        return [
            'id' => $appointment->id,
            'status' => $appointment->status,
            'label' => $status['label'],
            'tone' => $status['tone'],
            'date_time_label' => $this->appointmentDateTimeLabel($appointment),
            'is_active' => in_array($appointment->status, $this->motherBookingBlockingStatuses(), true)
                && $appointment->appointment_date->greaterThanOrEqualTo(today()),
        ];
    }

    private function motherActiveAppointmentPayload(Appointment $appointment): array
    {
        $appointment->loadMissing(['staff', 'conversation']);
        $status = $this->appointmentStatusDisplay($appointment->status);

        return [
            ...$this->appointmentPayload($appointment),
            'doctor_name' => $appointment->staff?->full_name ?? 'your healthcare worker',
            'date_time_label' => $this->appointmentDateTimeLabel($appointment),
            'status_display' => $status['label'],
            'status_tone' => $status['tone'],
            'can_cancel' => in_array($appointment->status, [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED], true),
            'cancel_url' => route('mother.clinic-schedule.cancel', $appointment),
            'view_anchor' => 'appointment-'.$appointment->id,
            'message' => $this->activeAppointmentMessage($appointment),
        ];
    }

    private function appointmentStatusDisplay(string $status): array
    {
        return match ($status) {
            Appointment::STATUS_PENDING => ['label' => 'Waiting for Confirmation', 'tone' => 'waiting'],
            Appointment::STATUS_CONFIRMED, Appointment::STATUS_RESCHEDULED, Appointment::STATUS_RESCHEDULE_REQUESTED => ['label' => 'Confirmed', 'tone' => 'confirmed'],
            Appointment::STATUS_COMPLETED => ['label' => 'Completed', 'tone' => 'completed'],
            Appointment::STATUS_CANCELLED, Appointment::STATUS_REJECTED, Appointment::STATUS_DECLINED => ['label' => Appointment::statusLabels()[$status] ?? 'Cancelled', 'tone' => 'cancelled'],
            default => ['label' => Appointment::statusLabels()[$status] ?? ucfirst(str_replace('_', ' ', $status)), 'tone' => 'neutral'],
        };
    }

    private function appointmentDateTimeLabel(Appointment $appointment): string
    {
        return $appointment->appointment_date->format('M j, Y')
            .' at '.$this->formatTime($appointment->start_time)
            .' - '.$this->formatTime($appointment->end_time);
    }

    private function activeAppointmentMessage(Appointment $appointment): string
    {
        $appointment->loadMissing('staff');

        return 'You already have an active appointment with '
            .($appointment->staff?->full_name ?? 'your healthcare worker')
            .' on '.$this->appointmentDateTimeLabel($appointment).'.';
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

    private function availabilityForStaffAppointment(ProgramStaff $staff, array $validated): ?StaffAvailability
    {
        $availabilityId = (int) ($validated['staff_availability_id'] ?? 0);

        if ($availabilityId <= 0) {
            $date = Carbon::parse($validated['appointment_date']);
            $availability = StaffAvailability::where('staff_id', $staff->id)
                ->where('is_active', true)
                ->where('day_of_week', $date->isoWeekday())
                ->where('appointment_type', $validated['appointment_type'])
                ->where('meeting_type', $validated['meeting_type'])
                ->where('start_time', '<=', $validated['start_time'])
                ->where('end_time', '>=', $validated['end_time'])
                ->orderBy('start_time')
                ->first();

            if ($availability) {
                return $availability;
            }

            throw ValidationException::withMessages([
                'staff_availability_id' => 'Choose one of your available time slots for this appointment.',
            ]);
        }

        $availability = StaffAvailability::whereKey($availabilityId)
            ->where('staff_id', $staff->id)
            ->where('is_active', true)
            ->first();

        if (! $availability) {
            throw ValidationException::withMessages([
                'staff_availability_id' => 'Choose one of your available time slots.',
            ]);
        }

        $this->ensureAvailabilityMatchesDateAndType(
            $availability,
            $validated['appointment_date'],
            $validated['appointment_type'],
            $validated['meeting_type'] ?? null
        );

        return $availability;
    }

    private function applyAvailabilityToAppointmentData(array $validated, ?StaffAvailability $availability): array
    {
        if (! $availability) {
            return $validated;
        }

        return array_merge($validated, [
            'appointment_type' => $availability->appointment_type,
            'meeting_type' => $availability->meeting_type,
            'start_time' => substr((string) $availability->start_time, 0, 5),
            'end_time' => substr((string) $availability->end_time, 0, 5),
            'location' => $availability->location ?? ($validated['location'] ?? null),
        ]);
    }

    private function ensureStaffIsApproved(ProgramStaff $staff): void
    {
        if (($staff->approval_status ?? 'approved') !== 'approved') {
            throw ValidationException::withMessages([
                'staff_availability_id' => 'This healthcare worker is not available for public booking yet.',
            ]);
        }
    }

    private function ensureStaffIsAcceptingAppointments(ProgramStaff $staff): void
    {
        if (! (bool) ($staff->accepting_appointments ?? true)) {
            throw ValidationException::withMessages([
                'staff_availability_id' => 'This healthcare worker is not accepting appointments right now.',
            ]);
        }
    }

    private function ensureStaffCanBookDate(ProgramStaff $staff, string $date, ?int $ignoreAppointmentId = null): void
    {
        $staffForCheck = DB::transactionLevel() > 0
            ? ProgramStaff::whereKey($staff->id)->lockForUpdate()->first()
            : $staff;

        if (! $staffForCheck) {
            throw ValidationException::withMessages([
                'staff_availability_id' => 'This healthcare worker is no longer available.',
            ]);
        }

        if ($this->staffIsBlocked($staffForCheck, $date)) {
            throw ValidationException::withMessages([
                'appointment_date' => 'This healthcare worker blocked this date. Please choose another date.',
            ]);
        }

        if (! $this->staffHasDailyCapacity($staffForCheck, $date, $ignoreAppointmentId)) {
            throw ValidationException::withMessages([
                'appointment_date' => 'This healthcare worker has reached the appointment limit for this date.',
            ]);
        }
    }

    private function ensureAvailabilityMatchesDateAndType(
        StaffAvailability $availability,
        string $date,
        string $appointmentType,
        ?string $meetingType = null,
    ): void
    {
        $appointmentDate = Carbon::parse($date);

        if ((int) $appointmentDate->isoWeekday() !== (int) $availability->day_of_week) {
            throw ValidationException::withMessages([
                'appointment_date' => 'This slot is available on '.$availability->dayLabel().'.',
            ]);
        }

        if ($availability->appointment_type !== $appointmentType) {
            throw ValidationException::withMessages([
                'appointment_type' => 'Choose a slot that matches the consultation type.',
            ]);
        }

        if ($meetingType && $availability->meeting_type !== $meetingType) {
            throw ValidationException::withMessages([
                'meeting_type' => 'Choose a slot that matches the consultation channel.',
            ]);
        }
    }

    private function ensureFutureAppointmentStart(string $date, mixed $startTime): void
    {
        $start = Carbon::parse($date.' '.substr((string) $startTime, 0, 5));

        if ($start->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages([
                'appointment_date' => 'Choose a future appointment time.',
            ]);
        }
    }

    private function hasAvailabilityConflict(
        ProgramStaff $staff,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $ignoreAvailabilityId = null,
    ): bool {
        return StaffAvailability::query()
            ->where('staff_id', $staff->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->when($ignoreAvailabilityId, fn ($query) => $query->whereKeyNot($ignoreAvailabilityId))
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();
    }

    private function defaultAppointmentType(): string
    {
        return array_key_first(Appointment::appointmentTypeLabels()) ?: 'prenatal_checkup';
    }

    private function availabilityFilters(array $validated): array
    {
        return [
            'staff_id' => isset($validated['staff_id']) ? (int) $validated['staff_id'] : null,
            'q' => trim((string) ($validated['q'] ?? '')),
            'barangay' => trim((string) ($validated['barangay'] ?? '')),
            'role' => trim((string) ($validated['role'] ?? '')),
            'meeting_type' => $validated['meeting_type'] ?? null,
            'accepting_only' => array_key_exists('accepting_only', $validated)
                ? (bool) $validated['accepting_only']
                : true,
        ];
    }

    private function matchingWorkerQuery(array $filters)
    {
        $query = ProgramStaff::query()
            ->where('approval_status', 'approved')
            ->whereHas('availabilities', function ($availabilityQuery) use ($filters): void {
                $availabilityQuery->where('is_active', true);

                if (! empty($filters['meeting_type'])) {
                    $availabilityQuery->where('meeting_type', $filters['meeting_type']);
                }
            })
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($filters['accepting_only'] ?? true) {
            $query->where(function ($staffQuery): void {
                $staffQuery->where('accepting_appointments', true)
                    ->orWhereNull('accepting_appointments');
            });
        }

        if (! empty($filters['staff_id'])) {
            $query->whereKey((int) $filters['staff_id']);
        }

        if (! empty($filters['barangay'])) {
            $barangay = $filters['barangay'];
            $query->where(function ($staffQuery) use ($barangay): void {
                $staffQuery->where('assigned_barangay', $barangay)
                    ->orWhere('assigned_barangay', 'like', '%'.$barangay.'%');
            });
        }

        if (! empty($filters['role'])) {
            $role = '%'.$filters['role'].'%';
            $query->where(function ($staffQuery) use ($role): void {
                $staffQuery->where('role', 'like', $role)
                    ->orWhere('position', 'like', $role);
            });
        }

        if (! empty($filters['q'])) {
            $tokens = preg_split('/\s+/', $filters['q'], -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($tokens as $token) {
                $like = '%'.$token.'%';
                $query->where(function ($staffQuery) use ($like): void {
                    $staffQuery->where('first_name', 'like', $like)
                        ->orWhere('middle_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('role', 'like', $like)
                        ->orWhere('position', 'like', $like)
                        ->orWhere('assigned_barangay', 'like', $like)
                        ->orWhere('assigned_facility', 'like', $like);
                });
            }
        }

        return $query;
    }

    private function openAvailabilitySlots(
        Carbon $date,
        string $appointmentType,
        ?string $preferredStartTime,
        Mother $mother,
        ?int $limit = null,
        array $filters = [],
    ): Collection {
        $query = StaffAvailability::with('staff')
            ->where('is_active', true)
            ->where('day_of_week', $date->isoWeekday())
            ->where('appointment_type', $appointmentType)
            ->whereHas('staff', function ($staffQuery) use ($filters): void {
                $staffQuery->where('approval_status', 'approved');

                if ($filters['accepting_only'] ?? true) {
                    $staffQuery->where(function ($acceptingQuery): void {
                        $acceptingQuery->where('accepting_appointments', true)
                            ->orWhereNull('accepting_appointments');
                    });
                }

                if (! empty($filters['staff_id'])) {
                    $staffQuery->whereKey((int) $filters['staff_id']);
                }

                if (! empty($filters['barangay'])) {
                    $barangay = $filters['barangay'];
                    $staffQuery->where(function ($barangayQuery) use ($barangay): void {
                        $barangayQuery->where('assigned_barangay', $barangay)
                            ->orWhere('assigned_barangay', 'like', '%'.$barangay.'%');
                    });
                }

                if (! empty($filters['role'])) {
                    $role = '%'.$filters['role'].'%';
                    $staffQuery->where(function ($roleQuery) use ($role): void {
                        $roleQuery->where('role', 'like', $role)
                            ->orWhere('position', 'like', $role);
                    });
                }

                if (! empty($filters['q'])) {
                    $tokens = preg_split('/\s+/', $filters['q'], -1, PREG_SPLIT_NO_EMPTY) ?: [];

                    foreach ($tokens as $token) {
                        $like = '%'.$token.'%';
                        $staffQuery->where(function ($searchQuery) use ($like): void {
                            $searchQuery->where('first_name', 'like', $like)
                                ->orWhere('middle_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('role', 'like', $like)
                                ->orWhere('position', 'like', $like)
                                ->orWhere('assigned_barangay', 'like', $like)
                                ->orWhere('assigned_facility', 'like', $like);
                        });
                    }
                }
            })
            ->orderBy('start_time');

        if (! empty($filters['meeting_type'])) {
            $query->where('meeting_type', $filters['meeting_type']);
        }

        if ($preferredStartTime) {
            $preferred = Carbon::createFromFormat('H:i', $preferredStartTime)->format('H:i:s');
            $query->where('start_time', '<=', $preferred)
                ->where('end_time', '>', $preferred);
        }

        if ($date->isSameDay(now())) {
            $query->where('start_time', '>', now()->format('H:i:s'));
        }

        $slots = $query->get()
            ->filter(function (StaffAvailability $availability) use ($date, $mother): bool {
                if (! $availability->staff) {
                    return false;
                }

                if ($this->staffIsBlocked($availability->staff, $date)) {
                    return false;
                }

                if (! $this->staffHasDailyCapacity($availability->staff, $date->toDateString())) {
                    return false;
                }

                return ! $this->hasAppointmentConflict(
                    (int) $mother->id,
                    (int) $availability->staff_id,
                    $date->toDateString(),
                    substr((string) $availability->start_time, 0, 5),
                    substr((string) $availability->end_time, 0, 5),
                );
            })
            ->values();

        return $limit ? $slots->take($limit)->values() : $slots;
    }

    private function nearestAvailableDates(
        Carbon $fromDate,
        string $appointmentType,
        Mother $mother,
        ?string $preferredStartTime = null,
        array $filters = [],
    ): Collection {
        $dates = collect();

        for ($daysAhead = 1; $daysAhead <= 30 && $dates->count() < 3; $daysAhead++) {
            $candidate = $fromDate->copy()->addDays($daysAhead);
            $slots = $this->openAvailabilitySlots($candidate, $appointmentType, $preferredStartTime, $mother, 2, $filters);

            if ($slots->isEmpty() && $preferredStartTime) {
                $slots = $this->openAvailabilitySlots($candidate, $appointmentType, null, $mother, 2, $filters);
            }

            if ($slots->isNotEmpty()) {
                $dates->push([
                    'date' => $candidate->toDateString(),
                    'date_label' => $candidate->format('D, M j'),
                    'day_label' => $candidate->format('l'),
                    'open_slots' => $slots->count(),
                    'first_time_label' => $slots->first()->timeLabel(),
                ]);
            }
        }

        return $dates;
    }

    private function availableDatesForMonth(Carbon $month, string $appointmentType, Mother $mother, array $filters = []): Collection
    {
        $dates = collect();
        $cursor = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        while ($cursor->lessThanOrEqualTo($end)) {
            if ($cursor->greaterThanOrEqualTo(today())) {
                $slots = $this->openAvailabilitySlots($cursor, $appointmentType, null, $mother, 3, $filters);

                if ($slots->isNotEmpty()) {
                    $dates->push([
                        'date' => $cursor->toDateString(),
                        'date_label' => $cursor->format('D, M j'),
                        'open_slots' => $slots->count(),
                        'first_time_label' => $slots->first()->timeLabel(),
                    ]);
                }
            }

            $cursor->addDay();
        }

        return $dates;
    }

    private function otherAvailableWorkers(Carbon $date, string $appointmentType, Mother $mother, array $filters = []): Collection
    {
        $relaxedFilters = array_merge($filters, [
            'staff_id' => null,
            'q' => '',
            'barangay' => '',
        ]);

        return $this->openAvailabilitySlots($date, $appointmentType, null, $mother, 8, $relaxedFilters)
            ->unique('staff_id')
            ->take(4)
            ->map(function (StaffAvailability $availability) use ($date): array {
                $staff = $availability->staff;

                return [
                    'staff_id' => $availability->staff_id,
                    'name' => $staff?->full_name ?? 'Healthcare worker',
                    'role' => $staff?->role_label ?? 'Healthcare Worker',
                    'facility' => $staff?->assigned_facility ?: ($availability->location ?: 'Official facility not set'),
                    'barangay' => $staff?->assigned_barangay ?: 'Barangay not set',
                    'date' => $date->toDateString(),
                    'date_label' => $date->format('D, M j'),
                    'time_label' => $availability->timeLabel(),
                ];
            })
            ->values();
    }

    private function nearbyAvailableBarangays(Carbon $date, string $appointmentType, Mother $mother, array $filters = []): Collection
    {
        $relaxedFilters = array_merge($filters, [
            'staff_id' => null,
            'q' => '',
            'barangay' => '',
        ]);

        $requestedBarangay = $filters['barangay'] ?? '';

        return $this->openAvailabilitySlots($date, $appointmentType, null, $mother, 20, $relaxedFilters)
            ->filter(fn (StaffAvailability $availability): bool => (string) ($availability->staff?->assigned_barangay ?? '') !== '')
            ->groupBy(fn (StaffAvailability $availability): string => (string) $availability->staff?->assigned_barangay)
            ->reject(fn (Collection $slots, string $barangay): bool => $requestedBarangay !== '' && strcasecmp($barangay, $requestedBarangay) === 0)
            ->map(function (Collection $slots, string $barangay): array {
                $first = $slots->first();

                return [
                    'barangay' => $barangay,
                    'open_slots' => $slots->count(),
                    'first_worker' => $first?->staff?->full_name ?? 'Healthcare worker',
                    'first_time_label' => $first?->timeLabel() ?? '',
                ];
            })
            ->take(4)
            ->values();
    }

    private function nearestAvailableDateForWorker(
        ProgramStaff $staff,
        Mother $mother,
        Carbon $fromDate,
        string $appointmentType,
        array $filters = [],
    ): ?array {
        $workerFilters = array_merge($filters, [
            'staff_id' => (int) $staff->id,
            'q' => '',
            'barangay' => '',
            'role' => '',
        ]);

        for ($daysAhead = 0; $daysAhead <= 30; $daysAhead++) {
            $candidate = $fromDate->copy()->addDays($daysAhead);
            $slots = $this->openAvailabilitySlots($candidate, $appointmentType, null, $mother, 1, $workerFilters);

            if ($slots->isNotEmpty()) {
                return [
                    'date' => $candidate->toDateString(),
                    'date_label' => $candidate->format('D, M j'),
                    'full_date_label' => $candidate->format('l, F j, Y'),
                    'time_label' => $slots->first()->timeLabel(),
                ];
            }
        }

        return null;
    }

    private function workerPayload(
        ProgramStaff $staff,
        Mother $mother,
        Carbon $fromDate,
        string $appointmentType,
        array $filters = [],
    ): array {
        $activeAvailabilities = $staff->availabilities
            ->where('is_active', true)
            ->when(! empty($filters['meeting_type']), fn (Collection $rows) => $rows->where('meeting_type', $filters['meeting_type']))
            ->values();
        $nearest = $this->nearestAvailableDateForWorker($staff, $mother, $fromDate, $appointmentType, $filters);
        $selectedDaySlots = $this->openAvailabilitySlots(
            $fromDate,
            $appointmentType,
            null,
            $mother,
            null,
            array_merge($filters, ['staff_id' => (int) $staff->id, 'q' => '', 'barangay' => '', 'role' => ''])
        );

        return [
            'id' => $staff->id,
            'full_name' => $staff->full_name,
            'role' => $staff->role_label,
            'facility' => $staff->assigned_facility ?: 'Official facility not set',
            'barangay' => $staff->assigned_barangay ?: 'Barangay not set',
            'accepting_appointments' => (bool) ($staff->accepting_appointments ?? true),
            'consultation_types' => $activeAvailabilities
                ->pluck('meeting_type')
                ->unique()
                ->map(fn (string $type): string => Appointment::meetingTypeLabels()[$type] ?? ucfirst(str_replace('_', ' ', $type)))
                ->values(),
            'working_days' => $activeAvailabilities
                ->pluck('day_of_week')
                ->unique()
                ->sort()
                ->map(fn (int $day): string => StaffAvailability::dayLabels()[$day] ?? 'Available day')
                ->values(),
            'nearest_available_date' => $nearest,
            'has_selected_date_slots' => $selectedDaySlots->isNotEmpty(),
            'selected_date_slots' => $selectedDaySlots
                ->map(fn (StaffAvailability $availability): array => $this->availabilityPayload($availability, $fromDate))
                ->values(),
        ];
    }

    private function staffIsBlocked(ProgramStaff $staff, Carbon|string $date): bool
    {
        $dateValue = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        if ($staff->relationLoaded('availabilityBlocks')) {
            return $staff->availabilityBlocks
                ->contains(fn (StaffAvailabilityBlock $block): bool => $block->blocked_date?->toDateString() === $dateValue);
        }

        return StaffAvailabilityBlock::where('staff_id', $staff->id)
            ->whereDate('blocked_date', $dateValue)
            ->exists();
    }

    private function staffHasDailyCapacity(ProgramStaff $staff, string $date, ?int $ignoreAppointmentId = null): bool
    {
        $limit = (int) ($staff->max_appointments_per_day ?? 8);

        if ($limit <= 0) {
            return false;
        }

        $query = Appointment::query()
            ->where('staff_id', $staff->id)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', Appointment::activeStatuses())
            ->when($ignoreAppointmentId, fn ($appointmentQuery) => $appointmentQuery->whereKeyNot($ignoreAppointmentId));

        $count = DB::transactionLevel() > 0
            ? $query->lockForUpdate()->pluck('id')->count()
            : $query->count();

        return $count < $limit;
    }

    private function availabilityPayload(StaffAvailability $availability, Carbon $date): array
    {
        $availability->loadMissing('staff');
        $staff = $availability->staff;

        return [
            'availability_id' => $availability->id,
            'staff_id' => $availability->staff_id,
            'staff_name' => $staff?->full_name ?? 'Healthcare worker',
            'staff_role' => $staff?->role_label ?? 'Healthcare Worker',
            'facility' => $staff?->assigned_facility ?: ($availability->location ?: 'Official facility not set'),
            'barangay' => $staff?->assigned_barangay ?: 'Barangay not set',
            'appointment_type' => $availability->appointment_type,
            'appointment_type_label' => $availability->typeLabel(),
            'meeting_type' => $availability->meeting_type,
            'meeting_type_label' => $availability->meetingLabel(),
            'date' => $date->toDateString(),
            'date_label' => $date->format('D, M j'),
            'day_label' => $availability->dayLabel(),
            'start_time' => substr((string) $availability->start_time, 0, 5),
            'end_time' => substr((string) $availability->end_time, 0, 5),
            'time_label' => $availability->timeLabel(),
            'location' => $availability->location ?: ($staff?->assigned_facility ?: 'To be announced'),
        ];
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
            'staff_role' => $appointment->staff?->role_label,
            'care_team' => $appointment->care_team_snapshot,
            'staff_availability_id' => $appointment->staff_availability_id,
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
            'reference_number' => $this->appointmentReference($appointment),
            'preferred_date' => $appointment->preferred_date?->toDateString(),
            'preferred_start_time' => $appointment->preferred_start_time ? substr((string) $appointment->preferred_start_time, 0, 5) : null,
            'reschedule_reason' => $appointment->reschedule_reason,
        ];
    }

    private function appointmentReference(Appointment $appointment): string
    {
        return 'INAY-APT-'.str_pad((string) $appointment->id, 6, '0', STR_PAD_LEFT);
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

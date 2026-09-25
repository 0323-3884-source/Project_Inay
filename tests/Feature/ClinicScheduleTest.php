<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Message;
use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Models\StaffAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClinicScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_mother_appointment_page_shows_doctor_list_without_fee_text(): void
    {
        $mother = Mother::create($this->motherAttributes());
        $staff = ProgramStaff::create($this->staffAttributes());
        $date = now()->addWeek()->startOfWeek();
        $this->createAvailability($staff, $date->isoWeekday());

        $this->withSession($this->motherSession($mother))
            ->get(route('mother.clinic-schedule.index'))
            ->assertOk()
            ->assertSee('My Appointments')
            ->assertSee('Doctor List')
            ->assertSee('Filter by Barangay')
            ->assertSee('San Gabriel')
            ->assertSee($staff->full_name)
            ->assertSee('Midwife')
            ->assertSee('San Gabriel Health Center')
            ->assertSee('Book Now')
            ->assertSee('Details')
            ->assertSee('Detail Doctor')
            ->assertSee('Experience')
            ->assertSee('Speciality')
            ->assertSee('Reviews')
            ->assertDontSee('&#8369;', false)
            ->assertDontSee('₱')
            ->assertDontSee('/hour');

        $this->withSession($this->staffSession($staff))
            ->get(route('staff.clinic-schedule.index'))
            ->assertOk()
            ->assertSee('Clinic Schedule')
            ->assertSee('Healthcare Worker Portal')
            ->assertSee('Scheduling Profile')
            ->assertSee('Available Hours')
            ->assertSee('Blocked Dates')
            ->assertSee('Create Appointment')
            ->assertSee('Appointment Requests');
    }

    public function test_mother_can_filter_doctor_list_by_barangay(): void
    {
        $mother = Mother::create($this->motherAttributes());
        $sanGabrielStaff = ProgramStaff::create($this->staffAttributes(
            email: 'san-gabriel-staff@example.test',
            staffId: 'STAFF-SAN-GABRIEL',
            firstName: 'Ana',
            lastName: 'Cruz',
            barangay: 'San Gabriel',
            facility: 'San Gabriel Health Center',
        ));
        $sanIsidroStaff = ProgramStaff::create($this->staffAttributes(
            email: 'san-isidro-staff@example.test',
            staffId: 'STAFF-SAN-ISIDRO',
            firstName: 'Lina',
            lastName: 'Dela Rosa',
            barangay: 'San Isidro',
            facility: 'San Isidro Health Station',
        ));

        $date = now()->addWeek()->startOfWeek();
        $this->createAvailability($sanGabrielStaff, $date->isoWeekday());
        $this->createAvailability($sanIsidroStaff, $date->isoWeekday());

        $this->withSession($this->motherSession($mother))
            ->get(route('mother.clinic-schedule.index', ['barangay' => 'San Gabriel']))
            ->assertOk()
            ->assertSee('Barangay:')
            ->assertSee('San Gabriel')
            ->assertSee($sanGabrielStaff->full_name)
            ->assertDontSee($sanIsidroStaff->full_name);
    }

    public function test_mother_can_book_from_doctor_list(): void
    {
        $mother = Mother::create($this->motherAttributes());
        $staff = ProgramStaff::create($this->staffAttributes());
        $date = now()->addWeek()->startOfWeek();
        $availability = $this->createAvailability($staff, $date->isoWeekday());

        $this->withSession($this->motherSession($mother))
            ->postJson(route('mother.clinic-schedule.store'), [
                'staff_availability_id' => $availability->id,
                'appointment_date' => $date->toDateString(),
                'appointment_type' => 'prenatal_checkup',
                'notes' => 'Headache and mild dizziness since yesterday.',
            ])
            ->assertCreated()
            ->assertJsonPath('appointment.staff_name', $staff->full_name)
            ->assertJsonPath('appointment.status', Appointment::STATUS_PENDING);

        $appointment = Appointment::firstOrFail();

        $this->assertSame($date->toDateString(), $appointment->appointment_date->toDateString());
        $this->assertDatabaseHas('appointments', [
            'mother_id' => $mother->id,
            'staff_id' => $staff->id,
            'staff_availability_id' => $availability->id,
            'notes' => 'Headache and mild dizziness since yesterday.',
            'status' => Appointment::STATUS_PENDING,
        ]);
    }

    public function test_mother_doctor_list_locks_booking_when_active_appointment_exists(): void
    {
        $mother = Mother::create($this->motherAttributes());
        $activeStaff = ProgramStaff::create($this->staffAttributes());
        $otherStaff = ProgramStaff::create($this->staffAttributes(
            email: 'other-schedule-staff@example.test',
            staffId: 'STAFF-SCHEDULE-OTHER',
            firstName: 'Lina',
            lastName: 'Reyes',
            barangay: 'San Gabriel',
            facility: 'San Gabriel Health Station',
        ));
        $date = now()->addWeek()->startOfWeek();
        $activeAvailability = $this->createAvailability($activeStaff, $date->isoWeekday());
        $this->createAvailability($otherStaff, $date->isoWeekday());

        Appointment::create([
            'mother_id' => $mother->id,
            'staff_id' => $activeStaff->id,
            'staff_availability_id' => $activeAvailability->id,
            'appointment_type' => 'prenatal_checkup',
            'meeting_type' => Appointment::MEETING_IN_PERSON,
            'appointment_date' => $date->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'San Gabriel Health Center',
            'status' => Appointment::STATUS_PENDING,
            'created_by_id' => $mother->id,
            'created_by_role' => Message::ROLE_MOTHER,
        ]);

        $this->withSession($this->motherSession($mother))
            ->get(route('mother.clinic-schedule.index'))
            ->assertOk()
            ->assertSee('You already have an active appointment with')
            ->assertSee($activeStaff->full_name)
            ->assertSee('Waiting for Confirmation')
            ->assertSee('Appointment Pending')
            ->assertSee('Active Appointment')
            ->assertSee('View Appointment')
            ->assertSee('Cancel Request');
    }

    public function test_mother_cannot_submit_multiple_active_appointment_requests(): void
    {
        $mother = Mother::create($this->motherAttributes());
        $activeStaff = ProgramStaff::create($this->staffAttributes());
        $otherStaff = ProgramStaff::create($this->staffAttributes(
            email: 'duplicate-other-staff@example.test',
            staffId: 'STAFF-DUPLICATE-OTHER',
            firstName: 'Lina',
            lastName: 'Reyes',
            barangay: 'San Gabriel',
            facility: 'San Gabriel Health Station',
        ));
        $date = now()->addWeek()->startOfWeek();
        $activeAvailability = $this->createAvailability($activeStaff, $date->isoWeekday());
        $otherAvailability = $this->createAvailability($otherStaff, $date->isoWeekday());

        Appointment::create([
            'mother_id' => $mother->id,
            'staff_id' => $activeStaff->id,
            'staff_availability_id' => $activeAvailability->id,
            'appointment_type' => 'prenatal_checkup',
            'meeting_type' => Appointment::MEETING_IN_PERSON,
            'appointment_date' => $date->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'San Gabriel Health Center',
            'status' => Appointment::STATUS_PENDING,
            'created_by_id' => $mother->id,
            'created_by_role' => Message::ROLE_MOTHER,
        ]);

        $this->withSession($this->motherSession($mother))
            ->postJson(route('mother.clinic-schedule.store'), [
                'staff_availability_id' => $otherAvailability->id,
                'appointment_date' => $date->toDateString(),
                'appointment_type' => 'prenatal_checkup',
                'notes' => 'Trying to book a second doctor.',
            ])
            ->assertStatus(409)
            ->assertJsonPath('active_appointment.staff_id', $activeStaff->id)
            ->assertJsonPath('active_appointment.status_display', 'Waiting for Confirmation');

        $this->assertSame(1, Appointment::where('mother_id', $mother->id)->count());
    }

    public function test_mother_can_cancel_pending_request_and_book_again(): void
    {
        $mother = Mother::create($this->motherAttributes());
        $staff = ProgramStaff::create($this->staffAttributes());
        $date = now()->addWeek()->startOfWeek();
        $availability = $this->createAvailability($staff, $date->isoWeekday());

        $appointment = Appointment::create([
            'mother_id' => $mother->id,
            'staff_id' => $staff->id,
            'staff_availability_id' => $availability->id,
            'appointment_type' => 'prenatal_checkup',
            'meeting_type' => Appointment::MEETING_IN_PERSON,
            'appointment_date' => $date->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'San Gabriel Health Center',
            'status' => Appointment::STATUS_PENDING,
            'created_by_id' => $mother->id,
            'created_by_role' => Message::ROLE_MOTHER,
        ]);

        $this->withSession($this->motherSession($mother))
            ->patchJson(route('mother.clinic-schedule.cancel', $appointment))
            ->assertOk()
            ->assertJsonPath('appointment.status', Appointment::STATUS_CANCELLED)
            ->assertJsonPath('active_appointment', null);

        $this->withSession($this->motherSession($mother))
            ->get(route('mother.clinic-schedule.index'))
            ->assertOk()
            ->assertSee('Cancelled')
            ->assertSee('Book Now')
            ->assertDontSee('Appointment Pending');
    }

    public function test_mother_search_endpoints_remain_disabled_for_json_requests(): void
    {
        $this->getJson(route('mother.clinic-schedule.workers'))
            ->assertGone()
            ->assertJsonPath('message', 'Appointment scheduling has been removed from the mother portal.');

        $this->getJson(route('mother.clinic-schedule.availability'))
            ->assertGone()
            ->assertJsonPath('message', 'Appointment scheduling has been removed from the mother portal.');
    }

    public function test_healthcare_worker_can_publish_availability_and_block_dates(): void
    {
        $staff = ProgramStaff::create($this->staffAttributes());

        $this->withSession($this->staffSession($staff))
            ->post(route('staff.clinic-schedule.availability.store'), [
                'day_of_week' => 1,
                'start_time' => '09:00',
                'end_time' => '10:00',
                'appointment_type' => 'prenatal_checkup',
                'meeting_type' => Appointment::MEETING_IN_PERSON,
                'location' => 'San Gabriel Health Center',
            ])
            ->assertRedirect(route('staff.clinic-schedule.index'));

        $this->assertDatabaseHas('staff_availabilities', [
            'staff_id' => $staff->id,
            'day_of_week' => 1,
            'appointment_type' => 'prenatal_checkup',
            'meeting_type' => Appointment::MEETING_IN_PERSON,
        ]);

        $this->withSession($this->staffSession($staff))
            ->post(route('staff.clinic-schedule.blocks.store'), [
                'blocked_date' => now()->addWeeks(2)->toDateString(),
                'reason' => 'Training day',
                'notes' => 'Community health training.',
            ])
            ->assertRedirect(route('staff.clinic-schedule.index'));

        $this->assertDatabaseHas('staff_availability_blocks', [
            'staff_id' => $staff->id,
            'reason' => 'Training day',
        ]);
    }

    public function test_healthcare_worker_can_confirm_a_mothers_appointment_request(): void
    {
        $mother = Mother::create($this->motherAttributes());
        $staff = ProgramStaff::create($this->staffAttributes());
        $date = now()->addWeek()->startOfWeek();
        $availability = $this->createAvailability($staff, $date->isoWeekday());

        $this->withSession($this->motherSession($mother))
            ->postJson(route('mother.clinic-schedule.store'), [
                'staff_availability_id' => $availability->id,
                'appointment_date' => $date->toDateString(),
                'appointment_type' => 'prenatal_checkup',
            ])
            ->assertCreated();

        $appointment = Appointment::firstOrFail();

        $this->withSession($this->staffSession($staff))
            ->patchJson(route('staff.clinic-schedule.confirm', $appointment))
            ->assertOk()
            ->assertJsonPath('appointment.status', Appointment::STATUS_CONFIRMED);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'staff_id' => $staff->id,
            'status' => Appointment::STATUS_CONFIRMED,
        ]);
    }

    public function test_live_calendar_hides_booked_slots_and_cancellation_reopens_them(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-09-01 08:00'));
        $mother = Mother::create($this->motherAttributes());
        $other = Mother::create([...$this->motherAttributes(), 'email' => 'other-calendar@example.test']);
        $staff = ProgramStaff::create([...$this->staffAttributes(), 'role' => 'Nurse']);
        $slot = $this->createAvailability($staff, 1);
        $later = StaffAvailability::create([
            'staff_id' => $staff->id, 'day_of_week' => 1, 'start_time' => '11:00', 'end_time' => '12:00',
            'appointment_type' => 'prenatal_checkup', 'meeting_type' => 'in_person', 'is_active' => true,
        ]);
        $this->withSession($this->motherSession($other))->postJson(route('mother.clinic-schedule.store'), [
            'staff_availability_id' => $slot->id, 'appointment_date' => '2026-09-07', 'appointment_type' => 'prenatal_checkup',
        ])->assertCreated();
        $appointment = Appointment::firstOrFail();
        $query = ['staff_id' => $staff->id, 'month' => '2026-09'];
        $calendar = $this->withSession($this->motherSession($mother))->getJson(route('mother.clinic-schedule.calendar', $query));
        $calendar->assertOk()->assertJsonPath('days.2026-09-07.status', 'available')
            ->assertJsonPath('days.2026-09-07.slots.0.status', 'booked')
            ->assertJsonPath('days.2026-09-07.slots.1.status', 'available')
            ->assertJsonPath('days.2026-09-08.status', 'unavailable');
        $this->assertStringNotContainsString($other->email, $calendar->getContent());
        $this->withSession($this->motherSession($mother))->postJson(route('mother.clinic-schedule.store'), [
            'staff_availability_id' => $slot->id, 'appointment_date' => '2026-09-07', 'appointment_type' => 'prenatal_checkup',
        ])->assertUnprocessable();

        // A fully occupied second Monday gives the browser both day and time occupied states.
        Appointment::create([
            ...$appointment->only(['mother_id', 'staff_id', 'appointment_type', 'meeting_type', 'created_by_id', 'created_by_role']),
            'appointment_date' => '2026-09-14', 'start_time' => '08:00', 'end_time' => '13:00', 'status' => 'confirmed',
        ]);
        $calendar = $this->getJson(route('mother.clinic-schedule.calendar', $query));
        $calendar->assertJsonPath('days.2026-09-14.status', 'booked');
        if (getenv('INAY_CARE_TEAM_FIXTURES') === '1') {
            $directory = storage_path('framework/testing/care-team');
            if (! is_dir($directory)) { mkdir($directory, 0777, true); }
            file_put_contents($directory.'/calendar.json', $calendar->getContent());
            file_put_contents($directory.'/booking.html', $this->get(route('mother.clinic-schedule.index'))->getContent());
        }
        $this->withSession($this->motherSession($other))->patchJson(route('mother.clinic-schedule.cancel', $appointment))->assertOk();
        $this->withSession($this->motherSession($mother))->getJson(route('mother.clinic-schedule.calendar', $query))
            ->assertJsonPath('days.2026-09-07.slots.0.status', 'available');
    }

    public function test_live_calendar_respects_blocks_limits_past_times_and_access(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-09-07 09:30'));
        $mother = Mother::create($this->motherAttributes());
        $staff = ProgramStaff::create($this->staffAttributes());
        $this->createAvailability($staff, 1);
        $query = ['staff_id' => $staff->id, 'month' => '2026-09'];
        $this->getJson(route('mother.clinic-schedule.calendar', $query))->assertForbidden();
        $this->withSession($this->staffSession($staff))->getJson(route('mother.clinic-schedule.calendar', $query))->assertForbidden();
        $this->withSession($this->motherSession($mother))->getJson(route('mother.clinic-schedule.calendar', $query))
            ->assertOk()->assertJsonPath('days.2026-09-07.slots.0.status', 'past');
        \App\Models\StaffAvailabilityBlock::create(['staff_id' => $staff->id, 'blocked_date' => '2026-09-14', 'reason' => 'Closed']);
        $this->getJson(route('mother.clinic-schedule.calendar', $query))->assertJsonPath('days.2026-09-14.status', 'unavailable');
        $staff->update(['max_appointments_per_day' => 0]);
        $this->getJson(route('mother.clinic-schedule.calendar', $query))->assertJsonPath('days.2026-09-21.slots.0.status', 'full');
        $this->getJson(route('mother.clinic-schedule.calendar', ['staff_id' => $staff->id, 'month' => '2025-09']))->assertUnprocessable();
    }

    public function test_chat_action_targets_selected_worker_and_duplicate_flash_is_removed(): void
    {
        $mother = Mother::create($this->motherAttributes());
        $staff = ProgramStaff::create([...$this->staffAttributes(), 'role' => 'Nurse']);
        $this->withSession($this->motherSession($mother))->get(route('mother.consultation', ['staff' => $staff->id]))
            ->assertRedirect(route('mother.clinic-schedule.index'));
        \App\Models\StaffMotherCasefile::create(['staff_id' => $staff->id, 'mother_id' => $mother->id]);
        $this->get(route('mother.consultation', ['staff' => $staff->id]))
            ->assertRedirect(route('mother.consultation', ['conversation' => \App\Models\Conversation::firstOrFail()->id]));
        $page = $this->withSession(['status' => 'Appointment request cancelled.'])->get(route('mother.clinic-schedule.index'));
        $page->assertOk()->assertViewHas('doctorCards', fn ($cards) => $cards->first()['category_key'] === 'nurse');
        $this->assertSame(1, substr_count($page->getContent(), 'Appointment request cancelled.'));
    }

    private function createAvailability(ProgramStaff $staff, int $dayOfWeek): StaffAvailability
    {
        return StaffAvailability::create([
            'staff_id' => $staff->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'appointment_type' => 'prenatal_checkup',
            'meeting_type' => Appointment::MEETING_IN_PERSON,
            'location' => 'San Gabriel Health Center',
            'is_active' => true,
        ]);
    }

    private function motherAttributes(): array
    {
        return [
            'first_name' => 'Maria',
            'middle_name' => null,
            'last_name' => 'Reyes',
            'email' => 'schedule-mother@example.test',
            'password' => Hash::make('password123'),
            'barangay' => 'San Gabriel',
            'contact_number' => '09170000000',
            'is_4ps_beneficiary' => false,
        ];
    }

    private function staffAttributes(
        string $email = 'schedule-staff@example.test',
        string $staffId = 'STAFF-SCHEDULE',
        string $firstName = 'Ana',
        string $lastName = 'Cruz',
        string $barangay = 'San Gabriel',
        string $facility = 'San Gabriel Health Center',
    ): array
    {
        return [
            'first_name' => $firstName,
            'middle_name' => null,
            'last_name' => $lastName,
            'email' => $email,
            'password' => Hash::make('password123'),
            'staff_id' => $staffId,
            'position' => 'Program Staff',
            'role' => 'Midwife',
            'contact_number' => '09171111111',
            'approval_status' => 'approved',
            'assigned_barangay' => $barangay,
            'assigned_facility' => $facility,
            'accepting_appointments' => true,
            'max_appointments_per_day' => 8,
        ];
    }

    private function motherSession(Mother $mother): array
    {
        return [
            'auth_role' => 'mother',
            'auth_id' => $mother->id,
            'auth_name' => $mother->full_name,
            'auth_email' => $mother->email,
        ];
    }

    private function staffSession(ProgramStaff $staff): array
    {
        return [
            'auth_role' => 'staff',
            'auth_id' => $staff->id,
            'auth_name' => $staff->full_name,
            'auth_email' => $staff->email,
        ];
    }
}

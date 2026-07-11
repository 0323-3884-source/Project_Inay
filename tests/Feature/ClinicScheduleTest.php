<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Models\StaffMotherCasefile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClinicScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_and_mother_can_open_clinic_schedule_pages(): void
    {
        [$mother, $staff] = $this->makeAssignedPair();

        $this->withSession($this->staffSession($staff))
            ->get(route('staff.clinic-schedule.index'))
            ->assertOk()
            ->assertSee('Clinic Schedule')
            ->assertSee('Create Appointment');

        $this->withSession($this->motherSession($mother))
            ->get(route('mother.clinic-schedule.index'))
            ->assertOk()
            ->assertSee('My Clinic Schedule');
    }

    public function test_staff_creates_appointment_and_mother_can_confirm_it(): void
    {
        [$mother, $staff] = $this->makeAssignedPair();
        $date = now()->addDays(7)->toDateString();

        $this->withSession($this->staffSession($staff))
            ->post(route('staff.clinic-schedule.store'), $this->appointmentPayload($mother, $date))
            ->assertRedirect(route('staff.clinic-schedule.index'));

        $appointment = Appointment::first();

        $this->assertNotNull($appointment);
        $this->assertSame(Appointment::STATUS_PENDING, $appointment->status);
        $this->assertDatabaseHas('app_notifications', [
            'recipient_id' => $mother->id,
            'recipient_role' => Message::ROLE_MOTHER,
            'appointment_id' => $appointment->id,
            'type' => 'appointment_created',
        ]);

        $this->withSession($this->motherSession($mother))
            ->get(route('mother.clinic-schedule.index'))
            ->assertOk()
            ->assertSee('Prenatal Checkup')
            ->assertSee('Confirm');

        $this->withSession($this->motherSession($mother))
            ->patch(route('mother.clinic-schedule.confirm', $appointment))
            ->assertRedirect(route('mother.clinic-schedule.index'));

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => Appointment::STATUS_CONFIRMED,
        ]);
        $this->assertDatabaseHas('app_notifications', [
            'recipient_id' => $staff->id,
            'recipient_role' => Message::ROLE_PROGRAM_STAFF,
            'appointment_id' => $appointment->id,
            'type' => 'appointment_confirmed',
        ]);
    }

    public function test_conflict_validation_blocks_overlapping_mother_or_staff_schedule(): void
    {
        [$mother, $staff] = $this->makeAssignedPair();
        $date = now()->addDays(8)->toDateString();

        $this->withSession($this->staffSession($staff))
            ->post(route('staff.clinic-schedule.store'), $this->appointmentPayload($mother, $date, '09:00', '10:00'))
            ->assertRedirect(route('staff.clinic-schedule.index'));

        $this->withSession($this->staffSession($staff))
            ->post(route('staff.clinic-schedule.store'), $this->appointmentPayload($mother, $date, '09:30', '10:30'))
            ->assertSessionHasErrors(['appointment' => 'This schedule conflicts with another appointment.']);

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_staff_cannot_schedule_unassigned_mother(): void
    {
        [, $staff] = $this->makeAssignedPair();
        $unassignedMother = Mother::create($this->motherAttributes('Lorna', 'Reyes', 'unassigned@example.test'));

        $this->withSession($this->staffSession($staff))
            ->postJson(route('staff.clinic-schedule.store'), $this->appointmentPayload($unassignedMother, now()->addDays(9)->toDateString()))
            ->assertForbidden()
            ->assertJsonPath('message', 'You can only schedule assigned mothers.');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_consultation_schedule_checkup_creates_system_message(): void
    {
        [$mother, $staff] = $this->makeAssignedPair();
        $conversation = Conversation::create([
            'mother_id' => $mother->id,
            'program_staff_id' => $staff->id,
        ]);

        $this->withSession($this->staffSession($staff))
            ->postJson(route('staff.clinic-schedule.store'), array_merge(
                $this->appointmentPayload($mother, now()->addDays(10)->toDateString()),
                [
                    'conversation_id' => $conversation->id,
                    'from_consultation' => '1',
                ],
            ))
            ->assertCreated()
            ->assertJsonPath('message.message_type', Message::TYPE_SYSTEM)
            ->assertJsonPath('message.message', 'Prenatal Checkup scheduled for '.now()->addDays(10)->format('F j, Y').' at 9:00 AM.');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $staff->id,
            'sender_role' => Message::ROLE_PROGRAM_STAFF,
            'receiver_id' => $mother->id,
            'receiver_role' => Message::ROLE_MOTHER,
            'message_type' => Message::TYPE_SYSTEM,
        ]);
    }

    private function makeAssignedPair(string $email = 'schedule@example.test'): array
    {
        $mother = Mother::create($this->motherAttributes('Maria', 'Reyes', $email));
        $staff = ProgramStaff::create($this->staffAttributes('Ana', 'Cruz', $email, 'STAFF-SCHEDULE'));

        StaffMotherCasefile::create([
            'staff_id' => $staff->id,
            'mother_id' => $mother->id,
        ]);

        return [$mother, $staff];
    }

    private function appointmentPayload(Mother $mother, string $date, string $start = '09:00', string $end = '10:00'): array
    {
        return [
            'mother_id' => $mother->id,
            'appointment_type' => 'prenatal_checkup',
            'meeting_type' => Appointment::MEETING_IN_PERSON,
            'appointment_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'location' => 'Main Health Center',
            'notes' => 'Bring previous monitoring record.',
        ];
    }

    private function motherAttributes(string $firstName, string $lastName, string $email): array
    {
        return [
            'first_name' => $firstName,
            'middle_name' => null,
            'last_name' => $lastName,
            'email' => $email,
            'password' => Hash::make('password123'),
            'barangay' => 'San Gabriel',
            'contact_number' => '09170000000',
            'is_4ps_beneficiary' => false,
        ];
    }

    private function staffAttributes(string $firstName, string $lastName, string $email, string $staffId): array
    {
        return [
            'first_name' => $firstName,
            'middle_name' => null,
            'last_name' => $lastName,
            'email' => $email,
            'password' => Hash::make('password123'),
            'staff_id' => $staffId,
            'position' => 'Program Staff',
            'contact_number' => '09171111111',
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

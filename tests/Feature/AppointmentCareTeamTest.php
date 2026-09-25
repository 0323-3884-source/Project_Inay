<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\HealthcareFacility;
use App\Models\MidwifeProfile;
use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Models\StaffAvailability;
use App\Models\StaffMotherCasefile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentCareTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_midwife_is_reused_without_changing_either_workers_profile_or_hours(): void
    {
        $nurse = $this->staff('Miguel', 'Nurse');
        $midwife = $this->staff('Rebecca', 'Midwife');
        $slot = $this->slot($midwife);
        $original = $midwife->fresh()->getAttributes();
        $this->saveProfile($nurse, ['midwife_selection' => 'staff:'.$midwife->id])->assertSessionHasNoErrors();
        $this->saveProfile($nurse, ['midwife_selection' => 'staff:'.$midwife->id])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('program_staff', 2);
        $this->assertDatabaseCount('healthcare_facilities', 1);
        $this->assertDatabaseCount('midwife_profiles', 1);
        $this->assertSame($original, $midwife->fresh()->getAttributes());
        $this->assertSame('Nurse', $nurse->fresh()->role);
        $this->assertEquals($midwife->id, $slot->fresh()->staff_id);
        $this->assertCount(2, HealthcareFacility::first()->staff);
        $this->withSession($this->sessionFor($nurse))->get(route('staff.clinic-schedule.index'))
            ->assertOk()->assertSee('Midwife Information')->assertSee('Rebecca Escote');
    }

    public function test_manual_midwife_is_deduplicated_across_workers_and_does_not_create_a_login(): void
    {
        $nurse = $this->staff('Miguel', 'Nurse');
        $other = $this->staff('Other', 'Nurse');
        $this->saveProfile($nurse, $this->manual())->assertSessionHasNoErrors();
        $this->saveProfile($other, $this->manual('  REBECCA   ESCOTE  '))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('program_staff', 2);
        $this->assertDatabaseCount('midwife_profiles', 1);
        $this->assertEquals($nurse->fresh()->assigned_midwife_id, $other->fresh()->assigned_midwife_id);
        $this->assertSame('Rebecca Escote', MidwifeProfile::first()->full_name);
    }

    public function test_manual_entry_matching_registered_midwife_reuses_the_registered_identity(): void
    {
        $nurse = $this->staff('Miguel', 'Nurse');
        $midwife = $this->staff('Rebecca', 'Midwife');
        $this->saveProfile($nurse, $this->manual('rebecca escote'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('midwife_profiles', 1);
        $this->assertEquals($midwife->id, $nurse->fresh()->assignedMidwife->program_staff_id);
    }

    public function test_invalid_or_cross_facility_assignment_rolls_back_the_whole_profile_save(): void
    {
        $nurse = $this->staff('Miguel', 'Nurse');
        $midwife = $this->staff('Rebecca', 'Midwife');
        $midwife->update(['assigned_facility' => 'Another Health Center']);
        $this->saveProfile($nurse, ['midwife_selection' => 'staff:'.$midwife->id, 'max_appointments_per_day' => 3])
            ->assertSessionHasErrors('midwife_selection');
        $this->assertEquals(8, $nurse->fresh()->max_appointments_per_day);
        $this->assertNull($nurse->fresh()->assigned_midwife_id);
        $this->assertDatabaseCount('midwife_profiles', 0);

        $this->saveProfile($nurse, ['midwife_selection' => 'staff:'.$nurse->id])->assertSessionHasErrors('midwife_selection');
        $this->saveProfile($nurse, ['midwife_selection' => 'profile:99999'])->assertSessionHasErrors('midwife_selection');
    }

    public function test_booking_saves_and_renders_care_team_and_keeps_it_when_profiles_change(): void
    {
        $nurse = $this->staff('Miguel', 'Nurse');
        $midwife = $this->staff('Rebecca', 'Midwife');
        $this->saveProfile($nurse, ['midwife_selection' => 'staff:'.$midwife->id])->assertSessionHasNoErrors();
        $mother = $this->mother();
        $this->book($mother, $this->slot($nurse))->assertCreated()
            ->assertJsonPath('appointment.care_team.midwife.name', 'Rebecca Escote')
            ->assertJsonPath('appointment.care_team.worker_name', 'Miguel Escote');
        $appointment = Appointment::firstOrFail();
        $snapshot = $appointment->care_team_snapshot;

        $this->saveProfile($nurse, ['midwife_selection' => '', 'assigned_facility' => 'New Facility'])->assertSessionHasNoErrors();
        $midwife->update(['first_name' => 'Changed']);
        $this->assertSame($snapshot, $appointment->fresh()->care_team_snapshot);
        $page = $this->withSession($this->sessionFor($mother))->get(route('mother.clinic-schedule.index'));
        $page->assertOk()->assertSee('Assigned Healthcare Worker')->assertSee('Assigned Midwife')
            ->assertSee('Rebecca Escote')->assertSee('Barangay San Diego Health Center')
            ->assertSee('appointment-'.$appointment->id);
        $this->exportFixture('mother', $page->getContent());
    }

    public function test_unavailable_midwife_is_not_assigned_to_a_new_appointment(): void
    {
        $nurse = $this->staff('Miguel', 'Nurse');
        $this->saveProfile($nurse, [...$this->manual(), 'midwife_availability_status' => 'unavailable'])->assertSessionHasNoErrors();
        $mother = $this->mother();
        $this->book($mother, $this->slot($nurse))->assertCreated()->assertJsonPath('appointment.care_team.midwife', null);
        $this->assertNull(Appointment::first()->midwife_profile_id);
        $this->withSession($this->sessionFor($mother))->get(route('mother.clinic-schedule.index'))
            ->assertOk()->assertDontSee('Assigned Midwife')->assertDontSee('Rebecca Escote');
    }

    public function test_midwife_who_moves_facility_is_not_assigned_to_future_bookings(): void
    {
        $nurse = $this->staff('Miguel', 'Nurse');
        $midwife = $this->staff('Rebecca', 'Midwife');
        $this->saveProfile($nurse, ['midwife_selection' => 'staff:'.$midwife->id])->assertSessionHasNoErrors();
        $midwife->update(['assigned_facility' => 'Different Center']);
        $this->assertEquals($midwife->healthcare_facility_id, $midwife->midwifeProfile->healthcare_facility_id);
        $this->book($this->mother(), $this->slot($nurse))->assertCreated()->assertJsonPath('appointment.care_team.midwife', null);
    }

    public function test_manual_record_owner_can_change_availability_but_other_workers_cannot(): void
    {
        $nurse = $this->staff('Miguel', 'Nurse');
        $other = $this->staff('Other', 'Nurse');
        $this->saveProfile($nurse, $this->manual())->assertSessionHasNoErrors();
        $selection = 'profile:'.$nurse->fresh()->assigned_midwife_id;
        $this->saveProfile($other, ['midwife_selection' => $selection, 'existing_midwife_availability_status' => 'unavailable'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(MidwifeProfile::first()->is_available);
        $this->saveProfile($nurse, ['midwife_selection' => $selection, 'existing_midwife_availability_status' => 'unavailable'])
            ->assertSessionHasNoErrors();
        $this->assertFalse(MidwifeProfile::first()->is_available);
    }

    public function test_staff_created_appointments_also_save_the_midwife(): void
    {
        $nurse = $this->staff('Miguel', 'Nurse');
        $this->saveProfile($nurse, $this->manual())->assertSessionHasNoErrors();
        $mother = $this->mother();
        StaffMotherCasefile::create(['staff_id' => $nurse->id, 'mother_id' => $mother->id]);
        $slot = $this->slot($nurse);
        $this->withSession($this->sessionFor($nurse))->postJson(route('staff.clinic-schedule.store'), [
            'mother_id' => $mother->id, 'staff_availability_id' => $slot->id,
            'appointment_date' => now()->addWeek()->startOfWeek()->toDateString(),
            'appointment_type' => 'prenatal_checkup', 'meeting_type' => 'in_person',
            'start_time' => '09:00', 'end_time' => '10:00',
        ])->assertCreated()->assertJsonPath('appointment.care_team.midwife.name', 'Rebecca Escote');
    }

    public function test_old_appointments_are_not_retroactively_assigned_a_midwife(): void
    {
        $nurse = $this->staff('Miguel', 'Nurse');
        $mother = $this->mother();
        Appointment::create([
            'mother_id' => $mother->id, 'staff_id' => $nurse->id,
            'appointment_date' => now()->addWeek()->toDateString(), 'appointment_type' => 'prenatal_checkup',
            'meeting_type' => 'in_person', 'start_time' => '09:00', 'end_time' => '10:00', 'status' => 'pending',
            'created_by_id' => $nurse->id, 'created_by_role' => 'program_staff',
        ]);
        $this->saveProfile($nurse, $this->manual())->assertSessionHasNoErrors();
        $this->withSession($this->sessionFor($mother))->get(route('mother.clinic-schedule.index'))
            ->assertOk()->assertDontSee('Assigned Midwife')->assertDontSee('Rebecca Escote');
        $this->assertNull(Appointment::first()->care_team_snapshot);
    }

    public function test_mother_cannot_change_staff_scheduling_profile(): void
    {
        $nurse = $this->staff('Miguel', 'Nurse');
        $this->withSession($this->sessionFor($this->mother()))
            ->patch(route('staff.clinic-schedule.profile.update'), $this->manual())
            ->assertRedirect(route('login'));
        $this->assertNull($nurse->fresh()->assigned_midwife_id);
    }

    public function test_profile_form_and_facility_identity_handle_spacing_and_case(): void
    {
        $nurse = $this->staff('Miguel', 'Nurse');
        $midwife = $this->staff('Rebecca', 'Midwife');
        $midwife->update(['assigned_facility' => ' barangay  SAN DIEGO health center ', 'assigned_barangay' => 'san diego']);
        $this->assertEquals($nurse->healthcare_facility_id, $midwife->healthcare_facility_id);
        $this->saveProfile($nurse, ['midwife_selection' => 'staff:'.$midwife->id])->assertSessionHasNoErrors();
        $page = $this->withSession($this->sessionFor($nurse))->get(route('staff.clinic-schedule.index'));
        $page->assertOk()->assertSee('Midwife full name')->assertSee('Contact number (optional)')->assertSee('Availability status');
        $this->exportFixture('staff', $page->getContent());
    }

    private function staff(string $name, string $role): ProgramStaff
    {
        return ProgramStaff::create([
            'first_name' => $name, 'last_name' => 'Escote', 'email' => strtolower($name).'@example.test',
            'password' => 'unused', 'staff_id' => 'TEST-'.$name, 'role' => $role, 'position' => $role,
            'contact_number' => '09171111111', 'approval_status' => 'approved',
            'assigned_barangay' => 'San Diego', 'assigned_facility' => 'Barangay San Diego Health Center',
            'accepting_appointments' => true, 'max_appointments_per_day' => 8,
        ]);
    }

    private function mother(): Mother
    {
        return Mother::create([
            'first_name' => 'Maria', 'last_name' => 'Reyes', 'email' => 'mother@example.test',
            'password' => 'unused', 'barangay' => 'San Diego', 'contact_number' => '09170000000',
        ]);
    }

    private function slot(ProgramStaff $staff): StaffAvailability
    {
        return StaffAvailability::create([
            'staff_id' => $staff->id, 'day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '10:00',
            'appointment_type' => 'prenatal_checkup', 'meeting_type' => 'in_person', 'is_active' => true,
        ]);
    }

    private function sessionFor(ProgramStaff|Mother $person): array
    {
        return ['auth_id' => $person->id, 'auth_role' => $person instanceof Mother ? 'mother' : 'staff'];
    }

    private function manual(string $name = 'Rebecca Escote'): array
    {
        return [
            'midwife_selection' => 'new', 'midwife_full_name' => $name,
            'midwife_barangay' => 'San Diego', 'midwife_facility' => 'Barangay San Diego Health Center',
            'midwife_availability_status' => 'available',
        ];
    }

    private function saveProfile(ProgramStaff $staff, array $values)
    {
        return $this->withSession($this->sessionFor($staff))->patch(route('staff.clinic-schedule.profile.update'), [
            'assigned_barangay' => 'San Diego', 'assigned_facility' => 'Barangay San Diego Health Center',
            'max_appointments_per_day' => 8, 'accepting_appointments' => 1, ...$values,
        ]);
    }

    private function book(Mother $mother, StaffAvailability $slot)
    {
        return $this->withSession($this->sessionFor($mother))->postJson(route('mother.clinic-schedule.store'), [
            'staff_availability_id' => $slot->id, 'appointment_date' => now()->addWeek()->startOfWeek()->toDateString(),
            'appointment_type' => 'prenatal_checkup',
        ]);
    }

    private function exportFixture(string $name, string $html): void
    {
        if (getenv('INAY_CARE_TEAM_FIXTURES') !== '1') {
            return;
        }
        $directory = storage_path('framework/testing/care-team');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        file_put_contents($directory.'/'.$name.'.html', $html);
    }
}

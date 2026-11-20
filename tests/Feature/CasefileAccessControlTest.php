<?php

namespace Tests\Feature;

use App\Models\{AdminUser, Conversation, DswdStaff, Infant, Mother, ProgramStaff, StaffMotherCasefile};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CasefileAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role = 'Program Staff'): ProgramStaff
    {
        return ProgramStaff::create(['first_name' => 'Fictional', 'last_name' => 'Clinician', 'email' => uniqid().'@example.test', 'password' => 'unused', 'staff_id' => uniqid(), 'position' => $role, 'role' => $role, 'approval_status' => 'approved', 'contact_number' => '09000000000']);
    }

    private function mother(string $name): Mother
    {
        return Mother::create(['first_name' => $name, 'last_name' => 'Synthetic', 'email' => uniqid().'@example.test', 'password' => 'unused', 'barangay' => 'Test', 'contact_number' => '09000000000']);
    }

    private function signIn(ProgramStaff $staff): void
    {
        $this->withSession(['auth_role' => 'staff', 'auth_id' => $staff->id]);
    }

    public function test_staff_and_bhw_cannot_self_assign_or_escalate_by_form_fields(): void
    {
        $mother = $this->mother('Private');
        foreach (['Program Staff', 'Barangay Health Worker', 'Physician'] as $role) {
            $staff = $this->staff($role);
            $this->signIn($staff);
            $this->post('/staff/mothers', ['mother_ids' => [$mother->id], 'role' => 'admin', 'admin_authenticated' => true])->assertForbidden();
            $this->get('/staff/mothers/'.$mother->id)->assertForbidden();
            $this->post('/admin/program-staff/'.$staff->id.'/casefiles', ['mother_ids' => [$mother->id]])->assertForbidden();
            $this->assertDatabaseMissing('staff_mother_casefiles', ['staff_id' => $staff->id, 'mother_id' => $mother->id]);
        }
    }

    public function test_only_clinical_admins_can_approve_assignments(): void
    {
        $staff = $this->staff();
        $mother = $this->mother('Assigned');
        foreach (['admin_dswd', 'admin_cho', 'admin'] as $role) {
            $admin = AdminUser::create(['username' => 'synthetic-'.$role, 'password' => 'synthetic-password', 'role' => $role]);
            $this->withSession(['admin_authenticated' => true, 'admin_id' => $admin->id]);
            $response = $this->post('/admin/program-staff/'.$staff->id.'/casefiles', ['mother_ids' => [$mother->id], 'role' => 'admin']);
            $role === 'admin_dswd' ? $response->assertForbidden() : $response->assertRedirect();
        }
        $this->assertDatabaseCount('staff_mother_casefiles', 1);
        $this->signIn($staff);
        $this->withSession(['admin_authenticated' => false]);
        $this->get('/staff/mothers/'.$mother->id)->assertOk();
    }

    public function test_staff_lists_and_both_csv_exports_only_include_assigned_families(): void
    {
        $staff = $this->staff('Barangay Health Worker');
        $own = $this->mother('AllowedUnique');
        $other = $this->mother('ForbiddenUnique');
        $staff->casefileMothers()->attach($own);
        Infant::create(['mother_id' => $other->id, 'full_name' => 'ForbiddenChild', 'sex' => 'female', 'birth_date' => today()]);
        $this->signIn($staff);
        $this->get('/staff/mothers')->assertOk()->assertSee('AllowedUnique')->assertDontSee('ForbiddenUnique');
        foreach (['csv', 'all_csv'] as $format) {
            $csv = $this->get('/staff/mothers?export='.$format.'&mother_id='.$other->id)->assertOk()->streamedContent();
            $this->assertStringContainsString('AllowedUnique', $csv);
            $this->assertStringNotContainsString('ForbiddenUnique', $csv);
            $this->assertStringNotContainsString('ForbiddenChild', $csv);
        }
        $csv = $this->get('/staff/mothers?export=csv&q=ForbiddenUnique')->assertOk()->streamedContent();
        $this->assertStringNotContainsString('ForbiddenUnique', $csv);
    }

    public function test_unassigned_print_pdf_vitals_and_updates_are_forbidden(): void
    {
        $staff = $this->staff();
        $mother = $this->mother('OtherStaffPatient');
        $this->staff()->casefileMothers()->attach($mother);
        $child = Infant::create(['mother_id' => $mother->id, 'full_name' => 'Synthetic Child', 'sex' => 'female', 'birth_date' => today()]);
        $this->signIn($staff);
        foreach (['print', 'pdf'] as $format) {
            $this->get('/staff/mothers/'.$mother->id.'/'.$format)->assertForbidden();
            $this->get('/staff/neonatal/'.$child->id.'/'.$format)->assertForbidden();
        }
        $this->getJson('/api/program-staff/mothers/'.$mother->id.'/maternal-vitals')->assertForbidden();
        $this->patch('/staff/mothers/'.$mother->id, ['first_name' => 'Tampered'])->assertForbidden();
        $this->assertSame('OtherStaffPatient', $mother->fresh()->first_name);
    }

    public function test_consultation_loading_does_not_recreate_revoked_assignments(): void
    {
        $staff = $this->staff();
        $mother = $this->mother('Revoked');
        $conversation = Conversation::create(['mother_id' => $mother->id, 'program_staff_id' => $staff->id]);
        \App\Models\Appointment::create(['mother_id' => $mother->id, 'staff_id' => $staff->id, 'appointment_type' => 'Prenatal Checkup', 'meeting_type' => 'in_person', 'appointment_date' => today(), 'start_time' => '08:00', 'end_time' => '09:00', 'created_by_id' => $mother->id, 'created_by_role' => 'mother']);
        $this->signIn($staff);
        $this->getJson('/consultation/conversations')->assertOk();
        $this->getJson('/consultation/conversations/'.$conversation->id.'/messages')->assertForbidden();
        $this->assertDatabaseCount('staff_mother_casefiles', 0);
        $this->withSession(['auth_role' => 'mother', 'auth_id' => $mother->id]);
        $this->getJson('/consultation/conversations')->assertOk();
        $this->assertDatabaseCount('staff_mother_casefiles', 0);
    }

    public function test_dswd_and_mother_cannot_use_clinical_export_or_assignment_endpoint(): void
    {
        $staff = $this->staff();
        $mother = $this->mother('Family');
        $dswd = DswdStaff::create(['name' => 'Synthetic Officer', 'email' => 'officer@example.test', 'password' => 'synthetic-password', 'is_active' => true]);
        $this->withSession(['auth_role' => 'dswd_staff', 'auth_id' => $dswd->id]);
        $this->get('/staff/mothers?export=all_csv')->assertForbidden();
        $this->post('/staff/mothers', ['mother_ids' => [$mother->id]])->assertForbidden();
        $this->get('/dswd/reports/download')->assertOk();
        $this->withSession(['auth_role' => 'mother', 'auth_id' => $mother->id]);
        $this->post('/staff/mothers', ['mother_ids' => [$mother->id]])->assertForbidden();
        $this->get('/staff/mothers?export=all_csv')->assertRedirect('/login');
        $this->assertDatabaseCount('staff_mother_casefiles', 0);
    }
}

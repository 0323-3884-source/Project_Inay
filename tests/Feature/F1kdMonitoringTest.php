<?php

namespace Tests\Feature;

use App\Models\DswdStaff;
use App\Models\F1kdMonitoring;
use App\Models\Infant;
use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Models\StaffMotherCasefile;
use App\Support\F1kdCompliance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class F1kdMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function mother(array $attributes = []): Mother
    {
        return Mother::create($attributes + ['first_name'=>'Ana', 'last_name'=>'Cruz', 'email'=>uniqid().'@test.example', 'password'=>'password', 'contact_number'=>'09170000000', 'barangay'=>'Alpha', 'municipality_city'=>'Town', 'is_4ps_beneficiary'=>true, 'pregnancy_status'=>'pregnant', 'blood_type'=>'SECRET']);
    }

    private function dswd(): void
    {
        $staff = DswdStaff::create(['name'=>'Officer', 'email'=>'dswd@test.example', 'password'=>'password']);
        $this->withSession(['auth_role'=>'dswd_staff', 'auth_id'=>$staff->id]);
    }

    private function staff(Mother $mother): ProgramStaff
    {
        $staff = ProgramStaff::create(['first_name'=>'Nurse', 'last_name'=>'Cruz', 'email'=>uniqid().'@test.example', 'password'=>'password', 'staff_id'=>uniqid(), 'position'=>'Program Staff', 'role'=>'Nurse', 'contact_number'=>'09170000000', 'approval_status'=>'approved']);
        StaffMotherCasefile::create(['staff_id'=>$staff->id, 'mother_id'=>$mother->id]);
        $this->withSession(['auth_role'=>'staff', 'auth_id'=>$staff->id]);
        return $staff;
    }

    public function test_roster_counts_individuals_and_defaults_to_verification_without_clinical_data(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 2));
        $mother = $this->mother();
        $other = $this->mother(['is_4ps_beneficiary'=>false]);
        foreach (['2026-10-02','2024-09-03','2024-09-02','2026-10-03'] as $date) Infant::create(['mother_id'=>$mother->id,'full_name'=>'Child '.$date,'sex'=>'male','birth_date'=>$date,'notes'=>'PRIVATE NOTES']);
        Infant::create(['mother_id'=>$other->id,'full_name'=>'Excluded child','birth_date'=>'2026-10-01']);
        $this->dswd();
        $this->get('/dswd/dashboard')->assertOk()->assertViewHas('summary', fn ($s) => $s['total']===3 && $s['children']===2 && $s['verification']===3 && $s['compliant']===0);
        $response = $this->get('/dswd/f1kd/mother-'.$mother->id)->assertOk()->assertDontSee('SECRET')->assertDontSee('PRIVATE NOTES')->assertDontSee($mother->email);
        $this->assertFalse(property_exists($response->viewData('beneficiary'), 'blood_type'));
        $this->get('/dswd/f1kd?classification=child')->assertOk()->assertViewHas('beneficiaries', fn ($p) => $p->total()===2);
        $this->get('/dswd/f1kd?month=2026-09')->assertOk()->assertViewHas('beneficiaries', fn ($p) => $p->total()===0);
        $this->get('/dswd/f1kd/mother-'.$other->id)->assertNotFound();
        $this->get('/dswd/f1kd?month=invalid')->assertSessionHasErrors('month');
        $this->travelBack();
    }

    public function test_only_approved_assigned_staff_can_update_and_history_is_retained(): void
    {
        $mother = $this->mother();
        $subject = 'mother-'.$mother->id;
        $url = '/staff/f1kd/'.$subject;
        $checklist = array_fill_keys(array_keys(F1kdCompliance::MATERNAL), 'compliant');
        $this->get($url)->assertForbidden();
        $this->dswd();
        $this->put($url, ['checklist'=>$checklist])->assertForbidden();
        $staff = $this->staff($mother);
        $this->get($url)->assertOk();
        $this->put($url, ['checklist'=>$checklist])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('f1kd_monitorings', ['subject_key'=>$subject,'status'=>'compliant']);
        $this->put($url, ['checklist'=>['prenatal'=>'compliant']])->assertSessionHasErrors('checklist.immunization');
        $previous = now()->startOfMonth()->subMonth()->format('Y-m-d');
        $record = F1kdMonitoring::first()->replicate();
        $record->reporting_month=$previous; $record->save();
        $other = $this->mother();
        $this->put('/staff/f1kd/mother-'.$other->id, ['checklist'=>$checklist])->assertForbidden();
        $staff->update(['approval_status'=>'rejected']);
        $this->put($url, ['checklist'=>$checklist])->assertForbidden();
        $this->withSession(['auth_role'=>'mother','auth_id'=>$mother->id])->put($url, ['checklist'=>$checklist])->assertForbidden();
        $this->withSession(['auth_role'=>'dswd_staff','auth_id'=>DswdStaff::first()->id]);
        $this->get('/dswd/f1kd/'.$subject)->assertOk()->assertSee(substr($previous,0,7));
        $mother->update(['pregnancy_status'=>'postpartum']);
        $this->get('/dswd/f1kd/'.$subject.'?month='.substr($previous,0,7))->assertOk();
    }

    public function test_status_filters_pagination_and_safe_aggregate_exports(): void
    {
        $mother = $this->mother(['barangay'=>'=2+2']);
        $this->staff($mother);
        $checklist = array_fill_keys(array_keys(F1kdCompliance::MATERNAL), 'not_applicable');
        $checklist['prenatal']='verification'; $checklist['service_unavailable']='unavailable';
        $this->put('/staff/f1kd/mother-'.$mother->id, ['checklist'=>$checklist])->assertSessionHasNoErrors();
        $this->dswd();
        $this->get('/dswd/f1kd?status=unavailable')->assertOk()->assertViewHas('beneficiaries', fn ($p) => $p->total()===1);
        $this->get('/dswd/f1kd?q=INAY-'.str_pad($mother->id,5,'0',STR_PAD_LEFT))->assertOk()->assertSee('Ana');
        $response=$this->get('/dswd/f1kd/reports/download?barangay=%3D2%2B2')->assertOk()->assertDownload();
        $csv=$response->streamedContent();
        $this->assertStringContainsString("'=2+2",$csv);
        $this->assertStringNotContainsString('Ana',$csv);
        $this->assertStringNotContainsString('SECRET',$csv);
        for ($i=0;$i<16;$i++) $this->mother();
        $this->get('/dswd/f1kd')->assertOk()->assertViewHas('beneficiaries', fn ($p) => $p->count()===15 && $p->total()===17)->assertSee('Next');
        $this->get('/dswd/f1kd/reports')->assertOk()->assertSee('Print report');
    }
}

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
        $staff = DswdStaff::firstOrCreate(['email'=>'dswd@test.example'], ['name'=>'Officer', 'password'=>'password']);
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
        $attendance = ['month'=>now()->format('Y-m'), 'attendance_status'=>'attended'];
        $this->get($url)->assertForbidden();
        $this->dswd();
        $this->put($url, $attendance)->assertForbidden();
        $staff = $this->staff($mother);
        $this->get($url)->assertOk();
        $this->put($url, $attendance)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('f1kd_monitorings', ['subject_key'=>$subject,'status'=>'compliant']);
        $this->put($url, ['month'=>now()->format('Y-m')])->assertSessionHasErrors('attendance_status');
        $previous = now()->startOfMonth()->subMonth()->format('Y-m-d');
        $record = F1kdMonitoring::first()->replicate();
        $record->reporting_month=$previous; $record->save();
        $other = $this->mother();
        $this->put('/staff/f1kd/mother-'.$other->id, $attendance)->assertForbidden();
        $staff->update(['approval_status'=>'rejected']);
        $this->put($url, $attendance)->assertForbidden();
        $this->withSession(['auth_role'=>'mother','auth_id'=>$mother->id])->put($url, $attendance)->assertForbidden();
        $this->withSession(['auth_role'=>'dswd_staff','auth_id'=>DswdStaff::first()->id]);
        $this->get('/dswd/f1kd/'.$subject)->assertOk()->assertSee('Monthly attendance history');
        $mother->update(['pregnancy_status'=>'postpartum']);
        $this->get('/dswd/f1kd/'.$subject.'?month='.substr($previous,0,7))->assertOk();
    }

    public function test_status_filters_pagination_and_safe_aggregate_exports(): void
    {
        $mother = $this->mother(['barangay'=>'=2+2']);
        $this->staff($mother);
        $this->put('/staff/f1kd/mother-'.$mother->id, ['month'=>now()->format('Y-m'), 'attendance_status'=>'did_not_attend', 'remark_code'=>'service_unavailable'])->assertSessionHasNoErrors();
        $this->dswd();
        $this->get('/dswd/f1kd?status=non_compliant')->assertOk()->assertViewHas('beneficiaries', fn ($p) => $p->total()===1);
        $this->get('/dswd/f1kd?q=INAY-'.str_pad($mother->id,5,'0',STR_PAD_LEFT))->assertOk()->assertSee('Ana');
        $response=$this->get('/dswd/f1kd/reports/download?barangay=%3D2%2B2')->assertOk()->assertDownload();
        $csv=$response->streamedContent();
        $this->assertStringContainsString("'=2+2",$csv);
        $this->assertStringNotContainsString('Ana',$csv);
        $this->assertStringNotContainsString('SECRET',$csv);
        for ($i=0;$i<16;$i++) $this->mother();
        $this->get('/dswd/f1kd')->assertOk()->assertViewHas('beneficiaries', fn ($p) => $p->count()===15 && $p->total()===17)->assertSee('Next');
        $this->get('/dswd/f1kd/reports')->assertOk()->assertSee('Print report')
            ->assertSee('Monthly 4Ps Reports')->assertSee('Monthly beneficiary attendance')
            ->assertSee('View / Verify')->assertViewHas('beneficiaries', fn ($p) => $p->count()===15 && $p->total()===17);
        $this->get('/dswd/dashboard')->assertOk()->assertSee('Monthly 4Ps Overview')
            ->assertSee('name="month"', false)->assertSee('Maternal Monitoring')->assertSee('Monthly 4Ps Reports');
    }

    public function test_attendance_updates_one_record_and_preserves_other_periods_and_legacy_checklist(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 5));
        $mother = $this->mother();
        $staff = $this->staff($mother);
        $subject = 'mother-'.$mother->id;
        $url = '/staff/f1kd/'.$subject;
        $legacy = array_fill_keys(array_keys(F1kdCompliance::MATERNAL), 'compliant');
        $record = F1kdMonitoring::create([
            'subject_key'=>$subject, 'reporting_month'=>'2026-09-01', 'mother_id'=>$mother->id,
            'classification'=>'pregnant', 'barangay'=>'Alpha', 'municipality_city'=>'Town',
            'checklist'=>$legacy, 'status'=>'compliant', 'recorded_by_staff_id'=>$staff->id,
        ]);
        $this->get($url.'?month=2026-09')->assertOk()->assertSee('Not Yet Recorded')->assertDontSee('Compliance checklist');
        $this->assertSame('verification', $record->monthly_status);
        $this->put($url, ['month'=>'2026-09', 'attendance_status'=>'attended'])->assertSessionHasNoErrors();
        $this->assertSame($legacy, $record->fresh()->checklist);
        $this->put($url, ['month'=>'2026-10', 'attendance_status'=>'did_not_attend', 'remark_code'=>'service_unavailable'])->assertSessionHasNoErrors();
        $this->put($url, ['month'=>'2026-10', 'attendance_status'=>'attended'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('f1kd_monitorings', 2);
        $this->assertSame('attended', F1kdMonitoring::where('subject_key', $subject)->whereDate('reporting_month', '2026-09-01')->firstOrFail()->attendance_status);
        $october = F1kdMonitoring::where('subject_key', $subject)->whereDate('reporting_month', '2026-10-01')->firstOrFail();
        $this->assertSame('attended', $october->attendance_status);
        $this->assertNull($october->remark_code);
        $this->get($url.'?month=2026-11')->assertOk()->assertSee('Not Yet Recorded')->assertDontSee('Save Record');
        $this->dswd();
        $this->get('/dswd/f1kd/'.$subject.'?month=2026-09')->assertOk()->assertViewHas('beneficiary', fn ($r)=>$r->attendance_status==='attended')->assertDontSee('Save Record');
        $this->get('/dswd/f1kd/'.$subject.'?month=2026-11')->assertOk()->assertViewHas('beneficiary', fn ($r)=>$r->status==='verification' && $r->attendance_status===null);
        $this->travelBack();
    }

    public function test_attendance_and_period_validation_rejects_invalid_values_without_writing(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 5));
        $mother = $this->mother();
        $this->staff($mother);
        $url = '/staff/f1kd/mother-'.$mother->id;
        $this->put($url, ['month'=>'2026-10', 'attendance_status'=>'compliant'])->assertSessionHasErrors('attendance_status');
        $this->put($url, ['month'=>'2026-10', 'attendance_status'=>'attended', 'remark_code'=>'invalid'])->assertSessionHasErrors('remark_code');
        $this->put($url, ['month'=>'bad', 'attendance_status'=>'attended'])->assertSessionHasErrors('month');
        $this->put($url, ['month'=>'2026-11', 'attendance_status'=>'attended'])->assertSessionHasErrors('month');
        $this->put($url, ['attendance_status'=>'attended'])->assertSessionHasErrors('month');
        $this->put('/staff/f1kd/mother-999999', ['month'=>'2026-10', 'attendance_status'=>'attended'])->assertNotFound();
        $this->assertDatabaseCount('f1kd_monitorings', 0);
        $this->travelBack();
    }

    public function test_searching_a_non_pregnant_4ps_mother_finds_her_eligible_child(): void
    {
        $mother = $this->mother(['first_name'=>'Martha', 'last_name'=>'Isles', 'pregnancy_status'=>'not_pregnant']);
        $child = Infant::create(['mother_id'=>$mother->id, 'full_name'=>'James Juan Dela Cruz', 'birth_date'=>today()->subMonths(2)]);
        Infant::create(['mother_id'=>$mother->id, 'full_name'=>'Adult child', 'birth_date'=>today()->subYears(25)]);
        $this->dswd();
        $this->get('/dswd/f1kd?q=Martha%20Isles')->assertOk()->assertSee('James Juan Dela Cruz')->assertSee('Mother: Martha Isles')
            ->assertViewHas('beneficiaries', fn ($rows)=>$rows->total()===2 && $rows->contains(fn ($row)=>$row->key==='child-'.$child->id));
        $this->get('/dswd/f1kd/child-'.$child->id)->assertOk()->assertSee('Martha Isles');
        $this->get('/dswd/f1kd/mother-'.$mother->id)->assertOk();
    }

    public function test_staff_and_dswd_share_the_same_beneficiary_and_reporting_period(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 5));
        $mother = $this->mother();
        $other = $this->mother(['first_name'=>'Other household']);
        $staff = $this->staff($mother);
        $subject = 'mother-'.$mother->id;
        $child = Infant::create(['mother_id'=>$mother->id, 'full_name'=>'Baby Shared', 'birth_date'=>'2026-06-01']);
        foreach ([$subject, 'child-'.$child->id] as $key) {
            $this->put('/staff/f1kd/'.$key, ['month'=>'2026-09', 'attendance_status'=>'did_not_attend', 'remark_code'=>'service_unavailable'])->assertSessionHasNoErrors();
        }
        $response = $this->get('/staff/mothers/'.$mother->id.'?f1kd_month=2026-09')->assertOk();
        $rows = $response->viewData('f1kdBeneficiaries');
        $this->assertCount(2, $rows);
        $this->assertSame([$mother->id], $rows->pluck('mother_id')->unique()->values()->all());
        $this->assertSame('2026-09', $response->viewData('f1kdPeriod'));
        $this->assertSame('did_not_attend', $rows->firstWhere('key', $subject)->attendance_status);
        $this->get('/staff/f1kd/'.$subject.'?month=2026-09')->assertOk()->assertSee('f1kd_month=2026-09');
        $this->dswd();
        foreach ([$subject, 'child-'.$child->id] as $key) {
            $this->get('/dswd/f1kd/'.$key.'?month=2026-09')->assertOk()
                ->assertViewHas('beneficiary', fn ($row)=>$row->key===$key && $row->month==='2026-09' && $row->attendance_status==='did_not_attend' && $row->remark_code==='service_unavailable');
        }
        $this->withSession(['auth_role'=>'staff', 'auth_id'=>$staff->id]);
        $this->put('/staff/f1kd/'.$subject, ['month'=>'2026-09', 'attendance_status'=>'attended'])->assertSessionHasNoErrors();
        $this->dswd();
        $this->get('/dswd/f1kd/'.$subject.'?month=2026-09')->assertOk()->assertViewHas('beneficiary', fn ($row)=>$row->status==='compliant' && $row->remark_code===null);
        $this->assertDatabaseCount('f1kd_monitorings', 2);
        $this->travelBack();
    }

    public function test_dswd_verifies_shared_record_and_staff_sees_and_updates_it(): void
    {
        $mother = $this->mother(['pregnancy_status'=>'not_pregnant']);
        $staff = $this->staff($mother);
        $subject = 'mother-'.$mother->id;
        $month = now()->format('Y-m');
        $payload = ['month'=>$month,'attendance_status'=>'did_not_attend','remark_code'=>'service_unavailable'];
        $this->put('/staff/f1kd/'.$subject, $payload)->assertSessionHasNoErrors();
        $id = F1kdMonitoring::firstOrFail()->id;
        $this->dswd();
        $this->put('/dswd/f1kd/'.$subject, ['month'=>$month,'attendance_status'=>'attended'])->assertSessionHasNoErrors();
        $record = F1kdMonitoring::firstOrFail();
        $this->assertSame($id, $record->id);
        $this->assertSame($staff->id, $record->recorded_by_staff_id);
        $this->assertNotNull($record->dswd_verified_at);
        $this->assertNotNull($record->verified_by_dswd_staff_id);
        $this->withSession(['auth_role'=>'staff','auth_id'=>$staff->id]);
        $this->get('/staff/f1kd/'.$subject)->assertOk()->assertSee('Assigned Program Staff')->assertSee('DSWD verification')->assertViewHas('beneficiary', fn ($row)=>$row->attendance_status==='attended' && $row->dswd_verified_at!==null);
        $this->get('/staff/mothers/'.$mother->id)->assertOk()->assertSee('Verified by DSWD')->assertSee($staff->full_name);
        $this->put('/staff/f1kd/'.$subject, $payload)->assertSessionHasNoErrors();
        $this->assertNull($record->fresh()->dswd_verified_at);
        $this->put('/dswd/f1kd/'.$subject, $payload)->assertForbidden();
        $this->dswd();
        $other = $this->mother(['is_4ps_beneficiary'=>false]);
        $this->put('/dswd/f1kd/mother-'.$other->id, $payload)->assertNotFound();
        $this->put('/dswd/f1kd/'.$subject, ['month'=>$month,'attendance_status'=>'invalid'])->assertSessionHasErrors('attendance_status');
        $this->assertDatabaseCount('f1kd_monitorings', 1);
    }

    public function test_dswd_can_create_a_shared_month_without_faking_a_program_staff_recorder(): void
    {
        $mother = $this->mother(['pregnancy_status'=>'postpartum']);
        $this->dswd();
        $subject = 'mother-'.$mother->id;
        $month = now()->startOfMonth()->subMonth()->format('Y-m');
        $payload = ['month'=>$month,'attendance_status'=>'attended','remark_code'=>'delivered'];
        $this->put('/dswd/f1kd/'.$subject, $payload)->assertSessionHasNoErrors();
        $record = F1kdMonitoring::firstOrFail();
        $this->assertNull($record->recorded_by_staff_id);
        $this->assertNotNull($record->verified_by_dswd_staff_id);
        $staff = $this->staff($mother);
        $this->get('/staff/f1kd/'.$subject.'?month='.$month)->assertOk()->assertSee('Delivered')
            ->assertViewHas('beneficiary', fn ($row)=>$row->attendance_status==='attended' && $row->dswd_verified_at!==null);
        $this->get('/staff/mothers/'.$mother->id.'?f1kd_month='.$month)->assertOk()->assertViewHas('f1kdBeneficiaries', fn ($rows)=>$rows->count()===1 && $rows->first()->remark_code==='delivered');
        $this->withSession(['auth_role'=>'mother','auth_id'=>$mother->id])->put('/dswd/f1kd/'.$subject, $payload)->assertForbidden();
        $this->dswd();
        DswdStaff::firstOrFail()->update(['is_active'=>false]);
        $this->put('/dswd/f1kd/'.$subject, $payload)->assertRedirect('/login');
        $this->assertDatabaseCount('f1kd_monitorings', 1);
    }

    public function test_child_attendance_is_individual_and_summary_uses_only_attendance(): void
    {
        $mother = $this->mother();
        $this->staff($mother);
        $child = Infant::create(['mother_id'=>$mother->id, 'full_name'=>'Baby Cruz', 'sex'=>'male', 'birth_date'=>today()->subMonths(3)]);
        $month = now()->format('Y-m');
        $this->put('/staff/f1kd/child-'.$child->id, ['month'=>$month, 'attendance_status'=>'did_not_attend'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('f1kd_monitorings', ['subject_key'=>'child-'.$child->id, 'infant_id'=>$child->id, 'status'=>'non_compliant', 'remark_code'=>null]);
        $this->get('/staff/f1kd/child-'.$child->id)->assertOk()->assertSee('Baby Cruz')->assertSee('Did Not Attend');
        $this->dswd();
        $this->get('/dswd/dashboard')->assertOk()->assertViewHas('summary', fn ($s)=>$s['total']===2 && $s['non_compliant']===1 && $s['verification']===1 && $s['compliant']===0);
        $this->get('/dswd/f1kd/child-'.$child->id)->assertOk()->assertSee('Did Not Attend')->assertDontSee('Compliance checklist');
        $this->get('/dswd/f1kd?status=verification')->assertOk()->assertViewHas('beneficiaries', fn ($p)=>$p->total()===1);
        $this->get('/dswd/f1kd/reports/download')->assertOk()->assertDownload();
        $mother->update(['is_4ps_beneficiary'=>false]);
        $this->get('/dswd/f1kd/child-'.$child->id)->assertNotFound();
    }
}

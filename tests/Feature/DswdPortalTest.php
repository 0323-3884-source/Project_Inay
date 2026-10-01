<?php

namespace Tests\Feature;

use App\Http\Controllers\DswdController;
use App\Mail\AccountPasswordReset;
use App\Models\DswdStaff;
use App\Models\Infant;
use App\Models\Mother;
use App\Support\DswdStatistics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DswdPortalTest extends TestCase
{
    use RefreshDatabase;

    private function account(): DswdStaff
    {
        return DswdStaff::create(['name' => 'DSWD Officer', 'email' => 'dswd@example.test', 'password' => 'secure-password']);
    }

    private function signIn(?DswdStaff $account = null): DswdStaff
    {
        $account ??= $this->account();
        $this->withSession(['auth_role' => 'dswd_staff', 'auth_id' => $account->id, 'auth_name' => $account->name]);
        return $account;
    }

    private function mother(array $attributes = []): Mother
    {
        static $index = 0;
        $index++;
        return Mother::create($attributes + ['first_name' => 'Beneficiary', 'last_name' => 'Cruz', 'email' => "mother$index@example.test", 'password' => Hash::make('mother-password'), 'barangay' => 'San Jose', 'municipality_city' => 'San Pablo', 'contact_number' => '09170000000', 'pregnancy_status' => 'pregnant', 'is_4ps_beneficiary' => true, 'blood_type' => 'AB+']);
    }

    private function child(Mother $mother, string $birthDate): void
    {
        Infant::create(['mother_id' => $mother->id, 'full_name' => 'PRIVATE CHILD NAME', 'sex' => 'female', 'birth_date' => $birthDate, 'notes' => 'PRIVATE MEDICAL NOTES']);
    }

    public function test_dswd_login_redirects_and_does_not_accept_credentials_for_other_roles(): void
    {
        $account = $this->account();
        $this->get('/login')->assertOk()->assertSee('DSWD / 4Ps Staff')->assertSee('Login as DSWD / 4Ps Staff');
        foreach (['mother', 'staff'] as $role) {
            $this->post('/login', ['role' => $role, 'email' => $account->email, 'password' => 'secure-password'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['role' => 'dswd_staff', 'email' => $account->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['role' => 'dswd_staff', 'email' => $account->email, 'password' => 'secure-password'])->assertRedirect('/dswd/dashboard')->assertSessionHas('auth_role', 'dswd_staff');
        $this->get('/login')->assertRedirect('/dswd/dashboard');
        $this->get('/dswd/dashboard')->assertOk();
        $this->post('/logout')->assertRedirect('/login')->assertSessionMissing('auth_role');
        $this->get('/dswd/dashboard')->assertRedirect('/login');
        $account->update(['is_active' => false]);
        $this->post('/login', ['role' => 'dswd_staff', 'email' => $account->email, 'password' => 'secure-password'])->assertSessionHasErrors('email');
    }

    public function test_dswd_routes_require_active_role_and_medical_routes_are_forbidden(): void
    {
        $this->get('/dswd/dashboard')->assertRedirect('/login');
        foreach (['mother', 'staff'] as $role) {
            $this->withSession(['auth_role' => $role, 'auth_id' => 1])->get('/dswd/dashboard')->assertForbidden();
        }
        $account = $this->signIn();
        $mother = $this->mother();
        foreach (['/staff/dashboard', '/staff/mothers', '/staff/mothers/'.$mother->id, '/staff/mothers/'.$mother->id.'/pdf', '/staff/neonatal-vaccines', '/staff/clinic-schedule', '/mother/dashboard', '/maternal-monitoring', '/child-health', '/api/program-staff/mothers/'.$mother->id.'/maternal-vitals', '/api/mother/maternal-vitals', '/consultation/conversations', '/staff-coordination/threads', '/admin-staff-messages/threads', '/notifications', '/admin/statistics', '/admin/dswd-staff'] as $path) {
            $this->get($path)->assertForbidden();
        }
        $this->patch('/staff/mothers/'.$mother->id, ['first_name' => 'Changed'])->assertForbidden();
        $this->post('/api/program-staff/mothers/'.$mother->id.'/maternal-vitals', [])->assertForbidden();
        $this->assertSame('Beneficiary', $mother->fresh()->first_name);
        $account->update(['is_active' => false]);
        $this->get('/dswd/statistics')->assertRedirect('/login')->assertSessionMissing('auth_role');
    }

    public function test_location_groups_merge_missing_values_and_sort_equal_totals_by_label(): void
    {
        $this->mother(['barangay' => '', 'municipality_city' => null]);
        $this->mother(['barangay' => '', 'municipality_city' => '']);
        $this->mother(['barangay' => 'Alpha', 'municipality_city' => 'City A']);
        $this->mother(['barangay' => 'Alpha', 'municipality_city' => 'City A']);
        $this->mother(['barangay' => 'Beta', 'municipality_city' => 'City B']);
        $this->mother(['barangay' => 'Excluded', 'municipality_city' => 'Excluded', 'is_4ps_beneficiary' => false]);

        $summary = app(DswdStatistics::class)->summary([]);
        $this->assertSame(['Alpha' => 2, 'Not recorded' => 2, 'Beta' => 1], $summary['groups']['barangay']);
        $this->assertSame(['City A' => 2, 'Not recorded' => 2, 'City B' => 1], $summary['groups']['municipality_city']);
        $filtered = app(DswdStatistics::class)->summary(['municipality_city' => '__unrecorded__']);
        $this->assertSame(['Not recorded' => 2], $filtered['groups']['barangay']);
        $this->assertSame(['Not recorded' => 2], $filtered['groups']['municipality_city']);
    }

    public function test_summary_counts_4ps_only_and_handles_age_boundaries(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 30)->startOfDay());
        $mother = $this->mother();
        $this->mother(['pregnancy_status' => 'not_pregnant']);
        $this->mother(['pregnancy_status' => null]);
        $nonBeneficiary = $this->mother(['is_4ps_beneficiary' => false]);
        foreach (['2026-09-30', '2026-03-01', '2026-02-28', '2025-09-01', '2025-08-30', '2024-08-31', '2024-08-30', '2026-10-01'] as $date) {
            $this->child($mother, $date);
        }
        $this->child($nonBeneficiary, '2026-09-01');
        $this->signIn();
        $summary = $this->get('/dswd/dashboard')->assertOk()->viewData('summary');
        $this->assertSame(3, $summary['total']);
        $this->assertSame(1, $summary['pregnant']);
        $this->assertSame(1, $summary['mothers_with_children']);
        $this->assertSame(6, $summary['children']);
        $this->assertSame([2, 2, 2], array_values($summary['ages']));
        $this->assertSame(1, $summary['pregnancy']['unknown']);
        $this->travelBack();
    }

    public function test_filters_search_pagination_and_profile_allowlist(): void
    {
        $mother = $this->mother(['first_name' => 'VisibleName']);
        $other = $this->mother(['first_name' => 'ExcludedName', 'barangay' => 'Other', 'municipality_city' => null, 'pregnancy_status' => 'not_pregnant']);
        $nonBeneficiary = $this->mother(['first_name' => 'Not4Ps', 'is_4ps_beneficiary' => false]);
        $this->child($mother, today()->toDateString());
        $this->signIn();
        $this->get('/dswd/beneficiaries?q=VisibleName')->assertOk()->assertSee('VisibleName')->assertDontSee('ExcludedName')->assertDontSee('Not4Ps');
        $this->get('/dswd/beneficiaries?q=INAY-'.$mother->id)->assertOk()->assertSee('VisibleName')->assertDontSee('ExcludedName');
        $this->get('/dswd/statistics?barangay=San%20Jose&municipality_city=San%20Pablo&pregnancy_status=pregnant')->assertOk()->assertViewHas('summary', fn ($s) => $s['total'] === 1);
        $this->get('/dswd/statistics?municipality_city=__unrecorded__')->assertOk()->assertViewHas('summary', fn ($s) => $s['total'] === 1);
        $this->get('/dswd/statistics?date_to=2000-01-01')->assertOk()->assertViewHas('summary', fn ($s) => $s['total'] === 0);
        $this->get('/dswd/statistics?date_from=2026-01-02&date_to=2026-01-01')->assertSessionHasErrors('date_to');
        $this->get('/dswd/statistics?pregnancy_status=invalid')->assertSessionHasErrors('pregnancy_status');
        $response = $this->get('/dswd/beneficiaries/'.$mother->id)->assertOk()->assertSee('VisibleName')->assertDontSee('PRIVATE CHILD NAME')->assertDontSee('PRIVATE MEDICAL NOTES')->assertDontSee('AB+')->assertDontSee($mother->email)->assertDontSee('Maternal Monitoring')->assertDontSee('Consultation');
        $attributes = array_keys($response->viewData('beneficiary')->getAttributes());
        $this->assertEqualsCanonicalizing(['id','first_name','middle_name','last_name','barangay','municipality_city','pregnancy_status','created_at','young_children_count'], $attributes);
        $this->get('/dswd/beneficiaries/'.$nonBeneficiary->id)->assertNotFound();
        for ($i = 0; $i < 16; $i++) { $this->mother(); }
        $this->get('/dswd/beneficiaries?barangay=San%20Jose')->assertOk()->assertViewHas('beneficiaries', fn ($p) => $p->count() === 15 && $p->total() === 17)->assertSee('Next');
    }

    public function test_reports_contain_only_aggregate_data_and_export_is_protected(): void
    {
        $mother = $this->mother(['first_name' => 'PRIVATE BENEFICIARY NAME', 'barangay' => '=2+2']);
        $this->signIn();
        foreach (array_keys(DswdController::REPORTS) as $type) {
            $this->get('/dswd/reports?report='.$type)->assertOk()->assertDontSee('PRIVATE BENEFICIARY NAME');
            $response = $this->get('/dswd/reports/download?report='.$type)->assertOk()->assertDownload();
            $csv = $response->streamedContent();
            $this->assertStringNotContainsString($mother->email, $csv);
            $this->assertStringNotContainsString('PRIVATE BENEFICIARY NAME', $csv);
            $this->assertStringNotContainsString('AB+', $csv);
            if ($type === 'barangay') { $this->assertStringContainsString("'=2+2", $csv); }
        }
        $this->get('/dswd/reports?report=invalid')->assertSessionHasErrors('report');
        $this->withSession(['auth_role' => 'staff'])->get('/dswd/reports/download')->assertForbidden();
    }

    public function test_admin_can_provision_and_revoke_accounts_and_dswd_can_evaluate_and_update_profile(): void
    {
        $this->withSession(['admin_authenticated' => true, 'admin_id' => 1])->get('/admin/dswd-staff')->assertOk();
        $this->post('/admin/dswd-staff', ['name'=>'Officer', 'email'=>'officer@example.test', 'password'=>'secure-password', 'password_confirmation'=>'secure-password'])->assertSessionHasNoErrors();
        $account = DswdStaff::firstOrFail();
        $this->assertTrue(Hash::check('secure-password', $account->password));
        $this->patch('/admin/dswd-staff/'.$account->id, ['is_active'=>false])->assertSessionHasNoErrors();
        $this->assertFalse($account->fresh()->is_active);
        $this->patch('/admin/dswd-staff/'.$account->id, ['is_active'=>true]);
        $this->flushSession();
        $this->signIn($account);
        $this->get('/dswd/profile')->assertOk();
        $this->patch('/dswd/profile', ['name'=>'Officer Updated', 'office'=>'City Office'])->assertSessionHasNoErrors();
        $this->patch('/dswd/profile', ['name'=>'Officer Updated', 'password'=>'new-password', 'password_confirmation'=>'new-password', 'current_password'=>'wrong'])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('secure-password', $account->fresh()->password));
        $this->post('/dswd/evaluation', ['rating'=>5,'feedback'=>'Clear navigation'])->assertSessionHasNoErrors();
        $this->get('/dswd/evaluation')->assertOk()->assertSee('Clear navigation');
        $this->assertDatabaseHas('dswd_evaluations', ['dswd_staff_id'=>$account->id,'rating'=>5]);
        $this->post('/dswd/evaluation', ['rating'=>9])->assertSessionHasErrors('rating');
    }

    public function test_password_recovery_supports_dswd_and_tokens_are_role_scoped(): void
    {
        Mail::fake();
        $account=$this->account();
        $this->get('/forgot-password?role=dswd_staff')->assertOk()->assertSee('DSWD / 4Ps Staff');
        $this->post('/forgot-password', ['role'=>'dswd_staff','email'=>$account->email])->assertSessionHasNoErrors();
        $mail=Mail::sent(AccountPasswordReset::class)->first();
        $this->assertSame('dswd_staff', $mail->role);
        $this->get($mail->resetUrl)->assertOk()->assertSee('DSWD / 4Ps Staff');
        $payload=['role'=>'dswd_staff','email'=>$account->email,'token'=>basename(parse_url($mail->resetUrl, PHP_URL_PATH)),'password'=>'replacement-password','password_confirmation'=>'replacement-password'];
        $this->post('/reset-password', array_merge($payload,['role'=>'staff']))->assertSessionHasErrors('token');
        $this->post('/reset-password', $payload)->assertRedirect('/login');
        $this->assertTrue(Hash::check('replacement-password', $account->fresh()->password));
        $this->post('/reset-password', $payload)->assertSessionHasErrors('token');
    }

    public function test_journey_shows_document_links_without_exposing_storage_paths(): void
    {
        $mother = $this->mother();
        $other = $this->mother();
        foreach ([$mother, $other] as $owner) {
            \App\Models\InayKaalamanUpload::create([
                'mother_id' => $owner->id, 'month' => $owner->id === $mother->id ? 2 : 3,
                'record_type' => 'Prescription', 'original_name' => 'private-diagnosis.pdf',
                'path' => 'private/secret-record.pdf', 'mime_type' => 'application/pdf', 'size' => 200,
            ]);
        }
        \App\Models\InayKaalamanProgress::create([
            'mother_id' => $mother->id, 'month' => 2, 'activity_type' => 'video',
            'item_key' => 'video-1', 'item_title' => 'Private activity title', 'status' => 'watched',
        ]);
        $this->signIn();
        $response = $this->get('/dswd/beneficiaries/'.$mother->id)->assertOk()
            ->assertSee('Pregnancy Journey')->assertSee('Learning &amp; Documents', false)
            ->assertSee('Month 9')->assertSee('1 file(s) received')
            ->assertSee('private-diagnosis.pdf')->assertDontSee('secret-record.pdf')
            ->assertSee('Prescription')->assertSee('View document')->assertSee('dswd-document-dialog')->assertDontSee('Private activity title');
        $this->assertSame([2], $response->viewData('journeyUploads')->keys()->all());
        $this->assertSame(['month', 'total', 'last_received'], array_keys((array) $response->viewData('journeyUploads')->get(2)));
        $this->assertSame(['month', 'activity_type', 'status'], array_keys((array) $response->viewData('journeyLearning')->get(2)->first()));
    }

    public function test_document_preview_checks_role_ownership_membership_and_actual_file_type(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $mother = $this->mother();
        $other = $this->mother();
        $disk->put('records/example.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
        $upload = \App\Models\InayKaalamanUpload::create(['mother_id' => $mother->id, 'month' => 1, 'record_type' => 'Certificate', 'original_name' => 'example.pdf', 'path' => 'records/example.pdf']);
        $url = '/dswd/beneficiaries/'.$mother->id.'/documents/'.$upload->id.'/preview';
        $this->get($url)->assertRedirect('/login');
        $this->withSession(['auth_role' => 'staff', 'auth_id' => 1])->get($url)->assertForbidden();
        $account = $this->signIn();
        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class, $response->baseResponse);
        $this->assertStringContainsString('%PDF', file_get_contents($response->baseResponse->getFile()->getPathname()));
        $this->withHeader('Range', 'bytes=0-3')->get($url)->assertStatus(206)->assertHeader('Content-Length', '4');
        $this->flushHeaders();
        $this->get('/dswd/beneficiaries/'.$other->id.'/documents/'.$upload->id.'/preview')->assertNotFound();
        $mother->update(['is_4ps_beneficiary' => false]);
        $this->get($url)->assertNotFound();
        $mother->update(['is_4ps_beneficiary' => true]);
        $disk->put('records/example.pdf', '<html><script>alert(1)</script></html>');
        $this->get($url)->assertStatus(415);
        $disk->delete('records/example.pdf');
        $this->get($url)->assertNotFound();
        $account->update(['is_active' => false]);
        $this->get($url)->assertRedirect('/login');
    }
}

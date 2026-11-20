<?php

namespace Tests\Feature;

use App\Models\Mother;
use Database\Seeders\MotherChildDemoSeeder;
use Database\Seeders\MotherChildBulkDemoSeeder;
use Database\Seeders\DemoCasefileAssignmentSeeder;
use App\Models\ProgramStaff;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MotherChildDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_casefiles_appear_for_approved_staff_without_assigning_real_mothers(): void
    {
        $staff = ProgramStaff::create([
            'first_name' => 'Demo', 'last_name' => 'Staff', 'email' => 'demo.staff@example.test',
            'password' => Hash::make('test-password'), 'staff_id' => 'DEMO-STAFF',
            'position' => 'Program Staff', 'approval_status' => 'approved', 'contact_number' => '09000000000',
        ]);
        $other = Mother::create([
            'email' => 'unrelated@example.test', 'first_name' => 'Unrelated', 'last_name' => 'Record',
            'password' => Hash::make('test-password'), 'contact_number' => '09000000000', 'barangay' => 'San Roque',
        ]);
        $this->seed(MotherChildDemoSeeder::class);
        $this->seed(MotherChildBulkDemoSeeder::class);
        $this->seed(DemoCasefileAssignmentSeeder::class);
        $this->seed(DemoCasefileAssignmentSeeder::class);
        $this->assertSame(51, $staff->casefileMothers()->count());
        $this->assertFalse($staff->casefileMothers()->where('mothers.id', $other->id)->exists());
        $this->withSession(['auth_role' => 'staff', 'auth_id' => $staff->id])
            ->get('/staff/mothers')->assertOk()->assertSee('demo.mother.041@example.test')
            ->assertViewHas('mothers', fn ($mothers) => $mothers->total() === 51 && $mothers->count() === 10);
        $this->get('/staff/mothers?page=6')->assertOk()
            ->assertViewHas('mothers', fn ($mothers) => $mothers->total() === 51 && $mothers->count() === 1);
    }

    public function test_fifty_more_demo_families_are_linked_and_reruns_preserve_records(): void
    {
        $this->seed(MotherChildDemoSeeder::class);
        $this->seed(MotherChildBulkDemoSeeder::class);
        $this->seed(MotherChildBulkDemoSeeder::class);
        $mothers = Mother::where('email', 'like', 'demo.mother.%@example.test')->get();
        $this->assertCount(50, $mothers);
        $this->assertSame(51, Mother::count());
        foreach ($mothers as $mother) {
            $this->assertSame(1, $mother->infants()->count());
            $child = $mother->infants()->firstOrFail();
            $this->assertSame(4, $child->growthRecords()->count());
            $this->assertSame(2, $child->vaccineRecords()->count());
            $this->assertSame(3, $mother->maternalMonitoringRecords()->count());
        }
        $this->post('/login', ['role' => 'mother', 'email' => 'demo.mother.050@example.test', 'password' => 'DemoInay2026!'])
            ->assertRedirect('/mother/dashboard');
        $this->get('/child-health')->assertOk()->assertSee('Leo Demo Bautista');
    }

    public function test_demo_family_can_log_in_and_view_its_profiles_and_health_records(): void
    {
        $this->seed(MotherChildDemoSeeder::class);
        $mother = Mother::where('email', 'demo.mother@example.test')->firstOrFail();
        $child = $mother->infants()->firstOrFail();
        $this->seed(MotherChildDemoSeeder::class);
        $this->assertSame(1, $mother->infants()->count());
        $this->assertSame(4, $child->growthRecords()->count());
        $this->assertSame(2, $child->vaccineRecords()->count());
        $this->assertSame(3, $mother->maternalMonitoringRecords()->count());

        $this->post('/login', ['role' => 'mother', 'email' => $mother->email, 'password' => 'DemoInay2026!'])
            ->assertRedirect('/mother/dashboard')->assertSessionHas('auth_id', $mother->id);
        $this->get('/mother/dashboard')->assertOk()->assertSee('Amara');
        $this->get('/mother/profile')->assertOk()->assertSee('Amara');
        $this->get('/child-health?child='.$child->id)->assertOk()->assertSee('Lia Demo Santos');
        $this->get('/maternal-monitoring')->assertOk()->assertSee('112/74');
        $this->getJson('/api/mother/maternal-vitals')->assertOk()->assertJsonCount(3, 'records');
    }
}

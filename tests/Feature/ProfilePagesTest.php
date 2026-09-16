<?php

namespace Tests\Feature;

use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Models\MaternalMonitoringRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_profiles_are_role_scoped_and_only_update_allowed_fields(): void
    {
        $base = ['first_name' => 'Ana', 'last_name' => 'Cruz', 'email' => 'shared@example.test', 'password' => bcrypt('password123'), 'contact_number' => '09171111111'];
        $mother = Mother::create($base + ['barangay' => 'Concepcion']);
        $staff = ProgramStaff::create($base + ['staff_id' => 'PROFILE-1', 'position' => 'Nurse']);
        $this->get('/mother/profile')->assertRedirect('/login');
        foreach (['mother' => $mother, 'staff' => $staff] as $role => $account) {
            $otherRole = $role === 'mother' ? 'staff' : 'mother';
            $this->withSession(['auth_role' => $role, 'auth_id' => $account->id])
                ->get("/$role/profile")->assertOk()->assertSee('Personal Information');
            $this->get("/$otherRole/profile")->assertRedirect('/login');
            $this->patch("/$otherRole/profile", [])->assertRedirect('/login');
            $this->patch("/$role/profile", [
                'first_name' => 'Updated', 'last_name' => 'Cruz', 'contact_number' => '+63 917 222 3333',
                'barangay' => 'San Rafael', 'email' => 'hacked@example.test', 'staff_id' => 'CHANGED',
                'healthcare_worker_id_verified_at' => now(), 'is_4ps_beneficiary' => true,
            ])->assertRedirect("/$role/profile");
            $account->refresh();
            $this->assertSame('Updated', $account->first_name);
            $this->assertSame('shared@example.test', $account->email);
            if ($role === 'staff') {
                $this->assertSame('PROFILE-1', $account->staff_id);
                $this->assertNull($account->healthcare_worker_id_verified_at);
            } else {
                $this->assertSame('San Rafael', $account->barangay);
                $this->assertFalse($account->is_4ps_beneficiary);
                $this->assertSame('Ana', $staff->fresh()->first_name);
            }
            $this->from("/$role/profile")->patch("/$role/profile", ['first_name' => '', 'contact_number' => 'invalid'])
                ->assertSessionHasErrors(['first_name', 'contact_number']);
            $this->assertSame('Updated', $account->fresh()->first_name);
        }
    }

    public function test_mother_dashboard_only_shows_her_records(): void
    {
        $base = ['first_name' => 'Ana', 'last_name' => 'Cruz', 'password' => bcrypt('password123'), 'contact_number' => '09171111111', 'barangay' => 'Concepcion'];
        $mother = Mother::create($base + ['email' => 'dashboard@example.test']);
        $other = Mother::create($base + ['email' => 'other-dashboard@example.test']);
        MaternalMonitoringRecord::create(['mother_id' => $other->id, 'pregnancy_week' => 35, 'recorded_at' => now()]);
        $this->withSession(['auth_role' => 'mother', 'auth_id' => $mother->id])->get('/mother/dashboard')
            ->assertOk()->assertSee('No monitoring records yet')->assertViewHas('monitoringCount', 0);
        $record = MaternalMonitoringRecord::create(['mother_id' => $mother->id, 'pregnancy_week' => 24, 'recorded_at' => now()]);
        $this->get('/mother/dashboard')->assertOk()->assertViewHas('monitoringCount', 1)
            ->assertViewHas('latestRecord', fn ($latest) => $latest->id === $record->id)
            ->assertSee(route('mother.profile.show'));
    }
}

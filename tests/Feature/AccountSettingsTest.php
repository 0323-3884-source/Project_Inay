<?php

namespace Tests\Feature;

use App\Models\Mother;
use App\Models\ProgramStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_require_a_matching_account_and_change_only_its_password(): void
    {
        $base = ['first_name' => 'Ana', 'last_name' => 'Cruz', 'email' => 'shared@example.test', 'password' => Hash::make('old-password'), 'contact_number' => '09171111111'];
        $mother = Mother::create($base + ['barangay' => 'Concepcion']);
        $staff = ProgramStaff::create($base + ['staff_id' => 'SETTINGS-1', 'position' => 'Nurse']);

        foreach (['mother', 'staff'] as $role) {
            $this->get("/$role/settings")->assertRedirect('/login');
            $this->patch("/$role/settings/password")->assertRedirect('/login');
        }

        foreach (['mother' => $mother, 'staff' => $staff] as $role => $account) {
            $otherRole = $role === 'mother' ? 'staff' : 'mother';
            $this->withSession(['auth_role' => $role, 'auth_id' => $account->id])
                ->get("/$role/settings")->assertOk()->assertSee('Change Password');
            $this->get("/$role/profile")->assertSee(route("$role.settings"));
            $this->get("/$otherRole/settings")->assertRedirect('/login');
            $this->patch("/$otherRole/settings/password")->assertRedirect('/login');

            $this->from("/$role/settings")->patch("/$role/settings/password", [
                'current_password' => 'incorrect', 'password' => 'new-password', 'password_confirmation' => 'new-password',
            ])->assertSessionHasErrors('current_password');
            $this->assertTrue(Hash::check('old-password', $account->fresh()->password));
            $this->assertArrayNotHasKey('current_password', session()->getOldInput());
            $this->assertArrayNotHasKey('password', session()->getOldInput());

            $this->patch("/$role/settings/password", [
                'current_password' => 'old-password', 'password' => 'short', 'password_confirmation' => 'different',
            ])->assertSessionHasErrors('password');
            $this->assertTrue(Hash::check('old-password', $account->fresh()->password));

            // Isolate the successful request from the route's intentional rate limit.
            $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
            $this->patch("/$role/settings/password", [
                'current_password' => 'old-password', 'password' => 'new-password', 'password_confirmation' => 'new-password',
                'email' => 'changed@example.test', 'auth_id' => 999,
            ])->assertRedirect("/$role/settings")->assertSessionHas('status', 'Your password has been updated.');
            $this->assertTrue(Hash::check('new-password', $account->fresh()->password));
            $this->assertSame('shared@example.test', $account->fresh()->email);
            if ($role === 'mother') {
                $this->assertTrue(Hash::check('old-password', $staff->fresh()->password));
            }
        }
    }

    public function test_dashboard_describes_a_single_worker_and_preserves_multiple_assignments(): void
    {
        $mother = Mother::create(['first_name' => 'Ana', 'last_name' => 'Cruz', 'email' => 'mother@example.test', 'password' => Hash::make('password'), 'contact_number' => '09171111111', 'barangay' => 'Concepcion']);
        $this->withSession(['auth_role' => 'mother', 'auth_id' => $mother->id])->get('/mother/dashboard')
            ->assertOk()->assertSee('No healthcare worker assigned yet')->assertDontSee('Your Care Team');
        foreach (['Miguel', 'Maria'] as $index => $name) {
            $worker = ProgramStaff::create(['first_name' => $name, 'last_name' => 'Cruz', 'email' => "$name@example.test", 'password' => Hash::make('password'), 'staff_id' => "CARE-$index", 'position' => 'Nurse', 'contact_number' => '09171111111']);
            $mother->casefileStaff()->attach($worker);
            $this->get('/mother/dashboard')->assertOk()->assertSee($index === 0 ? 'Your Healthcare Worker</h2>' : 'Your Healthcare Workers</h2>', false)->assertSee('Miguel Cruz');
        }
        $this->get('/mother/dashboard')->assertSee('Maria Cruz');
    }
}

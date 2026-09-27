<?php

namespace Tests\Feature;

use App\Mail\AccountPasswordReset;
use App\Models\Mother;
use App\Models\ProgramStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function accounts(): array
    {
        $base = ['first_name' => 'Ana', 'last_name' => 'Cruz', 'email' => 'shared@example.test',
            'password' => Hash::make('original-password'), 'contact_number' => '09171111111'];

        return [
            Mother::create($base + ['barangay' => 'Concepcion']),
            ProgramStaff::create($base + ['staff_id' => 'RESET-1', 'position' => 'Nurse']),
        ];
    }

    public function test_reset_links_are_role_scoped_single_use_and_allow_login(): void
    {
        config(['app.url' => 'http://127.0.0.1:8000']);
        Mail::fake();
        [$mother, $staff] = $this->accounts();
        $this->get('/login')->assertOk()->assertSee('Forgot password?');
        $this->get('/forgot-password?role=staff')->assertOk()->assertSee('Send Reset Link');
        foreach (['mother' => $mother, 'staff' => $staff] as $role => $account) {
            $this->from('/forgot-password')->post('/forgot-password', ['role' => $role, 'email' => $account->email])
                ->assertRedirect('/forgot-password')->assertSessionHas('status');
            $mail = Mail::sent(AccountPasswordReset::class, fn ($mail) => $mail->role === $role)->first();
            $this->assertNotNull($mail);
            $this->assertStringStartsWith('http://127.0.0.1:8000/reset-password/', $mail->resetUrl);
            $this->assertTrue($mail->hasTo($account->email));
            $this->get($mail->resetUrl)->assertOk()->assertSee('Set a New Password');
            parse_str(parse_url($mail->resetUrl, PHP_URL_QUERY), $query);
            $token = basename(parse_url($mail->resetUrl, PHP_URL_PATH));
            $this->assertNotSame($token, DB::table('password_reset_tokens')->where('email', $role.':'.hash('sha256', $account->email))->value('token'));
            $payload = $query + ['token' => $token, 'password' => 'replacement-password', 'password_confirmation' => 'replacement-password'];
            $this->post('/reset-password', array_merge($payload, ['role' => $role === 'mother' ? 'staff' : 'mother']))
                ->assertSessionHasErrors('token');
            $this->post('/reset-password', $payload)->assertRedirect('/login');
            $this->assertTrue(Hash::check('replacement-password', $account->fresh()->password));
            if ($role === 'mother') {
                $this->assertTrue(Hash::check('original-password', $staff->fresh()->password));
                $this->post('/login', ['role' => 'mother', 'email' => $mother->email, 'password' => 'replacement-password'])
                    ->assertRedirect('/mother/dashboard');
            }
            $this->post('/reset-password', $payload)->assertSessionHasErrors('token');
        }
    }

    public function test_expired_tokens_and_invalid_passwords_do_not_change_password(): void
    {
        Mail::fake();
        [$mother] = $this->accounts();
        $this->post('/forgot-password', ['role' => 'mother', 'email' => $mother->email]);
        $mail = Mail::sent(AccountPasswordReset::class)->first();
        $payload = ['role' => 'mother', 'email' => $mother->email,
            'token' => basename(parse_url($mail->resetUrl, PHP_URL_PATH)),
            'password' => 'new-password', 'password_confirmation' => 'different-password'];
        $this->post('/reset-password', $payload)->assertSessionHasErrors('password');
        DB::table('password_reset_tokens')->update(['created_at' => now()->subMinutes(61)]);
        $payload['password_confirmation'] = 'new-password';
        $this->post('/reset-password', $payload)->assertSessionHasErrors('token');
        $this->assertTrue(Hash::check('original-password', $mother->fresh()->password));
    }

    public function test_reset_messages_are_direct_and_repeat_requests_are_throttled(): void
    {
        Mail::fake();
        [$mother] = $this->accounts();
        $this->post('/forgot-password', ['role' => 'mother', 'email' => 'missing@example.test'])
            ->assertSessionHasErrors(['email' => 'No account found with this email and selected role.']);
        Mail::assertNothingSent();
        $this->post('/forgot-password', ['role' => 'mother', 'email' => $mother->email])
            ->assertSessionHas('status', 'Password reset link sent. Check your inbox or spam folder.');
        $this->post('/forgot-password', ['role' => 'mother', 'email' => $mother->email])->assertSessionHasErrors('email');
        Mail::assertSentCount(1);
        $this->post('/forgot-password', ['role' => 'admin', 'email' => $mother->email])->assertSessionHasErrors('role');
    }
}

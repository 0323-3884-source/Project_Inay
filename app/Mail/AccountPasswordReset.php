<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AccountPasswordReset extends Mailable
{
    public function __construct(public string $resetUrl, public string $role) {}

    public function build(): static
    {
        return $this->subject('Reset your Project INAY password')->view('emails.account-password-reset');
    }
}

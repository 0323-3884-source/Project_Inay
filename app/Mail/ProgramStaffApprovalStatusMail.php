<?php

namespace App\Mail;

use App\Models\ProgramStaff;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProgramStaffApprovalStatusMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public ProgramStaff $staff,
        public string $status,
        public ?string $reason = null,
    ) {
    }

    public function build(): self
    {
        $subject = $this->status === 'approved'
            ? 'Project INAY Program Staff Account Approved'
            : 'Project INAY Program Staff Registration Update';

        return $this
            ->from(config('mail.from.address'), config('mail.from.name', 'Project INAY'))
            ->subject($subject)
            ->view('emails.program-staff-approval-status');
    }
}

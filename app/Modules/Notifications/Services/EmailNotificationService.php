<?php

namespace App\Modules\Notifications\Services;

use App\Mail\OpesCareNotificationMail;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    /**
     * Send a plain-text notification email.
     *
     * Respects the MAIL_MAILER env setting.
     * In tests (MAIL_MAILER=array) nothing is actually sent — use Mail::fake() to assert.
     */
    public function send(string $to, string $subject, string $body): void
    {
        Mail::to($to)->send(new OpesCareNotificationMail($subject, $body));
    }
}

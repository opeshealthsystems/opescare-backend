<?php

namespace App\Mail;

use App\Mail\Support\EmailContent;

/**
 * One-time sign-in code.
 *
 * The code is the only thing in this message that matters, so it is the
 * largest thing on the page: monospaced, widely spaced, in its own panel with
 * the validity window directly under it and the "never share this" line right
 * after. The subject and preheader never contain the code.
 */
class OtpMail extends BrandedMailable
{
    public function __construct(
        public readonly string $code,
        public readonly string $recipientEmail,
        public readonly int $expiryMinutes = 10
    ) {}

    protected function compose(): EmailContent
    {
        return EmailContent::make(__('mail.otp.subject'), __('mail.otp.title'))
            ->preheader(__('mail.otp.preheader', ['minutes' => $this->expiryMinutes]))
            ->eyebrow(__('mail.otp.eyebrow'))
            ->greeting(__('mail.common.greeting'))
            ->paragraph(__('mail.otp.intro'))
            ->code(
                $this->code,
                __('mail.otp.code_label'),
                __('mail.otp.expiry', ['minutes' => $this->expiryMinutes]),
            )
            ->afterCode(__('mail.common.never_share'))
            ->footnote(__('mail.otp.ignore'))
            ->signature(__('mail.chrome.signature'));
    }
}

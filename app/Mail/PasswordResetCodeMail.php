<?php

namespace App\Mail;

use App\Mail\Support\EmailContent;

/**
 * Password reset code for the mobile app (the web flow uses a link:
 * PasswordResetLinkNotification). Same code panel as OtpMail, same
 * "never share this" line; the subject never contains the code.
 */
class PasswordResetCodeMail extends BrandedMailable
{
    public function __construct(
        public readonly string $code,
        public readonly string $recipientEmail,
        public readonly int $expiryMinutes = 15
    ) {}

    protected function compose(): EmailContent
    {
        return EmailContent::make(__('mail.reset_code.subject'), __('mail.reset_code.title'))
            ->preheader(__('mail.reset_code.preheader', ['minutes' => $this->expiryMinutes]))
            ->eyebrow(__('mail.reset_code.eyebrow'))
            ->greeting(__('mail.common.greeting'))
            ->paragraph(__('mail.reset_code.intro'))
            ->code(
                $this->code,
                __('mail.reset_code.code_label'),
                __('mail.reset_code.expiry', ['minutes' => $this->expiryMinutes]),
            )
            ->afterCode(__('mail.common.never_share'))
            ->footnote(__('mail.reset_code.ignore'))
            ->signature(__('mail.chrome.signature'));
    }
}

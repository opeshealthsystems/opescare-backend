<?php

namespace App\Mail;

use App\Mail\Support\EmailContent;

/**
 * The platform's general-purpose notice: a subject and a body of text.
 *
 * Callers (EmailNotificationService, the welcome mail in MobileAuthController,
 * the contact / demo-request forwards in PublicPageController) keep the same
 * two-argument constructor. What changed is the rendering: this used to be a
 * text-only message with no HTML body at all; it now goes out as the branded
 * multipart email, with the original text preserved as paragraphs (one per
 * line of the body) and as the plain-text part.
 *
 * Optional named arguments let a caller add a title, a call to action and a
 * preferences link without a second mailable. The body is passed through
 * verbatim — this class translates its own chrome, not the caller's text.
 */
class OpesCareNotificationMail extends BrandedMailable
{
    public function __construct(
        public readonly string $mailSubject,
        public readonly string $bodyText,
        public readonly ?string $title = null,
        public readonly ?string $ctaLabel = null,
        public readonly ?string $ctaUrl = null,
        public readonly ?string $preferencesUrl = null,
    ) {}

    protected function compose(): EmailContent
    {
        $lines = preg_split('/\R/u', trim($this->bodyText)) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));

        $content = EmailContent::make($this->mailSubject, $this->title ?? $this->mailSubject)
            ->preheader($lines[0] ?? __('mail.notification.preheader'))
            ->eyebrow(__('mail.notification.eyebrow'))
            ->paragraphs($lines)
            ->preferences($this->preferencesUrl);

        if ($this->ctaLabel !== null && $this->ctaUrl !== null) {
            $content->button($this->ctaLabel, $this->ctaUrl);
        }

        return $content;
    }
}

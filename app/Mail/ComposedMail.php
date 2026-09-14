<?php

namespace App\Mail;

use App\Mail\Support\EmailContent;

/**
 * A branded email whose content was composed up front.
 *
 * This is what a Notification's toMail() returns: it decides the recipient's
 * locale (RecipientLocale::for), builds the EmailContent inside that locale
 * with ComposedMail::inLocale(), and hands the finished, translated data to
 * the shared layout.
 *
 * ── The recipient is YOUR job, not the channel's ─────────────────────────
 * Laravel's MailChannel adds the notifiable's address to a MailMessage. For
 * a returned Mailable it does the opposite: `return $message->send($mailer)`
 * — as-is, no recipient. The first version of this class relied on the
 * channel and every migrated notification therefore threw
 * `An email must have a "To"` on a real notify(); the design tests missed it
 * because they sent through Mail::to(). Every toMail() must end with
 * ->forNotifiable($notifiable, $this), and NotificationsReachTheirRecipientTest
 * sends each one through the real channel to hold that.
 */
final class ComposedMail extends BrandedMailable
{
    public function __construct(public readonly EmailContent $email) {}

    /**
     * Address the message the way MailChannel would have for a MailMessage.
     *
     * routeNotificationFor('mail') may return a bare address, a list of
     * addresses, or an [address => name] map — the same three shapes
     * MailChannel::getRecipients() accepts. Anonymous (on-demand) notifiables
     * return whatever Notification::route('mail', …) was given.
     */
    public function forNotifiable(object $notifiable, ?\Illuminate\Notifications\Notification $notification = null): static
    {
        $recipients = $notifiable->routeNotificationFor('mail', $notification);

        if (is_string($recipients)) {
            $recipients = [$recipients];
        }

        foreach ((array) $recipients as $key => $value) {
            if (is_string($key)) {
                $this->to($key, $value);   // address => name
            } elseif (is_string($value) && $value !== '') {
                $this->to($value);         // bare address
            }
        }

        return $this;
    }

    /**
     * Run $build with the application locale temporarily set to $locale, and
     * return a mailable pinned to that locale (so the layout chrome renders in
     * the same language as the content).
     *
     * @param  callable(): EmailContent  $build
     */
    public static function inLocale(string $locale, callable $build): self
    {
        $app      = app();
        $previous = $app->getLocale();

        if ($previous !== $locale) {
            $app->setLocale($locale);
        }

        try {
            $content = $build();
        } finally {
            if ($previous !== $locale) {
                $app->setLocale($previous);
            }
        }

        return (new self($content))->locale($locale);
    }

    protected function compose(): EmailContent
    {
        return $this->email;
    }
}

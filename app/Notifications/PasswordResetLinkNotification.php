<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The one email that carries a live password-reset token.
 *
 * Queued on purpose, and not only for latency. POST /forgot-password must not
 * answer measurably faster for an address that is not registered — otherwise
 * the endpoint is an enumeration oracle with a stopwatch instead of a
 * response body. The framework's PasswordBroker wraps the whole call in a
 * 200 ms timebox, and an inline SMTP conversation blows straight through it.
 * Handing the send to the queue keeps the in-request work to a token hash and
 * two small writes, which the timebox absorbs.
 *
 * The raw token is public because it is what the link is made of; it exists
 * only in this object and in the mail body. What is persisted is a hash of it
 * (Illuminate\Auth\Passwords\DatabaseTokenRepository::getPayload).
 */
class PasswordResetLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $token,
        public readonly int $expiryMinutes = 60,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $email = method_exists($notifiable, 'getEmailForPasswordReset')
            ? $notifiable->getEmailForPasswordReset()
            : (string) $notifiable->email;

        // The email rides along in the link because password_reset_tokens is
        // keyed by it: without the address there is nothing to check the token
        // against. This is Laravel's own convention for the same reason.
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $email,
        ]);

        return (new MailMessage)
            ->subject(__('passwords.mail.subject'))
            ->greeting(__('passwords.mail.greeting'))
            ->line(__('passwords.mail.intro'))
            ->action(__('passwords.mail.action'), $url)
            ->line(__('passwords.mail.expiry', ['minutes' => $this->expiryMinutes]))
            ->line(__('passwords.mail.ignore'))
            ->salutation(__('passwords.mail.salutation'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        // Never the token itself — this array is persisted by the database
        // notification channel and read back by anything with table access.
        return ['expiry_minutes' => $this->expiryMinutes];
    }
}

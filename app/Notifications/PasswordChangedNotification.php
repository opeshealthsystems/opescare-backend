<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent after a password has actually been changed through the reset link.
 *
 * This is the only message the rightful owner of an account receives if
 * somebody else completed a reset against it — the reset email itself can be
 * read and deleted by whoever intercepted it, but a second, unsolicited
 * "your password was changed" is what turns a silent takeover into a report.
 * It carries no token and no link that changes anything.
 */
class PasswordChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ?string $ipAddress = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('passwords.changed_mail.subject'))
            ->greeting(__('passwords.changed_mail.greeting'))
            ->line(__('passwords.changed_mail.intro'))
            ->line(__('passwords.changed_mail.sessions'));

        if ($this->ipAddress !== null && $this->ipAddress !== '') {
            $message->line(__('passwords.changed_mail.origin', ['ip' => $this->ipAddress]));
        }

        return $message
            ->line(__('passwords.changed_mail.warn'))
            ->salutation(__('passwords.mail.salutation'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return ['ip_address' => $this->ipAddress];
    }
}

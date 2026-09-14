<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\ComposedMail;
use App\Mail\Support\EmailContent;
use App\Mail\Support\RecipientLocale;
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

    public function toMail(object $notifiable): ComposedMail
    {
        $locale = RecipientLocale::for($notifiable, $this->locale);

        return ComposedMail::inLocale($locale, function () {
            $content = EmailContent::make(__('passwords.changed_mail.subject'), __('passwords.changed_mail.subject'))
                ->preheader(__('mail.password_changed.preheader'))
                ->eyebrow(__('mail.password_changed.eyebrow'), 'urgent')
                ->greeting(__('passwords.changed_mail.greeting'))
                ->paragraph(__('passwords.changed_mail.intro'))
                ->paragraph(__('passwords.changed_mail.sessions'));

            if ($this->ipAddress !== null && $this->ipAddress !== '') {
                $content->paragraph(__('passwords.changed_mail.origin', ['ip' => $this->ipAddress]));
            }

            // The only link is the public sign-in page: nothing in this
            // message changes anything (see the class comment).
            return $content
                ->paragraph(__('passwords.changed_mail.warn'))
                ->panel(__('mail.password_changed.panel_title'), [
                    __('mail.password_changed.panel_1'),
                    __('mail.password_changed.panel_2'),
                    __('mail.password_changed.panel_3'),
                ])
                ->button(__('mail.password_changed.action'), route('login'))
                ->signature(__('passwords.mail.salutation'));
        })->forNotifiable($notifiable, $this);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return ['ip_address' => $this->ipAddress];
    }
}

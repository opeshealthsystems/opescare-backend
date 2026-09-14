<?php

namespace Tests\Feature\Mail;

use App\Mail\ComposedMail;
use App\Mail\Support\EmailContent;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tests\TestCase;

/**
 * A notification's email must actually be addressed to somebody.
 *
 * MailChannel adds the notifiable's address to a MailMessage. For a returned
 * Mailable it sends the object as-is — no recipient. The first migration of
 * the seven notifications to ComposedMail relied on the channel and threw
 * `An email must have a "To"` on every real notify(); the design tests never
 * saw it because they sent through Mail::to(). This test goes through the
 * real channel, with the real array transport, on purpose.
 */
class NotificationsReachTheirRecipientTest extends TestCase
{
    private function stubNotification(): Notification
    {
        return new class extends Notification {
            public function via(object $notifiable): array
            {
                return ['mail'];
            }

            public function toMail(object $notifiable): ComposedMail
            {
                return ComposedMail::inLocale('en', fn () => EmailContent::make('Test subject', 'Test')
                    ->paragraph('Body.'))
                    ->forNotifiable($notifiable, $this);
            }
        };
    }

    public function test_an_on_demand_recipient_is_addressed(): void
    {
        config(['mail.default' => 'array']);
        Mail::getFacadeRoot()->mailer('array');

        NotificationFacade::route('mail', 'amina@example.test')->notify($this->stubNotification());

        $sent = Mail::mailer('array')->getSymfonyTransport()->messages();

        $this->assertCount(1, $sent);
        $this->assertSame('amina@example.test', $sent->first()->getOriginalMessage()->getTo()[0]->getAddress());
    }

    public function test_a_named_recipient_map_is_addressed_with_the_name(): void
    {
        config(['mail.default' => 'array']);

        NotificationFacade::route('mail', ['amina@example.test' => 'Amina Nkoto'])->notify($this->stubNotification());

        $to = Mail::mailer('array')->getSymfonyTransport()->messages()->first()->getOriginalMessage()->getTo()[0];

        $this->assertSame('amina@example.test', $to->getAddress());
        $this->assertSame('Amina Nkoto', $to->getName());
    }

    public function test_every_shipped_notification_addresses_its_mailable(): void
    {
        // The source-level guard for the seven real senders: a toMail() that
        // builds a ComposedMail and forgets forNotifiable() re-creates the
        // "no To header" 500 on the next real invite.
        $files = [
            'EmergencyAccessAlertNotification', 'FacilityStaffInviteNotification', 'FamilyEventNotification',
            'FamilyInviteNotification', 'HealthIdExpiryNotification', 'PasswordChangedNotification',
            'PasswordResetLinkNotification',
        ];

        foreach ($files as $class) {
            $src = file_get_contents(app_path("Notifications/{$class}.php"));

            if (! str_contains($src, 'ComposedMail::inLocale')) {
                continue;
            }

            $this->assertStringContainsString('->forNotifiable($notifiable, $this)', $src,
                "{$class}::toMail() returns a ComposedMail without addressing it");
        }
    }
}

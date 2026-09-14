<?php

namespace Tests\Feature\Mail;

use App\Mail\OpesCareNotificationMail;
use App\Mail\OtpMail;
use App\Mail\PasswordResetCodeMail;
use App\Models\FacilityStaffInvite;
use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\EmergencyAccessAlertNotification;
use App\Notifications\FacilityStaffInviteNotification;
use App\Notifications\FamilyEventNotification;
use App\Notifications\FamilyInviteNotification;
use App\Notifications\HealthIdExpiryNotification;
use App\Notifications\PasswordChangedNotification;
use App\Notifications\PasswordResetLinkNotification;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/**
 * Every email the platform sends, rendered in both languages through the real
 * mailer (array transport), and held to the design contract:
 *
 *   - the brand blue is present, the old dark/emerald/sky palette is gone and
 *     nothing is purple;
 *   - every link is absolute https and none points at the dead /portals/ tree;
 *   - a plain-text alternative exists (multipart), and a preheader;
 *   - French emails carry no English chrome;
 *   - no subject line carries a Health ID or a clinical word.
 *
 * No database: every fixture is built in memory. The locale-resolution test
 * that needs stored preferences lives in RecipientLocaleTest.
 *
 * As a side effect the rendered HTML of each message is written to
 * storage/app/private/email-previews/{name}.{locale}.html (a git-ignored
 * path) so a human can open them in a browser.
 */
class TransactionalEmailDesignTest extends TestCase
{
    private const HEALTH_ID = 'CM-HID-2024-TEST-0001';

    private const FORBIDDEN_COLOURS = ['#10b981', '#38bdf8', '#0f172a', 'purple', '#7c3aed', '#8b5cf6', '#6366f1', '#312e81'];

    /** Literals from the previous, English-only versions of these emails. */
    private const OLD_ENGLISH_CHROME = [
        'All rights reserved',
        'Hello,',
        'The OpesCare team',
        'Renew My Health ID',
        'View Your Access Logs',
        'Review & Accept',
        'View in Portal',
        'Two-Factor Verification',
        'Password Reset Request',
        'Do not reply to this email',
        'automated operational transmission',
        'Emergency Access Alert',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Production links are https; the dev APP_URL is not. The layout must
        // produce absolute https links wherever it is rendered.
        config(['app.url' => 'https://finder.opescare.cloud']);
        URL::forceRootUrl('https://finder.opescare.cloud');
        URL::forceScheme('https');
    }

    /** @return array<string, array{0:string}> */
    public static function senders(): array
    {
        return [
            'otp'               => ['otp'],
            'reset_code'        => ['reset_code'],
            'notification'      => ['notification'],
            'emergency'         => ['emergency'],
            'staff_invite'      => ['staff_invite'],
            'family_event'      => ['family_event'],
            'family_invite'     => ['family_invite'],
            'health_id_expiry'  => ['health_id_expiry'],
            'password_changed'  => ['password_changed'],
            'password_reset'    => ['password_reset'],
        ];
    }

    #[DataProvider('senders')]
    public function test_every_sender_renders_on_brand_in_english(string $name): void
    {
        $this->assertDesignContract($name, 'en');
    }

    #[DataProvider('senders')]
    public function test_every_sender_renders_on_brand_in_french(string $name): void
    {
        $this->assertDesignContract($name, 'fr');
    }

    public function test_recipient_locale_beats_the_sending_session_locale(): void
    {
        // The command / clinician session is English; the notification was
        // pinned to French for this recipient. The subject must be French.
        app()->setLocale('en');

        $notification = (new HealthIdExpiryNotification(self::HEALTH_ID, 'Amina Nkolo', '2026-12-01', 45))->locale('fr');
        $message      = $this->deliver($notification->toMail($this->user()));

        $this->assertSame(__('mail.health_id_expiry.subject', ['days' => 45], 'fr'), $message->getSubject());
        $this->assertStringContainsString('lang="fr"', (string) $message->getHtmlBody());
        // ...and the process locale is left as it was.
        $this->assertSame('en', app()->getLocale());
    }

    public function test_the_generic_notification_keeps_its_text_verbatim_in_the_text_part(): void
    {
        $body    = "Hello Amina,\n\nYour OpesCare account has been created.\n\nOpesCare Team";
        $message = $this->deliver((new OpesCareNotificationMail('Welcome to OpesCare', $body))->locale('en'));

        $this->assertStringContainsString('Your OpesCare account has been created.', (string) $message->getTextBody());
        $this->assertStringContainsString('Your OpesCare account has been created.', (string) $message->getHtmlBody());
        $this->assertSame('Welcome to OpesCare', $message->getSubject());
    }

    public function test_code_emails_carry_the_code_in_the_body_but_never_in_the_subject(): void
    {
        foreach ([new OtpMail('483920', 'amina@example.test'), new PasswordResetCodeMail('771204', 'amina@example.test')] as $mailable) {
            $message = $this->deliver($mailable->locale('en'));
            $code    = $mailable->code;

            $this->assertStringContainsString($code, (string) $message->getHtmlBody());
            $this->assertStringContainsString($code, (string) $message->getTextBody());
            $this->assertStringNotContainsString($code, $message->getSubject());
            $this->assertStringContainsString(__('mail.common.never_share', [], 'en'), (string) $message->getHtmlBody());
        }
    }

    // ── The contract ─────────────────────────────────────────────────────

    private function assertDesignContract(string $name, string $locale): void
    {
        $message = $this->deliver($this->build($name, $locale));

        $html    = (string) $message->getHtmlBody();
        $text    = (string) $message->getTextBody();
        $subject = (string) $message->getSubject();
        $lower   = strtolower($html);

        $this->writePreview($name, $locale, $html, $text, $subject);

        // Brand.
        $this->assertStringContainsString('#0f4c81', $lower, "$name/$locale: brand blue missing");
        foreach (self::FORBIDDEN_COLOURS as $bad) {
            $this->assertStringNotContainsString($bad, $lower, "$name/$locale: off-brand colour $bad");
        }

        // Structure: 600px table layout, preheader, light-only colour scheme, language tag.
        $this->assertStringContainsString('width="600"', $html);
        $this->assertStringContainsString('color-scheme" content="light only"', $html);
        $this->assertStringContainsString('lang="' . $locale . '"', $html);
        $this->assertMatchesRegularExpression('/<div style="display:none;[^>]*>[^<]{10,}/', $html, "$name/$locale: preheader missing");

        // Links: absolute https, nothing under the dead /portals/ tree.
        preg_match_all('/(?:href|src)="([^"]+)"/', $html, $m);
        $this->assertNotEmpty($m[1], "$name/$locale: no links at all");
        foreach ($m[1] as $url) {
            $this->assertStringStartsWith('https://', $url, "$name/$locale: non-absolute link $url");
            $this->assertStringNotContainsString('/portals/', $url, "$name/$locale: link into the dead /portals/ tree: $url");
        }

        // Multipart: a real plain-text alternative.
        $this->assertNotSame('', trim($text), "$name/$locale: no text part");
        $this->assertStringNotContainsString('<table', $text);
        $this->assertStringContainsString('OPESCARE', $text);

        // Subject hygiene: no Health ID, no clinical words, in either language.
        $this->assertStringNotContainsString(self::HEALTH_ID, $subject);
        $this->assertDoesNotMatchRegularExpression('/\b[A-Z]{2}-[A-Z]{3}-[A-Z0-9-]{4,}\b/', $subject, "$name/$locale: subject looks like it carries a Health ID: $subject");
        foreach (['diagnos', 'result', 'medication', 'résultat', 'médicament', 'ordonnance'] as $word) {
            $this->assertStringNotContainsStringIgnoringCase($word, $subject, "$name/$locale: clinical word in subject: $subject");
        }
        $this->assertNotSame('', trim($subject));

        // No emoji anywhere (icons are typography and colour in email).
        $this->assertDoesNotMatchRegularExpression('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $html . $subject);

        if ($locale === 'fr') {
            foreach (self::OLD_ENGLISH_CHROME as $literal) {
                $this->assertStringNotContainsString($literal, $html, "$name/fr: English chrome leaked: $literal");
            }
            $this->assertStringContainsString('Tous droits réservés', $html);
        } else {
            $this->assertStringContainsString('All rights reserved', $html);
        }
    }

    // ── Fixtures ─────────────────────────────────────────────────────────

    private function build(string $name, string $locale): Mailable
    {
        $user    = $this->user();
        $patient = $this->patient();

        return match ($name) {
            'otp'          => (new OtpMail('483920', 'amina@example.test', 10))->locale($locale),
            'reset_code'   => (new PasswordResetCodeMail('771204', 'amina@example.test', 15))->locale($locale),
            'notification' => (new OpesCareNotificationMail(
                'Welcome to OpesCare — Your Health ID is ready',
                "Hello Amina,\n\nYour OpesCare account has been created.\nYour Health ID: " . self::HEALTH_ID . "\n\nPlease visit a registered facility to complete identity verification.\n\nOpesCare Team",
            ))->locale($locale),
            'emergency' => (new EmergencyAccessAlertNotification(
                self::HEALTH_ID, 'Amina Nkolo', '12 Sep 2026, 03:41', 'fac-01', 'Unconscious patient, road traffic accident', '41.0.0.1',
            ))->locale($locale)->toMail($patient),
            'staff_invite' => (new FacilityStaffInviteNotification(
                new FacilityStaffInvite(['email' => 'doctor@example.test', 'expires_at' => now()->addDays(3)]),
                'raw-token-abc',
                'Hôpital Général de Douala',
                'Doctor',
                'Dr. Ngo Bassong',
            ))->locale($locale)->toMail((new AnonymousNotifiable)->route('mail', 'doctor@example.test')),
            'family_event' => (new FamilyEventNotification(
                $this->familyLink($user, $patient),
                'lab_result',
                'A new document was added to the record.',
            ))->locale($locale)->toMail($user),
            'family_invite' => (new FamilyInviteNotification($this->familyLink($user, $patient), 'raw-token-xyz'))
                ->locale($locale)->toMail($user),
            'health_id_expiry' => (new HealthIdExpiryNotification(self::HEALTH_ID, 'Amina Nkolo', '2026-12-01', 21))
                ->locale($locale)->toMail($user),
            'password_changed' => (new PasswordChangedNotification('41.0.0.1'))->locale($locale)->toMail($user),
            'password_reset'   => (new PasswordResetLinkNotification('reset-token-123', 60))->locale($locale)->toMail($user),
        };
    }

    private function user(): User
    {
        return User::factory()->make(['name' => 'Amina Nkolo', 'email' => 'amina@example.test', 'patient_id' => null]);
    }

    private function patient(): Patient
    {
        return Patient::factory()->make(['first_name' => 'Amina', 'last_name' => 'Nkolo', 'health_id' => self::HEALTH_ID]);
    }

    private function familyLink(User $guardian, Patient $dependent): FamilyLink
    {
        $link = new FamilyLink([
            'relationship'       => 'parent',
            'access_level'       => 'read_only',
            'status'             => 'active',
            'notification_prefs' => [],
        ]);
        $link->setRelation('guardianUser', $guardian);
        $link->setRelation('dependentPatient', $dependent);

        return $link;
    }

    /** Send through the array transport and hand back the Symfony message. */
    private function deliver(Mailable $mailable): Email
    {
        $transport = Mail::mailer('array')->getSymfonyTransport();
        $transport->flush();

        Mail::mailer('array')->to('amina@example.test')->send($mailable);

        $sent = $transport->messages();
        $this->assertCount(1, $sent);

        /** @var Email $email */
        $email = $sent->first()->getOriginalMessage();

        return $email;
    }

    private function writePreview(string $name, string $locale, string $html, string $text, string $subject): void
    {
        $dir = storage_path('app/private/email-previews');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents("$dir/$name.$locale.html", "<!-- Subject: $subject -->\n" . $html);
        file_put_contents("$dir/$name.$locale.txt", "Subject: $subject\n\n" . $text);
    }
}

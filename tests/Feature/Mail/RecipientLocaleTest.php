<?php

namespace Tests\Feature\Mail;

use App\Mail\OtpMail;
use App\Mail\Support\RecipientLocale;
use App\Models\MobileAppSetting;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\EmergencyAccessAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * An email is written in the language the RECIPIENT chose, not the one the
 * sending request happened to be in. The only stored preference the platform
 * has for a patient is mobile_app_settings.preferred_language, so that is
 * what RecipientLocale reads — for a Patient, for the User linked to one, and
 * for a bare address handed to Mail::to().
 */
class RecipientLocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('https://finder.opescare.cloud');
        URL::forceScheme('https');
    }

    public function test_a_french_speaking_patient_gets_french_from_an_english_session(): void
    {
        app()->setLocale('en');

        $patient = Patient::factory()->create(['email' => 'aline@example.test']);
        MobileAppSetting::forPatient($patient->id)->update(['preferred_language' => 'fr']);
        $user = User::factory()->create(['patient_id' => $patient->id, 'email' => 'aline@example.test']);

        $this->assertSame('fr', RecipientLocale::for($patient));
        $this->assertSame('fr', RecipientLocale::for($user));
        $this->assertSame('fr', RecipientLocale::forEmail('aline@example.test'));

        // The whole way through: a break-glass alert raised from an English
        // clinician session lands in French.
        $mailable = (new EmergencyAccessAlertNotification('CM-HID-2024-TEST-0002', 'Aline', '12 Sep 2026, 03:41', 'fac', 'Trauma', ''))
            ->toMail($patient);

        $this->assertSame('fr', $mailable->locale);
        $this->assertStringContainsString(__('mail.emergency.title', [], 'fr'), $mailable->render());
        $this->assertSame('en', app()->getLocale());
    }

    public function test_the_stored_preference_wins_over_an_explicit_notification_locale(): void
    {
        $patient = Patient::factory()->create();
        MobileAppSetting::forPatient($patient->id)->update(['preferred_language' => 'fr']);

        $this->assertSame('fr', RecipientLocale::for($patient, 'en'));
    }

    public function test_without_a_stored_preference_the_explicit_locale_then_the_app_locale_apply(): void
    {
        $patient = Patient::factory()->create();

        app()->setLocale('en');
        $this->assertSame('fr', RecipientLocale::for($patient, 'fr'));
        $this->assertSame('en', RecipientLocale::for($patient));
        $this->assertSame('fr', RecipientLocale::for((new AnonymousNotifiable)->route('mail', 'x@example.test'), 'fr'));
        $this->assertSame('en', RecipientLocale::for(null));
    }

    public function test_an_unsupported_or_garbled_preference_falls_back_safely(): void
    {
        $patient = Patient::factory()->create();
        MobileAppSetting::forPatient($patient->id)->update(['preferred_language' => 'pt-BR']);

        app()->setLocale('en');
        $this->assertSame('en', RecipientLocale::for($patient));

        MobileAppSetting::forPatient($patient->id)->update(['preferred_language' => 'FR_ca']);
        $this->assertSame('fr', RecipientLocale::for($patient));
    }

    public function test_a_mailable_sent_to_a_bare_address_resolves_the_patient_preference(): void
    {
        app()->setLocale('en');

        $patient = Patient::factory()->create(['email' => 'paul@example.test']);
        MobileAppSetting::forPatient($patient->id)->update(['preferred_language' => 'fr']);

        $transport = Mail::mailer('array')->getSymfonyTransport();
        $transport->flush();

        Mail::mailer('array')->to('paul@example.test')->send(new OtpMail('123456', 'paul@example.test'));

        $sent = $transport->messages()->first()->getOriginalMessage();
        $this->assertSame(__('mail.otp.subject', [], 'fr'), $sent->getSubject());
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\Patient;
use App\Models\User;
use App\Services\Identity\QrTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Web sign-in by email OR Health ID, and the OPESCARD QR that only fills the
 * identifier box. The password is always required; a Health ID or a card QR
 * is never a credential on its own.
 */
class HealthIdLoginTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Correct-Horse-9';
    private const HID      = 'CM-HID-7KQ9-MP42-X8D1';

    private function patientWithUser(string $healthId = self::HID): array
    {
        $patient = Patient::factory()->create(['health_id' => $healthId]);
        $user = User::factory()->create([
            'password'   => bcrypt(self::PASSWORD),
            'patient_id' => $patient->id,
        ]);

        return [$patient, $user];
    }

    public function test_email_login_still_works_with_the_legacy_email_field(): void
    {
        [, $user] = $this->patientWithUser();

        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_email_login_works_with_the_login_field(): void
    {
        [, $user] = $this->patientWithUser();

        $this->post('/login', ['login' => $user->email, 'password' => self::PASSWORD]);

        $this->assertAuthenticatedAs($user);
    }

    #[DataProvider('healthIdSpellings')]
    public function test_health_id_login_forgives_case_spaces_and_dashes(string $typed): void
    {
        [, $user] = $this->patientWithUser();

        $this->post('/login', ['login' => $typed, 'password' => self::PASSWORD]);

        $this->assertAuthenticatedAs($user);
    }

    public static function healthIdSpellings(): array
    {
        return [
            'canonical' => ['CM-HID-7KQ9-MP42-X8D1'],
            'lowercase' => ['cm-hid-7kq9-mp42-x8d1'],
            'spaces'    => ['  CM HID 7KQ9 MP42 X8D1 '],
            'no dashes' => ['cmhid7kq9mp42x8d1'],
        ];
    }

    public function test_a_merged_health_id_signs_into_the_survivors_account(): void
    {
        [$survivor, $user] = $this->patientWithUser('CM-HID-SURV-IVOR-0001');

        $retired = Patient::factory()->create(['health_id' => 'CM-HID-RETI-RED0-0002']);
        $retired->forceFill([
            'identity_status'        => 'merged',
            'merged_into_patient_id' => $survivor->id,
        ])->save();

        $this->post('/login', ['login' => 'cm-hid-reti-red0-0002', 'password' => self::PASSWORD]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_and_unknown_health_id_give_the_same_error(): void
    {
        $this->patientWithUser();

        $generic = ['login' => __('onboarding.login.errors.invalid_credentials')];

        $this->from('/login')->post('/login', ['login' => self::HID, 'password' => 'nope-nope-1'])
            ->assertRedirect('/login')->assertSessionHasErrors($generic);

        $this->from('/login')->post('/login', ['login' => 'CM-HID-ZZZZ-ZZZZ-ZZZZ', 'password' => self::PASSWORD])
            ->assertRedirect('/login')->assertSessionHasErrors($generic);

        $this->from('/login')->post('/login', ['login' => 'nobody@example.test', 'password' => self::PASSWORD])
            ->assertRedirect('/login')->assertSessionHasErrors($generic);

        $this->assertGuest();
    }

    public function test_a_health_id_with_no_linked_account_cannot_sign_in(): void
    {
        Patient::factory()->create(['health_id' => self::HID]);

        $this->post('/login', ['login' => self::HID, 'password' => self::PASSWORD]);

        $this->assertGuest();
    }

    public function test_the_per_identifier_throttle_shares_all_spellings_of_one_health_id(): void
    {
        $this->patientWithUser();

        // Switch off the route's per-IP throttle so this pins the
        // controller's own per-identifier limiter.
        $this->withoutMiddleware(ThrottleRequests::class);

        foreach (['cm-hid-7kq9-mp42-x8d1', 'CM HID 7KQ9 MP42 X8D1', 'cmhid7kq9mp42x8d1', self::HID, 'Cm-Hid-7kq9-Mp42-X8d1'] as $typed) {
            $this->post('/login', ['login' => $typed, 'password' => 'wrong-wrong-1']);
        }

        // Correct password now refused: the normalised identifier is locked.
        $this->from('/login')->post('/login', ['login' => self::HID, 'password' => self::PASSWORD])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_resolve_returns_only_the_health_id_for_a_valid_card_qr(): void
    {
        [$patient] = $this->patientWithUser();
        $issued = app(QrTokenService::class)->generateToken($patient->id, 'card_qr', null);

        $response = $this->postJson('/login/opescard/resolve', ['payload' => $issued['secure_url']]);

        $response->assertOk()->assertExactJson(['health_id' => self::HID]);
        $body = $response->getContent();
        $this->assertStringNotContainsString((string) $patient->first_name, $body);
        $this->assertStringNotContainsString((string) $patient->last_name, $body);

        // The bare token works too.
        $this->postJson('/login/opescard/resolve', ['payload' => $issued['raw_token']])
            ->assertOk()->assertExactJson(['health_id' => self::HID]);

        // A scan never signs anyone in.
        $this->assertGuest();
    }

    public function test_resolve_follows_a_merge_to_the_survivors_health_id(): void
    {
        [$survivor] = $this->patientWithUser('CM-HID-SURV-IVOR-0001');
        $retired = Patient::factory()->create(['health_id' => 'CM-HID-RETI-RED0-0002']);
        $issued = app(QrTokenService::class)->generateToken($retired->id, 'card_qr', null);
        $retired->forceFill(['identity_status' => 'merged', 'merged_into_patient_id' => $survivor->id])->save();

        $this->postJson('/login/opescard/resolve', ['payload' => $issued['secure_url']])
            ->assertOk()->assertExactJson(['health_id' => 'CM-HID-SURV-IVOR-0001']);
    }

    public function test_resolve_refuses_junk_revoked_and_wrong_secret_alike(): void
    {
        [$patient] = $this->patientWithUser();
        $issued = app(QrTokenService::class)->generateToken($patient->id, 'card_qr', null);

        $this->postJson('/login/opescard/resolve', [])->assertStatus(422);
        $this->postJson('/login/opescard/resolve', ['payload' => 'hello world'])->assertNotFound();
        $this->postJson('/login/opescard/resolve', ['payload' => self::HID])->assertNotFound();
        $this->postJson('/login/opescard/resolve', ['payload' => $issued['raw_token'] . 'x'])->assertNotFound()
            ->assertJsonMissing(['health_id' => self::HID]);

        $temporary = app(QrTokenService::class)->generateToken($patient->id, 'temporary_consent_qr', 60);
        $this->postJson('/login/opescard/resolve', ['payload' => $temporary['raw_token']])->assertNotFound();

        $issued['model']->update(['status' => 'revoked']);
        $this->postJson('/login/opescard/resolve', ['payload' => $issued['raw_token']])->assertNotFound()
            ->assertJsonMissing(['health_id' => self::HID]);
    }

    public function test_login_page_allows_the_camera_for_self_only(): void
    {
        $this->get('/login')->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');
        $this->get('/')->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}

<?php

namespace Tests\Feature\Mobile;

use App\Models\Patient;
use App\Models\PushDeviceToken;
use App\Services\ExpoPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * POST /api/mobile/push-tokens — what may be stored as a push credential.
 *
 * `push_token` was validated as `required|string` and nothing more, and the
 * mobile client of the day sent the device's own random fingerprint in that
 * field. The endpoint stored it, answered 201, and the switch in the app read
 * "on" — while the row was permanently undeliverable, because Expo will never
 * accept a fingerprint. A silent failure that looked like a success at both
 * ends, and one that no amount of sender-side work could recover: by the time
 * `ExpoPushService` reads the table, the real token is already lost.
 *
 * So the gate belongs at the door, and it is the SAME predicate the sender
 * uses — `ExpoPushService::isExpoPushToken()`. These tests pin the two
 * together: whatever this endpoint accepts must be something the sender would
 * put on the wire, and whatever it rejects must be something the sender would
 * skip.
 */
class MobilePushTokenRegistrationTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private Patient $patient;

    /** A device fingerprint — 32 hex characters, exactly what used to be sent. */
    private const FINGERPRINT = 'a3f9c1e07b2d4e6f8a1b2c3d4e5f6071';

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-8400-0001-01',
            'first_name'    => 'Pascaline',
            'last_name'     => 'Push',
            'sex'           => 'female',
            'date_of_birth' => '1990-01-01',
            'is_demo'       => false,
        ]);
    }

    private function register(string $pushToken)
    {
        return $this->mobilePostJson($this->patient, '/api/mobile/push-tokens', [
            'device_fingerprint' => self::FINGERPRINT,
            'platform'           => 'android',
            'push_token'         => $pushToken,
        ]);
    }

    public function test_a_device_fingerprint_is_refused_and_nothing_is_stored(): void
    {
        $this->register(self::FINGERPRINT)
            ->assertStatus(422)
            ->assertJsonValidationErrors('push_token');

        $this->assertSame(0, PushDeviceToken::count(), 'An undeliverable row was stored anyway.');
    }

    /** @return array<string, array{0: string}> */
    public static function nonTokens(): array
    {
        return [
            'empty-ish whitespace'      => ['   '],
            'a bare uuid'               => ['0198f2c1-4b6e-7a29-9d31-6c5f8b2a4e10'],
            'the prefix with no body'   => ['ExponentPushToken[]'],
            'the prefix without brackets' => ['ExponentPushToken'],
            'an FCM-shaped token'       => ['fMEV1s2rSaC:APA91bHqRxT_9dZ0kQwErTyUiOpAsDfGhJkLzXcVbNm'],
            'a trailing bracket only'   => ['ExponentPushToken[abc'],
        ];
    }

    #[DataProvider('nonTokens')]
    public function test_nothing_that_is_not_an_expo_token_is_accepted(string $value): void
    {
        $this->register($value)
            ->assertStatus(422)
            ->assertJsonValidationErrors('push_token');

        $this->assertSame(0, PushDeviceToken::count());
    }

    /** @return array<string, array{0: string}> */
    public static function realTokens(): array
    {
        return [
            // Expo has minted both prefixes and its push API accepts either.
            'Exponent prefix' => ['ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]'],
            'Expo prefix'     => ['ExpoPushToken[yyyyyyyyyyyyyyyyyyyyyy]'],
        ];
    }

    #[DataProvider('realTokens')]
    public function test_a_real_expo_token_is_accepted_and_stored_verbatim(string $token): void
    {
        $this->register($token)
            ->assertStatus(201)
            ->assertJsonPath('status', 'registered')
            ->assertJsonPath('platform', 'android');

        $row = PushDeviceToken::sole();

        $this->assertSame($token, $row->push_token, 'The token was not stored as sent.');
        $this->assertSame(self::FINGERPRINT, $row->device_fingerprint);
        $this->assertNotSame(
            $row->device_fingerprint,
            $row->push_token,
            'push_token and device_fingerprint are the same value again.'
        );

        // The whole point of the gate: every row the door lets through is a row
        // the sender will actually put on the wire.
        $this->assertTrue(ExpoPushService::isExpoPushToken($row->push_token));
    }

    public function test_re_registering_the_same_device_replaces_the_token_rather_than_adding_a_row(): void
    {
        $this->register('ExponentPushToken[aaaaaaaaaaaaaaaaaaaaaa]')->assertStatus(201);
        $this->register('ExponentPushToken[bbbbbbbbbbbbbbbbbbbbbb]')->assertStatus(201);

        $this->assertSame(1, PushDeviceToken::count());
        $this->assertSame('ExponentPushToken[bbbbbbbbbbbbbbbbbbbbbb]', PushDeviceToken::sole()->push_token);
    }

    public function test_a_refused_registration_does_not_overwrite_a_good_token(): void
    {
        $this->register('ExponentPushToken[cccccccccccccccccccccc]')->assertStatus(201);

        $this->register(self::FINGERPRINT)->assertStatus(422);

        $this->assertSame(
            'ExponentPushToken[cccccccccccccccccccccc]',
            PushDeviceToken::sole()->push_token,
            'A rejected registration still clobbered the reachable token.'
        );
    }
}

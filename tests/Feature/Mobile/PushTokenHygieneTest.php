<?php

namespace Tests\Feature\Mobile;

use App\Models\Patient;
use App\Models\PushDeviceToken;
use App\Support\PatientPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * push_device_tokens hygiene: one row per handset, one account per token.
 *
 *  - A malformed push_token is refused (422); a registration with no
 *    push_token at all is the in-app path — stored, marked undeliverable,
 *    never sent to.
 *  - The same handset, or the same token under a new fingerprint, updates
 *    its row (registered_at moves) instead of adding one.
 *  - A token that turns up under another account revokes the old row:
 *    a signed-out phone stops ringing for its previous owner.
 *  - DELETE /api/mobile/push-tokens revokes this handset's rows, or all of
 *    the account's, for sign-out.
 */
class PushTokenHygieneTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private const SEND = 'https://exp.host/--/api/v2/push/send';

    private const TOKEN = 'ExponentPushToken[hygiene-test-device-0001]';

    private const FINGERPRINT_A = 'a3f9c1e07b2d4e6f8a1b2c3d4e5f6071';

    private const FINGERPRINT_B = 'b7e2d4c6a8f0e1d3c5b7a9f1e3d5c7b9';

    private Patient $alice;

    private Patient $bob;

    protected function setUp(): void
    {
        parent::setUp();

        config(['push_channels.expo.enabled' => true]);

        Http::fake([
            self::SEND => Http::response(['data' => [['status' => 'ok', 'id' => 'ticket-0001']]], 200),
            '*'        => Http::response([], 200),
        ]);

        $this->alice = Patient::create([
            'health_id'     => 'OC-TST-8800-0001-01',
            'first_name'    => 'Alice',
            'last_name'     => 'Hygiene',
            'sex'           => 'female',
            'date_of_birth' => '1990-01-01',
        ]);

        $this->bob = Patient::create([
            'health_id'     => 'OC-TST-8800-0002-01',
            'first_name'    => 'Bob',
            'last_name'     => 'Hygiene',
            'sex'           => 'male',
            'date_of_birth' => '1985-05-05',
        ]);
    }

    /** @param  array<string, mixed>  $body */
    private function register(Patient $patient, array $body)
    {
        return $this->mobilePostJson($patient, '/api/mobile/push-tokens', $body + [
            'device_fingerprint' => self::FINGERPRINT_A,
            'platform'           => 'android',
        ]);
    }

    private function revokeAll(Patient $patient, array $body = [])
    {
        return $this->withHeaders($this->mobileAuthHeaders($patient))->deleteJson('/api/mobile/push-tokens', $body);
    }

    private function sendRequests(): int
    {
        return count(Http::recorded(fn (ClientRequest $r) => $r->url() === self::SEND));
    }

    // ── what may be stored ──────────────────────────────────────────────

    public function test_a_malformed_token_is_refused_with_422_and_nothing_is_stored(): void
    {
        // Sending the key at all means "here is my credential": blank and empty
        // are refused too — only OMITTING push_token is the fingerprint-only path.
        foreach (['ExponentPushToken[abc', 'ExpoPushToken[]', 'fMEV1s2rSaC:APA91bH', self::FINGERPRINT_A, '', '   '] as $bad) {
            $this->register($this->alice, ['push_token' => $bad])
                ->assertStatus(422)
                ->assertJsonValidationErrors('push_token');
        }

        $this->assertSame(0, PushDeviceToken::count());
    }

    public function test_a_well_formed_token_is_stored_and_reported_deliverable(): void
    {
        $this->register($this->alice, ['push_token' => self::TOKEN])
            ->assertStatus(201)
            ->assertJsonPath('status', 'registered')
            ->assertJsonPath('platform', 'android')
            ->assertJsonPath('deliverable', true);

        $row = PushDeviceToken::sole();
        $this->assertSame(self::TOKEN, $row->push_token);
        $this->assertTrue($row->isDeliverable());
    }

    public function test_a_fingerprint_only_registration_is_stored_marked_undeliverable_and_never_sent_to(): void
    {
        $this->register($this->alice, ['platform' => 'web'])
            ->assertStatus(201)
            ->assertJsonPath('status', 'registered')
            ->assertJsonPath('platform', 'web')
            ->assertJsonPath('deliverable', false);

        $row = PushDeviceToken::sole();
        $this->assertSame('', $row->push_token);
        $this->assertTrue($row->is_active);
        $this->assertFalse($row->isDeliverable());

        $result = PatientPush::send($this->alice, 'message', 'T', 'B');

        $this->assertSame(0, $this->sendRequests(), 'A fingerprint must never reach Expo.');
        $this->assertSame(1, $result->skippedNoToken);
        $this->assertTrue($result->hadNothingToSendTo());
    }

    public function test_a_fingerprint_only_row_is_healed_by_a_later_real_token_from_the_same_handset(): void
    {
        $this->register($this->alice, [])->assertStatus(201);
        $this->register($this->alice, ['push_token' => self::TOKEN])->assertStatus(201);

        $this->assertSame(1, PushDeviceToken::count());
        $this->assertSame(self::TOKEN, PushDeviceToken::sole()->push_token);
    }

    // ── no duplicates ───────────────────────────────────────────────────

    public function test_the_same_handset_re_registering_updates_registered_at_instead_of_duplicating(): void
    {
        $this->travelTo(now()->subHour());
        $first = $this->register($this->alice, ['push_token' => self::TOKEN])->assertStatus(201)->json('token_id');
        $registeredAt = PushDeviceToken::sole()->registered_at;

        $this->travelBack();
        $second = $this->register($this->alice, ['push_token' => self::TOKEN])->assertStatus(201)->json('token_id');

        $this->assertSame($first, $second, 'Same handset, same row.');
        $this->assertSame(1, PushDeviceToken::count());
        $this->assertTrue(PushDeviceToken::sole()->registered_at->gt($registeredAt), 'registered_at moved forward.');
    }

    public function test_the_same_token_under_a_new_fingerprint_updates_the_existing_row(): void
    {
        // A reinstall regenerates the device fingerprint; Expo hands back the same token.
        $this->travelTo(now()->subHour());
        $first = $this->register($this->alice, ['push_token' => self::TOKEN])->assertStatus(201)->json('token_id');
        $registeredAt = PushDeviceToken::sole()->registered_at;

        $this->travelBack();
        $second = $this->register($this->alice, ['push_token' => self::TOKEN, 'device_fingerprint' => self::FINGERPRINT_B])
            ->assertStatus(201)
            ->json('token_id');

        $this->assertSame($first, $second, 'One phone, one row — even after a reinstall.');
        $this->assertSame(1, PushDeviceToken::count());

        $row = PushDeviceToken::sole();
        $this->assertSame(self::FINGERPRINT_B, $row->device_fingerprint);
        $this->assertTrue($row->registered_at->gt($registeredAt));

        // And exactly one push goes out.
        PatientPush::send($this->alice, 'message', 'T', 'B');
        $this->assertSame(1, $this->sendRequests());
    }

    public function test_a_revoked_handset_row_is_revived_by_re_registration(): void
    {
        $id = $this->register($this->alice, ['push_token' => self::TOKEN])->json('token_id');
        PushDeviceToken::findOrFail($id)->revoke();

        $this->register($this->alice, ['push_token' => self::TOKEN])->assertStatus(201)->assertJsonPath('token_id', $id);

        $row = PushDeviceToken::findOrFail($id);
        $this->assertTrue($row->is_active);
        $this->assertNull($row->revoked_at);
    }

    // ── one account per token ───────────────────────────────────────────

    public function test_a_token_moving_to_another_patient_revokes_the_old_row(): void
    {
        $aliceRow = $this->register($this->alice, ['push_token' => self::TOKEN])->assertStatus(201)->json('token_id');

        // Alice signs out; Bob signs in on the same phone. Same Expo token.
        $bobRow = $this->register($this->bob, ['push_token' => self::TOKEN])->assertStatus(201)->json('token_id');

        $this->assertNotSame($aliceRow, $bobRow);

        $alice = PushDeviceToken::findOrFail($aliceRow);
        $this->assertFalse($alice->is_active);
        $this->assertNotNull($alice->revoked_at);

        $bob = PushDeviceToken::findOrFail($bobRow);
        $this->assertTrue($bob->is_active);
        $this->assertSame(self::TOKEN, $bob->push_token);

        // Alice's pushes no longer reach the phone; Bob's do.
        $result = PatientPush::send($this->alice, 'message', 'T', 'B');
        $this->assertSame(0, $this->sendRequests());
        $this->assertTrue($result->hadNothingToSendTo());

        PatientPush::send($this->bob, 'message', 'T', 'B');
        $this->assertSame(1, $this->sendRequests());
    }

    public function test_the_original_owner_re_registering_takes_the_token_back(): void
    {
        $aliceRow = $this->register($this->alice, ['push_token' => self::TOKEN])->json('token_id');
        $bobRow   = $this->register($this->bob, ['push_token' => self::TOKEN])->json('token_id');

        $this->register($this->alice, ['push_token' => self::TOKEN])->assertStatus(201)->assertJsonPath('token_id', $aliceRow);

        $this->assertTrue(PushDeviceToken::findOrFail($aliceRow)->is_active);
        $this->assertFalse(PushDeviceToken::findOrFail($bobRow)->is_active);
    }

    // ── DELETE /api/mobile/push-tokens ──────────────────────────────────

    public function test_delete_with_a_fingerprint_revokes_only_that_handset(): void
    {
        $this->register($this->alice, ['push_token' => self::TOKEN]);
        $this->register($this->alice, ['push_token' => 'ExponentPushToken[hygiene-test-device-0002]', 'device_fingerprint' => self::FINGERPRINT_B, 'platform' => 'ios']);

        $this->revokeAll($this->alice, ['device_fingerprint' => self::FINGERPRINT_A])
            ->assertOk()
            ->assertJsonPath('status', 'revoked')
            ->assertJsonPath('revoked', 1)
            ->assertJsonPath('scope', 'device');

        $this->assertFalse(PushDeviceToken::where('device_fingerprint', self::FINGERPRINT_A)->sole()->is_active);
        $this->assertTrue(PushDeviceToken::where('device_fingerprint', self::FINGERPRINT_B)->sole()->is_active, 'The other phone keeps ringing.');
    }

    public function test_delete_without_a_fingerprint_revokes_every_token_of_the_account_and_is_idempotent(): void
    {
        $this->register($this->alice, ['push_token' => self::TOKEN]);
        $this->register($this->alice, ['push_token' => 'ExponentPushToken[hygiene-test-device-0002]', 'device_fingerprint' => self::FINGERPRINT_B, 'platform' => 'ios']);
        $this->register($this->bob, ['push_token' => 'ExponentPushToken[hygiene-test-device-0003]']);

        $this->revokeAll($this->alice)
            ->assertOk()
            ->assertJsonPath('revoked', 2)
            ->assertJsonPath('scope', 'account');

        $this->assertSame(0, PushDeviceToken::where('patient_id', $this->alice->id)->where('is_active', true)->count());
        $this->assertSame(1, PushDeviceToken::where('patient_id', $this->bob->id)->where('is_active', true)->count(), 'Another account is untouched.');

        $this->revokeAll($this->alice)->assertOk()->assertJsonPath('revoked', 0);

        PatientPush::send($this->alice, 'message', 'T', 'B');
        $this->assertSame(0, $this->sendRequests());
    }

    public function test_delete_requires_a_patient_session(): void
    {
        $this->deleteJson('/api/mobile/push-tokens')->assertStatus(401);
    }
}

<?php

namespace Tests\Feature\Mobile;

use App\Models\MobileAppSetting;
use App\Models\Patient;
use App\Models\PushDelivery;
use App\Models\PushDeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The operator's two commands say what is true, not what is hoped.
 *
 *  opescare:push-test   sends one push through PatientPush and prints the
 *                       tickets — or "push disabled" / "no active token" /
 *                       "suppressed" / "Refusing" with a non-zero exit.
 *  opescare:push-status prints the switch, whether an access token is
 *                       present (never its value), reachable devices by
 *                       platform, and the recent ledger.
 *
 * Expo is faked throughout; no real token or access token ever appears.
 */
class PushCommandsTest extends TestCase
{
    use RefreshDatabase;

    private const SEND = 'https://exp.host/--/api/v2/push/send';

    private const RECEIPTS = 'https://exp.host/--/api/v2/push/getReceipts';

    private const TOKEN = 'ExponentPushToken[commands-test-device-0001]';

    private const DEMO_HEALTH_ID = 'OC-TST-9100-0001-01';

    private const REAL_HEALTH_ID = 'OC-TST-9100-0002-01';

    private Patient $demo;

    private Patient $real;

    protected function setUp(): void
    {
        parent::setUp();

        config(['push_channels.expo.enabled' => true, 'push_channels.expo.access_token' => null]);

        $this->fakeExpo();

        // Tests run with demo mode off, so the Patient global scope hides demo
        // records; the commands look across the partition on purpose.
        $this->demo = Patient::factory()->create(['health_id' => self::DEMO_HEALTH_ID, 'is_demo' => true]);
        $this->real = Patient::factory()->create(['health_id' => self::REAL_HEALTH_ID, 'is_demo' => false]);
    }

    /**
     * Http::fake() stubs accumulate and the FIRST registered match wins, so a
     * test that needs different receipts must start from a fresh factory or
     * setUp's stub answers instead of its own.
     *
     * @param  array<string, mixed>  $receipts
     */
    private function fakeExpo(array $receipts = []): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory(app(\Illuminate\Contracts\Events\Dispatcher::class)));

        Http::fake([
            self::SEND     => Http::response(['data' => [['status' => 'ok', 'id' => 'ticket-0001']]], 200),
            self::RECEIPTS => Http::response(['data' => $receipts], 200),
            '*'            => Http::response([], 200),
        ]);
    }

    private function device(Patient $patient, string $token = self::TOKEN, string $platform = 'android'): PushDeviceToken
    {
        return PushDeviceToken::create([
            'patient_id'         => $patient->id,
            'device_fingerprint' => substr(md5($token.$platform), 0, 32),
            'platform'           => $platform,
            'push_token'         => $token,
            'is_active'          => true,
            'registered_at'      => now(),
        ]);
    }

    private function sendRequests(): int
    {
        return count(Http::recorded(fn (ClientRequest $r) => $r->url() === self::SEND));
    }

    // ── opescare:push-test ──────────────────────────────────────────────

    public function test_push_test_sends_to_a_demo_patient_and_prints_the_ticket(): void
    {
        $this->device($this->demo);

        $this->artisan('opescare:push-test', ['health_id' => self::DEMO_HEALTH_ID])
            ->expectsOutputToContain('demo record')
            ->expectsOutputToContain('1 accepted')
            ->expectsOutputToContain('ticket-0001')
            ->assertExitCode(0);

        $this->assertSame(1, $this->sendRequests());

        $push = Http::recorded(fn (ClientRequest $r) => $r->url() === self::SEND)[0][0]->data()[0];
        $this->assertSame(self::TOKEN, $push['to']);
        $this->assertSame('message', $push['data']['type']);
        $this->assertSame('messages', $push['channelId']);
        $this->assertSame(__('push.test.title', [], 'en'), $push['title']);

        $row = PushDelivery::sole();
        $this->assertSame($this->demo->id, $row->patient_id);
        $this->assertSame('ticket-0001', $row->ticket_id);
        $this->assertSame(PushDelivery::STATUS_PENDING, $row->status);
    }

    public function test_push_test_honours_the_type_option(): void
    {
        $this->device($this->demo);

        $this->artisan('opescare:push-test', ['health_id' => self::DEMO_HEALTH_ID, '--type' => 'consent_request'])
            ->expectsOutputToContain('never suppressed')
            ->assertExitCode(0);

        $push = Http::recorded(fn (ClientRequest $r) => $r->url() === self::SEND)[0][0]->data()[0];
        $this->assertSame('consent_request', $push['data']['type']);
        $this->assertSame('consent', $push['channelId']);
        $this->assertSame('opescare_consent.wav', $push['sound']);
    }

    public function test_push_test_refuses_a_real_patient_without_force(): void
    {
        $this->device($this->real);

        $this->artisan('opescare:push-test', ['health_id' => self::REAL_HEALTH_ID])
            ->expectsOutputToContain('REAL record')
            ->expectsOutputToContain('Refusing')
            ->assertExitCode(1);

        $this->assertSame(0, $this->sendRequests());
        $this->assertSame(0, PushDelivery::count());
    }

    public function test_push_test_sends_to_a_real_patient_with_force(): void
    {
        $this->device($this->real);

        $this->artisan('opescare:push-test', ['health_id' => self::REAL_HEALTH_ID, '--force' => true])
            ->expectsOutputToContain('1 accepted')
            ->assertExitCode(0);

        $this->assertSame(1, $this->sendRequests());
    }

    public function test_push_test_says_when_no_patient_holds_the_health_id(): void
    {
        $this->artisan('opescare:push-test', ['health_id' => 'OC-TST-0000-0000-00'])
            ->expectsOutputToContain('No patient holds that Health ID')
            ->assertExitCode(1);

        $this->assertSame(0, $this->sendRequests());
    }

    public function test_push_test_says_push_disabled_and_sends_nothing(): void
    {
        config(['push_channels.expo.enabled' => false]);
        $this->device($this->demo);

        $this->artisan('opescare:push-test', ['health_id' => self::DEMO_HEALTH_ID])
            ->expectsOutputToContain('push disabled')
            ->assertExitCode(1);

        $this->assertSame(0, $this->sendRequests());
    }

    public function test_push_test_says_no_active_token_when_nothing_is_registered(): void
    {
        $this->artisan('opescare:push-test', ['health_id' => self::DEMO_HEALTH_ID])
            ->expectsOutputToContain('no active token')
            ->assertExitCode(1);

        $this->assertSame(0, $this->sendRequests());
    }

    public function test_push_test_says_no_active_token_when_every_device_is_fingerprint_only(): void
    {
        $this->device($this->demo, 'a3f9c1e07b2d4e6f8a1b2c3d4e5f6071');
        $this->device($this->demo, '', 'web');

        // One substring per printed line: the device table says which rows are
        // fingerprint-only, the closing warning says nothing was sent.
        $this->artisan('opescare:push-test', ['health_id' => self::DEMO_HEALTH_ID])
            ->expectsOutputToContain('no (fingerprint-only)')
            ->expectsOutputToContain('no active token')
            ->assertExitCode(1);

        $this->assertSame(0, $this->sendRequests());
    }

    public function test_push_test_ignores_a_revoked_device(): void
    {
        $this->device($this->demo)->revoke();

        $this->artisan('opescare:push-test', ['health_id' => self::DEMO_HEALTH_ID])
            ->expectsOutputToContain('no active token')
            ->assertExitCode(1);
    }

    public function test_push_test_reports_a_suppressed_push_honestly(): void
    {
        $this->device($this->demo);
        MobileAppSetting::forPatient($this->demo->id)->forceFill(['push_messages' => false])->save();

        $this->artisan('opescare:push-test', ['health_id' => self::DEMO_HEALTH_ID, '--type' => 'message'])
            ->expectsOutputToContain('suppressed')
            ->expectsOutputToContain('push_messages')
            ->assertExitCode(1);

        $this->assertSame(0, $this->sendRequests());
    }

    public function test_push_test_never_prints_the_whole_token(): void
    {
        $this->device($this->demo);

        $this->artisan('opescare:push-test', ['health_id' => self::DEMO_HEALTH_ID])
            ->expectsOutputToContain('ExponentPushToken[...0001]')
            ->doesntExpectOutputToContain(self::TOKEN)
            ->assertExitCode(0);
    }

    public function test_push_test_with_receipts_reads_them_onto_the_ledger(): void
    {
        $this->fakeExpo(['ticket-0001' => ['status' => 'ok']]);
        $this->device($this->demo);

        $this->artisan('opescare:push-test', ['health_id' => self::DEMO_HEALTH_ID, '--receipts' => true])
            ->expectsOutputToContain('Asking Expo for receipts')
            ->assertExitCode(0);

        $this->assertSame(PushDelivery::STATUS_OK, PushDelivery::sole()->status);
    }

    public function test_push_test_with_receipts_says_when_none_is_available_yet(): void
    {
        $this->fakeExpo([]);
        $this->device($this->demo);

        $this->artisan('opescare:push-test', ['health_id' => self::DEMO_HEALTH_ID, '--receipts' => true])
            ->expectsOutputToContain('Receipt not available yet')
            ->assertExitCode(0);

        $this->assertSame(PushDelivery::STATUS_PENDING, PushDelivery::sole()->status);
    }

    public function test_push_test_follows_an_active_health_id_alias(): void
    {
        $this->device($this->demo);
        \App\Models\HealthIdAlias::create([
            'patient_id'  => $this->demo->id,
            'alias_type'  => 'health_id',
            'alias_value' => 'OC-TST-9100-0009-09',
            'status'      => 'active',
        ]);

        $this->artisan('opescare:push-test', ['health_id' => 'oc-tst-9100-0009-09'])
            ->expectsOutputToContain(self::DEMO_HEALTH_ID)
            ->assertExitCode(0);
    }

    // ── opescare:push-status ────────────────────────────────────────────

    public function test_push_status_prints_the_switch_the_schedule_and_the_counts(): void
    {
        $this->device($this->demo);                                                // android, deliverable
        $this->device($this->demo, 'ExponentPushToken[commands-test-device-0002]', 'ios');
        $this->device($this->real, '', 'web');                                     // fingerprint-only
        $this->device($this->real, 'ExponentPushToken[commands-test-device-0003]', 'android')->revoke();

        PushDelivery::create(['patient_id' => $this->demo->id, 'platform' => 'android', 'type' => 'message', 'ticket_id' => 'ticket-ok', 'status' => PushDelivery::STATUS_OK, 'sent_at' => now()->subMinutes(30), 'receipt_checked_at' => now()]);
        PushDelivery::create(['patient_id' => $this->demo->id, 'platform' => 'ios', 'type' => 'lab_result', 'ticket_id' => 'ticket-pending', 'status' => PushDelivery::STATUS_PENDING, 'sent_at' => now()->subMinutes(2)]);
        PushDelivery::create(['patient_id' => $this->real->id, 'platform' => 'android', 'type' => 'appointment', 'status' => PushDelivery::STATUS_ERROR, 'error_code' => 'DeviceNotRegistered', 'sent_at' => now()->subHour()]);

        $this->artisan('opescare:push-status')
            ->expectsOutputToContain('enabled')
            ->expectsOutputToContain('not set')
            ->expectsOutputToContain('scheduled (*/15 * * * *')
            ->expectsOutputToContain('DeviceNotRegistered')
            ->expectsOutputToContain('lab_result')
            ->assertExitCode(0);
    }

    public function test_push_status_says_disabled_when_the_switch_is_off(): void
    {
        config(['push_channels.expo.enabled' => false]);

        $this->artisan('opescare:push-status')
            ->expectsOutputToContain('DISABLED')
            ->assertExitCode(0);
    }

    public function test_push_status_reports_an_access_token_as_present_and_never_prints_it(): void
    {
        config(['push_channels.expo.access_token' => 'not-a-real-expo-access-token-9f3a']);

        $this->artisan('opescare:push-status')
            ->expectsOutputToContain('present')
            ->doesntExpectOutputToContain('not-a-real-expo-access-token-9f3a')
            ->assertExitCode(0);
    }

    public function test_push_status_runs_on_an_empty_host(): void
    {
        $this->artisan('opescare:push-status')
            ->expectsOutputToContain('none registered')
            ->expectsOutputToContain('none yet')
            ->assertExitCode(0);
    }
}

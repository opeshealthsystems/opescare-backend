<?php

namespace Tests\Feature\Mobile;

use App\Models\Patient;
use App\Models\PushDeviceToken;
use App\Services\ExpoPushResult;
use App\Services\ExpoPushService;
use App\Support\PatientPush;
use App\Support\PushChannels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Every push the platform sends carries a sound and an Android channel that
 * fit its kind — and the shared PatientPush entry point keeps its promises.
 */
class PushChannelsTest extends TestCase
{
    use RefreshDatabase;

    private const EXPO_SEND = 'https://exp.host/--/api/v2/push/send';

    private const TOKEN = 'ExponentPushToken[channels-test-device-0001]';

    protected function setUp(): void
    {
        parent::setUp();

        config(['push_channels.expo.enabled' => true]);

        Http::fake([
            'exp.host/*' => Http::response(['data' => [['status' => 'ok', 'id' => 'ticket-0001']]], 200),
            '*'          => Http::response([], 200),
        ]);
    }

    /** @return list<array<string, mixed>> */
    private function expoMessages(): array
    {
        $messages = [];

        foreach (Http::recorded(fn (ClientRequest $r) => $r->url() === self::EXPO_SEND) as [$request]) {
            foreach ((array) $request->data() as $message) {
                $messages[] = $message;
            }
        }

        return $messages;
    }

    private function patientWithDevice(): Patient
    {
        $patient = Patient::create([
            'health_id'     => 'OC-TST-8600-0001-01',
            'first_name'    => 'Chantal',
            'last_name'     => 'Channel',
            'sex'           => 'female',
            'date_of_birth' => '1992-02-02',
        ]);

        PushDeviceToken::create([
            'patient_id'         => $patient->id,
            'device_fingerprint' => substr(md5(self::TOKEN), 0, 32),
            'platform'           => 'ios',
            'push_token'         => self::TOKEN,
            'is_active'          => true,
            'registered_at'      => now(),
        ]);

        return $patient;
    }

    // ── the map ─────────────────────────────────────────────────────────

    public function test_every_type_lands_on_its_channel_with_its_sound(): void
    {
        $expected = [
            'consent_request'   => ['consent',      'opescare_consent.wav'],
            'family_invitation' => ['consent',      'opescare_consent.wav'],
            'lab_result'        => ['results',      'opescare_result.wav'],
            'appointment'       => ['appointments', 'opescare_appointment.wav'],
            'message'           => ['messages',     'opescare_message.wav'],
            'emergency_access'  => ['emergency',    'opescare_alert.wav'],
            // Counter events and laboratory request decisions reuse the message /
            // result sounds; the app routes them by type.
            'prescription'      => ['messages',     'opescare_message.wav'],
            'reservation'       => ['messages',     'opescare_message.wav'],
            'lab_request'       => ['results',      'opescare_result.wav'],
        ];

        foreach ($expected as $type => [$channel, $sound]) {
            $this->assertSame($channel, PushChannels::channelFor($type), "channel for {$type}");
            $this->assertSame($sound, PushChannels::soundFor($channel), "sound for {$channel}");
            $this->assertSame($sound, PushChannels::soundForType($type), "sound for type {$type}");
        }

        $this->assertSame('default', PushChannels::soundFor('default'));
        $this->assertSame(array_keys($expected), array_keys(PushChannels::types()), 'Every documented type is in the config, and nothing else.');
    }

    public function test_unknown_types_and_channels_fall_back_to_default(): void
    {
        $this->assertSame('default', PushChannels::channelFor('not_a_type'));
        $this->assertSame('default', PushChannels::channelFor(null));
        $this->assertSame('default', PushChannels::channelFor(''));
        $this->assertSame('default', PushChannels::soundFor('not_a_channel'));
        $this->assertSame('default', PushChannels::soundFor(null));
        $this->assertSame('default', PushChannels::soundForType('not_a_type'));
    }

    public function test_a_type_pointing_at_an_undeclared_channel_falls_back_to_default(): void
    {
        config(['push_channels.types.rogue' => 'no_such_channel']);

        $this->assertSame('default', PushChannels::channelFor('rogue'));
    }

    // ── ExpoPushService wire shape ──────────────────────────────────────

    public function test_send_to_tokens_defaults_the_sound_to_the_channels_sound(): void
    {
        app(ExpoPushService::class)->sendToTokens([self::TOKEN], 'Result reviewed', 'Open the app.', ['type' => 'lab_result', 'lab_order_id' => 'abc'], 'results');

        $push = $this->expoMessages()[0] ?? null;
        $this->assertNotNull($push);
        $this->assertSame('opescare_result.wav', $push['sound']);
        $this->assertSame('results', $push['channelId']);
        $this->assertSame('high', $push['priority']);
        $this->assertSame('lab_result', $push['data']['type']);
        $this->assertSame('abc', $push['data']['lab_order_id']);
    }

    public function test_callers_passing_nothing_keep_the_previous_default_behaviour(): void
    {
        app(ExpoPushService::class)->sendToTokens([self::TOKEN], 'Hello', 'World');

        $push = $this->expoMessages()[0] ?? null;
        $this->assertNotNull($push);
        $this->assertSame('default', $push['sound']);
        $this->assertSame('default', $push['channelId']);
        $this->assertSame('high', $push['priority']);
        $this->assertArrayNotHasKey('data', $push);
    }

    public function test_an_explicit_sound_wins_over_the_channel_default(): void
    {
        app(ExpoPushService::class)->sendToTokens([self::TOKEN], 'T', 'B', ['type' => 'message'], 'messages', 'high', 'opescare_alert.wav');

        $push = $this->expoMessages()[0] ?? null;
        $this->assertNotNull($push);
        $this->assertSame('opescare_alert.wav', $push['sound']);
        $this->assertSame('messages', $push['channelId']);
    }

    public function test_is_configured_follows_the_switch(): void
    {
        $expo = app(ExpoPushService::class);

        config(['push_channels.expo.enabled' => true]);
        $this->assertTrue($expo->isConfigured());

        config(['push_channels.expo.enabled' => false]);
        $this->assertFalse($expo->isConfigured());
    }

    // ── PatientPush ─────────────────────────────────────────────────────

    public function test_patient_push_picks_the_channel_from_the_type_and_stamps_type_into_data(): void
    {
        $patient = $this->patientWithDevice();

        $result = PatientPush::send($patient, 'appointment', 'Appointment reminder', 'Tomorrow at 09:00.', [
            'appointment_id' => 'appt-1',
            'action_url'     => '/appointments/appt-1',
            'type'           => 'something_else',   // a caller cannot override the type
            'ignored'        => null,               // nulls are dropped, not sent as ""
        ]);

        $this->assertInstanceOf(ExpoPushResult::class, $result);
        $this->assertTrue($result->queued());

        $push = $this->expoMessages()[0] ?? null;
        $this->assertNotNull($push);
        $this->assertSame(self::TOKEN, $push['to']);
        $this->assertSame('appointments', $push['channelId']);
        $this->assertSame('opescare_appointment.wav', $push['sound']);
        $this->assertSame('appointment', $push['data']['type']);
        $this->assertSame('appt-1', $push['data']['appointment_id']);
        $this->assertSame('/appointments/appt-1', $push['data']['action_url']);
        $this->assertArrayNotHasKey('ignored', $push['data']);
    }

    public function test_patient_push_is_a_no_op_when_expo_push_is_switched_off(): void
    {
        config(['push_channels.expo.enabled' => false]);
        $patient = $this->patientWithDevice();

        $result = PatientPush::send($patient, 'consent_request', 'Access request', 'A facility is asking.', ['consent_request_id' => 'x']);

        $this->assertSame([], $this->expoMessages());
        $this->assertTrue($result->hadNothingToSendTo());
        $this->assertFalse($result->queued());
    }

    public function test_patient_push_never_throws(): void
    {
        Http::fake([
            'exp.host/*' => fn () => throw new ConnectionException('exp.host unreachable'),
            '*'          => Http::response([], 200),
        ]);
        $patient = $this->patientWithDevice();

        $result = PatientPush::send($patient, 'message', 'New message', 'You have a new message.', ['thread_id' => 't-1']);

        $this->assertInstanceOf(ExpoPushResult::class, $result);
        $this->assertFalse($result->queued());
        $this->assertNotNull($result->transportError);
    }

    public function test_patient_push_with_no_device_sends_nothing_and_reports_it(): void
    {
        $patient = Patient::create([
            'health_id'     => 'OC-TST-8600-0002-01',
            'first_name'    => 'Nobody',
            'last_name'     => 'Registered',
            'sex'           => 'male',
            'date_of_birth' => '1970-01-01',
        ]);

        $result = PatientPush::send($patient, 'lab_result', 'Result reviewed', 'Open the app.', ['lab_order_id' => 'l-1']);

        $this->assertSame([], $this->expoMessages());
        $this->assertTrue($result->hadNothingToSendTo());
    }

    public function test_locale_for_reads_the_patients_app_setting(): void
    {
        $patient = $this->patientWithDevice();

        $this->assertSame('en', PatientPush::localeFor($patient), 'No setting → the application locale (en in tests).');

        \App\Models\MobileAppSetting::create(['patient_id' => $patient->id, 'preferred_language' => 'fr-CM']);
        $this->assertSame('fr', PatientPush::localeFor($patient));

        \App\Models\MobileAppSetting::where('patient_id', $patient->id)->update(['preferred_language' => 'de']);
        $this->assertSame('en', PatientPush::localeFor($patient), 'A language we have no strings for falls back.');
    }
}

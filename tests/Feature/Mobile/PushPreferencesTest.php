<?php

namespace Tests\Feature\Mobile;

use App\Models\MobileAppSetting;
use App\Models\Patient;
use App\Models\PushDelivery;
use App\Models\PushDeviceToken;
use App\Support\PatientPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The patient's notification switches, honoured on the push path.
 *
 * mobile_app_settings.push_* are what PATCH /api/mobile/settings writes. A
 * `false` there stops the matching kind of push before it is built — no Expo
 * request, no ledger row, and the result says which switch did it. Two kinds
 * are exempt whatever the switches say: a consent decision (consent_request,
 * family_invitation) and an emergency-access alert must reach the phone.
 */
class PushPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private const SEND = 'https://exp.host/--/api/v2/push/send';

    private const TOKEN = 'ExponentPushToken[preferences-test-device-0001]';

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        config(['push_channels.expo.enabled' => true]);

        Http::fake([
            self::SEND => Http::response(['data' => [['status' => 'ok', 'id' => 'ticket-0001']]], 200),
            '*'        => Http::response([], 200),
        ]);

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-8900-0001-01',
            'first_name'    => 'Prudence',
            'last_name'     => 'Preference',
            'sex'           => 'female',
            'date_of_birth' => '1987-07-07',
        ]);

        PushDeviceToken::create([
            'patient_id'         => $this->patient->id,
            'device_fingerprint' => substr(md5(self::TOKEN), 0, 32),
            'platform'           => 'ios',
            'push_token'         => self::TOKEN,
            'is_active'          => true,
            'registered_at'      => now(),
        ]);
    }

    /** @param  array<string, bool>  $switches */
    private function settings(array $switches): MobileAppSetting
    {
        $settings = MobileAppSetting::forPatient($this->patient->id);
        $settings->forceFill($switches)->save();

        return $settings->refresh();
    }

    private function sendRequests(): int
    {
        return count(Http::recorded(fn (ClientRequest $r) => $r->url() === self::SEND));
    }

    /** @return array<string, array{0: string, 1: string}> type => governing column */
    public static function governedTypes(): array
    {
        return [
            'message'      => ['message',      'push_messages'],
            'appointment'  => ['appointment',  'push_appointments'],
            'lab_result'   => ['lab_result',   'push_lab_results'],
            'lab_request'  => ['lab_request',  'push_lab_results'],
            'prescription' => ['prescription', 'push_prescriptions'],
            'reservation'  => ['reservation',  'push_prescriptions'],
        ];
    }

    #[DataProvider('governedTypes')]
    public function test_an_opt_out_suppresses_the_push_before_it_is_built(string $type, string $column): void
    {
        $this->settings([$column => false]);

        $result = PatientPush::send($this->patient, $type, 'Title', 'Body', ['id' => 'x']);

        $this->assertSame(0, $this->sendRequests(), "A {$type} push went to Expo despite {$column}=false.");
        $this->assertSame(0, PushDelivery::count(), 'Nothing was attempted, so nothing is in the ledger.');
        $this->assertTrue($result->suppressed());
        $this->assertSame($column, $result->suppressedBy);
        $this->assertTrue($result->hadNothingToSendTo());
        $this->assertFalse($result->queued());
        $this->assertSame($column, $result->toArray()['suppressed_by']);
    }

    #[DataProvider('governedTypes')]
    public function test_a_switch_left_on_lets_the_push_through(string $type, string $column): void
    {
        $this->settings([$column => true]);

        $result = PatientPush::send($this->patient, $type, 'Title', 'Body');

        $this->assertSame(1, $this->sendRequests());
        $this->assertTrue($result->queued());
        $this->assertFalse($result->suppressed());
    }

    public function test_a_patient_with_no_settings_row_is_opted_in(): void
    {
        $this->assertSame(0, MobileAppSetting::count());

        $result = PatientPush::send($this->patient, 'message', 'Title', 'Body');

        $this->assertSame(1, $this->sendRequests());
        $this->assertTrue($result->queued());
    }

    public function test_switching_one_kind_off_does_not_silence_another(): void
    {
        $this->settings(['push_appointments' => false]);

        PatientPush::send($this->patient, 'appointment', 'Title', 'Body');
        $this->assertSame(0, $this->sendRequests());

        PatientPush::send($this->patient, 'lab_result', 'Title', 'Body');
        $this->assertSame(1, $this->sendRequests());
    }

    /** @return array<string, array{0: string}> */
    public static function alwaysDelivered(): array
    {
        return [
            'consent_request'   => ['consent_request'],
            'family_invitation' => ['family_invitation'],
            'emergency_access'  => ['emergency_access'],
        ];
    }

    #[DataProvider('alwaysDelivered')]
    public function test_a_decision_or_an_emergency_is_never_suppressed(string $type): void
    {
        // Every switch off — including push_consent_requests, which governs the
        // in-app inbox and the settings screen, not whether the phone rings.
        $this->settings([
            'push_appointments'     => false,
            'push_lab_results'      => false,
            'push_prescriptions'    => false,
            'push_billing'          => false,
            'push_consent_requests' => false,
            'push_messages'         => false,
        ]);

        $result = PatientPush::send($this->patient, $type, 'Title', 'Body', ['id' => 'x']);

        $this->assertSame(1, $this->sendRequests(), "{$type} must reach the phone whatever the switches say.");
        $this->assertTrue($result->queued());
        $this->assertFalse($result->suppressed());
        $this->assertNull(PatientPush::preferenceFor($type));
        $this->assertContains($type, PatientPush::ALWAYS_DELIVERED);
    }

    public function test_the_map_names_only_real_columns_and_the_exempt_types_are_not_in_it(): void
    {
        foreach (PatientPush::PREFERENCE_FOR_TYPE as $type => $column) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Schema::hasColumn('mobile_app_settings', $column),
                "PatientPush maps {$type} to mobile_app_settings.{$column}, which does not exist.",
            );
            $this->assertNotContains($type, PatientPush::ALWAYS_DELIVERED);
        }
    }

    public function test_the_switch_is_read_at_send_time_not_cached(): void
    {
        $this->settings(['push_messages' => false]);
        PatientPush::send($this->patient, 'message', 'Title', 'Body');
        $this->assertSame(0, $this->sendRequests());

        $this->settings(['push_messages' => true]);
        PatientPush::send($this->patient, 'message', 'Title', 'Body');
        $this->assertSame(1, $this->sendRequests());
    }
}

<?php

namespace Tests\Feature\Security;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\PushDeviceToken;
use App\Modules\Governance\Services\EmergencyAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Breaking the glass must reach the patient's phone.
 *
 * Emergency access is the one door that opens without the patient's consent,
 * so the whole governance case for it rests on them being told at once. A
 * database row alone is not being told: it waits to be discovered. The alert
 * therefore also goes out as a push, on the emergency channel with its own
 * sound, and it names the facility rather than an id — "CHU de Yaounde opened
 * your record" is the sentence that means something at 2am.
 *
 * `emergency_access` is a type PatientPush never suppresses: a person cannot
 * opt out of being told their record was opened without their permission.
 */
class BreakGlassAlertReachesPatientTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'ExponentPushToken[break-glass-test-000001]';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('push_channels.expo.enabled', true);
        Http::fake(['*exp.host*' => Http::response(['data' => [['status' => 'ok', 'id' => 'ticket-1']]], 200)]);
    }

    public function test_the_patient_is_pushed_the_alert_with_the_facility_name(): void
    {
        [$patient, $facility, $clinicianId] = $this->scene();

        $result = app(EmergencyAccessService::class)->openBreakGlass(
            $patient,
            $facility->id,
            $clinicianId,
            'Unconscious road-traffic casualty, no next of kin present.',
            '10.0.0.9',
        );

        $this->assertTrue($result['notified'], 'the break-glass must report the patient was told');

        $sent = null;
        Http::assertSent(function ($request) use (&$sent) {
            if (! str_contains($request->url(), 'exp.host')) {
                return false;
            }
            $sent = $request->data()[0] ?? null;

            return true;
        });

        $this->assertNotNull($sent, 'a push must be sent to the patient');
        $this->assertSame(self::TOKEN, $sent['to']);
        $this->assertSame('emergency', $sent['channelId']);
        $this->assertSame('opescare_alert.wav', $sent['sound']);
        $this->assertSame('emergency_access', $sent['data']['type']);
        $this->assertStringContainsString('CHU de Yaounde', $sent['body'], 'the alert names the facility, not an id');
    }

    public function test_the_bell_links_to_a_page_that_exists(): void
    {
        [$patient, $facility, $clinicianId] = $this->scene();

        app(EmergencyAccessService::class)->openBreakGlass(
            $patient,
            $facility->id,
            $clinicianId,
            'Unresponsive patient in resuscitation.',
            null,
        );

        $bell = \DB::table('notifications')->where('notifiable_id', $patient->id)->first();
        $this->assertNotNull($bell, 'the alert is also written to the record');

        $data = json_decode($bell->data, true);
        $this->assertSame('/patient/access-logs', $data['action_url']);
        // The route the link names is really served (it used to 404 on live).
        $this->assertTrue(
            app('router')->getRoutes()->hasNamedRoute('portals.patient.access-logs'),
            'the access-log page the alert points at must exist'
        );
    }

    public function test_a_push_failure_never_costs_the_patient_their_alert_or_the_clinician_their_access(): void
    {
        Http::fake(['*exp.host*' => Http::response('gateway down', 500)]);

        [$patient, $facility, $clinicianId] = $this->scene();

        $result = app(EmergencyAccessService::class)->openBreakGlass(
            $patient,
            $facility->id,
            $clinicianId,
            'Cardiac arrest, glass broken by the attending.',
            null,
        );

        $this->assertTrue($result['notified']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $patient->id]);
        $this->assertNotNull($result['event']);
    }

    /** A patient with a real phone token, a named facility, and a clinician. */
    private function scene(): array
    {
        $patient = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);

        PushDeviceToken::create([
            'patient_id'         => $patient->id,
            'device_fingerprint' => substr(md5(self::TOKEN), 0, 32),
            'platform'           => 'android',
            'push_token'         => self::TOKEN,
            'is_active'          => true,
            'registered_at'      => now(),
        ]);

        $facility = Facility::factory()->create(['name' => 'CHU de Yaounde']);

        return [$patient, $facility, (string) \App\Models\User::factory()->create()->id];
    }
}

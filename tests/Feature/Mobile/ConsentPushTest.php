<?php

namespace Tests\Feature\Mobile;

use App\Models\ConsentRequest;
use App\Models\Facility;
use App\Models\FamilyLink;
use App\Models\MobileAppSetting;
use App\Models\Patient;
use App\Models\PushDeviceToken;
use App\Models\Role;
use App\Models\User;
use App\Modules\Governance\Services\ConsentService;
use App\Services\Family\FamilyInvitationNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * A professional asks for access → the patient's phone rings with the
 * hospital chime and the app can raise its Approve/Deny prompt.
 *
 * Three things are pinned here:
 *
 *  1. ConsentService::requestConsent() sends exactly one Expo push per
 *     deliverable device, on the `consent` channel with the consent sound,
 *     carrying data.type=consent_request and the request id — and nothing
 *     from the record.
 *  2. The push is a courtesy: no token, a switched-off host, or Expo being
 *     down never changes what requestConsent() persists or returns.
 *  3. The two endpoints the app polls — `?status=pending` and
 *     `/pending-count` — agree on what "pending" means (open AND not lapsed)
 *     and the list carries what the prompt renders.
 */
class ConsentPushTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private const EXPO_SEND = 'https://exp.host/--/api/v2/push/send';

    private const TOKEN = 'ExponentPushToken[consent-test-device-0001]';

    private Patient $patient;

    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        config(['push_channels.expo.enabled' => true]);

        $this->fakeExpo();

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-8500-0001-01',
            'first_name'    => 'Clarisse',
            'last_name'     => 'Consent',
            'sex'           => 'female',
            'date_of_birth' => '1988-04-12',
        ]);

        $this->facility = Facility::factory()->create(['name' => 'Hôpital Central de Yaoundé']);
    }

    // ── helpers ─────────────────────────────────────────────────────────

    private function fakeExpo(mixed $expo = null): void
    {
        Http::fake([
            'exp.host/*' => $expo ?? Http::response(['data' => [['status' => 'ok', 'id' => 'ticket-0001']]], 200),
            // Anything else the consent path might reach (SMS/email fan-out)
            // is stubbed so a test never talks to the network.
            '*'          => Http::response([], 200),
        ]);
    }

    private function registerDevice(Patient $patient, string $token = self::TOKEN, bool $active = true): PushDeviceToken
    {
        return PushDeviceToken::create([
            'patient_id'         => $patient->id,
            'device_fingerprint' => substr(md5($token), 0, 32),
            'platform'           => 'android',
            'push_token'         => $token,
            'is_active'          => $active,
            'registered_at'      => now(),
            'revoked_at'         => $active ? null : now(),
        ]);
    }

    private function requestConsent(?Facility $facility = null, ?User $user = null): ConsentRequest
    {
        return app(ConsentService::class)->requestConsent(
            $this->patient->id,
            ($facility ?? $this->facility)->id,
            $user?->id,
            'treatment',
            ['patient.summary', 'allergies.read'],
            60,
            now()->addHours(24),
            'Follow-up after your visit',
        );
    }

    /** @return list<array<string, mixed>> every message body posted to Expo's send endpoint */
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

    private function openRequestFor(Facility $facility, ?\DateTimeInterface $expiresAt = null, string $status = 'pending_patient_approval'): ConsentRequest
    {
        $row = new ConsentRequest();
        $row->patient_id             = $this->patient->id;
        $row->requesting_facility_id = $facility->id;
        $row->purpose                = 'treatment';
        $row->requested_scope        = ['patient.summary'];
        $row->duration_minutes       = 60;
        $row->status                 = $status;
        $row->expires_at             = $expiresAt;
        $row->save();

        return $row;
    }

    // ── the push itself ─────────────────────────────────────────────────

    public function test_a_consent_request_pushes_the_hospital_chime_to_the_patients_device(): void
    {
        $this->registerDevice($this->patient);

        $request = $this->requestConsent();

        $messages = $this->expoMessages();
        $this->assertCount(1, $messages, 'Exactly one push per deliverable device.');

        $push = $messages[0];
        $this->assertSame(self::TOKEN, $push['to']);
        $this->assertSame('opescare_consent.wav', $push['sound']);
        $this->assertSame('consent', $push['channelId']);
        $this->assertSame('high', $push['priority']);
        $this->assertSame('consent_request', $push['data']['type']);
        $this->assertSame($request->id, $push['data']['consent_request_id']);
        $this->assertSame('Hôpital Central de Yaoundé', $push['data']['facility_name']);
        $this->assertSame('/consent-requests', $push['data']['action_url']);

        // Lock-screen copy: the facility and the ask, in the patient's locale.
        $this->assertSame(__('push.consent_request.title', [], 'en'), $push['title']);
        $this->assertSame(__('push.consent_request.body', ['facility' => 'Hôpital Central de Yaoundé'], 'en'), $push['body']);

        // Nothing from the record travels on the wire.
        $this->assertArrayNotHasKey('requested_scope', $push['data']);
        $this->assertArrayNotHasKey('justification', $push['data']);
    }

    public function test_the_push_speaks_the_patients_preferred_language(): void
    {
        $this->registerDevice($this->patient);
        MobileAppSetting::create(['patient_id' => $this->patient->id, 'preferred_language' => 'fr']);

        $this->requestConsent();

        $push = $this->expoMessages()[0] ?? null;
        $this->assertNotNull($push);
        $this->assertSame(__('push.consent_request.title', [], 'fr'), $push['title']);
        $this->assertSame(__('push.consent_request.body', ['facility' => 'Hôpital Central de Yaoundé'], 'fr'), $push['body']);
        $this->assertNotSame(__('push.consent_request.title', [], 'en'), $push['title'], 'FR and EN titles must differ for this test to mean anything.');
    }

    public function test_no_registered_device_means_no_call_to_expo(): void
    {
        $request = $this->requestConsent();

        $this->assertSame([], $this->expoMessages());
        $this->assertDatabaseHas('consent_requests', ['id' => $request->id, 'status' => 'pending_patient_approval']);
    }

    public function test_revoked_and_fingerprint_rows_are_not_pushed_to(): void
    {
        $this->registerDevice($this->patient, self::TOKEN, active: false);
        $this->registerDevice($this->patient, 'a3f9c1e07b2d4e6f8a1b2c3d4e5f6071'); // legacy fingerprint, active

        $this->requestConsent();

        $this->assertSame([], $this->expoMessages(), 'Neither a revoked token nor a fingerprint may reach Expo.');
    }

    public function test_a_switched_off_host_sends_nothing(): void
    {
        config(['push_channels.expo.enabled' => false]);
        $this->registerDevice($this->patient);

        $request = $this->requestConsent();

        $this->assertSame([], $this->expoMessages());
        $this->assertDatabaseHas('consent_requests', ['id' => $request->id]);
    }

    public function test_expo_being_down_never_fails_the_consent_request(): void
    {
        $this->registerDevice($this->patient);
        Http::fake([
            'exp.host/*' => fn () => throw new ConnectionException('exp.host unreachable'),
            '*'          => Http::response([], 200),
        ]);

        $request = $this->requestConsent();

        $this->assertDatabaseHas('consent_requests', ['id' => $request->id, 'status' => 'pending_patient_approval']);
        // The in-app row (what the inbox reads) is still written.
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->patient->id,
            'type'          => \App\Notifications\ConsentAccessRequestedNotification::class,
        ]);
    }

    public function test_expo_rejecting_the_send_never_fails_the_consent_request(): void
    {
        $this->registerDevice($this->patient);
        $this->fakeExpo(Http::response(['errors' => [['code' => 'PUSH_TOO_MANY_EXPERIENCE_IDS']]], 400));

        $request = $this->requestConsent();

        $this->assertDatabaseHas('consent_requests', ['id' => $request->id, 'status' => 'pending_patient_approval']);
    }

    public function test_a_family_invitation_rides_the_consent_channel_too(): void
    {
        $this->registerDevice($this->patient);
        $guardian = User::factory()->create(['name' => 'Marie Ngo']);
        $link     = FamilyLink::factory()->create([
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $this->patient->id,
            'relationship'         => 'parent',
        ]);

        FamilyInvitationNotifier::invited($link, $this->patient);

        $messages = $this->expoMessages();
        $this->assertCount(1, $messages);
        $this->assertSame('family_invitation', $messages[0]['data']['type']);
        $this->assertSame('consent', $messages[0]['channelId']);
        $this->assertSame('opescare_consent.wav', $messages[0]['sound']);
        $this->assertSame($link->id, $messages[0]['data']['family_link_id']);
        $this->assertSame('/family', $messages[0]['data']['action_url']);
        $this->assertStringContainsString('Marie Ngo', $messages[0]['body']);
    }

    // ── what the app polls ──────────────────────────────────────────────

    public function test_status_pending_lists_only_open_requests_that_have_not_lapsed(): void
    {
        $open = $this->requestConsent();

        $lapsed  = $this->openRequestFor(Facility::factory()->create(), now()->subHour());
        $denied  = $this->openRequestFor(Facility::factory()->create(), null, 'denied');
        $forever = $this->openRequestFor(Facility::factory()->create(), null); // no expiry → still open

        $response = $this->mobileGetJson($this->patient, '/api/mobile/consent-requests?status=pending');
        $response->assertOk();

        $ids = array_column($response->json(), 'id');
        sort($ids);
        $expected = [$open->id, $forever->id];
        sort($expected);

        $this->assertSame($expected, $ids);
        $this->assertNotContains($lapsed->id, $ids);
        $this->assertNotContains($denied->id, $ids);

        // No filter → everything, as before.
        $this->assertCount(4, $this->mobileGetJson($this->patient, '/api/mobile/consent-requests')->json());

        // Garbage is refused rather than silently ignored.
        $this->mobileGetJson($this->patient, '/api/mobile/consent-requests?status=whatever')->assertStatus(422);
    }

    public function test_each_pending_item_carries_what_the_prompt_renders(): void
    {
        $role = Role::create(['name' => 'doctor', 'description' => 'Doctor']);
        $user = User::factory()->create(['name' => 'Dr Awa Mbeki']);
        $user->role_id = $role->id;
        $user->save();

        $request = $this->requestConsent(user: $user);

        $item = $this->mobileGetJson($this->patient, '/api/mobile/consent-requests?status=pending')
            ->assertOk()
            ->json('0');

        $this->assertSame($request->id, $item['id']);
        $this->assertSame('pending_patient_approval', $item['status']);
        $this->assertSame('treatment', $item['purpose']);
        $this->assertSame(['patient.summary', 'allergies.read'], $item['requested_scope']);
        $this->assertSame(60, $item['duration_minutes']);
        $this->assertSame('Follow-up after your visit', $item['justification']);
        $this->assertNotNull($item['created_at']);
        $this->assertNotNull($item['expires_at']);
        $this->assertSame($this->facility->id, $item['requesting_facility']['id']);
        $this->assertSame('Hôpital Central de Yaoundé', $item['requesting_facility']['name']);
        $this->assertSame('Dr Awa Mbeki', $item['requesting_user']['name']);
        $this->assertSame('doctor', $item['requesting_user']['role']);

        // The flat keys the Privacy screen already reads are untouched.
        $this->assertSame('Hôpital Central de Yaoundé', $item['requesting_facility_name']);
        $this->assertSame($this->facility->id, $item['requesting_facility_id']);
    }

    public function test_pending_count_agrees_with_the_pending_list(): void
    {
        $this->mobileGetJson($this->patient, '/api/mobile/consent-requests/pending-count')
            ->assertOk()
            ->assertExactJson(['data' => ['pending' => 0]]);

        $this->requestConsent();
        $this->openRequestFor(Facility::factory()->create(), now()->subMinute());   // lapsed — not counted
        $this->openRequestFor(Facility::factory()->create(), null, 'approved');    // decided — not counted
        $this->openRequestFor(Facility::factory()->create(), now()->addDay());     // open — counted

        $this->mobileGetJson($this->patient, '/api/mobile/consent-requests/pending-count')
            ->assertOk()
            ->assertExactJson(['data' => ['pending' => 2]]);

        $this->assertCount(2, $this->mobileGetJson($this->patient, '/api/mobile/consent-requests?status=pending')->json());
    }

    public function test_pending_count_requires_a_patient_session(): void
    {
        $this->getJson('/api/mobile/consent-requests/pending-count')->assertStatus(401);
    }

    public function test_another_patients_requests_are_neither_listed_nor_counted(): void
    {
        $other = Patient::create([
            'health_id'     => 'OC-TST-8500-0002-01',
            'first_name'    => 'Other',
            'last_name'     => 'Patient',
            'sex'           => 'male',
            'date_of_birth' => '1979-09-09',
        ]);
        app(ConsentService::class)->requestConsent($other->id, $this->facility->id, null, 'treatment', ['patient.summary'], 60);

        $this->mobileGetJson($this->patient, '/api/mobile/consent-requests/pending-count')
            ->assertExactJson(['data' => ['pending' => 0]]);
        $this->assertSame([], $this->mobileGetJson($this->patient, '/api/mobile/consent-requests?status=pending')->json());
    }
}

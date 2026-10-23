<?php

namespace Tests\Feature\Connect;

use App\Jobs\SendWebhookJob;
use App\Models\ConsentGrant;
use App\Models\ConsentRequest;
use App\Models\Facility;
use App\Models\IntegrationClient;
use App\Models\Patient;
use App\Models\User;
use App\Models\WebhookSubscription;
use App\Modules\Governance\Services\ConsentService;
use App\Modules\MasterPatientIndex\Services\PatientMergeService;
use App\Services\Identity\HealthIdGeneratorService;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * OpesCare Connect partner patient/consent API — the contract OPES Triage
 * calls (resolve, verify, consent request + status, consented summary pull)
 * and the consent/MPI webhooks it listens for.
 */
class PartnerPatientApiTest extends TestCase
{
    use RefreshDatabase;

    private const ALL_SCOPES = ['health_id:read', 'patients:read', 'consents:read', 'consents:write'];

    private Facility $facility;
    private Facility $otherFacility;
    private IntegrationClient $client;
    private Patient $patient;
    private Patient $demoPatient;
    private string $healthId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility      = Facility::factory()->create();
        $this->otherFacility = Facility::factory()->create();

        $this->client = IntegrationClient::factory()->create([
            'facility_id' => $this->facility->id,
            'environment' => 'production',
            'scopes'      => self::ALL_SCOPES,
            'status'      => 'active',
        ]);

        $this->healthId = $this->newHealthId();
        $this->patient = $this->makePatient($this->healthId, 'Marie', 'Ngono', '1990-04-12', 'female', false, '+237670000001');
        $this->demoPatient = $this->makePatient($this->newHealthId(), 'Demo', 'Patient', '1985-01-01', 'male', true);
    }

    // ── helpers ────────────────────────────────────────────────────────────

    private function newHealthId(): string
    {
        return app(HealthIdGeneratorService::class)->generate('CM');
    }

    private function makePatient(string $hid, string $first, string $last, string $dob, string $sex, bool $demo, ?string $phone = null): Patient
    {
        $p = Patient::factory()->make([
            'health_id' => $hid, 'first_name' => $first, 'last_name' => $last,
            'date_of_birth' => $dob, 'sex' => $sex, 'country_code' => 'CM',
            'phone_number' => $phone,
        ]);
        $p->forceFill(['is_demo' => $demo])->save();

        return $p->fresh() ?? Patient::withoutGlobalScope('isolate_demo')->find($p->id);
    }

    private function token(?array $scopes = null, ?string $facilityId = 'default', string $env = 'production'): array
    {
        $jwt = app(JwtService::class)->issue([
            'sub'         => $this->client->client_id,
            'client_id'   => $this->client->client_id,
            'facility_id' => $facilityId === 'default' ? $this->facility->id : $facilityId,
            'environment' => $env,
            'scopes'      => $scopes ?? self::ALL_SCOPES,
        ]);

        return ['Authorization' => 'Bearer ' . $jwt];
    }

    private function grant(?Patient $patient = null, ?Facility $facility = null, array $scope = ['patients:read'], array $attrs = []): ConsentGrant
    {
        $g = new ConsentGrant(array_merge([
            'patient_id'        => ($patient ?? $this->patient)->id,
            'facility_id'       => ($facility ?? $this->facility)->id,
            'authorizing_actor' => 'patient',
            'scope'             => $scope,
            'status'            => 'active',
            'expires_at'        => now()->addDays(30),
        ], $attrs));
        $g->forceFill(['is_demo' => (bool) ($patient ?? $this->patient)->is_demo])->save();

        return $g;
    }

    private function subscribe(array $events, ?Facility $facility = null, string $secret = 'whsec_test_secret_value_0123456789'): WebhookSubscription
    {
        return WebhookSubscription::create([
            'client_id'         => $this->client->client_id,
            'facility_id'       => ($facility ?? $this->facility)->id,
            'callback_url'      => 'https://partner.example.com/hooks',
            'webhook_secret'    => $secret,
            'subscribed_events' => $events,
            'status'            => 'active',
        ]);
    }

    private function requestConsentViaApi(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->token())->postJson('/api/v1/connect/consents/request-medical-id', array_merge([
            'health_id'       => $this->healthId,
            'purpose'         => 'treatment',
            'requested_scope' => ['patients:read'],
            'expires_in_days' => 30,
        ], $overrides));
    }

    // ── auth + scope ───────────────────────────────────────────────────────

    public static function endpoints(): array
    {
        return [
            'resolve'        => ['POST', '/api/v1/connect/patients/resolve', 'patients:read'],
            'verify'         => ['GET', '/api/v1/connect/patients/verify/CM-HID-AAAA-BBBB-CCCC', 'patients:read'],
            'summary'        => ['GET', '/api/v1/connect/patients/CM-HID-AAAA-BBBB-CCCC/summary', 'health_id:read'],
            'consent_req'    => ['POST', '/api/v1/connect/consents/request-medical-id', 'consents:read'],
            'consent_status' => ['GET', '/api/v1/connect/consents/status/CM-HID-AAAA-BBBB-CCCC', 'consents:write'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_every_endpoint_requires_a_bearer_token(string $method, string $uri): void
    {
        $this->json($method, $uri, [])->assertStatus(401)->assertJson(['error' => 'missing_token']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_every_endpoint_refuses_a_token_without_its_scope(string $method, string $uri, string $otherScope): void
    {
        $this->withHeaders($this->token([$otherScope]))->json($method, $uri, [])
            ->assertStatus(403)->assertJson(['error' => 'insufficient_scope']);
    }

    public function test_a_revoked_client_is_refused(): void
    {
        $this->client->update(['status' => 'revoked']);

        $this->withHeaders($this->token())->getJson('/api/v1/connect/patients/verify/' . $this->healthId)
            ->assertStatus(401)->assertJson(['error' => 'client_revoked']);
    }

    // ── resolve ────────────────────────────────────────────────────────────

    public function test_resolve_by_health_id_returns_a_minimal_masked_match(): void
    {
        $res = $this->withHeaders($this->token())->postJson('/api/v1/connect/patients/resolve', [
            'health_id' => strtolower($this->healthId), 'purpose' => 'his_lookup',
        ]);

        $res->assertOk()->assertJson([
            'status'    => 'found',
            'health_id' => $this->healthId,
            'match'     => ['confidence' => 1.0, 'method' => 'health_id'],
            'patient'   => ['display_name' => 'M**** N****', 'sex' => 'female', 'year_of_birth' => 1990, 'country_code' => 'CM'],
        ]);
        $this->assertStringNotContainsString('Ngono', $res->getContent());
        $this->assertStringNotContainsString('1990-04-12', $res->getContent());
        $this->assertDatabaseHas('audit_events', ['action_type' => 'health_id_resolved', 'patient_id' => $this->patient->id]);
    }

    public function test_resolve_validation(): void
    {
        $h = $this->token();
        $this->withHeaders($h)->postJson('/api/v1/connect/patients/resolve', ['health_id' => $this->healthId])
            ->assertStatus(422)->assertJson(['error_code' => 'INVALID_PAYLOAD'])->assertJsonPath('errors.purpose.0', fn ($m) => is_string($m));
        $this->withHeaders($h)->postJson('/api/v1/connect/patients/resolve', ['purpose' => 'x', 'first_name' => 'Marie'])
            ->assertStatus(422)->assertJson(['error_code' => 'MISSING_LOOKUP_CRITERIA']);
        $this->withHeaders($h)->postJson('/api/v1/connect/patients/resolve', ['purpose' => 'x', 'full_name' => 'Marie Ngono', 'dob' => '1990-04-12', 'sex' => 'other'])
            ->assertStatus(422)->assertJsonPath('errors.sex.0', fn ($m) => is_string($m));
    }

    public function test_resolve_unknown_health_id_is_404(): void
    {
        $this->withHeaders($this->token())->postJson('/api/v1/connect/patients/resolve', ['health_id' => $this->newHealthId(), 'purpose' => 'his_lookup'])
            ->assertStatus(404)->assertJson(['status' => 'not_found', 'error_code' => 'HEALTH_ID_NOT_FOUND']);
    }

    public function test_demographic_resolve_needs_consent_before_any_search(): void
    {
        $this->withHeaders($this->token())->postJson('/api/v1/connect/patients/resolve', [
            'first_name' => 'Marie', 'last_name' => 'Ngono', 'date_of_birth' => '1990-04-12', 'purpose' => 'his_registration',
        ])->assertStatus(422)->assertJson(['status' => 'consent_required', 'error_code' => 'CONSENT_REQUIRED'])
            ->assertJsonMissing(['health_id' => $this->healthId]);
    }

    public function test_demographic_resolve_with_the_triage_payload_shape(): void
    {
        $this->withHeaders($this->token())->postJson('/api/v1/connect/patients/resolve', [
            'first_name' => ' marie ', 'last_name' => 'NGONO', 'date_of_birth' => '1990-04-12', 'sex' => 'female',
            'country_code' => 'CM', 'purpose' => 'his_registration', 'consent_acknowledged' => true, 'external_reference' => 'TRI-0001',
        ])->assertOk()->assertJson(['status' => 'found', 'health_id' => $this->healthId, 'match' => ['method' => 'demographics', 'confidence' => 0.9]]);
    }

    public function test_demographic_resolve_with_full_name_dob_and_phone(): void
    {
        $this->withHeaders($this->token())->postJson('/api/v1/connect/patients/resolve', [
            'full_name' => 'Ngono Marie', 'dob' => '1990-04-12', 'phone' => '+237670000001',
            'purpose' => 'his_registration', 'consent_acknowledged' => true,
        ])->assertOk()->assertJson(['health_id' => $this->healthId, 'match' => ['method' => 'demographics_phone', 'confidence' => 0.98]]);
    }

    public function test_demographic_miss_is_404_and_creates_nobody(): void
    {
        $before = Patient::withoutGlobalScope('isolate_demo')->count();

        $this->withHeaders($this->token())->postJson('/api/v1/connect/patients/resolve', [
            'first_name' => 'Nobody', 'last_name' => 'Here', 'date_of_birth' => '2001-01-01',
            'purpose' => 'his_registration', 'consent_acknowledged' => true,
        ])->assertStatus(404)->assertJson(['error_code' => 'PATIENT_NOT_FOUND']);

        $this->assertSame($before, Patient::withoutGlobalScope('isolate_demo')->count());
    }

    public function test_an_ambiguous_demographic_match_returns_no_health_ids(): void
    {
        $twin = $this->makePatient($this->newHealthId(), 'Marie', 'Ngono', '1990-04-12', 'female', false);

        $res = $this->withHeaders($this->token())->postJson('/api/v1/connect/patients/resolve', [
            'first_name' => 'Marie', 'last_name' => 'Ngono', 'date_of_birth' => '1990-04-12',
            'purpose' => 'his_registration', 'consent_acknowledged' => true,
        ])->assertStatus(409)->assertJson(['error_code' => 'AMBIGUOUS_MATCH']);

        $this->assertStringNotContainsString($this->healthId, $res->getContent());
        $this->assertStringNotContainsString($twin->health_id, $res->getContent());
    }

    // ── sandbox isolation ──────────────────────────────────────────────────

    public function test_a_sandbox_token_sees_only_demo_patients_and_production_never_sees_them(): void
    {
        $sandbox = $this->token(['health_id:read'], null, 'sandbox');

        $this->withHeaders($sandbox)->postJson('/api/v1/connect/patients/resolve', ['health_id' => $this->healthId, 'purpose' => 'x'])
            ->assertStatus(404);
        $this->withHeaders($sandbox)->getJson('/api/v1/connect/patients/verify/' . $this->healthId)->assertStatus(404);
        $this->withHeaders($sandbox)->getJson('/api/v1/connect/patients/verify/' . $this->demoPatient->health_id)
            ->assertOk()->assertJson(['status' => 'valid', 'health_id' => $this->demoPatient->health_id]);

        $this->withHeaders($this->token())->getJson('/api/v1/connect/patients/verify/' . $this->demoPatient->health_id)->assertStatus(404);
    }

    public function test_a_facility_less_sandbox_app_cannot_request_consent_or_pull_records(): void
    {
        $sandbox = $this->token(self::ALL_SCOPES, null, 'sandbox');

        $this->withHeaders($sandbox)->postJson('/api/v1/connect/consents/request-medical-id', [
            'health_id' => $this->demoPatient->health_id, 'purpose' => 'treatment', 'requested_scope' => ['patients:read'],
        ])->assertStatus(403)->assertJson(['error_code' => 'FACILITY_REQUIRED']);
        $this->withHeaders($sandbox)->getJson('/api/v1/connect/consents/status/' . $this->demoPatient->health_id)
            ->assertStatus(403)->assertJson(['error_code' => 'FACILITY_REQUIRED']);
        $this->withHeaders($sandbox + ['X-Consent-Grant-Id' => (string) Str::uuid()])
            ->getJson('/api/v1/connect/patients/' . $this->demoPatient->health_id . '/summary')
            ->assertStatus(403)->assertJson(['error_code' => 'FACILITY_REQUIRED']);
    }

    // ── verify ─────────────────────────────────────────────────────────────

    public function test_verify_checks_format_and_check_block(): void
    {
        $bad = substr($this->healthId, 0, -4) . (str_ends_with($this->healthId, 'AAAA') ? 'BBBB' : 'AAAA');

        $this->withHeaders($this->token())->getJson('/api/v1/connect/patients/verify/' . $bad)
            ->assertStatus(422)->assertJson(['status' => 'invalid', 'error_code' => 'HEALTH_ID_INVALID_FORMAT']);
        $this->withHeaders($this->token())->getJson('/api/v1/connect/patients/verify/OPC-2024-12345')
            ->assertStatus(422);
        $this->withHeaders($this->token())->getJson('/api/v1/connect/patients/verify/' . $this->newHealthId())
            ->assertStatus(404)->assertJson(['error_code' => 'HEALTH_ID_NOT_FOUND']);
    }

    public function test_verify_returns_active_and_verified_status(): void
    {
        $this->patient->forceFill(['verification_status' => 'verified', 'identity_status' => 'active'])->save();

        $this->withHeaders($this->token())->getJson('/api/v1/connect/patients/verify/' . $this->healthId)
            ->assertOk()->assertJson([
                'status' => 'valid', 'health_id' => $this->healthId, 'active' => true, 'verified' => true,
                'resolved_via_alias' => false,
                'patient' => ['display_name' => 'M**** N****', 'verification_status' => 'verified', 'identity_status' => 'active'],
            ]);

        $this->patient->forceFill(['identity_status' => 'suspended'])->save();
        $this->withHeaders($this->token())->getJson('/api/v1/connect/patients/verify/' . $this->healthId)
            ->assertOk()->assertJson(['active' => false]);
    }

    // ── consent request + status ───────────────────────────────────────────

    public function test_consent_request_is_bound_to_the_token_facility(): void
    {
        $res = $this->requestConsentViaApi(['facility_id' => $this->otherFacility->id]);

        $res->assertStatus(201)->assertJson(['status' => 'success', 'consent_status' => 'pending', 'grant_id' => null, 'existing' => false]);
        $request = ConsentRequest::withoutGlobalScopes()->findOrFail($res->json('consent_id'));
        $this->assertSame((string) $this->facility->id, (string) $request->requesting_facility_id);
        $this->assertSame((string) $this->patient->id, (string) $request->patient_id);
        $this->assertSame(['patients:read'], $request->requested_scope);
        $this->assertSame(30 * 1440, (int) $request->duration_minutes);
        $this->assertNotNull($res->json('expires_at'));

        // Asking again while the patient has not answered returns the same open ask.
        $this->requestConsentViaApi()->assertOk()->assertJson(['consent_id' => $request->id, 'existing' => true]);
    }

    public function test_consent_request_validation_and_unknown_patient(): void
    {
        $this->requestConsentViaApi(['requested_scope' => ['labs:write']])->assertStatus(422)->assertJson(['error_code' => 'INVALID_PAYLOAD']);
        $this->requestConsentViaApi(['purpose' => 'Free text purpose!'])->assertStatus(422);
        $this->requestConsentViaApi(['expires_in_days' => 999])->assertStatus(422);
        $this->requestConsentViaApi(['health_id' => $this->newHealthId()])->assertStatus(404)->assertJson(['error_code' => 'HEALTH_ID_NOT_FOUND']);

        $this->patient->forceFill(['identity_status' => 'suspended'])->save();
        $this->requestConsentViaApi()->assertStatus(403)->assertJson(['error_code' => 'HEALTH_ID_SUSPENDED']);
    }

    public function test_consent_status_lifecycle_for_the_calling_facility(): void
    {
        $status = fn () => $this->withHeaders($this->token())->getJson('/api/v1/connect/consents/status/' . $this->healthId);

        $status()->assertOk()->assertJson(['status' => 'none', 'grant_id' => null]);

        $requestId = $this->requestConsentViaApi()->json('consent_id');
        $status()->assertOk()->assertJson(['status' => 'pending', 'consent_status' => 'pending', 'request_id' => $requestId, 'scope' => ['patients:read']]);

        $patientUser = User::factory()->create();
        $grant = app(ConsentService::class)->approveConsent($requestId, $patientUser->id);
        $status()->assertOk()->assertJson([
            'status' => 'granted', 'grant_id' => (string) $grant->id, 'id' => (string) $grant->id,
            'scope' => ['patients:read'], 'expires_at' => $grant->expires_at->toIso8601String(),
        ]);

        // The other facility sees nothing of this facility's grant.
        $this->withHeaders($this->token(null, $this->otherFacility->id))->getJson('/api/v1/connect/consents/status/' . $this->healthId)
            ->assertOk()->assertJson(['status' => 'none']);

        // Already granted: a new request says so and hands back the grant id.
        $this->requestConsentViaApi()->assertOk()->assertJson(['consent_status' => 'granted', 'grant_id' => (string) $grant->id, 'id' => (string) $grant->id]);

        app(ConsentService::class)->revokeConsent($grant->id, $patientUser->id);
        $status()->assertOk()->assertJson(['status' => 'revoked', 'grant_id' => (string) $grant->id]);
    }

    public function test_consent_status_denied_and_expired(): void
    {
        $status = fn () => $this->withHeaders($this->token())->getJson('/api/v1/connect/consents/status/' . $this->healthId);

        $requestId = $this->requestConsentViaApi()->json('consent_id');
        app(ConsentService::class)->denyConsent($requestId, User::factory()->create()->id);
        $status()->assertOk()->assertJson(['status' => 'denied', 'request_id' => $requestId]);

        $this->grant(null, null, ['patients:read'], ['expires_at' => now()->subDay()]);
        $status()->assertOk()->assertJson(['status' => 'expired']);
    }

    // ── record pull ────────────────────────────────────────────────────────

    private function seedClinicalRecord(): void
    {
        $doctor = User::factory()->create();
        $visitId = (string) Str::uuid();
        $orderId = (string) Str::uuid();
        $rxId = (string) Str::uuid();
        $now = now();

        DB::table('visits')->insert(['id' => $visitId, 'patient_id' => $this->patient->id, 'facility_id' => $this->facility->id,
            'visit_type' => 'outpatient', 'status' => 'completed', 'started_at' => $now->copy()->subDays(3), 'is_demo' => false,
            'created_at' => $now, 'updated_at' => $now]);
        DB::table('diagnoses')->insert(['id' => (string) Str::uuid(), 'patient_id' => $this->patient->id, 'visit_id' => $visitId,
            'provider_id' => $doctor->id, 'display_name' => 'Malaria', 'status' => 'confirmed', 'is_primary' => true, 'is_demo' => false,
            'created_at' => $now, 'updated_at' => $now]);
        DB::table('lab_orders')->insert(['id' => $orderId, 'patient_id' => $this->patient->id, 'facility_id' => $this->facility->id,
            'test_name' => 'Full blood count', 'urgency' => 'routine', 'status' => 'resulted', 'ordered_at' => $now, 'resulted_at' => $now,
            'is_demo' => false, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('lab_results')->insert(['id' => (string) Str::uuid(), 'lab_order_id' => $orderId, 'patient_id' => $this->patient->id,
            'parameter_name' => 'Haemoglobin', 'value' => '9.1', 'unit' => 'g/dL', 'flag' => 'L', 'resulted_at' => $now,
            'is_demo' => false, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('prescriptions')->insert(['id' => $rxId, 'patient_id' => $this->patient->id, 'facility_id' => $this->facility->id,
            'status' => 'active', 'prescribed_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('prescription_items')->insert(['id' => (string) Str::uuid(), 'prescription_id' => $rxId, 'drug_name' => 'Artemether/Lumefantrine',
            'dose' => '4 tabs', 'frequency' => 'BD', 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('allergy_records')->insert(['id' => (string) Str::uuid(), 'patient_id' => $this->patient->id, 'provider_id' => $doctor->id,
            'substance' => 'Penicillin', 'severity' => 'severe', 'status' => 'active', 'is_demo' => false, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('problem_lists')->insert(['id' => (string) Str::uuid(), 'patient_id' => $this->patient->id, 'provider_id' => $doctor->id,
            'icd_code' => 'I10', 'icd_version' => '10', 'description' => 'Essential hypertension', 'status' => 'active', 'priority' => 'medium',
            'created_at' => $now, 'updated_at' => $now]);
        DB::table('immunization_records')->insert(['id' => (string) Str::uuid(), 'patient_id' => $this->patient->id, 'facility_id' => $this->facility->id,
            'vaccine_code' => '03', 'vaccine_system' => 'CVX', 'vaccine_name' => 'MMR', 'administered_at' => $now->copy()->subYear(),
            'status' => 'completed', 'verification_status' => 'verified', 'is_historical' => false, 'created_at' => $now, 'updated_at' => $now]);
    }

    public function test_summary_requires_an_active_grant_for_this_facility_patient_and_scope(): void
    {
        $url = '/api/v1/connect/patients/' . $this->healthId . '/summary';
        $h = $this->token();

        $this->withHeaders($h)->getJson($url)->assertStatus(403)->assertJson(['error_code' => 'CONSENT_REQUIRED']);
        $this->withHeaders($h + ['X-Consent-Grant-Id' => 'not-a-uuid'])->getJson($url)->assertStatus(403)->assertJson(['error_code' => 'CONSENT_REQUIRED']);
        $this->withHeaders($h + ['X-Consent-Grant-Id' => (string) Str::uuid()])->getJson($url)->assertStatus(403);

        $elsewhere = $this->grant(null, $this->otherFacility);
        $this->withHeaders($h + ['X-Consent-Grant-Id' => $elsewhere->id])->getJson($url)
            ->assertStatus(403)->assertJson(['error_code' => 'CONSENT_REQUIRED', 'reason' => 'facility_mismatch']);

        $otherPatient = $this->makePatient($this->newHealthId(), 'Paul', 'Biya', '1970-01-01', 'male', false);
        $forOther = $this->grant($otherPatient);
        $this->withHeaders($h + ['X-Consent-Grant-Id' => $forOther->id])->getJson($url)
            ->assertStatus(403)->assertJson(['error_code' => 'CONSENT_REQUIRED']);

        $expired = $this->grant(null, null, ['patients:read'], ['expires_at' => now()->subMinute()]);
        $this->withHeaders($h + ['X-Consent-Grant-Id' => $expired->id])->getJson($url)
            ->assertStatus(403)->assertJson(['error_code' => 'CONSENT_REQUIRED', 'consent_status' => 'expired']);

        $revoked = $this->grant(null, null, ['patients:read'], ['status' => 'revoked']);
        $this->withHeaders($h + ['X-Consent-Grant-Id' => $revoked->id])->getJson($url)
            ->assertStatus(403)->assertJson(['consent_status' => 'revoked']);

        $narrow = $this->grant(null, null, ['clinical:write']);
        $this->withHeaders($h + ['X-Consent-Grant-Id' => $narrow->id])->getJson($url)
            ->assertStatus(403)->assertJson(['error_code' => 'CONSENT_REQUIRED', 'reason' => 'scope_insufficient']);

        // An unknown Health ID with a valid grant is still a consent refusal, never a 404.
        $good = $this->grant();
        $this->withHeaders($h + ['X-Consent-Grant-Id' => $good->id])->getJson('/api/v1/connect/patients/' . $this->newHealthId() . '/summary')
            ->assertStatus(403);
    }

    public function test_summary_returns_the_sections_triage_reads(): void
    {
        $this->seedClinicalRecord();
        $grant = $this->grant();

        $res = $this->withHeaders($this->token() + ['X-Consent-Grant-Id' => $grant->id, 'X-Purpose-Of-Use' => 'treatment'])
            ->getJson('/api/v1/connect/patients/' . $this->healthId . '/summary');

        $res->assertOk()->assertJson([
            'status' => 'success', 'health_id' => $this->healthId,
            'consent' => ['grant_id' => (string) $grant->id, 'scope' => ['patients:read']],
            'sections' => [
                'recent_visits'      => [['visit_type' => 'outpatient', 'diagnoses' => ['Malaria']]],
                'recent_lab_results' => [['parameter_name' => 'Haemoglobin', 'test_name' => 'Full blood count', 'value' => '9.1', 'unit' => 'g/dL', 'flagged' => true]],
                'medications'        => [['drug_name' => 'Artemether/Lumefantrine', 'dose' => '4 tabs', 'frequency' => 'BD']],
                'allergies'          => [['substance' => 'Penicillin', 'severity' => 'severe']],
                'chronic_conditions' => [['name' => 'Essential hypertension', 'icd_code' => 'I10']],
                'immunizations'      => [['vaccine' => 'MMR', 'vaccine_code' => '03']],
            ],
        ]);

        $this->assertDatabaseHas('audit_events', ['action_type' => 'connect_record_pulled', 'patient_id' => $this->patient->id, 'consent_grant_id' => $grant->id]);
        $audit = DB::table('audit_events')->where('action_type', 'connect_record_pulled')->value('after_state');
        $this->assertStringNotContainsString('Ngono', (string) $audit, 'no PHI in the audit line');
    }

    public function test_end_to_end_a_grant_from_an_api_request_opens_the_summary(): void
    {
        $requestId = $this->requestConsentViaApi()->assertStatus(201)->json('consent_id');
        $grant = app(ConsentService::class)->approveConsent($requestId, User::factory()->create()->id);

        $this->withHeaders($this->token() + ['X-Consent-Grant-Id' => $grant->id])
            ->getJson('/api/v1/connect/patients/' . $this->healthId . '/summary')
            ->assertOk()->assertJsonStructure(['sections' => ['recent_visits', 'recent_lab_results', 'medications', 'allergies', 'chronic_conditions', 'immunizations']]);
    }

    // ── webhooks ───────────────────────────────────────────────────────────

    public function test_consent_denied_revoked_and_expired_webhooks(): void
    {
        Queue::fake();
        $sub = $this->subscribe(['consent.denied', 'consent.revoked', 'consent.expired']);
        $elsewhere = $this->subscribe(['consent.denied', 'consent.revoked', 'consent.expired'], $this->otherFacility);
        $consents = app(ConsentService::class);
        $user = User::factory()->create();

        $denied = $this->requestConsentViaApi()->json('consent_id');
        $consents->denyConsent($denied, $user->id);
        Queue::assertPushedOn('webhooks', SendWebhookJob::class, fn (SendWebhookJob $j) => $j->subscriptionId === $sub->id
            && $j->payload['event_type'] === 'consent.denied' && $j->payload['schema_version'] === 'v1'
            && $j->payload['resource']['consent_id'] === $denied && $j->payload['resource']['health_id'] === $this->healthId
            && $j->payload['resource']['status'] === 'denied' && ! empty($j->payload['event_id']) && ! empty($j->payload['occurred_at']));

        $grant = $this->grant();
        $consents->revokeConsent($grant->id, $user->id);
        Queue::assertPushed(SendWebhookJob::class, fn (SendWebhookJob $j) => $j->subscriptionId === $sub->id
            && $j->payload['event_type'] === 'consent.revoked' && $j->payload['resource']['grant_id'] === (string) $grant->id
            && $j->payload['resource']['health_id'] === $this->healthId);

        $lapsed = $this->grant(null, null, ['patients:read'], ['expires_at' => now()->subHour()]);
        $this->artisan('consent:expire-lapsed')->assertSuccessful();
        $this->assertSame('expired', ConsentGrant::withoutGlobalScopes()->find($lapsed->id)->status);
        Queue::assertPushed(SendWebhookJob::class, fn (SendWebhookJob $j) => $j->subscriptionId === $sub->id
            && $j->payload['event_type'] === 'consent.expired' && $j->payload['resource']['grant_id'] === (string) $lapsed->id);

        Queue::assertNotPushed(SendWebhookJob::class, fn (SendWebhookJob $j) => $j->subscriptionId === $elsewhere->id);
    }

    public function test_expire_dry_run_sends_nothing(): void
    {
        Queue::fake();
        $this->subscribe(['consent.expired']);
        $this->grant(null, null, ['patients:read'], ['expires_at' => now()->subHour()]);

        $this->artisan('consent:expire-lapsed', ['--dry-run' => true])->assertSuccessful();
        Queue::assertNothingPushed();
    }

    public function test_patient_merged_and_unmerged_webhooks_reach_facilities_holding_a_grant(): void
    {
        Queue::fake();
        $sub = $this->subscribe(['patient.merged', 'patient.unmerged']);
        $bystander = $this->subscribe(['patient.merged', 'patient.unmerged'], $this->otherFacility);
        $duplicate = $this->makePatient($this->newHealthId(), 'Marie', 'Ngono', '1990-04-12', 'female', false);
        $this->grant($duplicate);

        $merges = app(PatientMergeService::class);
        $merge = $merges->merge($this->patient, $duplicate, null, 'same person');

        Queue::assertPushed(SendWebhookJob::class, fn (SendWebhookJob $j) => $j->subscriptionId === $sub->id
            && $j->payload['event_type'] === 'patient.merged'
            && $j->payload['resource']['retired_health_id'] === $duplicate->health_id
            && $j->payload['resource']['survivor_health_id'] === $this->healthId
            && $j->payload['resource']['merge_id'] === (string) $merge->id);

        $merges->unmerge($merge->fresh(), null, 'not the same person');
        Queue::assertPushed(SendWebhookJob::class, fn (SendWebhookJob $j) => $j->subscriptionId === $sub->id
            && $j->payload['event_type'] === 'patient.unmerged'
            && $j->payload['resource']['retired_health_id'] === $duplicate->health_id);

        Queue::assertNotPushed(SendWebhookJob::class, fn (SendWebhookJob $j) => $j->subscriptionId === $bystander->id);
    }

    public function test_new_events_are_delivered_with_the_existing_signature(): void
    {
        $secret = 'whsec_' . Str::random(40);
        $sub = $this->subscribe(['consent.revoked'], null, $secret);
        Http::fake(['partner.example.com/*' => Http::response(['ok' => true], 200)]);

        $grant = $this->grant();
        app(ConsentService::class)->revokeConsent($grant->id, User::factory()->create()->id); // QUEUE_CONNECTION=sync: delivered now

        Http::assertSent(function (HttpRequest $req) use ($secret, $grant) {
            if (! str_contains($req->url(), 'partner.example.com')) {
                return false;
            }
            preg_match('/^t=(\d+),v1=([0-9a-f]{64})$/', $req->header('X-OpesCare-Signature')[0] ?? '', $m);
            $body = json_decode($req->body(), true);

            return $m !== []
                && hash_equals(hash_hmac('sha256', $m[1] . '.' . $req->body(), $secret), $m[2])
                && $req->header('X-OpesCare-Event-Id')[0] === $body['event_id']
                && $req->header('X-OpesCare-Webhook-Version')[0] === 'v1'
                && $body['event_type'] === 'consent.revoked'
                && $body['resource']['grant_id'] === (string) $grant->id
                && array_keys($body) === ['event_type', 'schema_version', 'occurred_at', 'resource', 'event_id'];
        });
        $this->assertDatabaseHas('webhook_delivery_logs', ['webhook_subscription_id' => $sub->id, 'event_type' => 'consent.revoked', 'status' => 'delivered']);
    }

    public function test_the_developer_portal_offers_the_new_events(): void
    {
        $this->assertEqualsCanonicalizing([
            'consent.granted', 'consent.denied', 'consent.revoked', 'consent.expired',
            'lab_result.released', 'prescription.issued', 'patient.merged', 'patient.unmerged',
        ], \App\Http\Controllers\MedicalId\DeveloperExtController::EVENT_TYPES);
    }
}

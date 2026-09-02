<?php

namespace Tests\Feature\Connect;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Patient;
use App\Models\Facility;
use App\Models\ConsentGrant;
use App\Models\ConsentRequest;
use App\Modules\Governance\Services\ConsentService;

class ConsentEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private ConsentService $service;
    private Patient $patient;
    private Facility $facilityA;
    private Facility $facilityB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ConsentService::class);

        $this->facilityA = Facility::create([
            'id'     => '00000000-0000-0000-0000-aaaaaaaaaaaa',
            'name'   => 'Facility A',
            'type'   => 'hospital',
            'status' => 'active',
        ]);

        $this->facilityB = Facility::create([
            'id'     => '00000000-0000-0000-0000-bbbbbbbbbbbb',
            'name'   => 'Facility B',
            'type'   => 'clinic',
            'status' => 'active',
        ]);

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-0001-0001-01',
            'first_name'    => 'Jane',
            'last_name'     => 'Consent',
            'sex'           => 'female',
            'date_of_birth' => '1995-01-01',
            'is_demo'       => false,
        ]);

        // Consent granted to Facility A only
        $req = ConsentRequest::create([
            'patient_id'             => $this->patient->id,
            'requesting_facility_id' => $this->facilityA->id,
            'purpose'                => 'treatment',
            'requested_scope'        => ['patients:read'],
            'duration_minutes'       => 60,
            'status'                 => 'approved',
        ]);

        ConsentGrant::create([
            'consent_request_id' => $req->id,
            'patient_id'         => $this->patient->id,
            'facility_id'        => $this->facilityA->id,
            'authorizing_actor'  => 'patient',
            'scope'              => ['patients:read'],
            'status'             => 'active',
            'expires_at'         => now()->addHour(),
        ]);
    }

    public function test_facility_a_can_access_with_valid_consent(): void
    {
        $result = $this->service->verifyAccess(
            $this->patient->id,
            $this->facilityA->id,
            null,
            'patients:read',
            'treatment'
        );

        $this->assertTrue($result);
    }

    public function test_facility_b_cannot_access_with_consent_granted_to_facility_a(): void
    {
        $result = $this->service->verifyAccess(
            $this->patient->id,
            $this->facilityB->id,
            null,
            'patients:read',
            'treatment'
        );

        $this->assertFalse($result);
    }

    public function test_expired_consent_is_rejected(): void
    {
        ConsentGrant::where('patient_id', $this->patient->id)->update([
            'expires_at' => now()->subMinute(),
        ]);

        $result = $this->service->verifyAccess(
            $this->patient->id,
            $this->facilityA->id,
            null,
            'patients:read',
            'treatment'
        );

        $this->assertFalse($result);
    }

    public function test_missing_scope_is_rejected(): void
    {
        $result = $this->service->verifyAccess(
            $this->patient->id,
            $this->facilityA->id,
            null,
            'labs:write',   // not in granted scopes
            'treatment'
        );

        $this->assertFalse($result);
    }

    // ── HTTP-level enforcement tests (added in Task 4) ────────────────────────

    /**
     * Create a sandbox integration client and exchange its credentials for a
     * real RS256 Bearer token via the production token endpoint.
     */
    private function bearerHeaders(array $extra = []): array
    {
        \App\Models\IntegrationClient::firstOrCreate(
            ['client_id' => 'test_client_id'],
            [
                'client_secret' => hash('sha256', 'test_client_secret'),
                'facility_id'   => '00000000-0000-0000-0000-000000000001',
                'name'          => 'Consent Enforcement Test Client',
                'environment'   => 'sandbox',
                'scopes'        => ['*'],
                'status'        => 'active',
            ]
        );

        $response = $this->postJson('/api/v1/connect/auth/token', [
            'client_id'     => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'grant_type'    => 'client_credentials',
        ]);
        $response->assertStatus(200);

        return array_merge(
            ['Authorization' => 'Bearer ' . $response->json('access_token')],
            $extra
        );
    }

    public function test_push_encounter_requires_consent_grant(): void
    {
        // Seed facility and client for this test
        Facility::create([
            'id' => '00000000-0000-0000-0000-000000000001',
            'name' => 'Test Clinic', 'type' => 'clinic', 'status' => 'active',
        ]);

        $response = $this->withHeaders($this->bearerHeaders([
            'Idempotency-Key' => 'idem-consent-enc-001',
            // No X-Consent-Grant-Id
        ]))->postJson('/api/v1/connect/records/encounters', [
            'health_id'             => 'OC-TST-0001-0001-01',
            'external_encounter_id' => 'ENC-001',
        ]);

        $response->assertStatus(403)
                 ->assertJsonFragment(['required_action' => 'request_consent']);
    }

    public function test_push_lab_result_requires_consent_grant(): void
    {
        Facility::firstOrCreate(['id' => '00000000-0000-0000-0000-000000000001'], [
            'name' => 'Test Clinic', 'type' => 'clinic', 'status' => 'active',
        ]);

        $response = $this->withHeaders($this->bearerHeaders([
            'Idempotency-Key' => 'idem-consent-lab-001',
        ]))->postJson('/api/v1/connect/records/lab-results', [
            'health_id'             => 'OC-TST-0001-0001-01',
            'external_lab_order_id' => 'LAB-001',
        ]);

        $response->assertStatus(403)
                 ->assertJsonFragment(['required_action' => 'request_consent']);
    }

    public function test_push_prescription_requires_consent_grant(): void
    {
        Facility::firstOrCreate(['id' => '00000000-0000-0000-0000-000000000001'], [
            'name' => 'Test Clinic', 'type' => 'clinic', 'status' => 'active',
        ]);

        $response = $this->withHeaders($this->bearerHeaders([
            'Idempotency-Key' => 'idem-consent-rx-001',
        ]))->postJson('/api/v1/connect/records/prescriptions', [
            'health_id'  => 'OC-TST-0001-0001-01',
            'medication' => ['name' => 'Amoxicillin', 'dose' => '500mg'],
        ]);

        $response->assertStatus(403)
                 ->assertJsonFragment(['required_action' => 'request_consent']);
    }

    public function test_pull_summary_requires_consent_grant(): void
    {
        Facility::firstOrCreate(['id' => '00000000-0000-0000-0000-000000000001'], [
            'name' => 'Test Clinic', 'type' => 'clinic', 'status' => 'active',
        ]);

        // Seed the patient with the standard health_id
        Patient::firstOrCreate(['health_id' => 'OC-CMR-7KQ9-MP42-X8D1'], [
            'first_name'    => 'John',
            'last_name'     => 'Doe',
            'sex'           => 'male',
            'date_of_birth' => '1990-04-12',
            'is_demo'       => false,
        ]);

        $response = $this->withHeaders($this->bearerHeaders())
            // No X-Consent-Grant-Id or X-Purpose-Of-Use
            ->getJson('/api/v1/connect/patients/OC-CMR-7KQ9-MP42-X8D1/summary');

        $response->assertStatus(403)
                 ->assertJsonFragment(['required_action' => 'request_consent']);
    }
}

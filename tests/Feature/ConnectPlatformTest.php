<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithMobileAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConnectPlatformTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private ?string $bearerToken = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bearerToken = null;

        // Sandbox test client — Bearer tokens for it are obtained through the
        // real /auth/token endpoint (SHA-256 secret path, as the developer
        // portal stores secrets).
        \App\Models\IntegrationClient::create([
            'client_id'     => 'test_client_id',
            'client_secret' => hash('sha256', 'test_client_secret'),
            'facility_id'   => '00000000-0000-0000-0000-000000000001',
            'name'          => 'Sandbox Test Client',
            'environment'   => 'sandbox',
            'scopes'        => json_encode(['*']),
            'status'        => 'active',
        ]);

        $facility = new \App\Models\Facility();
        $facility->id = '00000000-0000-0000-0000-000000000001';
        $facility->name = 'Metro Emergency General Clinic';
        $facility->type = 'clinic';
        $facility->status = 'active';
        $facility->save();

        $patient = new \App\Models\Patient();
        $patient->id = '00000000-0000-0000-0000-000000000003';
        $patient->health_id = 'OC-CMR-7KQ9-MP42-X8D1';
        $patient->first_name = 'John';
        $patient->last_name = 'Doe';
        $patient->sex = 'male';
        $patient->date_of_birth = '1990-04-12';
        $patient->identity_status = 'verified_by_facility';
        $patient->emergency_contact = [
            'name' => 'Mary Doe',
            'relation' => 'Spouse',
            'phone' => '+237 600-000-000'
        ];
        $patient->save();

        // Real integration client — secret stored as SHA-256 (as DeveloperPortalController::storeApp does)
        \App\Models\IntegrationClient::create([
            'client_id'     => 'real_client_001',
            'client_secret' => hash('sha256', 'real_secret_abc123'),
            'facility_id'   => '00000000-0000-0000-0000-000000000001',
            'name'          => 'Test Real Client',
            'environment'   => 'sandbox',
            'scopes'        => json_encode(['health_id:read', 'patients:read']),
            'status'        => 'active',
        ]);

        // Active consent grant for the test patient at the test facility
        $consentReq = \App\Models\ConsentRequest::create([
            'patient_id'             => '00000000-0000-0000-0000-000000000003',
            'requesting_facility_id' => '00000000-0000-0000-0000-000000000001',
            'purpose'                => 'treatment',
            'requested_scope'        => ['patients:read', 'patients:write', 'labs:write', 'prescriptions:write'],
            'duration_minutes'       => 1440,
            'status'                 => 'approved',
        ]);

        // forceCreate bypasses mass-assignment so the explicit id is stored
        // (HasUuids only auto-generates when id is empty, so our value is kept)
        \App\Models\ConsentGrant::forceCreate([
            'id'                 => '0c000000-0000-4000-8000-0000000cc001', // consent_grants.id is a Postgres uuid column
            'consent_request_id' => $consentReq->id,
            'patient_id'         => '00000000-0000-0000-0000-000000000003',
            'facility_id'        => '00000000-0000-0000-0000-000000000001',
            'authorizing_actor'  => 'patient',
            'scope'              => ['patients:read', 'patients:write', 'labs:write', 'prescriptions:write'],
            'status'             => 'active',
            'expires_at'         => now()->addDay(),
        ]);
    }

    /**
     * Obtain a real RS256 Bearer token for the sandbox test client through the
     * production token endpoint, and merge any extra request headers.
     */
    private function bearerHeaders(array $extra = []): array
    {
        if ($this->bearerToken === null) {
            $response = $this->postJson('/api/v1/connect/auth/token', [
                'client_id'     => 'test_client_id',
                'client_secret' => 'test_client_secret',
                'grant_type'    => 'client_credentials',
            ]);
            $response->assertStatus(200);
            $this->bearerToken = $response->json('access_token');
        }

        return array_merge(['Authorization' => 'Bearer ' . $this->bearerToken], $extra);
    }

    /**
     * Test B2B OAuth token issuing endpoint.
     */
    public function test_auth_token_issuance_succeeds_with_sandbox_credentials()
    {
        $response = $this->postJson('/api/v1/connect/auth/token', [
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'grant_type' => 'client_credentials'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'access_token',
                     'token_type',
                     'expires_in',
                     'scope'
                 ]);
    }

    /**
     * Test client verification middleware blocks unauthorized requests.
     *
     * The Finder's only Bearer-gated B2B surface is the stock-sync ingest
     * (the platform's /connect/patients/search was deleted with the Connect
     * patient APIs), so the auth gate is proven against the sync endpoint.
     */
    public function test_b2b_routes_block_unauthorized_clients()
    {
        // Missing Authorization: Bearer header
        $response = $this->postJson('/api/v1/connect/inventory/pharmacy-stock/sync', []);

        $response->assertStatus(401)
                 ->assertJson([
                     'error' => 'missing_token'
                 ]);

        // A forged/garbage token must also be rejected
        $this->withHeaders(['Authorization' => 'Bearer not.a.valid-token'])
            ->postJson('/api/v1/connect/inventory/pharmacy-stock/sync', [])
            ->assertStatus(401)
            ->assertJson(['error' => 'invalid_token']);
    }


    /**
     * Test B2C Mobile endpoints.
     */
    public function test_mobile_me_endpoint_returns_demographic_profiles()
    {
        $patient = \App\Models\Patient::find('00000000-0000-0000-0000-000000000003');
        $response = $this->withHeaders($this->mobileAuthHeaders($patient))
                         ->getJson('/api/mobile/me');

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'health_id' => 'OC-CMR-7KQ9-MP42-X8D1',
                     'display_name' => 'John D.'
                 ]);
    }

    /**
     * Real integration client authenticates correctly via SHA-256 secret:
     * the correct secret yields a Bearer token that grants API access.
     */
    public function test_real_client_can_authenticate_with_correct_secret(): void
    {
        $tokenResponse = $this->postJson('/api/v1/connect/auth/token', [
            'client_id'     => 'real_client_001',
            'client_secret' => 'real_secret_abc123',
            'grant_type'    => 'client_credentials',
        ]);

        $tokenResponse->assertStatus(200);
        $token = $tokenResponse->json('access_token');

        // An empty payload fails VALIDATION (422) — which proves the Bearer
        // token cleared the auth gate. 401 here would mean it did not.
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/connect/inventory/pharmacy-stock/sync', []);

        $response->assertStatus(422);
    }

    /**
     * Real integration client is blocked with wrong secret:
     * no Bearer token is issued, and forged tokens are rejected.
     */
    public function test_real_client_blocked_with_wrong_secret(): void
    {
        // Wrong secret cannot obtain a token
        $this->postJson('/api/v1/connect/auth/token', [
            'client_id'     => 'real_client_001',
            'client_secret' => 'wrong_secret',
            'grant_type'    => 'client_credentials',
        ])->assertStatus(401);

        // And a self-made (unsigned) token is rejected by the API
        $this->withHeaders([
            'Authorization' => 'Bearer forged.token.value',
        ])->postJson('/api/v1/connect/inventory/pharmacy-stock/sync', [])
            ->assertStatus(401);
    }

    /**
     * Rate-limit headers are present on Connect API responses.
     * ThrottleByClient stamps them on every response it passes, so the
     * validation 422 from an empty payload carries them too.
     */
    public function test_rate_limit_headers_present_on_connect_response(): void
    {
        $response = $this->withHeaders($this->bearerHeaders())
            ->postJson('/api/v1/connect/inventory/pharmacy-stock/sync', []);

        $response->assertStatus(422)
                 ->assertHeader('X-RateLimit-Limit')
                 ->assertHeader('X-RateLimit-Remaining');
    }
}

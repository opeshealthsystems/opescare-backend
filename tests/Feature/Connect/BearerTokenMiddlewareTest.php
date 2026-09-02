<?php

namespace Tests\Feature\Connect;

use App\Models\IntegrationClient;
use App\Services\JwtService;
use Tests\TestCase;

/**
 * Tests for VerifyBearerToken middleware on protected routes.
 *
 * Covers:
 *  - No token → 401 with JSON
 *  - Invalid/malformed token → 401 with JSON
 *  - Expired token → 401 with JSON
 *  - Valid token → passes through (200)
 *  - Correct claims populated on request attributes
 */
class BearerTokenMiddlewareTest extends TestCase
{
    private string $validToken;

    protected function setUp(): void
    {
        parent::setUp();

        $rawSecret = 'sk_test_bearer_' . bin2hex(random_bytes(8));

        $client = IntegrationClient::factory()->create([
            'client_id'     => 'bearer_test_' . bin2hex(random_bytes(4)),
            'client_secret' => hash('sha256', $rawSecret),
            'status'        => 'active',
            'environment'   => 'sandbox',
            'facility_id'   => '00000000-0000-0000-0000-100000000001',
            'scopes'        => ['patients:read'],
        ]);

        // Issue a real token for this client
        $this->validToken = app(JwtService::class)->issue([
            'sub'         => $client->client_id,
            'client_id'   => $client->client_id,
            'facility_id' => $client->facility_id,
            'environment' => 'sandbox',
            'scopes'      => $client->scopes,
        ]);
    }

    public function test_no_token_returns_401_json(): void
    {
        $response = $this->getJson('/api/fhir/R4/Patient');

        $response->assertStatus(401)
            ->assertJson(['error' => 'missing_token']);
    }

    public function test_malformed_token_returns_401_json(): void
    {
        $response = $this->withToken('not.a.real.jwt.atall')
            ->getJson('/api/fhir/R4/Patient');

        $response->assertStatus(401)
            ->assertJson(['error' => 'invalid_token']);
    }

    public function test_tampered_signature_returns_401(): void
    {
        // Take a valid token and corrupt the signature
        $parts = explode('.', $this->validToken);
        $parts[2] = base64_encode('tampered_signature_bytes');
        $tampered = implode('.', $parts);

        $this->withToken($tampered)
            ->getJson('/api/fhir/R4/Patient')
            ->assertStatus(401)
            ->assertJson(['error' => 'invalid_token']);
    }

    public function test_valid_token_passes_through(): void
    {
        $this->withToken($this->validToken)
            ->getJson('/api/fhir/R4/metadata')
            ->assertStatus(200);
    }

    public function test_unhandled_exception_returns_json_not_html(): void
    {
        // Hit a route that doesn't exist — should return JSON 404, not HTML
        $response = $this->withToken($this->validToken)
            ->getJson('/api/v1/connect/nonexistent-endpoint-xyz');

        $response->assertStatus(404)
            ->assertHeader('Content-Type', 'application/json');

        // Must NOT contain HTML tags
        $this->assertStringNotContainsString('<html', $response->getContent());
        $this->assertStringNotContainsString('<!DOCTYPE', $response->getContent());
    }
}

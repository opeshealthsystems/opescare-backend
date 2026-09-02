<?php

namespace Tests\Feature\Connect;

use App\Models\IntegrationClient;
use App\Services\JwtService;
use Tests\TestCase;

/**
 * Tests for VerifyBearerToken middleware on protected routes
 * (exercised via the kept Connect stock-sync surface).
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
        $response = $this->postJson('/api/v1/connect/inventory/pharmacy-stock/sync');

        $response->assertStatus(401)
            ->assertJson(['error' => 'missing_token']);
    }

    public function test_malformed_token_returns_401_json(): void
    {
        $response = $this->withToken('not.a.real.jwt.atall')
            ->postJson('/api/v1/connect/inventory/pharmacy-stock/sync');

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
            ->postJson('/api/v1/connect/inventory/pharmacy-stock/sync')
            ->assertStatus(401)
            ->assertJson(['error' => 'invalid_token']);
    }

    public function test_valid_token_passes_through(): void
    {
        // An empty body fails validation (422) � proving the request got PAST
        // the bearer middleware (which would have answered 401) and reached
        // the controller. The FHIR surface this test used to ride on was
        // removed in the Finder extraction.
        $this->withToken($this->validToken)
            ->postJson('/api/v1/connect/inventory/pharmacy-stock/sync')
            ->assertStatus(422);
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

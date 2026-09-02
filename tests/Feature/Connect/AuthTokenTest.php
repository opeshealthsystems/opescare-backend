<?php

namespace Tests\Feature\Connect;

use App\Models\IntegrationClient;
use App\Services\JwtService;
use Tests\TestCase;

/**
 * Tests for POST /api/v1/connect/auth/token
 *
 * Covers:
 *  - Real RS256 JWT is issued (3-part structure, correct header alg)
 *  - Wrong grant_type → 400
 *  - Wrong client_secret → 401
 *  - Inactive client → 401
 *  - Issued token is verifiable by JwtService
 *  - Issued token contains correct claims (sub, client_id, scopes, facility_id)
 */
class AuthTokenTest extends TestCase
{
    private IntegrationClient $client;
    private string $rawSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rawSecret = 'sk_sandbox_test_' . bin2hex(random_bytes(16));

        $this->client = IntegrationClient::factory()->create([
            'client_id'     => 'test_cdss_' . bin2hex(random_bytes(4)),
            'client_secret' => hash('sha256', $this->rawSecret),
            'status'        => 'active',
            'environment'   => 'sandbox',
            'facility_id'   => '00000000-0000-0000-0000-100000000001',
            'scopes'        => ['patients:read', 'labs:write'],
        ]);
    }

    public function test_issues_real_rs256_jwt(): void
    {
        $response = $this->postJson('/api/v1/connect/auth/token', [
            'client_id'     => $this->client->client_id,
            'client_secret' => $this->rawSecret,
            'grant_type'    => 'client_credentials',
        ]);

        $response->assertStatus(200);
        $token = $response->json('access_token');

        // JWT must have exactly 3 parts
        $parts = explode('.', $token);
        $this->assertCount(3, $parts, 'JWT must have header.payload.signature');

        // Header must declare RS256
        $header = json_decode(base64_decode(str_pad(strtr($parts[0], '-_', '+/'), strlen($parts[0]) % 4 == 0 ? 0 : 4 - strlen($parts[0]) % 4, '=')), true);
        $this->assertSame('RS256', $header['alg']);
        $this->assertSame('JWT', $header['typ']);
    }

    public function test_jwt_claims_are_correct(): void
    {
        $response = $this->postJson('/api/v1/connect/auth/token', [
            'client_id'     => $this->client->client_id,
            'client_secret' => $this->rawSecret,
            'grant_type'    => 'client_credentials',
        ]);

        $token  = $response->json('access_token');
        $jwt    = app(JwtService::class);
        $claims = $jwt->verify($token);

        $this->assertSame($this->client->client_id, $claims['client_id']);
        $this->assertSame($this->client->client_id, $claims['sub']);
        $this->assertSame('opescare-connect', $claims['iss']);
        $this->assertSame($this->client->facility_id, $claims['facility_id']);
        $this->assertContains('patients:read', $claims['scopes']);
        $this->assertContains('labs:write', $claims['scopes']);
        $this->assertGreaterThan(time(), $claims['exp']);
    }

    public function test_response_shape_matches_oauth2_spec(): void
    {
        $response = $this->postJson('/api/v1/connect/auth/token', [
            'client_id'     => $this->client->client_id,
            'client_secret' => $this->rawSecret,
            'grant_type'    => 'client_credentials',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'scope'])
            ->assertJson(['token_type' => 'Bearer', 'expires_in' => 3600]);
    }

    public function test_wrong_grant_type_returns_400(): void
    {
        $this->postJson('/api/v1/connect/auth/token', [
            'client_id'     => $this->client->client_id,
            'client_secret' => $this->rawSecret,
            'grant_type'    => 'authorization_code',
        ])->assertStatus(400);
    }

    public function test_wrong_secret_returns_401(): void
    {
        $this->postJson('/api/v1/connect/auth/token', [
            'client_id'     => $this->client->client_id,
            'client_secret' => 'wrong_secret',
            'grant_type'    => 'client_credentials',
        ])->assertStatus(401);
    }

    public function test_inactive_client_returns_401(): void
    {
        $this->client->update(['status' => 'suspended']);

        $this->postJson('/api/v1/connect/auth/token', [
            'client_id'     => $this->client->client_id,
            'client_secret' => $this->rawSecret,
            'grant_type'    => 'client_credentials',
        ])->assertStatus(401);
    }

    public function test_missing_fields_returns_400(): void
    {
        $this->postJson('/api/v1/connect/auth/token', [
            'grant_type' => 'client_credentials',
        ])->assertStatus(400);
    }
}

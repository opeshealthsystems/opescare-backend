<?php

namespace Tests\Feature\Oauth;

use App\Services\JwtService;
use Tests\TestCase;

/**
 * Conformance + guardrail tests for the OAuth 2.0 discovery surface (Gap 4):
 * RFC 7517 JWKS, RFC 8414 metadata, RFC 7662 introspection.
 */
class OAuthDiscoveryTest extends TestCase
{
    /** base64url-decode (handles the JWT header's stripped padding). */
    private function b64url(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }

    public function test_jwks_publishes_rsa_public_key_only(): void
    {
        $res = $this->getJson('/.well-known/jwks.json');

        $res->assertOk();
        $res->assertJsonStructure(['keys' => [['kty', 'use', 'alg', 'kid', 'n', 'e']]]);
        $this->assertSame('RSA', $res->json('keys.0.kty'));
        $this->assertSame('sig', $res->json('keys.0.use'));
        $this->assertSame('RS256', $res->json('keys.0.alg'));
        $this->assertNotEmpty($res->json('keys.0.kid'));

        // CRITICAL: private-key components must never appear in the JWK.
        $jwk = $res->json('keys.0');
        foreach (['d', 'p', 'q', 'dp', 'dq', 'qi'] as $privateParam) {
            $this->assertArrayNotHasKey($privateParam, $jwk, "JWK leaked private parameter '{$privateParam}'.");
        }
    }

    public function test_authorization_server_metadata_advertises_endpoints(): void
    {
        $res = $this->getJson('/.well-known/oauth-authorization-server');

        $res->assertOk();
        $res->assertJsonFragment(['grant_types_supported' => ['client_credentials']]);

        $data = $res->json();
        $this->assertStringContainsString('/api/v1/connect/auth/token', $data['token_endpoint']);
        $this->assertStringContainsString('/api/v1/connect/auth/introspect', $data['introspection_endpoint']);
        $this->assertStringContainsString('/.well-known/jwks.json', $data['jwks_uri']);
        $this->assertContains('patients', $data['scopes_supported']);
        $this->assertContains('RS256', $data['token_endpoint_auth_signing_alg_values_supported']);
    }

    public function test_issued_token_header_kid_matches_jwks(): void
    {
        $jwt = app(JwtService::class);
        $token = $jwt->issue(['sub' => 'c1', 'client_id' => 'c1', 'scopes' => ['patients']]);

        [$headerB64] = explode('.', $token);
        $header = json_decode($this->b64url($headerB64), true);

        $this->assertSame('RS256', $header['alg']);
        $this->assertSame($jwt->keyId(), $header['kid']);
        $this->assertSame(
            $this->getJson('/.well-known/jwks.json')->json('keys.0.kid'),
            $header['kid'],
            'JWT header kid must match the published JWKS kid.'
        );
    }

    public function test_introspection_reports_active_for_valid_token(): void
    {
        $jwt = app(JwtService::class);
        $token = $jwt->issue([
            'sub'         => 'client-xyz',
            'client_id'   => 'client-xyz',
            'facility_id' => 'fac-1',
            'environment' => 'sandbox',
            'scopes'      => ['patients', 'labs'],
        ]);

        $res = $this->postJson('/api/v1/connect/auth/introspect', ['token' => $token], [
            'X-Client-ID'     => 'test_client_id',
            'X-Client-Secret' => 'test_client_secret',
        ]);

        $res->assertOk();
        $res->assertJson([
            'active'     => true,
            'token_type' => 'Bearer',
            'scope'      => 'patients labs',
            'client_id'  => 'client-xyz',
            'iss'        => 'opescare-connect',
            'aud'        => 'opescare-api',
        ]);
    }

    public function test_introspection_reports_inactive_for_garbage_token(): void
    {
        $res = $this->postJson('/api/v1/connect/auth/introspect', ['token' => 'not.a.jwt'], [
            'X-Client-ID'     => 'test_client_id',
            'X-Client-Secret' => 'test_client_secret',
        ]);

        $res->assertOk();
        $res->assertExactJson(['active' => false]);
    }

    public function test_introspection_endpoint_is_itself_protected(): void
    {
        // RFC 7662 §2.1 — the introspection endpoint must be authenticated.
        $res = $this->postJson('/api/v1/connect/auth/introspect', ['token' => 'x.y.z']);
        $res->assertStatus(401);
    }
}

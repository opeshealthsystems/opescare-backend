<?php

namespace App\Http\Controllers\Api\V1\Connect;

use App\Http\Controllers\Controller;
use App\Models\IntegrationClient;
use App\Services\AuditLogger;
use App\Services\JwtService;
use App\Enums\OpesCareErrorCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private readonly JwtService $jwt) {}

    /**
     * Exchange client credentials for a signed RS256 Bearer token.
     *
     * POST /api/v1/connect/auth/token
     * Body: { client_id, client_secret, grant_type: "client_credentials" }
     *
     * FIX C-1 (audit 2026-06-07): Previously used hash('sha256', $clientSecret) for
     * direct DB lookup, bypassing the Argon2 rolling migration entirely.
     *
     * Now uses the same dual-path verification as VerifyIntegrationClient middleware:
     *   1. Try Argon2id (client_secret_argon) — fast path for migrated clients.
     *   2. Fall back to timing-safe SHA-256 comparison (client_secret) — legacy clients.
     *   3. On SHA-256 success: immediately upgrade to Argon2id (rolling migration).
     *
     * ISO 27001 A.9.4 / OWASP API2
     */
    public function issueToken(Request $request)
    {
        $clientId     = $request->input('client_id');
        $clientSecret = $request->input('client_secret');
        $grantType    = $request->input('grant_type');

        if ($grantType !== 'client_credentials' || ! $clientId || ! $clientSecret) {
            return response()->json([
                'status'     => 'rejected',
                'error_code' => OpesCareErrorCode::AUTHENTICATION_FAILED->value,
                'message'    => __('api.client_credentials_required'),
            ], 400);
        }

        // Load client by client_id only — do NOT hash-lookup here (unsafe for Argon2).
        $client = IntegrationClient::where('client_id', $clientId)
            ->where('status', 'active')
            ->first();

        if (! $client) {
            return $this->rejected();
        }

        $authenticated = false;
        $needsUpgrade  = false;

        // ── Path 1: Argon2id (migrated clients) ──────────────────────────────
        if ($client->client_secret_argon !== null) {
            if (Hash::check($clientSecret, $client->client_secret_argon)) {
                $authenticated = true;
                if (Hash::needsRehash($client->client_secret_argon)) {
                    $needsUpgrade = true;
                }
            }
        }

        // ── Path 2: SHA-256 fallback (legacy clients not yet migrated) ────────
        if (! $authenticated && $client->client_secret !== null) {
            if (hash_equals($client->client_secret, hash('sha256', $clientSecret))) {
                $authenticated = true;
                $needsUpgrade  = true; // Always upgrade from SHA-256 to Argon2id
            }
        }

        if (! $authenticated) {
            return $this->rejected();
        }

        // ── Rolling upgrade: write Argon2id on first SHA-256 success ─────────
        if ($needsUpgrade) {
            $client->updateQuietly([
                'client_secret_argon' => Hash::make($clientSecret),
                'secret_upgraded_at'  => now(),
            ]);
        }

        $scopes = is_array($client->scopes) ? $client->scopes : json_decode($client->scopes ?? '[]', true);

        $token = $this->jwt->issue([
            'sub'         => $client->client_id,
            'client_id'   => $client->client_id,
            'facility_id' => $client->facility_id,
            'environment' => $client->environment,
            'scopes'      => $scopes,
        ]);

        $request->attributes->add([
            'integration_client_id' => $client->client_id,
            'facility_id'           => $client->facility_id,
        ]);

        AuditLogger::log($request, 'api_token_issued', 'oauth_token', null);

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => (int) config('services.opescare_jwt.ttl', 3600),
            'scope'        => implode(' ', $scopes),
        ]);
    }

    /**
     * RFC 7662 OAuth 2.0 Token Introspection for Connect RS256 JWTs.
     *
     * POST /api/v1/connect/auth/introspect
     * Auth: X-Client-ID + X-Client-Secret (VerifyIntegrationClient) — the
     *       introspection endpoint is itself protected, per RFC 7662 §2.1.
     * Body: { token: <jwt> }
     *
     * Returns { active: true, ...claims } for a valid, unexpired, non-revoked
     * Connect token; { active: false } for anything else. Per RFC 7662 §2.2 the
     * endpoint never reveals WHY a token is inactive (no signature/expiry/
     * revocation distinction leaks to the caller).
     */
    public function introspect(Request $request)
    {
        $token = (string) $request->input('token', '');

        if ($token === '') {
            return response()->json(['active' => false]);
        }

        try {
            $claims = $this->jwt->verify($token);
        } catch (\Throwable $e) {
            // Malformed / bad signature / expired / revoked → inactive, no detail.
            return response()->json(['active' => false]);
        }

        $scopes = $claims['scopes'] ?? null;

        return response()->json([
            'active'      => true,
            'token_type'  => 'Bearer',
            'scope'       => is_array($scopes) ? implode(' ', $scopes) : (string) ($claims['scope'] ?? ''),
            'client_id'   => $claims['client_id'] ?? null,
            'sub'         => $claims['sub'] ?? null,
            'aud'         => $claims['aud'] ?? null,
            'iss'         => $claims['iss'] ?? null,
            'exp'         => $claims['exp'] ?? null,
            'iat'         => $claims['iat'] ?? null,
            'jti'         => $claims['jti'] ?? null,
            'facility_id' => $claims['facility_id'] ?? null,
            'environment' => $claims['environment'] ?? null,
        ]);
    }

    public function createWidgetSession(Request $request)
    {
        AuditLogger::log($request, 'widget_session_created', 'widget_session', null);

        return response()->json([
            'session_token'   => 'wgt_session_' . bin2hex(random_bytes(16)),
            'expires_at'      => date('Y-m-d\TH:i:s\Z', time() + 600),
            'allowed_origins' => ['https://*.opescare.com'],
        ]);
    }

    private function rejected(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status'     => 'rejected',
            'error_code' => OpesCareErrorCode::AUTHENTICATION_FAILED->value,
            'message'    => __('api.invalid_integration_credentials'),
        ], 401);
    }
}

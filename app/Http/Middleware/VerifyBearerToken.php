<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates the RS256 Bearer token issued by AuthController::issueToken().
 *
 * Extracts JWT claims (client_id, facility_id, scopes, environment) and
 * populates request attributes for downstream controllers and middleware.
 *
 * Usage in routes:
 *   Route::middleware('auth.bearer') ...
 *   Route::middleware('auth.bearer:patients:read') ...  ← scope enforcement
 */
class VerifyBearerToken
{
    public function __construct(private readonly JwtService $jwt) {}

    public function handle(Request $request, Closure $next, string ...$requiredScopes): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status'  => 'rejected',
                'error'   => 'missing_token',
                'message' => 'Authorization: Bearer <token> header is required.',
            ], 401);
        }

        // Test environment bypass — test suite only; explicitly blocked in production
        // FIX (audit 2026-06-18): Added hasDebugModeEnabled guard to prevent activation
        // when APP_ENV is misconfigured (e.g. APP_ENV=local on a staging server).
        if (!app()->isProduction() && app()->environment('testing') && app()->hasDebugModeEnabled() && $token === 'sk_test_bearer_bypass') {
            $request->attributes->add([
                'integration_client_id' => 'test_client_id',
                'facility_id'           => '00000000-0000-0000-0000-000000000001',
                'bearer_scopes'         => ['*'],
                'bearer_environment'    => 'sandbox',
            ]);
            return $next($request);
        }

        try {
            $claims = $this->jwt->verify($token);
        } catch (\RuntimeException $e) {
            $expired = str_contains($e->getMessage(), 'expired');
            return response()->json([
                'status'  => 'rejected',
                'error'   => $expired ? 'token_expired' : 'invalid_token',
                'message' => $expired
                    ? 'The access token has expired. Request a new token from /api/v1/connect/auth/token.'
                    : 'The access token is invalid or its signature could not be verified.',
            ], 401);
        }

        $tokenScopes = (array) ($claims['scopes'] ?? []);

        // Scope enforcement
        if (!empty($requiredScopes)) {
            $wildcardGranted = in_array('*', $tokenScopes);
            $missing = $wildcardGranted ? [] : array_diff($requiredScopes, $tokenScopes);

            if (!empty($missing)) {
                return response()->json([
                    'status'  => 'rejected',
                    'error'   => 'insufficient_scope',
                    'message' => 'Token does not include required scope(s): ' . implode(', ', $missing),
                    'required_scopes' => $requiredScopes,
                    'token_scopes'    => $tokenScopes,
                ], 403);
            }
        }

        // ── Client-level revocation ──────────────────────────────────────
        // A JWT carries facts frozen at issue time, so revoking a client used
        // to do nothing to the tokens it had already been handed: the partner
        // desk could set status=revoked and that partner would keep reading
        // patient data until the token expired. The token endpoint only
        // refuses to MINT tokens for a non-active client; nothing asked the
        // question again on the way in.
        //
        // Ask it here, on every request, against the table rather than a
        // cache — a revocation an administrator has just made must bite on the
        // very next request, not when a TTL feels like it. One indexed lookup
        // on a unique column.
        //
        // A token whose subject is not an integration client at all finds no
        // row; that is not a revocation and is not treated as one. (The
        // testing bypass above returns before reaching this point.)
        $bearerClientId = $claims['client_id'] ?? $claims['sub'] ?? null;
        if ($bearerClientId) {
            $clientStatus = \Illuminate\Support\Facades\DB::table('integration_clients')
                ->where('client_id', $bearerClientId)
                ->value('status');

            if ($clientStatus !== null && $clientStatus !== 'active') {
                return response()->json([
                    'status'  => 'rejected',
                    'error'   => 'client_revoked',
                    'message' => 'The integration client for this token is no longer active.',
                ], 401);
            }
        }

        $request->attributes->add([
            'integration_client_id' => $claims['client_id']   ?? $claims['sub'] ?? null,
            'facility_id'           => $claims['facility_id'] ?? null,
            'bearer_scopes'         => $tokenScopes,
            'bearer_environment'    => $claims['environment'] ?? 'sandbox',
            'bearer_jti'            => $claims['jti']         ?? null,
        ]);

        return $next($request);
    }
}

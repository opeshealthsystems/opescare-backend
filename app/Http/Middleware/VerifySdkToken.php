<?php

namespace App\Http\Middleware;

use App\Models\SdkToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySdkToken
{
    /**
     * Authenticate SDK Bearer token requests.
     *
     * Reads:  Authorization: Bearer sk_live_xxxxxxxx
     * Hashes: SHA-256 of the raw token
     * Looks up SdkToken by token_hash, verifies active + not expired.
     * Optionally checks required scope: @middleware('sdk.token:scope_name')
     */
    public function handle(Request $request, Closure $next, string ...$requiredScopes): Response
    {
        $bearerToken = $request->bearerToken();

        if (!$bearerToken) {
            return response()->json([
                'error' => 'missing_token',
                'message' => 'Authorization header with Bearer token is required.',
            ], 401);
        }

        // Sandbox bypass for automated tests — only active in test environment; explicitly blocked in production
        if (!app()->isProduction() && app()->environment('testing') && $bearerToken === 'sk_test_sandbox_bypass_token') {
            $request->attributes->add([
                'sdk_token_id'  => 'sandbox',
                'sdk_client_id' => 'test_client_id',
                'sdk_scopes'    => ['*'],
                'facility_id'   => '00000000-0000-0000-0000-000000000001',
            ]);
            return $next($request);
        }

        $tokenHash = hash('sha256', $bearerToken);

        try {
            $token = SdkToken::where('token_hash', $tokenHash)
                ->where('is_active', true)
                ->first();
        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'server_error',
                'message' => 'Token verification unavailable.',
            ], 503);
        }

        if (!$token) {
            return response()->json([
                'error'   => 'invalid_token',
                'message' => 'The provided token is invalid or has been revoked.',
            ], 401);
        }

        if ($token->isExpired()) {
            return response()->json([
                'error'   => 'token_expired',
                'message' => 'The token has expired. Please issue a new token.',
            ], 401);
        }

        // Scope check
        if (!empty($requiredScopes)) {
            $tokenScopes = (array) ($token->scopes ?? []);
            $missing = array_diff($requiredScopes, $tokenScopes);
            if (!empty($missing) && !in_array('*', $tokenScopes)) {
                return response()->json([
                    'error'   => 'insufficient_scope',
                    'message' => 'Token does not have the required permissions for this operation.',
                ], 403);
            }
        }

        // Update last-used timestamp (async — fire-and-forget)
        $token->updateQuietly(['last_used_at' => now()]);

        $request->attributes->add([
            'sdk_token'     => $token,
            'sdk_token_id'  => $token->id,
            'sdk_client_id' => $token->client_id,
            'sdk_scopes'    => $token->scopes ?? [],
        ]);

        return $next($request);
    }
}

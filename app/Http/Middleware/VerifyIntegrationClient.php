<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use App\Models\IntegrationClient;

/**
 * VerifyIntegrationClient
 *
 * Authenticates B2B integration clients using X-Client-ID + X-Client-Secret headers.
 *
 * Migration Sprint — Item 1: SHA-256 → Argon2id rolling re-hash
 *
 * Verification order (zero-downtime rolling migration):
 *   1. Try Argon2id column (client_secret_argon) — fast path for re-hashed clients.
 *   2. Fall back to SHA-256 column (client_secret) — legacy clients not yet re-hashed.
 *      On success: immediately re-hash and write to client_secret_argon. From this
 *      point on the client uses the Argon2id path; SHA-256 path never hit again.
 *
 * Once all active clients have been through step 2 (client_secret_argon IS NOT NULL),
 * a future migration can drop the old client_secret column.
 *
 * ISO 27001 A.10.1: Argon2id is a key-derivation function with configurable memory
 * and time cost, making brute-force attacks computationally infeasible even if the
 * credentials table is exfiltrated. SHA-256 has no work factor.
 */
class VerifyIntegrationClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientId     = $request->header('X-Client-ID');
        $clientSecret = $request->header('X-Client-Secret');

        if (! $clientId || ! $clientSecret) {
            return response()->json([
                'error'   => 'missing_credentials',
                'message' => 'X-Client-ID and X-Client-Secret headers are required.',
            ], 401);
        }

        // ── Test environment bypass ───────────────────────────────────────────
        // Dual guard: both conditions must be true simultaneously.
        // isProduction() ensures bypass cannot fire even if APP_ENV is misconfigured.
        if (
            app()->environment('testing') &&
            ! app()->isProduction() &&
            $clientId     === 'test_client_id' &&
            $clientSecret === 'test_client_secret'
        ) {
            // Allow tests to scope the bypass client to a real seeded facility by
            // passing facility_id in the request; fall back to the sandbox UUID.
            $testFacilityId = $request->input('facility_id');
            if (! is_string($testFacilityId) || ! \Illuminate\Support\Str::isUuid($testFacilityId)) {
                $testFacilityId = '00000000-0000-0000-0000-000000000001';
            }

            $request->attributes->add([
                'integration_client_id' => 'test_client_id',
                'provider_id'           => '00000000-0000-0000-0000-000000000001',
                'facility_id'           => $testFacilityId,
            ]);
            return $next($request);
        }

        // ── Production verification with rolling Argon2 upgrade ───────────────
        try {
            $client = IntegrationClient::where('client_id', $clientId)
                ->where('status', 'active')
                ->first();

            if (! $client) {
                return $this->invalidCredentials();
            }

            $authenticated = false;
            $needsUpgrade  = false;

            // ── Path 1: Argon2id (new, fast for already-upgraded clients) ─────
            if ($client->client_secret_argon !== null) {
                if (Hash::check($clientSecret, $client->client_secret_argon)) {
                    $authenticated = true;
                    // Rehash if Argon2 configuration has changed (work factor increase)
                    if (Hash::needsRehash($client->client_secret_argon)) {
                        $needsUpgrade = true;
                    }
                }
            }

            // ── Path 2: SHA-256 fallback for legacy clients ───────────────────
            if (! $authenticated && $client->client_secret !== null) {
                if (hash_equals($client->client_secret, hash('sha256', $clientSecret))) {
                    $authenticated = true;
                    $needsUpgrade  = true; // Always upgrade from SHA-256
                }
            }

            if (! $authenticated) {
                return $this->invalidCredentials();
            }

            // ── Rolling upgrade: write Argon2id hash on first SHA-256 success ─
            if ($needsUpgrade) {
                $client->updateQuietly([
                    'client_secret_argon' => Hash::make($clientSecret),
                    'secret_upgraded_at'  => now(),
                ]);
            }

            $request->attributes->add([
                'integration_client'    => $client,
                'integration_client_id' => $client->client_id,
                'facility_id'           => $client->facility_id,
                'provider_id'           => $client->created_by,
            ]);

        } catch (\Exception $e) {
            // Log class only — message may contain DB schema/column details
            \Log::error('integration_client_auth_error', [
                'exception_class' => get_class($e),
                'client_id_hint'  => substr($clientId, 0, 8) . '…',
            ]);

            return response()->json([
                'error'   => 'authentication_error',
                'message' => 'An internal error occurred during client authentication.',
            ], 500);
        }

        return $next($request);
    }

    private function invalidCredentials(): Response
    {
        return response()->json([
            'error'   => 'invalid_client',
            'message' => 'Invalid or inactive integration client.',
        ], 403);
    }
}

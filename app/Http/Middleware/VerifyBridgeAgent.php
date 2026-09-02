<?php

namespace App\Http\Middleware;

use App\Models\BridgeAgent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class VerifyBridgeAgent
{
    /**
     * Authenticate Bridge Agent requests.
     *
     * Header: X-Bridge-Agent-Key: <raw_key>
     *
     * Verification order (zero-downtime rolling migration, matching IntegrationClient pattern):
     *   1. Try Argon2id (agent_key_argon) — fast path for re-hashed agents.
     *   2. Fall back to SHA-256 (agent_key) — legacy agents not yet re-hashed.
     *      On success: immediately re-hash and write to agent_key_argon.
     *
     * ISO 27001 A.10.1: Argon2id has configurable memory/time cost.
     * SHA-256 has no work factor — all agents MUST eventually be migrated.
     *
     * FIX (audit 2026-06-18): Previously used plain SHA-256 with no work factor,
     * making brute-force of exfiltrated DB credentials computationally trivial.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $rawKey = $request->header('X-Bridge-Agent-Key');

        if (!$rawKey) {
            return response()->json([
                'error'   => 'missing_credentials',
                'message' => 'X-Bridge-Agent-Key header is required.',
            ], 401);
        }

        try {
            $matchedAgent = null;
            $needsUpgrade = false;

            // Fetch ALL active agents and test each key (agent count is tiny —
            // one per facility, typically <50 per deployment). This enables the
            // dual-path Argon2id/SHA-256 rolling migration without requiring a
            // lookup-friendly hash format.
            $agents = BridgeAgent::where('status', 'active')->get();
            if ($agents->isEmpty()) {
                return response()->json([
                    'error'   => 'invalid_key',
                    'message' => 'Invalid or inactive Bridge Agent key.',
                ], 401);
            }

            foreach ($agents as $candidate) {
                $authenticated = false;
                $upgrade       = false;

                // ── Path 1: Argon2id (migrated agents) ───────────────────────
                if ($candidate->agent_key_argon !== null) {
                    if (Hash::check($rawKey, $candidate->agent_key_argon)) {
                        $authenticated = true;
                        if (Hash::needsRehash($candidate->agent_key_argon)) {
                            $upgrade = true;
                        }
                    }
                }

                // ── Path 2: SHA-256 fallback (legacy agents) ─────────────────
                if (! $authenticated && $candidate->agent_key !== null) {
                    $keyHash = hash('sha256', $rawKey);
                    if (hash_equals($candidate->agent_key, $keyHash)) {
                        $authenticated = true;
                        $upgrade = true; // Always upgrade from SHA-256 to Argon2id
                    }
                }

                if ($authenticated) {
                    $matchedAgent = $candidate;
                    $needsUpgrade = $upgrade;
                    break;
                }
            }

            if (! $matchedAgent) {
                return response()->json([
                    'error'   => 'invalid_key',
                    'message' => 'Invalid or inactive Bridge Agent key.',
                ], 401);
            }

            // ── Rolling upgrade: write Argon2id on first SHA-256 success ─────
            if ($needsUpgrade) {
                $matchedAgent->updateQuietly([
                    'agent_key_argon' => Hash::make($rawKey),
                    'agent_key'       => null,  // Clear legacy SHA-256 hash
                    'secret_upgraded_at' => now(),
                ]);
            }

            // Update heartbeat
            $matchedAgent->updateQuietly([
                'last_seen_at' => now(),
                'ip_address'   => $request->ip(),
            ]);

        } catch (\Throwable $e) {
            \Log::error('bridge_agent_auth_error', [
                'exception_class' => get_class($e),
            ]);

            return response()->json(['error' => 'server_error', 'message' => 'Auth check failed.'], 503);
        }

        $request->attributes->add([
            'bridge_agent'    => $matchedAgent,
            'bridge_agent_id' => $matchedAgent->id,
            'facility_id'     => $matchedAgent->facility_id,
        ]);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\PatientAccessToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobilePatient
{
    /**
     * Authenticate a mobile patient using a bearer token.
     *
     * FIX (audit 2026-06-18): Previously used a 12-character prefix (72 bits of entropy)
     * to look up the token, reducing the search space for potential enumeration attacks.
     *
     * Now uses the full SHA-256 hash of the bearer token for DB lookup (256 bits),
     * then verifies with Hash::check() for constant-time comparison.
     *
     * Rolling migration:
     *   - New tokens store token_hash as bcrypt/Argon2id (via Hash::make) and
     *     token_lookup_hash as SHA-256 (for indexed lookup).
     *   - Database unique index on token_lookup_hash prevents collision.
     *   - token_prefix column is deprecated but preserved for backward compatibility
     *     during the migration window.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (!$bearer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Primary path: lookup by SHA-256 hash (full bearer entropy).
        // Fallback path: 12-char prefix for tokens issued before the migration.
        $lookupHash = hash('sha256', $bearer);

        $token = PatientAccessToken::where('token_lookup_hash', $lookupHash)
            ->where('expires_at', '>', now())
            ->first();

        // Fallback to prefix-based lookup (legacy tokens)
        if (! $token) {
            $prefix = substr($bearer, 0, 12);
            $token = PatientAccessToken::where('token_prefix', $prefix)
                ->whereNull('token_lookup_hash')  // Only legacy tokens without lookup hash
                ->where('expires_at', '>', now())
                ->first();

            // If found via prefix, upgrade the token by writing the lookup hash
            // so next request uses the fast path.
            if ($token && Hash::check($bearer, $token->token_hash)) {
                $token->updateQuietly([
                    'token_lookup_hash' => hash('sha256', $bearer),
                ]);
            } else {
                $token = null;  // Auth failed
            }
        }

        if (!$token || !Hash::check($bearer, $token->token_hash)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->attributes->set('patient_id', $token->patient_id);
        $request->attributes->set('patient_token', $token);

        // Resolve the patient's linked user account (users.patient_id) so
        // downstream controllers can attribute actions to a user identity
        // without ever trusting caller-supplied user_id values.
        $linkedUserId = \App\Models\User::where('patient_id', $token->patient_id)->value('id');
        if ($linkedUserId) {
            $request->attributes->set('patient_user_id', $linkedUserId);
        }

        return $next($request);
    }
}

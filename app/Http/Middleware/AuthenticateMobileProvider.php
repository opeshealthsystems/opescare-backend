<?php

namespace App\Http\Middleware;

use App\Models\ProviderAccessToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves a clinician's mobile session.
 *
 * Mirrors AuthenticateMobilePatient, and sets `provider_user_id` and
 * `provider_facility_id` on the request attributes. Controllers MUST read the
 * facility from there and never from the request body — the platform rule is
 * that facility_id only ever comes from the auth middleware.
 */
class AuthenticateMobileProvider
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (! $bearer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = ProviderAccessToken::where('token_lookup_hash', hash('sha256', $bearer))
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $token || ! Hash::check($bearer, $token->token_hash)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = $token->user;

        // The role can be withdrawn after the token was minted; re-check it on
        // every request rather than trusting what was true at login.
        if (! $user || ! $user->isProfessional()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->saveQuietly();

        $request->attributes->set('provider_user_id', $user->id);
        $request->attributes->set('provider_facility_id', $token->facility_id);
        $request->attributes->set('provider_token', $token);

        return $next($request);
    }
}

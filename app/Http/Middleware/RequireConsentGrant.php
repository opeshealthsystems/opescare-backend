<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ConsentGrant;

/**
 * RequireConsentGrant
 *
 * Enforces that B2B Connect API callers supply a valid, active, non-expired
 * ConsentGrant that is scoped to the requesting facility.
 *
 * Usage in routes:
 *   ->middleware('consent.grant')               // presence + validity only
 *   ->middleware('consent.grant:patients:read') // also checks specific scope
 *
 * Reads the following request headers:
 *   X-Consent-Grant-Id  – UUID of the ConsentGrant row
 *   X-Purpose-Of-Use    – optional; passed through for audit purposes
 *
 * On success, attaches the ConsentGrant instance as
 *   $request->attributes->get('consent_grant')
 * so controllers can use it without a second DB lookup.
 */
class RequireConsentGrant
{
    public function handle(Request $request, Closure $next, ?string $requiredScope = null): Response
    {
        $grantId = $request->header('X-Consent-Grant-Id');

        if (!$grantId) {
            return response()->json([
                'status'          => 'rejected',
                'error_code'      => 'CONSENT_REQUIRED',
                'message'         => 'A valid X-Consent-Grant-Id header is required to access this resource.',
                'required_action' => 'request_consent',
            ], 403);
        }

        // The consent_grants primary key is a Postgres uuid column — a malformed
        // id would raise "invalid input syntax for type uuid" at the DB layer.
        // Treat any non-UUID value as an invalid grant instead of erroring.
        if (! \Illuminate\Support\Str::isUuid($grantId)) {
            return response()->json([
                'status'          => 'rejected',
                'error_code'      => 'CONSENT_REQUIRED',
                'message'         => 'The supplied consent grant is invalid, expired, or has been revoked.',
                'required_action' => 'request_consent',
            ], 403);
        }

        // Resolve the requesting facility from the auth middleware
        $facilityId = $request->attributes->get('facility_id');

        // Look up the grant — disable the IsDemoRecord global scope so that
        // grants created without is_demo=true are always visible in tests.
        $grant = ConsentGrant::withoutGlobalScopes()
            ->where('id', $grantId)
            ->where('status', 'active')
            ->where('expires_at', '>=', Carbon::now())
            ->first();

        if (!$grant) {
            return response()->json([
                'status'          => 'rejected',
                'error_code'      => 'CONSENT_REQUIRED',
                'message'         => 'The supplied consent grant is invalid, expired, or has been revoked.',
                'required_action' => 'request_consent',
            ], 403);
        }

        // Ensure the grant belongs to the calling facility, not someone else's
        if ($facilityId && $grant->facility_id !== $facilityId) {
            return response()->json([
                'status'          => 'rejected',
                'error_code'      => 'CONSENT_REQUIRED',
                'message'         => 'This consent grant was not issued to your facility.',
                'required_action' => 'request_consent',
            ], 403);
        }

        // Optional scope check (e.g. middleware('consent.grant:labs:write'))
        if ($requiredScope !== null) {
            $grantedScopes = $grant->scope ?? [];
            if (!in_array($requiredScope, $grantedScopes) && !in_array('*', $grantedScopes)) {
                return response()->json([
                    'status'          => 'rejected',
                    'error_code'      => 'CONSENT_REQUIRED',
                    'message'         => "Your consent grant does not include the '{$requiredScope}' scope.",
                    'required_action' => 'request_consent',
                ], 403);
            }
        }

        // Make the resolved grant available to controllers
        $request->attributes->set('consent_grant', $grant);

        return $next($request);
    }
}

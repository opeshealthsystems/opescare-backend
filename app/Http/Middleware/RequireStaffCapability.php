<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\StaffCapabilities;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * `capability:prescribe` / `capability:clinical_chart,lab_results` — the route
 * is for professionals whose role holds at least one of the named
 * capabilities (see config/staff_capabilities.php).
 *
 * Works on both surfaces:
 *   • web portal routes — the signed-in user (after `auth`), judged by the
 *     role they hold at the ACTIVE facility (`session('active_facility_id')`,
 *     set by RequireFacilityContext / the facility selector, then the primary
 *     facility) — the same resolution EnsurePortalAccess applies, so the two
 *     gates agree on which posting is being judged;
 *   • clinician API routes — the provider the bearer token names (after
 *     `auth.provider`, which sets `provider_user_id` and, when the token is
 *     bound to a posting, `provider_facility_id`).
 *
 * Either way the global `users.role_id` is only the fallback for a user with
 * no assignment at that facility.
 *
 * A refusal is a 403 that names the reason (`error_code: role_forbidden` on
 * JSON) — never a 404, since the professional is already authenticated and
 * the route's existence is not a secret.
 */
class RequireStaffCapability
{
    public function handle(Request $request, Closure $next, string ...$capabilities): Response
    {
        $user = $this->actor($request);

        if (! $user || ! StaffCapabilities::canAt($user, $this->facilityId($request, $user), ...$capabilities)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message'    => __('api.capability_forbidden'),
                    'error_code' => 'role_forbidden',
                    'required'   => array_values($capabilities),
                ], 403);
            }

            abort(403, __('api.capability_forbidden'));
        }

        return $next($request);
    }

    private function actor(Request $request): ?User
    {
        $providerId = $request->attributes->get('provider_user_id');

        if ($providerId) {
            return User::with('role')->find($providerId);
        }

        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    /**
     * The facility whose role is judged. Only ever read from what middleware
     * set — the token's `provider_facility_id` attribute on the API, the
     * session on the web — never from headers, query or body.
     */
    private function facilityId(Request $request, User $user): ?string
    {
        if ($request->attributes->get('provider_user_id')) {
            $fromToken = $request->attributes->get('provider_facility_id');

            return $fromToken ? (string) $fromToken : null;
        }

        return StaffCapabilities::contextFacilityId($user);
    }
}

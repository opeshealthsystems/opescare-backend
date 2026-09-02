<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A caregiver account with no submitted profile is sent to finish signing up.
 *
 * Registration stops at email + password, so between sign-up and completion a
 * guardian exists who has not yet told us who they are or who they are asking
 * to act for. The guardian dashboard profile points at the patient portal,
 * which would show them an empty record — this sends them to the step that
 * actually needs doing instead.
 *
 * Only guardian-role users are affected; every other role passes through.
 */
class RequireCompleteGuardianProfile
{
    /** Reachable while the profile is still incomplete or awaiting review. */
    private const ALLOWED = [
        'portals/guardian/complete-profile',
        'portals/guardian/pending',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || ($user->role?->name) !== 'guardian') {
            return $next($request);
        }

        foreach (self::ALLOWED as $path) {
            if ($request->is($path) || $request->is($path . '/*')) {
                return $next($request);
            }
        }

        // Submitted, but the relationship is not verified yet — there is still
        // nothing in the portal for them to see.
        return redirect()->route(
            $user->profile_completed_at
                ? 'portals.guardian.pending'
                : 'portals.guardian.complete-profile'
        );
    }
}

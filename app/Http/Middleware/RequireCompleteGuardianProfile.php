<?php

namespace App\Http\Middleware;

use App\Models\FamilyLink;
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
 * The gate has an exit. A guardian who already stands in a relationship —
 * a FamilyLink they own (active, or an invite still pending) or a patient
 * record of their own — has a portal to use and passes straight through.
 * A guardian who has completed the profile but has no link yet may still
 * reach the family pages, because registering a child or inviting an adult
 * IS the way a link comes to exist; without that the "pending" page was a
 * dead end nothing ever resolved.
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

    /** Reachable once the profile is submitted: where a first link is made. */
    private const ALLOWED_AFTER_PROFILE = [
        'patient/family',
        'patient/dependents',
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

        if ($user->patient_id || $this->hasAnyLink($user->id)) {
            return $next($request);
        }

        if ($user->profile_completed_at) {
            foreach (self::ALLOWED_AFTER_PROFILE as $path) {
                if ($request->is($path) || $request->is($path . '/*')) {
                    return $next($request);
                }
            }
        }

        // Submitted, but no relationship exists yet — the pending page now
        // offers the two actions that create one.
        return redirect()->route(
            $user->profile_completed_at
                ? 'portals.guardian.pending'
                : 'portals.guardian.complete-profile'
        );
    }

    private function hasAnyLink(string $userId): bool
    {
        return FamilyLink::where('guardian_user_id', $userId)
            ->whereIn('status', ['active', 'pending_invite'])
            ->exists();
    }
}

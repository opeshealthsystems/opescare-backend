<?php

namespace App\Services\Portal;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

/**
 * DeskFacilityContext — ONE answer to "which facility is this desk acting for"
 * on the desks whose routes do not run RequireFacilityContext (queue, triage,
 * chart — routes/queue.php, routes/triage.php, routes/chart.php).
 *
 * WHY. PortalContextService::facilityId() is `session('active_facility_id')
 * ?? primary_facility_id`. On /facility/* the middleware fills the session
 * from the user's REAL postings first, so the fallback is never reached. On
 * the three desks above nothing fills it: a clinician who opens
 * /facility/queue first thing in a session was scoped to `primary_facility_id`
 * — a column that can name a facility they hold NO posting at (the demo
 * doctor's does) — and a clinician posted at two hospitals was pinned to one
 * of them silently, where every other page sends them to /select-facility.
 *
 * This class applies the middleware's rule from inside the controller, so the
 * desks agree with the rest of the portal without touching the route files:
 *
 *   1. session('active_facility_id')            → use it (an explicit switch wins)
 *   2. platform tier (facilityIdsFor === null)  → the old behaviour, unscoped
 *   3. exactly one posting                      → resolve it silently, pin the session
 *   4. more than one                            → redirect to /select-facility
 *   5. none                                     → null; the caller closes the desk (403)
 *
 * Never a header, never the body, never a "first facility" fallback.
 */
class DeskFacilityContext
{
    public function __construct(
        private readonly PortalContextService $ctx,
        private readonly FacilityMembershipService $memberships,
    ) {
    }

    /**
     * The facility this desk may act for, or null when the account holds none.
     *
     * @throws HttpResponseException redirect to the selector when the choice is ambiguous
     */
    public function resolve(): ?string
    {
        $active = session('active_facility_id');

        if (is_string($active) && $active !== '') {
            return $active;
        }

        $user = Auth::user();

        if (! $user) {
            return null;
        }

        $allowed = $this->memberships->facilityIdsFor($user);

        // Platform tier: unrestricted, exactly as RequireFacilityContext leaves it.
        if ($allowed === null) {
            $id = $this->ctx->facilityId();

            return $id ? (string) $id : null;
        }

        if (count($allowed) === 1) {
            session(['active_facility_id' => $allowed[0]]);

            return (string) $allowed[0];
        }

        if (count($allowed) > 1) {
            throw new HttpResponseException(
                redirect()->route('select-facility')
                    ->with('info', __('public.portal.select_facility_required', [], app()->getLocale()) ?: 'Please select a facility to continue.')
            );
        }

        return null;
    }
}

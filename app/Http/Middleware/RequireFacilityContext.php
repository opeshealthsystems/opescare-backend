<?php

namespace App\Http\Middleware;

use App\Services\Portal\FacilityMembershipService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * RequireFacilityContext
 *
 * Ensures every portal request has a resolved facility context before data
 * is loaded. Resolution order:
 *
 *  1. If session('active_facility_id') is already set — pass through.
 *  2. If the user holds exactly ONE posting — auto-set the session and pass
 *     through. A single-facility user is never asked to click.
 *  3. If the user holds MORE THAN ONE — send them to the facility selector, so
 *     they CHOOSE which facility they are acting as.
 *  4. If the user holds none — redirect to the selector as before (it will show
 *     them an empty list, which is the honest answer).
 *
 * ── WHY STEP 2 COUNTS INSTEAD OF JUST READING primary_facility_id ─────────
 * This used to be "if the user has a primary_facility_id, use it" — full stop.
 * `/select-facility` already existed and was already correct, and a clinician
 * posted at two hospitals could never reach it: the middleware silently pinned
 * them to their primary facility on the first request of every session, and
 * every portal query scoped to that from then on. The only users who ever saw
 * the selector were the ones with NO facility at all — the one group with
 * nothing to choose from.
 *
 * So the count decides. What a multi-facility user gets is one choice per
 * session, changeable afterwards from the sidebar ("Switch Facility" →
 * /select-facility, which re-runs the same entitlement check on POST).
 *
 * Roles that operate at platform level (no facility) bypass the redirect
 * naturally because their controllers use PortalContextService::facilityId()
 * which returns null and leaves queries unscoped (seeing all facilities).
 */
class RequireFacilityContext
{
    private readonly FacilityMembershipService $memberships;

    /**
     * The service is optional so `new RequireFacilityContext()` keeps working.
     * Laravel resolves middleware through the container and will inject it, but
     * this class is also constructed directly (tests do, and so does anything
     * that wants to run the check outside the HTTP stack) — a required argument
     * would turn every one of those call sites into a fatal.
     */
    public function __construct(?FacilityMembershipService $memberships = null)
    {
        $this->memberships = $memberships ?? app(FacilityMembershipService::class);
    }

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Explicit bypass for super-admin, platform-admin, and system-admin roles.
        // These roles operate at the platform level without facility context.
        // Log the bypass for audit trail.
        $platformAdminRoles = ['super_admin', 'platform_admin', 'system_admin', 'product_admin'];
        if ($user->role && in_array($user->role->name, $platformAdminRoles, true)) {
            Log::info('super_admin_facility_bypass', [
                'user_id'    => $user->id,
                'role'       => $user->role->name,
                'path'       => $request->path(),
                'ip_address' => $request->ip(),
            ]);
            return $next($request);
        }

        // A suspended account is refused at login only; an open session used to
        // keep working after FacilityAdminDeskService::deactivate() suspended
        // it. End that session here, on its next request.
        if (in_array((string) $user->status, ['suspended', 'inactive', 'disabled', 'deactivated'], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        // The user's REAL postings: active facility_role_assignments, active
        // staff_profiles, plus primary_facility_id. Same resolver the selector
        // and the mobile switch endpoint use, so the three cannot disagree
        // about which facilities a person may act at.
        $allowed = $this->memberships->facilityIdsFor($user);

        // Already resolved for this session — including by an explicit switch.
        // Re-checked against the live postings on every request: a member
        // removed from this facility (posting ended) must lose it immediately,
        // not when their session cookie happens to expire.
        if ($active = session('active_facility_id')) {
            if ($allowed === null || in_array((string) $active, array_map('strval', $allowed), true)) {
                return $next($request);
            }

            Log::warning('facility_context_revoked', [
                'user_id'     => $user->id,
                'facility_id' => $active,
                'path'        => $request->path(),
            ]);
            session()->forget('active_facility_id');
        }

        // null = platform tier (a wider list than the four names above — it also
        // covers privacy, security, compliance, support and partner functions).
        // Those accounts are not facility staff: their portals run unscoped, and
        // sending them to a selector listing every facility in the country would
        // be a choice with no meaning. Keep exactly the old behaviour for them.
        if ($allowed === null) {
            if ($user->primary_facility_id) {
                session(['active_facility_id' => $user->primary_facility_id]);
            }
            return $next($request);
        }

        // Exactly one posting: there is nothing to choose. Resolve it silently,
        // exactly as before, so single-facility staff notice no change at all.
        if (count($allowed) === 1) {
            session(['active_facility_id' => $allowed[0]]);
            return $next($request);
        }

        // More than one, or none: fall through to the selector. Nothing is
        // auto-assigned here any more — picking one for a multi-facility
        // clinician is exactly the silent guess this feature removes.

        // For routes that strictly need one, redirect to the facility selector.
        // Exempt the selector route itself, the admin governance portal
        // (platform-level, no facility required), and the patient portal
        // (patients are identified by patient_id, not facility).
        if ($request->is('select-facility*') || $request->is('admin*') || $request->is('patient*') || $request->is('portals/admin*') || $request->is('portals/patient*')) {
            return $next($request);
        }

        return redirect()->route('select-facility')
            ->with('info', __('public.portal.select_facility_required', [], app()->getLocale())
                ?: 'Please select a facility to continue.');
    }
}

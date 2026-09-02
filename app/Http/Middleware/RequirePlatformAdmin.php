<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * RequirePlatformAdmin — separates PLATFORM administration (god-mode) from
 * FACILITY administration.
 *
 * Bug it fixes: EnsurePortalAccess allowed facility admins (clinic_admin,
 * hospital_admin, …) into /portals/admin in the SAME bucket as platform admins
 * (super_admin, platform_admin, …). The platform Control Center, Security Ops,
 * subscriptions, and the bare /admin/* god-mode data routes were therefore
 * reachable by a facility user. This middleware restricts those platform-only
 * paths to the platform role tier; facility admins keep their facility-scoped
 * admin views and get 403 on platform-only areas.
 *
 * It is a no-op on any path that is not platform-only, so it is safe to attach
 * broadly.
 */
class RequirePlatformAdmin
{
    /** Roles operated by the platform owner (OpesCare) — full god-mode tier. */
    private const PLATFORM_ROLES = [
        'super_admin', 'platform_admin', 'system_admin', 'product_admin',
        'legal_admin', 'country_admin', 'regional_admin',
        // Compliance / security / support / partner are platform-company
        // functions (not facility staff) and legitimately use the platform console.
        'privacy_officer', 'data_protection_officer', 'security_officer',
        'compliance_officer', 'audit_reviewer', 'emergency_access_reviewer',
        'support_agent', 'support_manager', 'customer_success',
        'implementation_lead', 'training_support',
        'partner_admin', 'partner_reviewer', 'partner_compliance', 'partner_technical',
    ];

    /**
     * Path prefixes that expose platform-wide (cross-facility / god-mode)
     * capabilities and must never be reachable by a facility-tier admin.
     */
    private const PLATFORM_ONLY_PREFIXES = [
        // Platform console
        'portals/admin/cc',
        'portals/admin/security',
        'portals/admin/subscription',
        'portals/admin/connect',
        'portals/admin/bridge',
        'portals/admin/kpi',
        'portals/admin/legal',
        'portals/admin/certifications',
        'portals/admin/code-mappings',
        'portals/admin/developer',
        'portals/admin/go-live',
        'portals/admin/reports',
        // Bare /admin/* god-mode data management (all users/facilities/patients)
        'admin/users',
        'admin/facilities',
        'admin/patients',
        'admin/staff',
        'admin/organizations',
        'admin/roles',
        'admin/care-map',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // The bare facility-admin dashboard (/portals/admin) IS facility-scoped
        // and shared by facility admins; everything UNDER /portals/admin/* is
        // platform god-mode (Control Center, Onboarding, Security, KPI, Legal,
        // Subscriptions, god-mode data, …) and must be platform-tier only.
        // The predicate lives in isPlatformOnlyPath() so the nav can ask it too.
        if (! self::isPlatformOnlyPath($request->path())) {
            return $next($request);
        }

        if (! Auth::check()) {
            return redirect()->route('login');
        }

        if (! self::isPlatformTier(Auth::user())) {
            abort(403, 'This area is restricted to OpesCare platform administrators.');
        }

        return $next($request);
    }

    /**
     * Is this user in the platform-owner role tier?
     *
     * Public and static because the NAVIGATION has to ask the same question.
     * A sidebar that links to a platform-only page for a facility-tier user is
     * a guaranteed 403, and the only way that cannot drift back is for the nav
     * and this middleware to read the same list through the same resolver —
     * hence @platformadmin in AppServiceProvider calls straight into here.
     */
    public static function isPlatformTier(?\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        // Facility-aware, matching EnsurePortalAccess: a user can hold a
        // different role at the facility they are currently acting in. Falls
        // back to the global role, which is what platform admins have (they
        // belong to no facility).
        $facilityId = session('active_facility_id') ?? $user->primary_facility_id ?? null;
        $role = ($facilityId && method_exists($user, 'roleAtFacility'))
            ? $user->roleAtFacility($facilityId)
            : null;
        $roleName = ($role?->name) ?? ($user->role?->name);

        return in_array($roleName, self::PLATFORM_ROLES, true);
    }

    /** Does this path require the platform tier? Shared with the nav layer. */
    public static function isPlatformOnlyPath(string $path): bool
    {
        $path = ltrim($path, '/');

        if ($path !== 'portals/admin' && str_starts_with($path, 'portals/admin/')) {
            return true;
        }

        foreach (self::PLATFORM_ONLY_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }
}

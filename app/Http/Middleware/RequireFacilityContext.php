<?php

namespace App\Http\Middleware;

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
 *  2. If user has primary_facility_id — auto-set session and pass through.
 *  3. Otherwise — redirect to facility selector (multi-facility admin case).
 *
 * Roles that operate at platform level (no facility) bypass the redirect
 * naturally because their controllers use PortalContextService::facilityId()
 * which returns null and leaves queries unscoped (seeing all facilities).
 */
class RequireFacilityContext
{
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

        // Already resolved for this session
        if (session('active_facility_id')) {
            return $next($request);
        }

        // Auto-resolve from user's primary facility
        if ($user->primary_facility_id) {
            session(['active_facility_id' => $user->primary_facility_id]);
            return $next($request);
        }

        // No facility context available — for routes that strictly need one,
        // redirect to the facility selector. Exempt the selector route itself,
        // the admin governance portal (platform-level, no facility required),
        // and the patient portal (patients are identified by patient_id, not facility).
        if ($request->is('select-facility*') || $request->is('portals/admin*') || $request->is('portals/patient*')) {
            return $next($request);
        }

        return redirect()->route('select-facility')
            ->with('info', __('public.portal.select_facility_required', [], app()->getLocale())
                ?: 'Please select a facility to continue.');
    }
}

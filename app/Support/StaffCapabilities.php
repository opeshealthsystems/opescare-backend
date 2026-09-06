<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;

/**
 * What a professional account may open, read from config/staff_capabilities.php.
 *
 * Used by the `capability:` middleware (web and clinician API), the
 * `@capability()` Blade directive (sidebars, dashboards) and the clinician
 * app's mirror of the same map. Never compares roles by hand anywhere else:
 * a new role gets its surfaces by being added to the config, once.
 *
 * ── Which role? The one at the ACTIVE FACILITY ──────────────────────────────
 *
 * A professional can hold a different role at each posting
 * (facility_role_assignments). EnsurePortalAccess has always gated on the
 * facility-scoped role — `session('active_facility_id')`, then the primary
 * facility, then the global `users.role_id` — so this class resolves the role
 * the same way, in the same order. Otherwise a nurse at hospital A who is a
 * doctor at hospital B would pass the portal gate as a nurse and then be told
 * by the capability layer that she may prescribe.
 *
 * roleFor() is the single resolver. can() reads the web context (session →
 * primary facility) for the Blade directive; canAt() takes an explicit
 * facility for the middleware, which on the clinician API knows the facility
 * from the bearer token (`provider_facility_id`) rather than from a session.
 */
final class StaffCapabilities
{
    /** @return list<string> every capability name */
    public static function all(): array
    {
        return array_keys((array) config('staff_capabilities.capabilities', []));
    }

    /** @return list<string> the roles that hold a capability */
    public static function roles(string $capability): array
    {
        return array_values((array) config("staff_capabilities.capabilities.{$capability}", []));
    }

    /** @return list<string> the capabilities a role name holds */
    public static function forRole(?string $role): array
    {
        if ($role === null || $role === '') {
            return [];
        }

        $held = [];
        foreach ((array) config('staff_capabilities.capabilities', []) as $capability => $roles) {
            if (in_array($role, (array) $roles, true)) {
                $held[] = $capability;
            }
        }

        return $held;
    }

    public static function roleCan(?string $role, string ...$capabilities): bool
    {
        if ($role === null || $role === '') {
            return false;
        }

        foreach ($capabilities as $capability) {
            if (in_array($role, self::roles($capability), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Any-of: true when the user's role AT THE ACTIVE FACILITY (web context:
     * session, then primary facility, then the global role) holds at least
     * one of the capabilities. This is what the `@capability` directive calls.
     */
    public static function can(?User $user, string ...$capabilities): bool
    {
        return self::canAt($user, self::contextFacilityId($user), ...$capabilities);
    }

    /**
     * Any-of against the role held at an explicit facility (null = global role).
     * The middleware uses this so the clinician API can pass the facility the
     * token was issued for instead of reading a session it does not have.
     */
    public static function canAt(?User $user, ?string $facilityId, string ...$capabilities): bool
    {
        if (! $user) {
            return false;
        }

        return self::roleCan(self::roleFor($user, $facilityId), ...$capabilities);
    }

    /**
     * The name of the role a user acts under at a facility — the active
     * facility_role_assignments row there, falling back to the global role
     * (User::roleAtFacility() already implements that fallback, including for
     * a malformed facility id). Null when the user has no role anywhere.
     */
    public static function roleFor(User $user, ?string $facilityId): ?string
    {
        return self::roleModelFor($user, $facilityId)?->name;
    }

    /** Same resolution as roleFor(), returning the Role row (badges, sidebar picker). */
    public static function roleModelFor(User $user, ?string $facilityId): ?Role
    {
        if ($facilityId !== null && $facilityId !== '') {
            return $user->roleAtFacility((string) $facilityId);
        }

        return $user->role;
    }

    /**
     * The facility the current WEB request acts for: the session value that
     * RequireFacilityContext / the facility selector set, else the user's
     * primary facility — the same order EnsurePortalAccess uses, so the portal
     * gate and the capability gate can never disagree about which posting is
     * being judged. Null outside a session (console, API) for a user with no
     * facility at all.
     */
    public static function contextFacilityId(?User $user): ?string
    {
        $fromSession = null;

        try {
            if (app()->bound('session')) {
                $fromSession = app('session')->get('active_facility_id');
            }
        } catch (\Throwable) {
            $fromSession = null; // no session store in this context (API, console)
        }

        $facilityId = $fromSession ?: $user?->primary_facility_id;

        return $facilityId ? (string) $facilityId : null;
    }
}

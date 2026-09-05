<?php

namespace App\Support;

use App\Models\User;

/**
 * What a professional account may open, read from config/staff_capabilities.php.
 *
 * Used by the `capability:` middleware (web and clinician API), the
 * `@capability()` Blade directive (sidebars, dashboards) and the clinician
 * app's mirror of the same map. Never compares roles by hand anywhere else:
 * a new role gets its surfaces by being added to the config, once.
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

    /** Any-of: true when the user's role holds at least one of the capabilities. */
    public static function can(?User $user, string ...$capabilities): bool
    {
        return self::roleCan($user?->role?->name, ...$capabilities);
    }
}

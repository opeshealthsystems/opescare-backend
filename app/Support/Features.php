<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Features — the single source of truth for the V1 launch-scope module freeze.
 *
 * Two questions, one answer:
 *   1. "Is module <key> live?"            -> Features::enabled('insurance')
 *   2. "Is this URI part of a frozen module?" -> Features::featureForRequest($request)
 *
 * Both FAIL CLOSED. An unknown key, a missing config file, a non-boolean value
 * — every one of those reads as frozen. The only way to be enabled is for
 * config('features.flags.<key>') to be exactly true.
 *
 * This is deliberately not EnforceModuleEntitlement. That middleware gates on a
 * subscription entitlement and fails OPEN (no organization -> $next; no active
 * subscription -> $next). It is an upsell gate. This is a kill switch.
 *
 * @see config/features.php
 * @see \App\Http\Middleware\EnforceFeatureFlag
 * @see docs/plans/V1_LAUNCH_SCOPE.md
 */
final class Features
{
    /**
     * URI-pattern freeze map: feature key => Request::is() patterns.
     *
     * Populated once at boot from bootstrap/app.php, which is the one place
     * that declares what is frozen. Kept out of config/ on purpose: this map
     * must stay correct even when config:cache is stale, and it is applied at
     * middleware-registration time, not at request time.
     *
     * @var array<string, list<string>>
     */
    private static array $frozenPaths = [];

    /**
     * Declare the frozen URI surface. Called from bootstrap/app.php.
     *
     * @param  array<string, list<string>>  $map
     */
    public static function freeze(array $map): void
    {
        self::$frozenPaths = $map;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function frozenPaths(): array
    {
        return self::$frozenPaths;
    }

    /**
     * The PLATFORM-level state of a capability.
     *
     * config/features.php supplies the default; the feature_states table
     * overrides it when a platform administrator has changed it through the
     * control centre.
     *
     * FAILS CLOSED at every step. An unknown key is frozen. An unreadable
     * database is frozen — or rather, falls back to the config default, which
     * is itself frozen unless it says otherwise. A database problem must never
     * be able to open a module that is supposed to be shut.
     */
    public static function state(string $key): FeatureState
    {
        $flags = config('features.flags');

        if (! is_array($flags) || ! array_key_exists($key, $flags)) {
            return FeatureState::Frozen;   // unknown key => never granted
        }

        $default = FeatureState::parse($flags[$key]);

        $override = self::storedState($key);

        return $override ?? $default;
    }

    /**
     * The administrator-set state, or null if none / unreadable.
     *
     * Cached per request. Deliberately swallows every storage failure and
     * returns null so the caller falls back to the config default: during a
     * migration, an outage, or a boot before the table exists, the answer must
     * be 'use the default' rather than an exception in the middleware stack.
     */
    private static function storedState(string $key): ?FeatureState
    {
        if (array_key_exists($key, self::$stateCache)) {
            return self::$stateCache[$key];
        }

        $resolved = null;

        try {
            $row = \Illuminate\Support\Facades\DB::table('feature_states')
                ->where('feature_key', $key)
                ->first(['state', 'expires_at', 'expiry_state', 'scheduled_state', 'scheduled_for']);

            if ($row) {
                $resolved = FeatureState::parse($row->state);

                // A scheduled transition whose moment has passed applies even if
                // the sweeper command has not run yet, so the state a user sees
                // never lags the schedule an administrator was shown.
                if ($row->scheduled_state && $row->scheduled_for && now()->gte($row->scheduled_for)) {
                    $resolved = FeatureState::parse($row->scheduled_state);
                }

                // A temporary pilot or timed disable that has run out reverts.
                if ($row->expires_at && now()->gte($row->expires_at)) {
                    $resolved = FeatureState::parse($row->expiry_state);
                }
            }
        } catch (\Throwable $e) {
            $resolved = null;   // fall back to the config default
        }

        return self::$stateCache[$key] = $resolved;
    }

    /** Per-request memo of resolved platform states. */
    private static array $stateCache = [];

    /** Drop the memo — for tests and for the control centre after a write. */
    public static function forgetStateCache(): void
    {
        self::$stateCache = [];
    }

    /**
     * Is this module reachable at the platform level?
     *
     * Unchanged in meaning from the boolean era: LIVE and PILOT are reachable,
     * FROZEN and DISABLED are not. Whether a given ORGANIZATION may use a pilot
     * capability is decided by allowsFor() one level down; this answers only
     * 'does the platform serve this at all', which is what the global
     * EnforceFeatureFlag middleware needs.
     */
    public static function enabled(string $key): bool
    {
        return self::state($key)->grantsAccess();
    }

    /**
     * Inverse of enabled(), for readability at call sites.
     */
    public static function frozen(string $key): bool
    {
        return ! self::enabled($key);
    }

    /**
     * Is this capability available to THIS user, all the way down the chain?
     *
     *     Platform  ->  Country / Jurisdiction  ->  Organization
     *               ->  Facility  ->  User / Role
     *
     * The MOST RESTRICTIVE applicable state wins. A capability is available
     * only where every level agrees; any single level saying no is decisive.
     * That direction is the whole point — a facility must never be able to turn
     * on something the platform has frozen, and enabling something for one
     * organization must never enable it for another.
     *
     * Role permission is deliberately NOT decided here. That is what the portal
     * and route middleware already do, and duplicating it would create a second
     * answer that can drift from the first.
     */
    public static function allowsFor(string $key, mixed $user = null): bool
    {
        $state = self::state($key);

        if (! $state->grantsAccess()) {
            return false;   // platform says no; nothing below can overrule it
        }

        $organizationId = self::organizationIdFor($user);

        // A pilot is reachable only by an organization explicitly enrolled in
        // it. No organization context means no enrolment, so no access.
        if ($state->requiresEnrolment() && ! $organizationId) {
            return false;
        }

        if ($organizationId && ! self::organizationAllows($key, $organizationId, $state)) {
            return false;
        }

        return true;
    }

    /**
     * The organization entitlement layer, which already exists as
     * module_entitlements.
     *
     * Two different questions, depending on platform state:
     *   LIVE  — available unless the organization has been explicitly revoked.
     *   PILOT — available only if the organization has been explicitly granted.
     *
     * That asymmetry is what makes 'pilot' mean something. Storage failures
     * resolve to no-access for a pilot and access for live, matching the
     * respective defaults rather than inventing a third behaviour.
     */
    private static function organizationAllows(string $key, string $organizationId, FeatureState $state): bool
    {
        try {
            $entitlement = \Illuminate\Support\Facades\DB::table('module_entitlements')
                ->where('organization_id', $organizationId)
                ->where('module_key', $key)
                ->whereNull('revoked_at')
                ->first(['is_enabled']);
        } catch (\Throwable $e) {
            return ! $state->requiresEnrolment();
        }

        if ($state->requiresEnrolment()) {
            return (bool) ($entitlement->is_enabled ?? false);
        }

        // Live: an explicit row may still switch it off for this organization.
        return $entitlement === null || (bool) $entitlement->is_enabled;
    }

    /** Best-effort organization id for a user, mirroring EnforceModuleEntitlement. */
    private static function organizationIdFor(mixed $user): ?string
    {
        if (! $user) {
            return null;
        }

        $id = $user->organization_id
            ?? $user->facility?->parent_organization_id
            ?? $user->primary_facility_id
            ?? null;

        return $id ? (string) $id : null;
    }

    /**
     * Which frozen module does this request belong to, if any?
     *
     * Returns null when the URI is not part of any frozen module — the request
     * is then none of this mechanism's business and passes straight through.
     */
    public static function featureForRequest(Request $request): ?string
    {
        foreach (self::$frozenPaths as $key => $patterns) {
            if ($patterns !== [] && $request->is(...$patterns)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Is this PATH switched off by the freeze?
     *
     * For code that holds a url or a path rather than a Request — chiefly the
     * two places that decide where a signed-in user is sent (EnsurePortalAccess
     * and DashboardProfileService). Both must refuse to redirect anyone onto a
     * frozen portal, because EnforceFeatureFlag 404s it and the user lands on a
     * dead page after a perfectly successful login.
     *
     * Accepts a full url or a bare path; only the path is considered.
     */
    public static function pathIsFrozen(string $pathOrUrl): bool
    {
        $path = parse_url($pathOrUrl, PHP_URL_PATH) ?: '/';

        $feature = self::featureForRequest(Request::create($path, 'GET'));

        return $feature !== null && ! self::enabled($feature);
    }
}

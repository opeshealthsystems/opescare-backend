<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\DashboardProfile;
use Illuminate\Support\Facades\Auth;

class DashboardProfileService
{
    public function profileForUser(User $user): ?DashboardProfile
    {
        return $user->role?->dashboardProfile;
    }

    /**
     * Where to send this user after sign-in.
     *
     * Must never return a portal that the V1 launch-scope freeze has switched
     * off. EnforceFeatureFlag 404s those paths — deliberately and
     * byte-identically to a nonexistent route — so returning one here means the
     * user completes a successful login and lands on a dead page. That is
     * exactly what happened to every insurance account.
     */
    public function landingUrlForUser(User $user): string
    {
        // A professional not yet attached to any facility (freshly self-registered,
        // or between postings) cannot enter the facility desk, which requires a
        // facility context — they would bounce to /login. Send them to their own
        // profile, where they complete their details and watch for verification.
        if ($user->isProfessional() && ! $this->hasFacilityContext($user)) {
            return url('/professional/profile');
        }

        $profile = $this->profileForUser($user);

        $url = $profile ? $profile->landingUrl() : url('/portals/patient');

        if (! \App\Support\Features::pathIsFrozen($url) && $this->pathExists($url)) {
            return $url;
        }

        // The configured landing page is frozen, but that does not mean the
        // user has nowhere to go — only that this particular page is gone. The
        // pharmacy dashboards are configured to open on
        // /portals/staff/inventory/pharmacy, which inventory_ops freezes, while
        // their portal itself is perfectly alive. Fall back to the portal root
        // before concluding there is nothing for them.
        $portal = $profile?->portal_prefix;

        if ($portal) {
            $root = url('/portals/' . ltrim($portal, '/'));

            if (! \App\Support\Features::pathIsFrozen($root) && $this->pathExists($root)) {
                return $root;
            }
        }

        // Portal root frozen too — the whole surface is out of this release.
        return route('portal.unavailable');
    }

    /**
     * Whether any GET route answers at this path.
     *
     * The frozen-path check above covers the platform's freeze-flag world; this
     * covers the Finder's, where removed modules are DELETED rather than frozen
     * and so are invisible to the freeze map. Without it, a role whose portal
     * was removed (every insurance account, for instance) is sent from login
     * straight to a 404 -- the exact dead end the portal-unavailable page
     * exists to prevent.
     */
    private function pathExists(string $url): bool
    {
        try {
            app('router')->getRoutes()->match(
                \Illuminate\Http\Request::create($url, 'GET')
            );

            return true;
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException|\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
            return false;
        }
    }

    private function hasFacilityContext(User $user): bool
    {
        if (! empty($user->primary_facility_id)) {
            return true;
        }

        return \App\Models\FacilityRoleAssignment::where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    public function landingUrlForCurrent(): string
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return route('login');
        }

        return $this->landingUrlForUser($user);
    }

    public function portalPrefixForUser(User $user): string
    {
        return $this->profileForUser($user)?->portal_prefix ?? 'patient';
    }
}

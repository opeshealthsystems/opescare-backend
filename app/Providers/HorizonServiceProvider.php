<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        // Platform-tier roles allowed to view Horizon when no email allowlist is set.
        $platformRoles = ['super_admin', 'platform_admin', 'system_admin'];

        Gate::define('viewHorizon', function ($user) use ($platformRoles) {
            if (!$user) {
                return false;
            }

            // Read from config (NOT env()) so the allowlist survives config:cache
            // in production — env() returns null once config is cached.
            $allowedEmails = array_filter(
                array_map('trim', explode(',', (string) config('horizon.admin_emails', ''))),
                fn ($e) => $e !== ''
            );

            if (!empty($allowedEmails)) {
                return in_array(trim((string) $user->email), $allowedEmails, true);
            }

            // Fallback: platform administrators (uses the real role names; User has
            // no hasRole() — it exposes roleName()).
            return in_array($user->roleName(), $platformRoles, true);
        });
    }
}

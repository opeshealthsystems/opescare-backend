<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class CommunicationAuthorizationTest extends TestCase
{
    public function test_all_non_admin_communication_routes_require_authentication(): void
    {
        $prefixes = [
            'api/v1/notifications',
            'api/v1/notification-preferences',
            'api/v1/tasks',
            'api/v1/messages',
            'api/v1/broadcasts',
        ];

        $violations = collect(app('router')->getRoutes())
            ->filter(fn ($route) => collect($prefixes)->contains(
                fn ($prefix) => str_starts_with($route->uri(), $prefix)
            ))
            ->reject(fn ($route) => str_starts_with($route->uri(), 'api/v1/admin'))
            ->reject(function ($route) {
                $middleware = $route->gatherMiddleware();

                return in_array('auth:sanctum', $middleware, true)
                    || in_array('auth.mobile', $middleware, true)
                    || in_array('auth', $middleware, true)
                    || in_array('verify.integration.client', $middleware, true);
            })
            ->map(fn ($route) => implode('|', $route->methods()) . ' ' . $route->uri())
            ->values()
            ->all();

        $this->assertSame([], $violations);
    }
}

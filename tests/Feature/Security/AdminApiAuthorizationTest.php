<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class AdminApiAuthorizationTest extends TestCase
{
    public function test_all_v1_admin_routes_require_api_admin_middleware(): void
    {
        $violations = collect(app('router')->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/admin'))
            ->reject(fn ($route) => in_array('api.admin', $route->gatherMiddleware(), true))
            ->map(fn ($route) => implode('|', $route->methods()) . ' ' . $route->uri())
            ->values()
            ->all();

        $this->assertSame([], $violations);
    }
}

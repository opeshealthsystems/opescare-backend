<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class ModuleEntitlementSecurityTest extends TestCase
{
    public function test_module_entitlement_middleware_uses_is_enabled_column(): void
    {
        $source = file_get_contents(app_path('Http/Middleware/EnforceModuleEntitlement.php'));

        $this->assertStringContainsString("where('is_enabled', true)", $source);
        $this->assertStringNotContainsString("where('enabled', true)", $source);
    }

    public function test_billable_modules_have_module_middleware_attached(): void
    {
        $routes = collect(app('router')->getRoutes());

        $expected = [
            'api/v1/billing' => 'module:billing',
            'api/v1/insurance' => 'module:insurance',
            'api/v1/telemedicine' => 'module:telemedicine',
            'api/v1/analytics' => 'module:analytics',
        ];

        foreach ($expected as $prefix => $middleware) {
            $matching = $routes->filter(fn ($route) => str_starts_with($route->uri(), $prefix));

            $this->assertNotEmpty($matching, "No routes found for {$prefix}");
            $this->assertTrue(
                $matching->every(fn ($route) => in_array($middleware, $route->gatherMiddleware(), true)),
                "{$prefix} must include {$middleware}"
            );
        }
    }
}

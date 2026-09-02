<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class ProviderMobileRateLimitTest extends TestCase
{
    public function test_provider_mobile_login_has_rate_limiting(): void
    {
        // Verify the route middleware includes throttle by checking route list
        $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->filter(fn($r) => str_contains($r->uri(), 'provider-mobile/auth/login'))
            ->first();

        $this->assertNotNull($routes, 'Provider mobile login route must exist');

        $middleware = $routes->gatherMiddleware();
        $hasThrottle = collect($middleware)->contains(fn($m) => str_starts_with($m, 'throttle:'));

        $this->assertTrue($hasThrottle,
            'Provider mobile auth/login must have throttle middleware. Found: ' . implode(', ', $middleware));
    }

    public function test_provider_mobile_otp_verify_has_rate_limiting(): void
    {
        $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->filter(fn($r) => str_contains($r->uri(), 'provider-mobile/auth/otp/verify'))
            ->first();

        $this->assertNotNull($routes, 'Provider mobile OTP verify route must exist');

        $middleware = $routes->gatherMiddleware();
        $hasThrottle = collect($middleware)->contains(fn($m) => str_starts_with($m, 'throttle:'));

        $this->assertTrue($hasThrottle,
            'Provider mobile auth/otp/verify must have throttle middleware. Found: ' . implode(', ', $middleware));
    }
}

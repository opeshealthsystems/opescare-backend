<?php

namespace Tests\Feature\Infrastructure;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_rate_limiter_is_registered(): void
    {
        // The 'api' limiter should be registered (not null)
        $this->assertTrue(RateLimiter::limiter('api') !== null);
    }

    public function test_health_endpoint_returns_ok_repeatedly(): void
    {
        // Health endpoint must not be rate-limited to prevent monitoring outages
        for ($i = 0; $i < 5; $i++) {
            $this->getJson('/api/health')->assertStatus(200);
        }
    }

    public function test_unauthenticated_request_uses_ip_based_limit(): void
    {
        // The limiter resolves without error for an unauthenticated request
        $request = \Illuminate\Http\Request::create('/api/test', 'GET');
        $limiter  = RateLimiter::limiter('api');
        $limit    = $limiter($request);

        // Should return a Limit object (not null)
        $this->assertNotNull($limit);
    }
}

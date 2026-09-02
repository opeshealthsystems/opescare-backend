<?php

namespace Tests\Feature\Infrastructure;

use App\Services\Infrastructure\RegionHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DatabaseHealthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_healthy_database_allows_request_through(): void
    {
        $health = Mockery::mock(RegionHealthService::class);
        $health->shouldReceive('isDatabaseHealthy')->once()->andReturn(true);
        $this->app->instance(RegionHealthService::class, $health);

        $this->getJson('/api/health')->assertStatus(200);
    }

    public function test_unhealthy_database_returns_503(): void
    {
        $health = Mockery::mock(RegionHealthService::class);
        $health->shouldReceive('isDatabaseHealthy')->once()->andReturn(false);
        $health->shouldReceive('alertFailover')->once()->with('database');
        $this->app->instance(RegionHealthService::class, $health);

        $response = $this->getJson('/api/health');

        $response->assertStatus(503)
            ->assertJson([
                'error'       => 'Service temporarily unavailable',
                'retry_after' => 30,
            ]);
    }

    public function test_region_health_service_returns_status_shape(): void
    {
        $service = app(RegionHealthService::class);
        $status  = $service->getHealthStatus();

        $this->assertArrayHasKey('database', $status);
        $this->assertArrayHasKey('redis', $status);
        $this->assertArrayHasKey('region', $status);
        $this->assertArrayHasKey('timestamp', $status);
    }
}

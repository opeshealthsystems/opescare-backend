<?php
namespace Tests\Feature\Infrastructure;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_returns_200(): void
    {
        $response = $this->getJson('/api/health');
        $response->assertStatus(200);
    }

    public function test_health_check_returns_required_structure(): void
    {
        $response = $this->getJson('/api/health');
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => [
                'database',
                'cache',
            ],
        ]);
    }

    public function test_health_check_database_is_ok(): void
    {
        $response = $this->getJson('/api/health');
        $response->assertJson(['checks' => ['database' => 'ok']]);
    }

    public function test_health_check_does_not_require_authentication(): void
    {
        // Call without any auth token
        $response = $this->getJson('/api/health');
        $response->assertStatus(200);
    }

    public function test_health_check_status_field_is_ok(): void
    {
        $response = $this->getJson('/api/health');
        $response->assertJson(['status' => 'ok']);
    }
}

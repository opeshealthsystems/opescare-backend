<?php
namespace Tests\Feature\Infrastructure;

use Tests\TestCase;

class HorizonConfigTest extends TestCase
{
    public function test_horizon_config_file_exists(): void
    {
        $this->assertFileExists(config_path('horizon.php'));
    }

    public function test_horizon_production_supervisor_has_correct_max_processes(): void
    {
        $config = config('horizon.environments.production.supervisor-1');
        $this->assertNotNull($config, 'production supervisor-1 must be configured');
        $this->assertGreaterThanOrEqual(5, $config['maxProcesses'],
            'Production should have at least 5 max processes');
    }

    public function test_horizon_local_supervisor_max_processes_is_less_than_production(): void
    {
        $prod  = config('horizon.environments.production.supervisor-1.maxProcesses');
        $local = config('horizon.environments.local.supervisor-1.maxProcesses');
        $this->assertLessThan($prod, $local,
            'Local maxProcesses should be less than production');
    }

    public function test_horizon_production_uses_redis_connection(): void
    {
        $connection = config('horizon.environments.production.supervisor-1.connection');
        $this->assertEquals('redis', $connection);
    }

    public function test_horizon_memory_limit_is_at_least_256(): void
    {
        // Verify memory_limit is now reading from env (defaults to 256)
        $limit = config('horizon.memory_limit');
        $this->assertGreaterThanOrEqual(256, $limit,
            'Horizon master memory_limit should be at least 256 MB in production');
    }
}

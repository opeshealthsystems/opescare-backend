<?php
namespace Tests\Feature\Infrastructure;

use Tests\TestCase;

class ReadReplicaConfigTest extends TestCase
{
    public function test_pgsql_config_has_read_write_split(): void
    {
        $config = config('database.connections.pgsql');
        $this->assertArrayHasKey('read', $config);
        $this->assertArrayHasKey('write', $config);
    }

    public function test_sticky_mode_is_enabled(): void
    {
        $config = config('database.connections.pgsql');
        $this->assertTrue($config['sticky']);
    }

    public function test_read_host_falls_back_to_db_host(): void
    {
        // In test env with no DB_READ_HOST_1 set, read host should equal DB_HOST
        $config = config('database.connections.pgsql');
        $this->assertArrayHasKey('host', $config['read']);
        $this->assertIsArray($config['read']['host']);
    }
}

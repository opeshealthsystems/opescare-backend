<?php

namespace Tests\Feature\Infrastructure;

use Tests\TestCase;

class BackupConfigTest extends TestCase
{
    public function test_backup_config_file_exists(): void
    {
        $this->assertFileExists(config_path('backup.php'));
    }

    public function test_backup_config_has_pgsql_source(): void
    {
        $databases = config('backup.backup.source.databases');
        $this->assertIsArray($databases);
        $this->assertContains('pgsql', $databases);
    }

    public function test_backup_config_has_s3_destination(): void
    {
        $disks = config('backup.backup.destination.disks');
        $this->assertIsArray($disks);
        $this->assertContains('s3', $disks);
    }

    public function test_backup_config_has_encryption_password_key(): void
    {
        // Password key should exist (value may be null in test env)
        $config = config('backup.backup');
        $this->assertArrayHasKey('password', $config);
    }

    public function test_backup_cleanup_strategy_is_configured(): void
    {
        $strategy = config('backup.cleanup.defaultStrategy');
        $this->assertArrayHasKey('keepAllBackupsForDays', $strategy);
        $this->assertGreaterThanOrEqual(7, $strategy['keepAllBackupsForDays']);
    }
}

<?php
namespace Tests\Feature\Config;

use Tests\TestCase;

class ProductionConfigTest extends TestCase
{
    public function test_queue_is_not_sync_in_production(): void
    {
        if (app()->isProduction()) {
            $this->assertNotEquals('sync', config('queue.default'),
                'QUEUE_CONNECTION=sync in production blocks request processing');
        } else {
            $this->assertTrue(true); // Skip in non-production
        }
    }

    public function test_cache_is_not_file_in_production(): void
    {
        if (app()->isProduction()) {
            $this->assertNotEquals('file', config('cache.default'),
                'CACHE_STORE=file in production does not support rate limiting across servers');
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_opescare_system_provider_id_is_configured(): void
    {
        $providerId = config('opescare.system_provider_id');
        $this->assertNotEmpty($providerId, 'OPESCARE_SYSTEM_PROVIDER_ID must be set');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $providerId,
            'OPESCARE_SYSTEM_PROVIDER_ID must be a valid UUID'
        );
    }
}

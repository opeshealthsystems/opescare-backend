<?php
namespace Tests\Feature\Config;

use Tests\TestCase;

class ProductionSafetyProviderTest extends TestCase
{
    public function test_production_safety_provider_class_exists(): void
    {
        $this->assertTrue(
            class_exists(\App\Providers\ProductionSafetyServiceProvider::class),
            'ProductionSafetyServiceProvider must exist'
        );
    }

    public function test_production_safety_provider_is_registered(): void
    {
        $providers = require base_path('bootstrap/providers.php');
        $this->assertContains(
            \App\Providers\ProductionSafetyServiceProvider::class,
            $providers,
            'ProductionSafetyServiceProvider must be registered in bootstrap/providers.php'
        );
    }

    public function test_production_safety_provider_boots_without_error_in_test_env(): void
    {
        // In test environment (non-production), boot() should do nothing (early return)
        $provider = new \App\Providers\ProductionSafetyServiceProvider(app());
        $provider->boot(); // Should not throw
        $this->assertTrue(true, 'Provider boot() must not throw in non-production environment');
    }
}

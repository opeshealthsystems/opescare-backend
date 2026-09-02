<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class ProductionConfigurationTest extends TestCase
{
    public function test_cors_origins_are_environment_configured(): void
    {
        $source = file_get_contents(config_path('cors.php'));

        $this->assertStringContainsString('CORS_ALLOWED_ORIGINS', $source);
        $this->assertStringNotContainsString("'allowed_origins' => ['*']", $source);
    }

    public function test_production_safety_provider_throws_for_debug_and_demo_mode(): void
    {
        $source = file_get_contents(app_path('Providers/ProductionSafetyServiceProvider.php'));

        $this->assertStringContainsString('throw new', $source);
        $this->assertStringContainsString('APP_DEBUG', $source);
        $this->assertStringContainsString('OPESCARE_DEMO_MODE', $source);
    }
}

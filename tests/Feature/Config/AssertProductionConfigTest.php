<?php

namespace Tests\Feature\Config;

use Tests\TestCase;

class AssertProductionConfigTest extends TestCase
{
    private function setGoodConfig(): void
    {
        config([
            'app.key'        => 'base64:'.base64_encode(str_repeat('a', 32)),
            'app.debug'      => false,
            'cache.default'  => 'redis',
            'queue.default'  => 'redis',
            'session.driver' => 'redis',
            'mail.default'   => 'smtp',
            'services.mtn_momo.subscription_key' => '',
        ]);
    }

    public function test_passes_when_required_config_present(): void
    {
        $this->setGoodConfig();
        $this->artisan('config:assert-production')->assertExitCode(0);
    }

    public function test_fails_when_debug_on(): void
    {
        $this->setGoodConfig();
        config(['app.debug' => true]);
        $this->artisan('config:assert-production')->assertExitCode(1);
    }

    public function test_fails_when_cache_not_redis(): void
    {
        $this->setGoodConfig();
        config(['cache.default' => 'database']);
        $this->artisan('config:assert-production')->assertExitCode(1);
    }

    public function test_fails_when_momo_partially_configured(): void
    {
        $this->setGoodConfig();
        config([
            'services.mtn_momo.subscription_key' => 'present',
            'services.mtn_momo.api_key' => '',
        ]);
        $this->artisan('config:assert-production')->assertExitCode(1);
    }
}

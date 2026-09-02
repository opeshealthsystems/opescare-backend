<?php
namespace Tests\Feature\Infrastructure;

use Tests\TestCase;

class LoggingConfigTest extends TestCase
{
    public function test_datadog_channel_is_configured(): void
    {
        $channels = config('logging.channels');
        $this->assertArrayHasKey('datadog', $channels);
    }

    public function test_papertrail_channel_is_configured(): void
    {
        $channels = config('logging.channels');
        $this->assertArrayHasKey('papertrail', $channels);
    }

    public function test_production_stack_channel_exists(): void
    {
        $channels = config('logging.channels');
        $this->assertArrayHasKey('production_stack', $channels);
    }

    public function test_production_stack_includes_daily_channel(): void
    {
        $stack = config('logging.channels.production_stack.channels');
        $this->assertContains('daily', $stack);
    }
}

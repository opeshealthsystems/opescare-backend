<?php

namespace Tests\Feature\Mobile;

use Tests\TestCase;

class AppConfigTest extends TestCase
{
    public function test_app_config_returns_version_gate(): void
    {
        config()->set('mobile.min_supported_build', 7);
        config()->set('mobile.latest_version', '1.2.0');
        config()->set('mobile.store_url', 'https://play.google.com/store/apps/details?id=cm.opescare.patient');

        $res = $this->getJson('/api/mobile/app-config');

        $res->assertOk()
            ->assertExactJson([
                'min_supported_build' => 7,
                'latest_version'      => '1.2.0',
                'store_url'           => 'https://play.google.com/store/apps/details?id=cm.opescare.patient',
            ]);
    }

    public function test_app_config_is_public(): void
    {
        // No auth header — must still succeed (it gates the app before login).
        $this->getJson('/api/mobile/app-config')->assertOk();
    }
}

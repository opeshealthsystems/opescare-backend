<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Production (opescare.com) has no demo accounts, so the sign-in and
 * registration pages must never offer demo sign-in or demo credentials there.
 * The demo panel renders only with OPESCARE_DEMO_MODE=true on a non-production host.
 */
class LoginPageHasNoDemoTest extends TestCase
{
    use RefreshDatabase;

    private function assertNoDemo(string $html): void
    {
        $this->assertStringNotContainsString('id="demoPanel"', $html);
        $this->assertStringNotContainsString('demoLoginForm', $html);
        $this->assertStringNotContainsString('@opescare.test', $html);
        $this->assertStringNotContainsString('demo_secret_sandbox', $html);
    }

    public function test_default_login_page_has_no_demo_panel(): void
    {
        Config::set('demo.enabled', false);

        $this->assertNoDemo($this->get('/login')->assertOk()->getContent());
    }

    public function test_production_never_renders_the_demo_panel_even_if_demo_mode_is_on(): void
    {
        Config::set('demo.enabled', true);
        $this->app->detectEnvironment(fn () => 'production');

        try {
            // Production forces HTTPS; ask over https so the page itself renders.
            $html = $this->get('https://localhost/login')->assertOk()->getContent();
        } finally {
            $this->app->detectEnvironment(fn () => 'testing');
        }

        $this->assertNoDemo($html);
    }

    public function test_register_pages_have_no_demo_credentials(): void
    {
        Config::set('demo.enabled', true);

        foreach (['/signup', '/signup/patient', '/signup/guardian', '/signup/organization'] as $uri) {
            $this->assertNoDemo($this->get($uri)->assertOk()->getContent());
        }
    }
}

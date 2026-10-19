<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Regressions found by the 2026-10-19 web audit (docs/WEB_AUDIT.md).
 */
class WebAuditRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('demo.enabled', true);
    }

    public function test_login_demo_panel_is_translated_in_french(): void
    {
        $html = $this->withSession(['locale' => 'fr'])->get('/login')->assertOk()->getContent();

        // Demo mode on a non-production host is the only place the panel renders.
        $this->assertStringContainsString('id="demoPanel"', $html);

        $this->assertStringContainsString(__('public.demo.tab_staff', [], 'fr'), $html);
        $this->assertStringContainsString(e(__('public.demo.sandbox_notice', [], 'fr')), $html);
        $this->assertStringNotContainsString('Sandbox environment — demo data only', $html);
    }

    public function test_no_view_ships_an_image_with_an_empty_src(): void
    {
        // An <img src=""> resolves to the current page URL and shows up as a
        // broken image (patient dashboard temp-QR placeholder, 2026-10-19).
        $offenders = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        foreach ($it as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')
                && preg_match('/<img\b[^>]*\ssrc=""/i', file_get_contents($file->getPathname()))) {
                $offenders[] = $file->getPathname();
            }
        }
        $this->assertSame([], $offenders);
    }

    public function test_internal_demo_page_hides_link_to_disabled_public_demo(): void
    {
        Config::set('demo.internal_enabled', true);
        Config::set('demo.public_enabled', false);

        $html = $this->get('/demo-access/internal')->assertOk()->getContent();

        $this->assertStringNotContainsString(route('demo.public'), $html);
    }

    public function test_internal_demo_page_links_public_demo_when_enabled(): void
    {
        Config::set('demo.internal_enabled', true);
        Config::set('demo.public_enabled', true);

        $html = $this->get('/demo-access/internal')->assertOk()->getContent();

        $this->assertStringContainsString(route('demo.public'), $html);
    }
}

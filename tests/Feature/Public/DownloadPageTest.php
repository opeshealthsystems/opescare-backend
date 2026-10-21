<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * /download (public.download) — the Android app page. Facts come from
 * config('mobile.apk'); the file itself is served by nginx on prod.
 */
class DownloadPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['blog.path' => base_path('tests/Feature/Blog/fixtures/blog')]);
        Cache::flush();
    }

    public function test_renders_in_both_languages_with_the_apk_link(): void
    {
        foreach (['en', 'fr'] as $locale) {
            $html = $this->get('/download?lang=' . $locale)->assertOk()->getContent();

            $this->assertStringContainsString(e(__('public_download.hero.cta_download', [], $locale)), $html);
            $this->assertStringNotContainsString('public_download.', strip_tags($html), "untranslated key ({$locale})");
            $this->assertMatchesRegularExpression('#href="[^"]*/downloads/opescare\.apk"\s+download="opescare\.apk"#', $html);
            $this->assertStringContainsString(config('mobile.apk.sha256'), $html);
            $this->assertStringContainsString(route('mobile.app'), $html);
            $this->assertStringContainsString('js/download-page.js', $html);
            $this->assertStringContainsString('<svg', $html, 'QR code');
            $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=\s*"/i', $html, 'inline event handler');
        }
    }

    public function test_inside_the_app_walkthroughs_render_every_flow_in_both_languages(): void
    {
        foreach (['en', 'fr'] as $locale) {
            $html = $this->get('/download?lang=' . $locale)->assertOk()->getContent();

            $this->assertStringContainsString('data-dl-flows', $html);
            $this->assertStringContainsString(e(__('public_download.flows.title', [], $locale)), $html);
            $this->assertStringContainsString(e(__('public_download.flows.sample', [], $locale)), $html);
            foreach (['consent', 'access', 'rx', 'results', 'hid', 'appts', 'family'] as $flow) {
                $this->assertStringContainsString('id="dl-flow-' . $flow . '"', $html);
                $this->assertStringContainsString(e(__('public_download.flows.' . $flow . '.label', [], $locale)), $html);
            }
            $this->assertStringContainsString('CM-HID 0000 0000 0000', $html);
            $this->assertStringContainsString('images/health-id/living/adult-female-base.png', $html);
            $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=\s*"/i', $html, 'inline event handler');
        }
    }

    public function test_software_application_and_breadcrumb_json_ld(): void
    {
        $html = $this->get('/download')->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">\s*(.*?)\s*</script>#s', $html, $m);
        $docs = collect($m[1])->map(fn ($j) => json_decode($j, true))->filter();
        $app = $docs->firstWhere('@type', 'SoftwareApplication');

        $this->assertNotNull($app);
        $this->assertSame('Android', $app['operatingSystem']);
        $this->assertSame('HealthApplication', $app['applicationCategory']);
        $this->assertSame(config('mobile.apk.version'), $app['softwareVersion']);
        $this->assertStringEndsWith('/downloads/opescare.apk', $app['downloadUrl']);
        $this->assertSame('XAF', $app['offers']['priceCurrency']);
        $this->assertContains('BreadcrumbList', $docs->pluck('@type')->all());
    }

    public function test_sitemaps_and_shared_links_include_the_page(): void
    {
        $this->assertStringContainsString(url('/download'), $this->get('/sitemap-pages.xml')->assertOk()->getContent());
        $this->assertStringContainsString(url('/download'), $this->get('/sitemap')->assertOk()->getContent());
        $this->assertStringContainsString(route('public.download'), $this->get('/')->assertOk()->getContent());
        $this->assertStringContainsString(route('public.download'), $this->get('/login')->assertOk()->getContent());
    }

    public function test_no_view_references_the_old_apk_name(): void
    {
        foreach (File::allFiles(resource_path('views')) as $file) {
            $this->assertStringNotContainsString('opescare-finder.apk', $file->getContents(), $file->getRelativePathname());
        }
    }
}

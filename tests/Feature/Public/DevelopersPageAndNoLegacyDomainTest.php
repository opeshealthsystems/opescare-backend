<?php

namespace Tests\Feature\Public;

use App\Services\Fhir\FhirIngestGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * opescare.cloud only 301s back to opescare.com since 2026-10-18, so no public
 * page may send anyone there. /developers is a real developer page on
 * opescare.com, and the legacy marketing routes point at opescare.com pages.
 */
class DevelopersPageAndNoLegacyDomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public static function publicPaths(): array
    {
        return array_map(fn ($p) => [$p], [
            '/developers', '/developers/pricing', '/security', '/sla', '/request-demo',
            '/solutions/hospitals', '/solutions/laboratories', '/solutions/insurers',
            '/solutions/public-health', '/solutions/pharmacies',
        ]);
    }

    #[DataProvider('publicPaths')]
    public function test_public_page_never_outputs_the_legacy_domain(string $path): void
    {
        foreach (['en', 'fr'] as $locale) {
            $html = $this->get($path . '?lang=' . $locale)->assertOk()->getContent();
            $this->assertStringNotContainsString('opescare.cloud', $html, "{$path} ({$locale})");
        }
    }

    public function test_developers_page_renders_in_both_languages_with_access_path(): void
    {
        foreach (['en', 'fr'] as $locale) {
            $html = $this->get('/developers?lang=' . $locale)->assertOk()->getContent();

            $this->assertStringContainsString(e(__('public_developers.hero.title_2', [], $locale)), $html);
            $this->assertStringContainsString(e(__('public_developers.fhir.title', [], $locale)), $html);
            $this->assertStringNotContainsString('public_developers.', strip_tags($html), "untranslated key ({$locale})");
            $this->assertStringContainsString(route('register.developer'), $html);
            $this->assertStringContainsString(route('docs.index'), $html);
            $this->assertStringContainsString(route('portals.developer.dashboard'), $html);
            $this->assertStringContainsString('https://opescare.com/fhir/health-id', $html);
            $this->assertStringContainsString('CM-HID-XXXX-XXXX-XXXX', $html);
            $this->assertStringContainsString('system/Observation.write', $html);
            $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=\s*"/i', $html, 'inline event handler');
        }
    }

    public function test_developers_page_lists_the_real_public_api_plans(): void
    {
        $html = $this->get('/developers?lang=en')->assertOk()->getContent();

        foreach (\App\Models\ApiPlan::public() as $plan) {
            $this->assertStringContainsString(e($plan->name), $html);
        }
    }

    public function test_fhir_ingest_accepts_the_opescare_com_health_id_system(): void
    {
        // The system the developer page and the quickstart publish must be one
        // the ingest guard accepts (end-to-end acceptance: FhirIngestTest).
        $this->assertContains('https://opescare.com/fhir/health-id', FhirIngestGuard::HEALTH_ID_SYSTEMS);
    }
}

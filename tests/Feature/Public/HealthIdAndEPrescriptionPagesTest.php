<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * /e-prescription and /health-id — the patient-facing explainer pages.
 *
 * Same contract as ExplainerPagesTest: EN + FR, FAQPage + BreadcrumbList
 * JSON-LD from the visible FAQ, both sitemaps, linked from the shared
 * header/footers, and no claim the code does not support (no HL7, no
 * outbound FHIR server).
 */
class HealthIdAndEPrescriptionPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['blog.path' => base_path('tests/Feature/Blog/fixtures/blog')]);
        Cache::flush();
    }

    public static function pages(): array
    {
        return [
            'e-prescription' => ['/e-prescription', 'public_erx'],
            'health-id' => ['/health-id', 'public_hid'],
        ];
    }

    #[DataProvider('pages')]
    public function test_renders_in_both_languages_with_its_own_content(string $path, string $ns): void
    {
        foreach (['en', 'fr'] as $locale) {
            $html = $this->get($path . '?lang=' . $locale)->assertOk()->getContent();

            $this->assertStringContainsString(e(__($ns . '.hero.title_2', [], $locale)), $html);
            $this->assertStringContainsString(e(__($ns . '.flow.title', [], $locale)), $html);
            $this->assertStringNotContainsString($ns . '.', strip_tags($html), "untranslated key on {$path} ({$locale})");
            $this->assertStringContainsString(route('register.patient'), $html);
            $this->assertStringContainsString(route('public.download'), $html);
            $this->assertStringContainsString('js/explainer-flow.js', $html);
            $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=\s*"/i', $html, 'inline event handler');
        }
    }

    public function test_e_prescription_has_both_animated_strips(): void
    {
        $html = $this->get('/e-prescription')->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'data-ex-flow'));
        $this->assertStringContainsString(e(__('public_erx.counter.title', [], 'en')), $html);
        $this->assertStringContainsString('/verify/rx', $html);
    }

    public function test_health_id_links_to_mpi_and_interoperability(): void
    {
        $this->get('/health-id')->assertOk()
            ->assertSee(route('public.master-patient-index'), false)
            ->assertSee(route('public.interoperability'), false)
            ->assertSee(route('register.guardian'), false)
            ->assertSee('CM-HID-XXXX-XXXX-XXXX');
    }

    #[DataProvider('pages')]
    public function test_faq_and_breadcrumb_json_ld(string $path, string $ns): void
    {
        $html = $this->get($path)->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">\s*(.*?)\s*</script>#s', $html, $m);
        $decoded = collect($m[1])->map(fn ($j) => json_decode($j, true))->filter();

        $this->assertContains('FAQPage', $decoded->pluck('@type')->all());
        $this->assertContains('BreadcrumbList', $decoded->pluck('@type')->all());

        $faq = $decoded->firstWhere('@type', 'FAQPage');
        $this->assertCount(count(__($ns . '.faq.categories.0.items', [], 'en')), $faq['mainEntity']);
        $this->assertGreaterThanOrEqual(4, count($faq['mainEntity']));

        $this->assertStringContainsString('<link rel="canonical" href="' . url($path) . '">', $html);
    }

    #[DataProvider('pages')]
    public function test_makes_no_claim_the_code_does_not_support(string $path, string $ns): void
    {
        foreach (['en', 'fr'] as $locale) {
            $text = strip_tags($this->get($path . '?lang=' . $locale)->assertOk()->getContent());

            $this->assertStringNotContainsStringIgnoringCase('HL7', $text);
            $this->assertStringNotContainsStringIgnoringCase('outbound', $text);
            $this->assertStringNotContainsStringIgnoringCase('sortant', $text);
        }
    }

    public function test_both_pages_are_in_the_xml_and_human_sitemaps(): void
    {
        $xml = $this->get('/sitemap-pages.xml')->assertOk()->getContent();
        $this->assertStringContainsString('<loc>' . url('/e-prescription') . '</loc>', $xml);
        $this->assertStringContainsString('<loc>' . url('/health-id') . '</loc>', $xml);

        $this->get('/sitemap')->assertOk()
            ->assertSee(url('/e-prescription'), false)
            ->assertSee(url('/health-id'), false)
            ->assertSee(__('sitemap.pages.health_id', [], 'en'))
            ->assertSee(__('sitemap.pages.e_prescription', [], 'en'));
    }

    public function test_linked_from_home_header_and_shared_footers(): void
    {
        // Home: the header "Health ID" link now points at /health-id; footer lists both.
        $this->get('/?lang=en')->assertOk()
            ->assertSee(route('public.health-id'), false)
            ->assertSee(route('public.e-prescription'), false);

        // The shared layouts.finder footer (any page on that layout, e.g. /about).
        $this->get('/about')->assertOk()
            ->assertSee(route('public.health-id'), false)
            ->assertSee(route('public.e-prescription'), false);

        // Each explainer reaches the other through the shared header/footer.
        $this->get('/interoperability')->assertOk()
            ->assertSee(route('public.health-id'), false)
            ->assertSee(route('public.e-prescription'), false);
    }
}

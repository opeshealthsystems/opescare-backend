<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * /interoperability and /master-patient-index — the two explainer pages.
 *
 * Both render in EN and FR, carry FAQPage + BreadcrumbList JSON-LD built from
 * the visible FAQ, are in the XML and human sitemaps, are linked from the home
 * page and the shared footers, and never claim capabilities the code does not
 * have (no HL7, no outbound FHIR server, no automatic merging).
 */
class ExplainerPagesTest extends TestCase
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
            'interoperability' => ['/interoperability', 'public_interop'],
            'mpi' => ['/master-patient-index', 'public_mpi'],
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
            $this->assertStringContainsString(route('register.organization'), $html);
            $this->assertStringContainsString(route('public.solutions.index'), $html);
            $this->assertStringContainsString('js/explainer-flow.js', $html);
            $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=\s*"/i', $html, 'inline event handler');
        }
    }

    #[DataProvider('pages')]
    public function test_faq_and_breadcrumb_json_ld(string $path, string $ns): void
    {
        $html = $this->get($path)->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">\s*(.*?)\s*</script>#s', $html, $m);
        $types = collect($m[1])->map(fn ($j) => json_decode($j, true))->filter()->pluck('@type')->all();

        $this->assertContains('FAQPage', $types);
        $this->assertContains('BreadcrumbList', $types);

        $faq = collect($m[1])->map(fn ($j) => json_decode($j, true))->firstWhere('@type', 'FAQPage');
        $this->assertCount(count(__($ns . '.faq.categories.0.items', [], 'en')), $faq['mainEntity']);
        $this->assertGreaterThanOrEqual(4, count($faq['mainEntity']));

        $this->assertStringContainsString('<link rel="canonical" href="' . url($path) . '">', $html);
        $this->assertStringContainsString('hreflang="fr" href="' . url($path) . '?lang=fr"', $html);
    }

    #[DataProvider('pages')]
    public function test_makes_no_claim_the_code_does_not_support(string $path, string $ns): void
    {
        foreach (['en', 'fr'] as $locale) {
            $text = strip_tags($this->get($path . '?lang=' . $locale)->assertOk()->getContent());

            $this->assertStringNotContainsStringIgnoringCase('HL7', $text);
            $this->assertStringNotContainsStringIgnoringCase('outbound', $text);
            $this->assertDoesNotMatchRegularExpression('/automatic(ally)? merg|fusion(ne)? automatique/i', str_replace(['Does OpesCare merge records automatically?', 'OpesCare fusionne-t-il les dossiers automatiquement ?'], '', html_entity_decode($text)));
        }
    }

    public function test_both_pages_are_in_the_xml_and_human_sitemaps(): void
    {
        $xml = $this->get('/sitemap-pages.xml')->assertOk()->getContent();
        $this->assertStringContainsString('<loc>' . url('/interoperability') . '</loc>', $xml);
        $this->assertStringContainsString('<loc>' . url('/master-patient-index') . '</loc>', $xml);

        $this->get('/sitemap')->assertOk()
            ->assertSee(url('/interoperability'), false)
            ->assertSee(url('/master-patient-index'), false)
            ->assertSee(__('sitemap.pages.master_patient_index', [], 'en'));
    }

    public function test_linked_from_home_page_and_shared_footer(): void
    {
        $home = $this->get('/?lang=en')->assertOk();
        $home->assertSee(route('public.interoperability'), false)
            ->assertSee(route('public.master-patient-index'), false);

        // The shared layouts.finder footer (any page on that layout, e.g. /about).
        $this->get('/about')->assertOk()
            ->assertSee(route('public.interoperability'), false)
            ->assertSee(route('public.master-patient-index'), false);

        // Each explainer links to the other through the shared header/footer.
        $this->get('/interoperability')->assertOk()->assertSee(route('public.master-patient-index'), false);
        $this->get('/master-patient-index')->assertOk()->assertSee(route('public.interoperability'), false);
    }
}

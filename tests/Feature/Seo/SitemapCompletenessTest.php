<?php

namespace Tests\Feature\Seo;

use App\Models\CareFacility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The site map is complete: /sitemap.xml indexes pages, blog, docs and
 * facility chunks; every child carries hreflang alternates; hidden listings,
 * auth pages, embeds and file endpoints stay out; and the human /sitemap
 * page lists the same sections in both languages.
 */
class SitemapCompletenessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['blog.path' => base_path('tests/Feature/Blog/fixtures/blog')]);
        Cache::flush();
    }

    private function facility(string $name, string $status): CareFacility
    {
        return CareFacility::create([
            'facility_name'       => $name,
            'facility_type'       => 'pharmacy',
            'listing_status'      => $status,
            'verification_status' => 'unverified',
            'city'                => 'Douala',
            'region'              => 'Littoral',
            'address'             => '1 Rue de la Paix',
            'latitude'            => 4.05,
            'longitude'           => 9.7,
            'phone_primary'       => '+237650000000',
        ]);
    }

    private function xml(string $path): \SimpleXMLElement
    {
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($this->get($path)->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent());
        $this->assertNotFalse($doc, "{$path} is not valid XML");

        return $doc;
    }

    private function locs(\SimpleXMLElement $doc, string $child = 'url'): array
    {
        return array_map(fn ($n) => (string) $n->loc, iterator_to_array($doc->{$child}, false));
    }

    public function test_index_lists_every_child_sitemap(): void
    {
        $this->facility('Visible', 'active');

        $index = $this->xml('/sitemap.xml');
        $this->assertSame('sitemapindex', $index->getName());

        $locs = $this->locs($index, 'sitemap');
        foreach (['/sitemap-pages.xml', '/sitemap-blog.xml', '/sitemap-docs.xml', '/sitemap-facilities-1.xml'] as $child) {
            $this->assertContains(url($child), $locs);
            $this->xml($child);   // each child resolves to valid XML
        }
    }

    public function test_static_pages_are_listed_with_alternates_and_private_pages_are_not(): void
    {
        $doc  = $this->xml('/sitemap-pages.xml');
        $locs = $this->locs($doc);

        foreach (['/', '/network/medicine-finder', '/care-map', '/privacy', '/terms', '/about', '/sitemap'] as $p) {
            $this->assertContains(rtrim(url($p), '/') ?: url('/'), array_map(fn ($l) => rtrim($l, '/'), $locs), "{$p} missing");
        }
        $this->assertContains(url('/faq') . '?lang=fr', $locs);

        foreach ($locs as $loc) {
            $path = parse_url($loc, PHP_URL_PATH) ?: '/';
            $this->assertDoesNotMatchRegularExpression('#^/(login|signup|register|portals|admin|api|embed|docs|verify)(/|$)#', $path, "{$loc} must not be indexed");
            $this->assertStringNotContainsString('.xml', $path);
            $this->assertStringNotContainsString('.json', $path);
        }

        $faq = collect(iterator_to_array($doc->url, false))->first(fn ($u) => (string) $u->loc === url('/faq'));
        $alts = [];
        foreach ($faq->children('http://www.w3.org/1999/xhtml') as $link) {
            $a = $link->attributes(); $alts[(string) $a['hreflang']] = (string) $a['href'];
        }
        $this->assertSame(['en' => url('/faq'), 'fr' => url('/faq') . '?lang=fr', 'x-default' => url('/faq')], $alts);
    }

    public function test_docs_sitemap_lists_docs_pages_and_flows(): void
    {
        $locs = $this->locs($this->xml('/sitemap-docs.xml'));

        $this->assertContains(url('/docs'), $locs);
        $this->assertContains(url('/docs/api') . '?lang=fr', $locs);
        foreach (\App\Support\Docs\DocsDiagrams::FLOWS as $flow) {
            $this->assertContains(url('/docs/flows/' . $flow), $locs);
        }
        $this->assertNotContains(url('/docs/flows.json'), $locs);
    }

    public function test_published_facility_is_listed_and_hidden_one_is_not(): void
    {
        $shown  = $this->facility('Pharmacie Visible', 'active');
        $hidden = $this->facility('Pharmacie Hidden', 'suspended');

        $doc  = $this->xml('/sitemap-facilities-1.xml');
        $locs = $this->locs($doc);

        $this->assertContains(route('public.care-map.profile', $shown->id), $locs);
        $this->assertNotContains(route('public.care-map.profile', $hidden->id), $locs);
        $this->assertNotEmpty((string) $doc->url[0]->lastmod);
        $this->assertStringContainsString('hreflang="fr"', $this->get('/sitemap-facilities-1.xml')->getContent());
    }

    public function test_blog_post_is_listed(): void
    {
        $locs = $this->locs($this->xml('/sitemap-blog.xml'));
        $this->assertContains(url('/blog/find-medicine-test'), $locs);
        $this->assertContains(url('/blog/trouver-medicament-test') . '?lang=fr', $locs);   // French slug

        config(['blog.authors' => ['opes-health-systems' => ['type' => 'Organization']]]);
        $authorLocs = $this->locs($this->xml('/sitemap-blog.xml'));
        $this->assertContains(route('blog.author', 'opes-health-systems'), $authorLocs);
    }

    public function test_robots_points_at_the_index(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: ' . url('/sitemap.xml'), false);
    }

    public function test_human_sitemap_lists_sections_in_both_languages(): void
    {
        $this->facility('Visible', 'active');

        $this->get('/sitemap')->assertOk()
            ->assertSee(__('sitemap.sections.services', [], 'en'))
            ->assertSee(__('sitemap.sections.docs', [], 'en'))
            ->assertSee(url('/docs/api'), false)
            ->assertSee(url('/blog/find-medicine-test'), false);

        $this->get('/sitemap?lang=fr')->assertOk()
            ->assertSee('Plan du site')
            ->assertSee(url('/privacy') . '?lang=fr', false);

        $this->get('/?lang=en')->assertOk()->assertSee(route('public.sitemap'), false);
    }

    public function test_inactive_listing_profiles_are_kept_out_of_search_indexes(): void
    {
        $live = $this->facility('Pharmacie Ouverte', 'active');
        $hidden = $this->facility('Pharmacie Suspendue', 'suspended');

        $this->get(route('public.care-map.profile', $live->id))->assertOk()->assertHeaderMissing('X-Robots-Tag');
        $this->get(route('public.care-map.profile', $hidden->id))->assertOk()->assertHeader('X-Robots-Tag', 'noindex, follow');
    }
}

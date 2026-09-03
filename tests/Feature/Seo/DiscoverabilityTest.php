<?php

namespace Tests\Feature\Seo;

use App\Models\CareFacility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Search, answer and generative engines must be able to find, read and quote
 * the public Finder — in BOTH languages — and must be handed every facility
 * profile, because "pharmacie douala" queries land on profiles, not the home
 * page.
 *
 * The shape under guard: /sitemap.xml is an INDEX pointing at
 * /sitemap-pages.xml (route-table generated) and /sitemap-facilities-N.xml
 * (chunked, lastmod from updated_at, cached 24h); robots.txt welcomes AI
 * crawlers while keeping them out of authenticated surfaces; llms.txt states
 * what the product is AND what the data is not (reported, timestamped,
 * unverified) — for a health finder the caveats are the product.
 */
class DiscoverabilityTest extends TestCase
{
    use RefreshDatabase;

    /** Pages that carry the most discovery weight. */
    public static function keyPages(): array
    {
        return ['/', '/network/medicine-finder', '/network/blood-finder',
                '/how-it-works', '/about', '/faq'];
    }

    private function makeFacility(array $overrides = []): CareFacility
    {
        static $i = 0;
        $i++;

        return CareFacility::create(array_merge([
            'facility_name'       => "Test Facility {$i}",
            'facility_type'       => 'pharmacy',
            'listing_status'      => 'active',
            'verification_status' => 'unverified',
            'city'                => 'Douala',
            'region'              => 'Littoral',
            'address'             => "{$i} Rue de la Paix",
            'latitude'            => 4.05,
            'longitude'           => 9.7,
            'phone_primary'       => '+237650000000',
        ], $overrides));
    }

    public function test_robots_welcomes_ai_crawlers_and_points_at_the_sitemap(): void
    {
        $body = $this->get('/robots.txt')->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->getContent();

        // The crawlers that decide whether this product can be cited at all.
        foreach (['GPTBot', 'PerplexityBot', 'ClaudeBot', 'Google-Extended', 'OAI-SearchBot'] as $bot) {
            $this->assertStringContainsString($bot, $body, "{$bot} is not addressed in robots.txt");
        }

        $this->assertStringContainsString('Sitemap:', $body);
        $this->assertStringContainsString('/llms.txt', $body);
    }

    public function test_robots_keeps_crawlers_out_of_authenticated_surfaces(): void
    {
        // This is a privacy control, not an SEO preference: these paths carry
        // personal data, sessions and credentials.
        $body = $this->get('/robots.txt')->assertOk()->getContent();

        foreach (['/portals/', '/api/', '/login', '/care-map/facility/*/claim'] as $private) {
            $this->assertStringContainsString("Disallow: {$private}", $body);
        }
    }

    public function test_sitemap_is_an_index_listing_pages_and_facility_chunks(): void
    {
        $this->makeFacility();

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc, 'sitemap.xml is not valid XML');
        $this->assertSame('sitemapindex', $doc->getName(), 'sitemap.xml must be a sitemap index');

        $locs = array_map(fn ($s) => (string) $s->loc, iterator_to_array($doc->sitemap, false));

        $this->assertContains(url('/sitemap-pages.xml'), $locs);
        $this->assertContains(url('/sitemap-facilities-1.xml'), $locs);
    }

    public function test_facility_profiles_are_chunked_with_lastmod_and_hreflang(): void
    {
        // Chunk size 2 + three facilities → the 2,728-row production shape
        // (index → N chunk files) reproduced at test scale.
        config(['finder-seo.sitemap.chunk' => 2]);

        $facilities = [$this->makeFacility(), $this->makeFacility(), $this->makeFacility()];

        $index = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent());
        $locs  = array_map(fn ($s) => (string) $s->loc, iterator_to_array($index->sitemap, false));

        $this->assertContains(url('/sitemap-facilities-1.xml'), $locs);
        $this->assertContains(url('/sitemap-facilities-2.xml'), $locs, 'three facilities at chunk size 2 must produce a second chunk');

        $chunk1 = simplexml_load_string($this->get('/sitemap-facilities-1.xml')->assertOk()->getContent());
        $chunk2 = simplexml_load_string($this->get('/sitemap-facilities-2.xml')->assertOk()->getContent());

        $this->assertCount(2, $chunk1->url);
        $this->assertCount(1, $chunk2->url);

        // Every facility URL ships exactly once, across all chunks.
        $listed = [];
        foreach ([$chunk1, $chunk2] as $chunk) {
            foreach ($chunk->url as $url) {
                $listed[] = (string) $url->loc;

                $this->assertNotEmpty((string) $url->lastmod, "{$url->loc} has no lastmod — crawlers will re-fetch 2,700 unchanged profiles");

                $alternates = $url->children('http://www.w3.org/1999/xhtml');
                $this->assertCount(3, $alternates, "{$url->loc} is missing hreflang alternates");
            }
        }

        foreach ($facilities as $facility) {
            $this->assertContains(route('public.care-map.profile', $facility->id), $listed);
        }

        // Past the last chunk is a 404, not an empty file.
        $this->get('/sitemap-facilities-3.xml')->assertNotFound();
    }

    public function test_a_delisted_facility_is_not_in_the_sitemap(): void
    {
        $active   = $this->makeFacility();
        $delisted = $this->makeFacility(['listing_status' => 'delisted']);

        $xml = $this->get('/sitemap-facilities-1.xml')->assertOk()->getContent();

        $this->assertStringContainsString($active->id, $xml);
        $this->assertStringNotContainsString($delisted->id, $xml);
    }

    public function test_pages_sitemap_is_valid_xml_listing_both_languages(): void
    {
        $xml = $this->get('/sitemap-pages.xml')->assertOk()->getContent();

        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc, 'sitemap-pages.xml is not valid XML');

        $this->assertGreaterThan(20, count($doc->url), 'pages sitemap looks suspiciously short');

        foreach ($doc->url as $url) {
            $alternates = $url->children('http://www.w3.org/1999/xhtml');

            $this->assertCount(
                3,
                $alternates,
                "{$url->loc} is missing hreflang alternates — its French variant will never be discovered"
            );
        }
    }

    public function test_the_sitemap_never_exposes_a_private_path(): void
    {
        $doc = simplexml_load_string($this->get('/sitemap-pages.xml')->assertOk()->getContent());

        $leaked = [];
        foreach ($doc->url as $url) {
            $path = parse_url((string) $url->loc, PHP_URL_PATH) ?: '/';

            if (preg_match('#^/(portals|api|v1|fhir|admin|horizon|login|signup|register|demo-access|document-preview)#', $path)) {
                $leaked[] = $path;
            }
        }

        $this->assertSame([], array_unique($leaked), "\n" . implode("\n", array_unique($leaked)) . "\n");
    }

    public function test_llms_txt_states_what_the_data_is_and_is_not(): void
    {
        // Written to be quoted verbatim. For a health finder the caveats ARE
        // the product: an engine that presents stock reports as guarantees
        // can send a sick person to a closed pharmacy.
        $body = $this->get('/llms.txt')->assertOk()->getContent();

        $this->assertStringContainsString('OpesCare Finder', $body);
        $this->assertStringContainsString('MINSANTE', $body);
        $this->assertStringContainsString('OpenStreetMap', $body);
        $this->assertStringContainsString('never a guarantee', $body);
        $this->assertStringContainsString('NOT an OpesCare accreditation', $body);
        $this->assertStringContainsString('?lang=fr', $body);
    }

    public function test_every_key_page_carries_canonical_and_hreflang(): void
    {
        $failures = [];

        foreach (self::keyPages() as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            if (! str_contains($html, 'rel="canonical"')) {
                $failures[] = "{$path} has no canonical url";
            }

            foreach (['hreflang="en"', 'hreflang="fr"', 'hreflang="x-default"'] as $tag) {
                if (! str_contains($html, $tag)) {
                    $failures[] = "{$path} is missing {$tag}";
                }
            }
        }

        $this->assertSame([], $failures, "\n" . implode("\n", $failures) . "\n");
    }

    public function test_every_key_page_emits_valid_structured_data(): void
    {
        $failures = [];

        foreach (self::keyPages() as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            if (! preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m)) {
                $failures[] = "{$path} emits no JSON-LD at all";

                continue;
            }

            foreach ($m[1] as $i => $json) {
                $decoded = json_decode(trim($json), true);

                if ($decoded === null) {
                    // Invalid JSON-LD is worse than none — engines discard the
                    // whole block, silently.
                    $failures[] = "{$path} block {$i}: invalid JSON — " . json_last_error_msg();
                } elseif (($decoded['@context'] ?? null) !== 'https://schema.org') {
                    // Caught in the wild: Blade compiles directives before
                    // echos, so a literal "@context" outside a protected @php
                    // block is eaten by the @context directive — the JSON stays
                    // syntactically valid while the schema key becomes garbage.
                    $failures[] = "{$path} block {$i}: no schema.org @context key — Blade's @context directive may have eaten it";
                }
            }
        }

        $this->assertSame([], $failures, "\n" . implode("\n", $failures) . "\n");
    }

    public function test_the_faq_marks_up_its_questions_for_answer_engines(): void
    {
        $html = $this->get('/faq')->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

        $faq = null;
        foreach ($m[1] as $json) {
            $decoded = json_decode(trim($json), true);
            if (($decoded['@type'] ?? null) === 'FAQPage') {
                $faq = $decoded;
            }
        }

        $this->assertNotNull($faq, 'the FAQ page has no FAQPage schema — answer engines must infer the Q&A pairs');
        $this->assertGreaterThanOrEqual(10, count($faq['mainEntity']));
        $this->assertSame('Question', $faq['mainEntity'][0]['@type']);
        $this->assertNotEmpty($faq['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function test_a_crawler_can_reach_the_french_site_without_a_session(): void
    {
        // The whole point of ?lang= being honoured: locale used to come only
        // from the session, so every crawler saw English.
        $en = $this->get('/')->assertOk()->getContent();
        $fr = $this->get('/?lang=fr')->assertOk()->getContent();

        $this->assertStringContainsString('<html lang="en"', $en);
        $this->assertStringContainsString('<html lang="fr"', $fr);
        $this->assertNotSame($en, $fr, 'the French url returned identical markup to English');
    }

    public function test_an_unsupported_locale_is_ignored_rather_than_obeyed(): void
    {
        // ?lang= is attacker-controlled input that reaches App::setLocale.
        $html = $this->get('/?lang=../../etc/passwd')->assertOk()->getContent();

        $this->assertStringContainsString('<html lang="en"', $html);
    }
}

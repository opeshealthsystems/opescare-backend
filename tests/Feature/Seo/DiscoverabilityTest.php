<?php

namespace Tests\Feature\Seo;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Search, answer and generative engines must be able to find, read and quote
 * the public site — in BOTH languages.
 *
 * The gap this closes: locale lived only in the session, so every page had one
 * url and a crawler (which carries no session) always received English. Half of
 * 8,214 translated keys were unreachable by any engine, on a platform whose
 * primary market speaks French. There was also no sitemap, no canonical, no
 * hreflang and no structured data anywhere on the site.
 */
class DiscoverabilityTest extends TestCase
{
    use RefreshDatabase;

    /** Pages that carry the most discovery weight. */
    public static function keyPages(): array
    {
        return ['/', '/how-it-works', '/interoperability', '/security',
                '/network/medicine-finder', '/network/blood-finder', '/faq'];
    }

    public function test_robots_welcomes_ai_crawlers_and_points_at_the_sitemap(): void
    {
        $body = $this->get('/robots.txt')->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->getContent();

        // The crawlers that decide whether this platform can be cited at all.
        foreach (['GPTBot', 'PerplexityBot', 'ClaudeBot', 'Google-Extended', 'OAI-SearchBot'] as $bot) {
            $this->assertStringContainsString($bot, $body, "{$bot} is not addressed in robots.txt");
        }

        $this->assertStringContainsString('Sitemap:', $body);
        $this->assertStringContainsString('/llms.txt', $body);
    }

    public function test_robots_keeps_crawlers_out_of_authenticated_surfaces(): void
    {
        // This is a privacy control, not an SEO preference: these paths carry
        // patient data, sessions and credentials.
        $body = $this->get('/robots.txt')->assertOk()->getContent();

        foreach (['/portals/', '/api/', '/login', '/document-preview'] as $private) {
            $this->assertStringContainsString("Disallow: {$private}", $body);
        }
    }

    public function test_sitemap_is_valid_xml_listing_both_languages(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc, 'sitemap.xml is not valid XML');

        $this->assertGreaterThan(20, count($doc->url), 'sitemap looks suspiciously short');

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
        $doc = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent());

        $leaked = [];
        foreach ($doc->url as $url) {
            $path = parse_url((string) $url->loc, PHP_URL_PATH) ?: '/';

            if (preg_match('#^/(portals|api|v1|fhir|admin|horizon|login|signup|register|demo-access|document-preview)#', $path)) {
                $leaked[] = $path;
            }
        }

        $this->assertSame([], array_unique($leaked), "\n" . implode("\n", array_unique($leaked)) . "\n");
    }

    public function test_llms_txt_states_what_the_platform_is_and_is_not(): void
    {
        // Written to be quoted verbatim. The "is not" half matters as much as
        // the "is": a hospital management system is the thing OpesCare is most
        // often mistaken for, including by models summarising it.
        $body = $this->get('/llms.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Health ID', $body);
        $this->assertStringContainsString('not a hospital management system', $body);
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
                if (json_decode(trim($json), true) === null) {
                    // Invalid JSON-LD is worse than none — engines discard the
                    // whole block, silently.
                    $failures[] = "{$path} block {$i}: invalid JSON — " . json_last_error_msg();
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
        // The whole point. Before ?lang= was honoured, this was impossible:
        // locale came only from the session, so every crawler saw English.
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

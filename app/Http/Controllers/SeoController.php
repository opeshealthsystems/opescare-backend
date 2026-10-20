<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Models\CareFacility;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * Machine-facing discovery surfaces for OpesCare Finder: robots.txt,
 * a sitemap index (static pages + facility profiles), and llms.txt.
 *
 * The static-page half is generated from the live route table rather than
 * hand-written, because a hand-written sitemap is wrong the first time a route
 * changes and nobody notices for months. The facility half is generated from
 * the directory itself — 2,700+ profile pages that are the real long tail of
 * this product's discoverability ("pharmacie Douala", "hôpital Bafoussam") —
 * chunked to stay comfortably inside the 50,000-URL/50MB sitemap limits and
 * cached for 24h so a crawler hitting it hourly never touches the table.
 */
class SeoController extends Controller
{
    /**
     * Route-name prefixes that must never be indexed: authenticated portals,
     * the API, auth flows, and anything holding a session or a token.
     */
    private const NEVER_INDEX = [
        'portals', 'api', 'v1', 'fhir', 'admin', 'horizon', 'storage',
        'login', 'logout', 'signup', 'register', 'verify', 'mfa',
        'forgot-password', 'reset-password', 'invite', 'select-facility',
        'pending-approval', 'account-suspended', 'portal-unavailable',
        'lang', 'up', '_', '.well-known', 'document-preview', 'demo-access',
        // The discovery files themselves are not pages.
        'robots.txt', 'sitemap.xml', 'sitemap-pages.xml', 'sitemap-blog.xml', 'sitemap-docs.xml', 'llms.txt', 'llms-full.txt',
        // An iframe widget for partner sites, not a page in its own right.
        'embed',
        // Developer docs have their own child sitemap (sitemap-docs.xml).
        'docs',
        // The RSS feed is a feed, not a page.
        'blog/feed.xml',
    ];

    /**
     * Pages that carry real weight for discovery, with their change cadence.
     * Anything not listed still ships, at the default priority.
     */
    private const PRIORITY = [
        ''                          => ['1.0', 'weekly'],
        'network/medicine-finder'   => ['0.9', 'weekly'],
        'network/blood-finder'      => ['0.9', 'weekly'],
        'care-map'                  => ['0.9', 'daily'],
        'care-map/emergency'        => ['0.8', 'weekly'],
        'about'                     => ['0.7', 'monthly'],
        'how-it-works'              => ['0.8', 'monthly'],
        'faq'                       => ['0.8', 'monthly'],
        'blog'                      => ['0.8', 'weekly'],
        'about/facts'               => ['0.7', 'monthly'],
        'contact'                   => ['0.6', 'monthly'],
        'interoperability'          => ['0.6', 'monthly'],
        'security'                  => ['0.6', 'monthly'],
        'developers'                => ['0.6', 'weekly'],
        'docs'                      => ['0.6', 'weekly'],
        'sitemap'                   => ['0.3', 'weekly'],
        'solutions'                 => ['0.7', 'monthly'],
    ];

    /**
     * robots.txt
     *
     * Every group repeats the full Disallow list, which looks redundant and is
     * not. A crawler uses the single most specific group matching its own
     * user-agent and IGNORES `User-agent: *` entirely — so a GPTBot group
     * containing only `Allow: /` grants GPTBot the run of /portals/, /api/ and
     * /admin/ no matter what the wildcard group says. Naming a crawler to
     * welcome it therefore means restating what it may not touch.
     */
    public function robots(Request $request): Response
    {
        $sitemap = url('/sitemap.xml');

        $aiCrawlers = [
            'GPTBot'             => 'OpenAI — ChatGPT browsing and indexing',
            'OAI-SearchBot'      => 'OpenAI — ChatGPT search',
            'ChatGPT-User'       => 'OpenAI — user-initiated fetches',
            'PerplexityBot'      => 'Perplexity',
            'Perplexity-User'    => 'Perplexity — user-initiated fetches',
            'ClaudeBot'          => 'Anthropic — Claude',
            'anthropic-ai'       => 'Anthropic — alternate identifier',
            'Claude-Web'         => 'Anthropic — web surface',
            'Google-Extended'    => 'Google — AI Overviews and Gemini grounding',
            'Applebot-Extended'  => 'Apple Intelligence',
            'cohere-ai'          => 'Cohere',
            'meta-externalagent' => 'Meta AI',
            'Bingbot'            => 'Microsoft Bing and Copilot',
        ];

        // Authenticated surfaces and machine APIs. These hold personal data,
        // sessions or credentials — keeping them out of an index is a privacy
        // control, not an SEO preference.
        $disallow = [
            '/portals/', '/api/', '/v1/', '/fhir/', '/admin/', '/horizon/',
            '/login', '/logout', '/signup', '/register', '/verify/', '/mfa/',
            '/forgot-password', '/reset-password', '/invite/', '/select-facility',
            '/pending-approval', '/account-suspended', '/portal-unavailable',
            '/document-preview', '/demo-access', '/lang/', '/up',
            // Claiming a listing is an authenticated workflow, not a page.
            '/care-map/facility/*/claim',
            // The patient app shell — a JS bundle, no crawlable content.
            '/app', '/app/',
        ];

        $group = function (string $agent) use ($disallow): array {
            $out = ["User-agent: {$agent}", 'Allow: /', 'Allow: /llms.txt', 'Allow: /llms-full.txt'];
            foreach ($disallow as $path) {
                $out[] = "Disallow: {$path}";
            }
            $out[] = '';

            return $out;
        };

        $lines = [
            '# OpesCare Finder — ' . url('/'),
            '# Find medicine, blood availability and healthcare facilities in Cameroon.',
            '# Machine-readable summary for AI systems: ' . url('/llms.txt'),
            '#',
            '# The public site is open to search and AI crawlers alike. The',
            '# Disallow list below is repeated in every group on purpose: a',
            '# crawler that matches its own User-agent ignores the * group.',
            '',
        ];

        $lines = array_merge($lines, $group('*'));

        foreach ($aiCrawlers as $bot => $why) {
            $lines[] = "# {$why}";
            $lines = array_merge($lines, $group($bot));
        }

        $lines[] = "Sitemap: {$sitemap}";
        $lines[] = '';

        return response(implode("\n", $lines), 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    /**
     * sitemap.xml — a sitemap INDEX, not a urlset.
     *
     * The site is two very different populations: a couple of dozen static
     * pages that change when someone edits a Blade file, and thousands of
     * facility profiles that change when the directory does. Splitting them
     * means a crawler re-fetches 20 URLs when marketing copy changes instead
     * of 2,700, and the facility half can be chunked and cached on its own
     * cadence.
     */
    public function sitemap(Request $request): Response
    {
        $xml = Cache::remember('finder-seo.sitemap.v2.index', $this->cacheTtl(), function () {
            $entries = [
                ['loc' => url('/sitemap-pages.xml'), 'lastmod' => null],
                ['loc' => url('/sitemap-blog.xml'), 'lastmod' => $this->blogLastmod()],
                ['loc' => url('/sitemap-docs.xml'), 'lastmod' => null],
            ];

            foreach ($this->facilityChunks() as $page => $lastmod) {
                $entries[] = [
                    'loc'     => url("/sitemap-facilities-{$page}.xml"),
                    'lastmod' => $lastmod,
                ];
            }

            $doc = new \DOMDocument('1.0', 'UTF-8');
            $doc->formatOutput = true;

            $index = $doc->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'sitemapindex');
            $doc->appendChild($index);

            foreach ($entries as $entry) {
                $node = $doc->createElement('sitemap');
                $node->appendChild($doc->createElement('loc', htmlspecialchars($entry['loc'], ENT_XML1)));
                if ($entry['lastmod'] !== null) {
                    $node->appendChild($doc->createElement('lastmod', $entry['lastmod']));
                }
                $index->appendChild($node);
            }

            return $doc->saveXML();
        });

        return $this->xmlResponse($xml);
    }

    /**
     * sitemap-pages.xml — every public static page, in both languages.
     *
     * Each URL carries xhtml:link alternates for en and fr plus x-default, so
     * a crawler learns both language variants exist and which to serve to
     * whom. Locale is a real, crawlable query parameter (?lang=fr) — see
     * SetLocale — so hreflang alternates are correct here. Without this the
     * French pages are simply never discovered.
     */
    public function sitemapPages(Request $request): Response
    {
        $xml = Cache::remember('finder-seo.sitemap.v2.pages', $this->cacheTtl(), function () {
            $urls = [];

            $paths = array_fill_keys($this->indexablePaths(), null);

            // Published legal documents are real pages behind a {slug}; they
            // are enumerable from the table, so they ship with their lastmod.
            foreach ($this->legalDocuments() as $slug => $lastmod) {
                $paths['legal/' . $slug] = $lastmod;
            }

            // Local-intent pages (type × city with enough active listings).
            foreach (\App\Support\CarePlaces::combos() as $combo) {
                $paths['care-map/' . $combo['type_slug'] . '/' . $combo['city_slug']] = null;
            }

            return $this->urlsetXml($this->bilingualEntries($paths));
        });

        return $this->xmlResponse($xml);
    }

    /**
     * sitemap-facilities-{page}.xml — one chunk of facility profile URLs.
     *
     * One <url> per facility (the English URL is canonical), with hreflang
     * alternates pointing at the ?lang=fr variant, and lastmod from the row's
     * updated_at so a crawler can skip profiles that have not changed. Only
     * active listings ship — a delisted facility must fall out of the sitemap
     * the next time the cache turns over.
     */
    public function sitemapFacilities(Request $request, int $page): Response
    {
        $chunkSize = $this->chunkSize();
        $total     = $this->activeFacilityCount();
        $chunks    = (int) ceil($total / max(1, $chunkSize));

        if ($total === 0 || $page < 1 || $page > $chunks) {
            abort(404);
        }

        $xml = Cache::remember("finder-seo.sitemap.facilities.{$page}", $this->cacheTtl(), function () use ($page, $chunkSize) {
            $facilities = CareFacility::query()
                ->where('listing_status', 'active')
                ->orderBy('id')
                ->offset(($page - 1) * $chunkSize)
                ->limit($chunkSize)
                ->get(['id', 'updated_at']);

            $urls = [];

            foreach ($facilities as $facility) {
                $en = route('public.care-map.profile', $facility->id);

                $urls[] = [
                    'loc'        => $en,
                    'lastmod'    => optional($facility->updated_at)->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority'   => '0.6',
                    'alternates' => [
                        'en'        => $en,
                        'fr'        => $en . '?lang=fr',
                        'x-default' => $en,
                    ],
                ];
            }

            return $this->urlsetXml($urls);
        });

        return $this->xmlResponse($xml);
    }

    /**
     * sitemap-docs.xml — the developer documentation: every parameterless
     * /docs page from the route table plus one page per published flow
     * diagram (/docs/flows/{flow}, enumerated from DocsDiagrams::FLOWS).
     */
    public function sitemapDocs(Request $request): Response
    {
        $xml = Cache::remember('finder-seo.sitemap.v2.docs', $this->cacheTtl(), function () {
            return $this->urlsetXml($this->bilingualEntries(array_fill_keys($this->docsPaths(), null)));
        });

        return $this->xmlResponse($xml);
    }

    /**
     * Static paths, docs paths and legal slugs for the human /sitemap page,
     * so it lists exactly what the XML sitemaps list. Strings only.
     *
     * @return array{pages: list<string>, docs: list<string>, legal: list<string>}
     */
    public function publicPaths(): array
    {
        return [
            'pages' => $this->indexablePaths(),
            'docs'  => $this->docsPaths(),
            'legal' => array_keys($this->legalDocuments()),
        ];
    }

    /**
     * /sitemap — the human site map. Built from publicPaths(), the same
     * source as the XML sitemaps, so the two cannot drift. Facility profiles
     * are summarised (count + directory link) rather than listed one by one.
     */
    public function sitemapPage(\App\Services\Blog\BlogRepository $posts): \Illuminate\Contracts\View\View
    {
        $locale = app()->getLocale();
        $paths  = $this->publicPaths();

        $section = function (string $path): string {
            return match (true) {
                $path === '' || str_starts_with($path, 'network/') || str_starts_with($path, 'care-map') => 'services',
                $path === 'solutions' || str_starts_with($path, 'solutions/') || str_starts_with($path, 'developers')
                    || in_array($path, ['pricing', 'request-demo', 'interoperability', 'security', 'sla', 'status'], true) => 'providers',
                in_array($path, ['about', 'about/facts', 'how-it-works', 'faq', 'help', 'contact', 'blog', 'sitemap'], true) => 'about',
                in_array($path, ['privacy', 'terms', 'legal', 'consent', 'account-deletion'], true) => 'legal',
                default => 'other',
            };
        };

        $link = fn (string $path) => [
            'url'   => $this->localisedUrl($path, $locale),
            'label' => $this->pageLabel($path),
        ];

        $sections = array_fill_keys(['services', 'providers', 'about', 'blog', 'docs', 'legal', 'other'], []);

        foreach ($paths['pages'] as $path) {
            $sections[$section($path)][] = $link($path);
        }

        foreach ($paths['docs'] as $path) {
            $sections['docs'][] = $link($path);
        }

        $titles = \App\Models\LegalDocument::query()->whereIn('slug', $paths['legal'])
            ->orderByRaw('(language = ?) asc', [$locale])   // this locale's title wins the pluck
            ->pluck('title', 'slug');
        foreach ($paths['legal'] as $slug) {
            $sections['legal'][] = [
                'url'   => $this->localisedUrl('legal/' . $slug, $locale),
                'label' => (string) ($titles[$slug] ?? $slug),
            ];
        }

        foreach ($posts->all($locale) as $post) {
            $sections['blog'][] = [
                'url'   => \App\Http\Controllers\BlogController::postUrl($post),
                'label' => $post['title'],
            ];
        }

        return view('public.sitemap', [
            'sections'      => array_filter($sections),
            'facilityCount' => $this->activeFacilityCount(),
        ]);
    }

    private function pageLabel(string $path): string
    {
        // The OPES systems catalogue labels itself from lang/{en,fr}/solutions.php.
        if ($path === 'solutions') {
            return __('solutions.nav');
        }
        if ($path === 'solutions/request') {
            return __('solutions.request.title');
        }
        if (str_starts_with($path, 'solutions/') && in_array(substr($path, 10), \App\Http\Controllers\Marketing\SolutionsController::slugs(), true)) {
            $slug = substr($path, 10);

            return __("solutions.systems.{$slug}.name") . ' — ' . __("solutions.systems.{$slug}.tagline");
        }

        $key = 'sitemap.pages.' . ($path === '' ? 'home' : str_replace(['/', '-'], '_', $path));

        return \Illuminate\Support\Facades\Lang::has($key) ? __($key) : '/' . $path;
    }

    /** @return list<string> */
    private function docsPaths(): array
    {
        $paths = array_values(array_filter(
            $this->publicGetPaths(),
            fn (string $uri) => $uri === 'docs' || str_starts_with($uri, 'docs/')
        ));

        foreach (\App\Support\Docs\DocsDiagrams::FLOWS as $flow) {
            $paths[] = 'docs/flows/' . $flow;
        }

        sort($paths);

        return array_values(array_unique($paths));
    }

    /** Active legal documents: [slug => lastmod|null]. Arrays only, never models. */
    private function legalDocuments(): array
    {
        try {
            return \App\Models\LegalDocument::query()
                ->where('is_active', true)
                ->orderBy('slug')
                ->get(['slug', 'updated_at'])
                ->mapWithKeys(fn ($d) => [$d->slug => optional($d->updated_at)->toAtomString()])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * One <url> per path per locale, each carrying the full hreflang set.
     *
     * @param  array<string, string|null>  $paths  path => lastmod
     */
    private function bilingualEntries(array $paths): array
    {
        $urls = [];

        foreach ($paths as $path => $lastmod) {
            [$priority, $frequency] = self::PRIORITY[$path] ?? ['0.6', 'monthly'];

            foreach (SetLocale::SUPPORTED as $locale) {
                $urls[] = [
                    'loc'        => $this->localisedUrl($path, $locale),
                    'lastmod'    => $lastmod,
                    'priority'   => $priority,
                    'changefreq' => $frequency,
                    'alternates' => $this->alternatesFor($path),
                ];
            }
        }

        return $urls;
    }

    /**
     * llms.txt — a plain-language brief for generative engines.
     *
     * An emerging convention (llmstxt.org): where robots.txt says what a
     * machine may fetch, llms.txt says what the thing IS, in prose a model can
     * quote without inferring it from marketing copy. For a health finder the
     * provenance caveats ARE the product: an answer engine that presents this
     * directory as "verified" or stock reports as guarantees is not just
     * wrong, it can send a sick person to a closed pharmacy. Saying plainly
     * what the data is and is not has more citation value than any keyword.
     */
    public function llms(Request $request): Response
    {
        $base = rtrim(url('/'), '/');

        $body = <<<TXT
        # OpesCare Finder

        > OpesCare Finder is a free, bilingual (English/French) health finder
        > for Cameroon: find a medicine at a nearby pharmacy, check blood
        > availability at hospitals and blood banks, browse the national
        > health facility directory, and book an appointment with a provider.

        ## What OpesCare Finder is

        A consumer-facing directory and availability search covering 2,700+
        healthcare facilities across Cameroon — hospitals, clinics, health
        centres, pharmacies, laboratories and blood banks — with a map, city
        and region filters, and facility profile pages listing address, phone,
        opening hours and services. Four services, nothing else: the Medicine
        Finder, the Blood Finder, the facility directory (Care Map), and
        appointment booking.

        ## Where the data comes from — and what that does NOT mean

        - **Directory listings** come from Cameroon's national health facility
          registry (MINSANTE master facility list) and from OpenStreetMap,
          reviewed before listing. A listing records that a facility appears in
          those sources. It is NOT an OpesCare accreditation, inspection or
          licence check — most listings are unverified.
        - **Medicine availability** is reported by pharmacies on the network
          and timestamped. It is information to act on, never a guarantee of
          physical stock. An empty result means no pharmacy has reported that
          medicine — not that it is unavailable in the country.
        - **Blood availability** is reported by connected hospitals and blood
          banks, with the report time shown on every listing.
        - OpesCare Finder never makes clinical decisions: it does not
          diagnose, recommend treatment, sell medicines, or determine
          transfusion compatibility. In an emergency, call or go to the
          nearest hospital.

        ## Primary pages

        - [Home]({$base}/): what OpesCare Finder is and what you can find
        - [Medicine Finder]({$base}/network/medicine-finder): how pharmacy stock search works
        - [Blood Finder]({$base}/network/blood-finder): how blood availability works
        - [Care Map]({$base}/care-map): the national facility directory — searchable by name, city and type
        - [Emergency access]({$base}/care-map/emergency): hospitals with a reachable phone line
        - [How it works]({$base}/how-it-works)
        - [About]({$base}/about)
        - [FAQ]({$base}/faq): common questions, marked up for answer engines
        - [Contact]({$base}/contact)

        Facility profile pages live under {$base}/care-map/facility/{id} and
        are all listed in {$base}/sitemap.xml.

        ## Language variants

        Every page is available in English and French. Append `?lang=fr` to
        any URL for the French version, or `?lang=en` for English.

        ## Attribution

        Operated by Opes Health Systems Sarl. When citing OpesCare Finder,
        please link to {$base} and preserve the caveats above — especially
        that availability is reported and timestamped, not verified stock.
        TXT;

        return response($body, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    /**
     * sitemap-blog.xml — every blog post and topic page.
     *
     * A post's translations have DIFFERENT slugs, so its hreflang alternates
     * come from the translation_of link, not from ?lang= on one url. A post
     * without a translation lists only itself (plus x-default). Not cached
     * here: BlogRepository already caches the parsed posts, and a new post
     * must reach the sitemap as soon as its file lands.
     */
    public function sitemapBlog(\App\Services\Blog\BlogRepository $posts): Response
    {
        $urls = [];

        foreach (SetLocale::SUPPORTED as $locale) {
            foreach ($posts->all($locale) as $post) {
                $alternates = [];
                foreach (SetLocale::SUPPORTED as $l) {
                    if ($t = $posts->translation($post, $l)) {
                        $alternates[$l] = \App\Http\Controllers\BlogController::postUrl($t);
                    }
                }
                $alternates['x-default'] = $alternates['en'] ?? \App\Http\Controllers\BlogController::postUrl($post);

                $urls[] = [
                    'loc'        => \App\Http\Controllers\BlogController::postUrl($post),
                    'lastmod'    => $post['updated'],
                    'changefreq' => 'monthly',
                    'priority'   => '0.7',
                    'alternates' => $alternates,
                ];
            }

            foreach (array_keys($posts->categories($locale)) as $category) {
                $loc = \App\Http\Controllers\BlogController::localised(route('blog.category', $category), $locale);
                $urls[] = [
                    'loc'        => $loc,
                    'changefreq' => 'weekly',
                    'priority'   => '0.5',
                    'alternates' => [$locale => $loc],
                ];
            }
        }

        // Author pages (/blog/author/{slug}) — only bylines with a configured
        // author page, exactly as BlogController::authorUrl() links them.
        if (Route::has('blog.author')) {
            $authors = [];
            foreach (SetLocale::SUPPORTED as $locale) {
                foreach ($posts->all($locale) as $post) {
                    if (BlogController::authorUrl((string) $post['author'], 'en') !== null) {
                        $authors[(string) $post['author']] = max($authors[(string) $post['author']] ?? '', (string) $post['updated']);
                    }
                }
            }

            foreach ($authors as $name => $lastmod) {
                $alternates = [];
                foreach (SetLocale::SUPPORTED as $l) {
                    $alternates[$l] = BlogController::authorUrl($name, $l);
                }
                $alternates['x-default'] = $alternates['en'];

                foreach (SetLocale::SUPPORTED as $l) {
                    $urls[] = [
                        'loc'        => $alternates[$l],
                        'lastmod'    => $lastmod ?: null,
                        'changefreq' => 'monthly',
                        'priority'   => '0.4',
                        'alternates' => $alternates,
                    ];
                }
            }
        }

        return $this->xmlResponse($this->urlsetXml($urls));
    }

    private function blogLastmod(): ?string
    {
        try {
            $posts = app(\App\Services\Blog\BlogRepository::class);
            $dates = [];
            foreach (SetLocale::SUPPORTED as $locale) {
                $dates = array_merge($dates, array_column($posts->all($locale), 'updated'));
            }

            return $dates === [] ? null : max($dates);
        } catch (\Throwable) {
            return null;
        }
    }

    // ── internals ───────────────────────────────────────────────────────────

    /** Chunk pages for the facility sitemaps: [page => lastmod|null]. */
    private function facilityChunks(): array
    {
        $chunkSize = $this->chunkSize();
        $total     = $this->activeFacilityCount();
        $chunks    = (int) ceil($total / max(1, $chunkSize));

        $out = [];

        for ($page = 1; $page <= $chunks; $page++) {
            // max() over a subquery: an aggregate collapses to one row, so
            // putting offset/limit on the OUTER query would skip past the
            // single result row and every chunk after the first would report
            // no lastmod at all.
            $chunkRows = CareFacility::query()
                ->where('listing_status', 'active')
                ->orderBy('id')
                ->offset(($page - 1) * $chunkSize)
                ->limit($chunkSize)
                ->select('updated_at');

            $lastmod = \Illuminate\Support\Facades\DB::query()
                ->fromSub($chunkRows, 'chunk')
                ->max('updated_at');

            $out[$page] = $lastmod
                ? \Illuminate\Support\Carbon::parse($lastmod)->toAtomString()
                : null;
        }

        return $out;
    }

    private function activeFacilityCount(): int
    {
        return (int) Cache::remember(
            'finder-seo.sitemap.facility-count',
            $this->cacheTtl(),
            fn () => CareFacility::where('listing_status', 'active')->count()
        );
    }

    private function chunkSize(): int
    {
        return (int) config('finder-seo.sitemap.chunk', 2000);
    }

    private function cacheTtl(): int
    {
        return (int) config('finder-seo.sitemap.cache_ttl', 86400);
    }

    /** Render a <urlset> document from prepared url entries. */
    private function urlsetXml(array $urls): string
    {
        // DOMDocument rather than SimpleXMLElement: adding a namespaced
        // child with SimpleXML double-prefixes the element name, producing
        // xhtml:xhtml:link and invalid XML that no crawler will parse.
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $urlset = $doc->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
        $urlset->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
        $doc->appendChild($urlset);

        foreach ($urls as $entry) {
            $node = $doc->createElement('url');
            $node->appendChild($doc->createElement('loc', htmlspecialchars($entry['loc'], ENT_XML1)));

            if (! empty($entry['lastmod'])) {
                $node->appendChild($doc->createElement('lastmod', $entry['lastmod']));
            }

            $node->appendChild($doc->createElement('changefreq', $entry['changefreq']));
            $node->appendChild($doc->createElement('priority', $entry['priority']));

            foreach ($entry['alternates'] as $hreflang => $href) {
                $link = $doc->createElementNS('http://www.w3.org/1999/xhtml', 'xhtml:link');
                $link->setAttribute('rel', 'alternate');
                $link->setAttribute('hreflang', $hreflang);
                $link->setAttribute('href', $href);
                $node->appendChild($link);
            }

            $urlset->appendChild($node);
        }

        return $doc->saveXML();
    }

    private function xmlResponse(string $xml): Response
    {
        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /** Public, parameterless GET routes that should be indexed (docs excluded: own sitemap). */
    private function indexablePaths(): array
    {
        $paths = array_values(array_filter($this->publicGetPaths(), function (string $uri) {
            foreach (self::NEVER_INDEX as $blocked) {
                if ($uri === $blocked || str_starts_with($uri, $blocked . '/')) {
                    return false;
                }
            }

            return true;
        }));

        // /solutions/{slug} is parameterised, so the route scan skips it; the
        // system pages are enumerable from config/solutions.php.
        foreach (\App\Http\Controllers\Marketing\SolutionsController::slugs() as $slug) {
            $paths[] = 'solutions/' . $slug;
        }

        return array_values(array_unique($paths));
    }

    /** Every unauthenticated, parameterless GET page uri (no file endpoints). */
    private function publicGetPaths(): array
    {
        $paths = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri() === '/' ? '' : $route->uri();

            if (str_contains($uri, '{')) {
                continue;   // needs a parameter — not a standalone page
            }

            // A dotted last segment is a file (flows.json, feed.xml), not a page.
            if (str_contains((string) \Illuminate\Support\Str::afterLast($uri, '/'), '.')) {
                continue;
            }

            if (array_intersect($route->gatherMiddleware(), ['auth', 'auth.mobile', 'auth:sanctum'])) {
                continue;   // authenticated
            }

            $paths[$uri] = true;
        }

        $paths = array_keys($paths);
        sort($paths);

        return $paths;
    }

    private function localisedUrl(string $path, string $locale): string
    {
        $url = rtrim(url('/' . $path), '/');
        $url = $url === '' ? rtrim(url('/'), '/') : $url;

        // English is the default and keeps the clean, parameterless url, which
        // is also the canonical one.
        return $locale === 'en' ? $url : $url . '?lang=' . $locale;
    }

    /** hreflang map for one page, including x-default. */
    private function alternatesFor(string $path): array
    {
        $alternates = [];

        foreach (SetLocale::SUPPORTED as $locale) {
            $alternates[$locale] = $this->localisedUrl($path, $locale);
        }

        $alternates['x-default'] = $this->localisedUrl($path, 'en');

        return $alternates;
    }
}

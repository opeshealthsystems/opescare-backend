<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

/**
 * Machine-facing discovery surfaces: robots.txt, sitemap.xml and llms.txt.
 *
 * All three are generated from the live route table rather than hand-written,
 * because a hand-written sitemap is wrong the first time a route changes and
 * nobody notices for months. The route table is the only thing that always
 * tells the truth about what exists.
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
        'robots.txt', 'sitemap.xml', 'llms.txt',
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
        'how-it-works'              => ['0.8', 'monthly'],
        'interoperability'          => ['0.8', 'monthly'],
        'faq'                       => ['0.8', 'monthly'],
        'security'                  => ['0.7', 'monthly'],
        'developers'                => ['0.7', 'weekly'],
        'docs'                      => ['0.7', 'weekly'],
    ];

    /**
     * robots.txt — explicitly welcoming AI crawlers.
     *
     * The previous file was `User-agent: * / Disallow:`, which permits
     * everything by omission. Naming the AI crawlers is not redundant: it makes
     * the intent auditable, and it means a future tightening of the wildcard
     * rule cannot silently remove AI visibility. It also points at the sitemap,
     * which the old file did not.
     */
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

        // Authenticated surfaces and machine APIs. These hold patient data,
        // sessions or credentials — keeping them out of an index is a privacy
        // control, not an SEO preference.
        $disallow = [
            '/portals/', '/api/', '/v1/', '/fhir/', '/admin/', '/horizon/',
            '/login', '/logout', '/signup', '/register', '/verify/', '/mfa/',
            '/forgot-password', '/reset-password', '/invite/', '/select-facility',
            '/pending-approval', '/account-suspended', '/portal-unavailable',
            '/document-preview', '/demo-access', '/lang/', '/up',
            // The patient app shell — a JS bundle, no crawlable content.
            '/app', '/app/',
        ];

        $group = function (string $agent) use ($disallow): array {
            $out = ["User-agent: {$agent}", 'Allow: /'];
            foreach ($disallow as $path) {
                $out[] = "Disallow: {$path}";
            }
            $out[] = '';

            return $out;
        };

        $lines = [
            '# OpesCare — ' . url('/'),
            '# A patient-centred health identity and interoperability platform.',
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

        return response(implode("
", $lines), 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    /**
     * sitemap.xml — every public page, in both languages.
     *
     * Each URL carries xhtml:link alternates for en and fr plus x-default, so a
     * crawler learns both language variants exist and which to serve to whom.
     * Without this the French pages are simply never discovered.
     */
    public function sitemap(Request $request): Response
    {
        $urls = [];

        foreach ($this->indexablePaths() as $path) {
            [$priority, $frequency] = self::PRIORITY[$path] ?? ['0.6', 'monthly'];

            foreach (SetLocale::SUPPORTED as $locale) {
                $urls[] = [
                    'loc'        => $this->localisedUrl($path, $locale),
                    'priority'   => $priority,
                    'changefreq' => $frequency,
                    'alternates' => $this->alternatesFor($path),
                ];
            }
        }

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

        return response($doc->saveXML(), 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * llms.txt — a plain-language brief for generative engines.
     *
     * An emerging convention (llmstxt.org): where robots.txt says what a
     * machine may fetch, llms.txt says what the thing IS, in prose a model can
     * quote without having to infer it from marketing copy. For a platform this
     * easy to mistake for a hospital management system, saying plainly what it
     * is and is not has more citation value than any keyword.
     */
    public function llms(Request $request): Response
    {
        $base = rtrim(url('/'), '/');

        $body = <<<TXT
        # OpesCare

        > OpesCare is a patient-centred digital health identity and
        > interoperability platform for Cameroon, built multi-country and
        > bilingual (English and French). Patients hold one portable Health ID
        > and one longitudinal medical record that travels between facilities
        > under their consent.

        ## What OpesCare is

        OpesCare is health infrastructure, not hospital software. It connects
        the systems healthcare facilities already run — it does not require any
        facility to replace its existing software. A hospital keeps its own HIS
        and connects over an API; a facility with no system at all uses a
        browser portal called OpesCare Lite.

        ## What OpesCare is not

        OpesCare is not a hospital management system, an electronic medical
        record product, a billing or accounting system, or an insurance claims
        platform. Those remain the responsibility of each facility's own
        software.

        ## Core capabilities

        - **Health ID** — a portable, verified digital health identity owned by the patient.
        - **Master Patient Index** — resolves one person to one record across facilities, with uncertain matches sent to human review rather than merged automatically.
        - **Consent and access control** — every exchange of patient data is checked against a consent grant, scoped and time-limited, and recorded in a log the patient can read.
        - **Emergency access** — a limited break-glass profile when consent cannot be obtained, requiring a stated reason and notifying the patient.
        - **Interoperability** — HL7 FHIR R4, a REST Connect API, SDKs for PHP, TypeScript and Python, an embeddable widget, webhooks, and an on-premises Bridge Agent for legacy systems.

        ## Network services

        - **Medicine Finder** — search pharmacies on the network for medicine availability, with the timestamp of the last report shown on every listing.
        - **Blood Finder** — search connected hospitals and blood banks by blood group and component.
        - **Care Map** — a directory of healthcare facilities listed in Cameroon's national health facility registry (MINSANTE).
        - **Appointments** — cross-facility booking and referral-linked scheduling.

        ## Key facts

        - Operates in Cameroon first, aligned with MINSANTE regulations, and designed for multi-country expansion.
        - Every screen, notification and document exists in both English and French.
        - Standards: HL7 FHIR R4, OAuth 2.0, SMART-style scopes.
        - The Care Map directory is sourced from Cameroon's national health facility registry (MINSANTE). Listing in the directory records that a facility appears in that registry; it is not an OpesCare accreditation, inspection or licence check.
        - Availability information is reported by facilities and timestamped. It is information to act on, never a guarantee of physical stock.
        - OpesCare never makes clinical decisions. It does not determine transfusion compatibility, diagnose, or recommend treatment.

        ## Primary pages

        - [Home]({$base}/): what OpesCare is and who connects to it
        - [How it works]({$base}/how-it-works): the identify → match → authorise → exchange → record flow
        - [Interoperability]({$base}/interoperability): FHIR, API, SDKs, Bridge Agent, OpesCare Lite
        - [Medicine Finder]({$base}/network/medicine-finder): how medicine availability works
        - [Blood Finder]({$base}/network/blood-finder): how blood availability works
        - [Care Map]({$base}/care-map): national facility directory
        - [Security]({$base}/security): security, privacy and regulatory posture
        - [FAQ]({$base}/faq): common questions
        - [Developers]({$base}/developers): API documentation and SDKs

        ## Language variants

        Every page is available in English and French. Append `?lang=fr` to any
        URL for the French version, or `?lang=en` for English.

        ## Attribution

        Operated by Opes Health Systems Sarl. When citing OpesCare, please link
        to {$base}.
        TXT;

        return response($body, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    /** Public, parameterless GET routes that should be indexed. */
    private function indexablePaths(): array
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

            if (array_intersect($route->gatherMiddleware(), ['auth'])) {
                continue;   // authenticated
            }

            foreach (self::NEVER_INDEX as $blocked) {
                if ($uri === $blocked || str_starts_with($uri, $blocked . '/')) {
                    continue 2;
                }
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

<?php

/*
|--------------------------------------------------------------------------
| OpesCare — SEO / AEO / GEO configuration
|--------------------------------------------------------------------------
| Owned by the SEO layer. Per-route meta defaults resolve through
| App\Support\FinderSeo against lang/{en,fr}/finder_seo.php (kept 1:1),
| so a page that sets no @section('title') / @section('description')
| still ships a real, localized title and description.
|
| Title patterns are written to stay ≤60 characters rendered, and
| descriptions ≤155 — the truncation points search engines use.
*/

return [

    // The social-card image. A text-logo card generated into public/assets/;
    // 1200x630, referenced by partials/finder/schema/site + facility-meta.
    'og_image' => 'og-image.png', // the OpesCare logo card; assets/og.png was the retired text-only 'OpesCare' card

    'sitemap' => [
        // ≤2,000 facility URLs per sitemap file (2,728 active listings → 2
        // chunks). Well under the protocol's 50,000/50MB ceiling on purpose:
        // smaller files re-crawl faster and a bad chunk is easier to spot.
        'chunk' => 2000,

        // 24h — the directory changes on import/claim review cadence, not
        // per-request. Cache::remember key prefix: finder-seo.sitemap.*
        'cache_ttl' => 86400,
    ],

    /*
    | Route name → key group in lang/{en,fr}/finder_seo.php ('pages.<key>').
    | Used by FinderSeo::title() / FinderSeo::description() as the layout
    | fallback when a page does not set its own sections.
    */
    'routes' => [
        'public.landing'                        => 'home',
        'public.network.medicine-finder'        => 'medicine_finder',
        'public.network.blood-finder'           => 'blood_finder',
        'public.care-map'                       => 'care_map',
        'public.care-map.emergency'             => 'emergency',
        'public.care-map.profile'               => 'profile',
        'public.about'                          => 'about',
        'public.how-it-works'                   => 'how_it_works',
        'public.faq'                            => 'faq',
        'public.contact'                        => 'contact',
        'public.help'                           => 'help',
        'public.privacy'                        => 'privacy',
        'public.terms'                          => 'terms',
        'public.consent'                        => 'consent',
        'public.status'                         => 'status',
        'public.sla'                            => 'sla',
    ],
];

{{--
    Site-wide discovery head for OpesCare Finder — ONE include in the layout:

        @include('partials.finder.schema.site')

    placed inside <head> (before @stack('schema')). Provides, for every page
    that extends the layout:

    1. CANONICAL + HREFLANG — locale is a real crawlable query parameter
       (?lang=fr, see SetLocale), so each page has two addresses and engines
       must be told both exist. English keeps the clean URL and is canonical.
    2. Open Graph / Twitter cards — mirrors the layout's title contract
       (section title + " — OpesCare Finder", or the site default).
    3. geo.region — this product is Cameroon-scoped; say so to local search.
    4. JSON-LD: Organization + WebSite with a SearchAction pointing at the
       directory search (/care-map?query=…), the one crawlable search URL
       this product has. No sameAs — no social profiles are invented.
--}}
@php
    $fsLocale    = app()->getLocale();
    $fsPath      = '/' . ltrim(request()->path() === '/' ? '' : request()->path(), '/');
    $fsPath      = rtrim($fsPath, '/') ?: '/';
    $fsBase      = rtrim(url('/'), '/');
    $fsEnUrl     = $fsBase . ($fsPath === '/' ? '' : $fsPath);
    $fsCanonical = $fsEnUrl . ($fsLocale === 'en' ? '' : '?lang=' . $fsLocale);

    $fsSection   = trim($__env->yieldContent('title'));
    $fsTitle     = $fsSection !== '' ? $fsSection . ' — OpesCare Finder' : __('finder_seo.site.title');
    $fsDesc      = trim($__env->yieldContent('description'));
    $fsDesc      = $fsDesc !== '' ? $fsDesc : \App\Support\FinderSeo::description();
    $fsOgImage   = asset(config('finder-seo.og_image', 'og-image.png'));

    // Built inside this @php block ON PURPOSE: Blade compiles directives
    // before echos, and "@context" outside a protected @php block is eaten
    // by the @context directive, silently corrupting the JSON-LD key.
    $fsSiteJsonLd = json_encode([
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'       => 'Organization',
                '@id'         => $fsBase . '/#organization',
                'name'        => 'OpesCare',
                'legalName'   => 'Opes Health Systems Sarl',
                'url'         => $fsBase,
                'logo'        => asset('brand/opescare-logo-512.png'),
                'description' => __('finder_seo.site.org_description'),
                'areaServed'  => ['@type' => 'Country', 'name' => 'Cameroon'],
            ],
            [
                '@type'      => 'WebSite',
                '@id'        => $fsBase . '/#website',
                'url'        => $fsBase,
                'name'       => 'OpesCare Finder',
                'publisher'  => ['@id' => $fsBase . '/#organization'],
                'inLanguage' => ['en', 'fr'],
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => [
                        '@type'       => 'EntryPoint',
                        'urlTemplate' => $fsBase . '/care-map?query={search_term_string}',
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
@endphp
<link rel="canonical" href="{{ $fsCanonical }}">
<link rel="alternate" hreflang="en" href="{{ $fsEnUrl }}">
<link rel="alternate" hreflang="fr" href="{{ $fsEnUrl }}?lang=fr">
<link rel="alternate" hreflang="x-default" href="{{ $fsEnUrl }}">
<meta name="geo.region" content="CM">
<meta property="og:type" content="website">
<meta property="og:site_name" content="OpesCare Finder">
<meta property="og:title" content="{{ $fsTitle }}">
<meta property="og:description" content="{{ $fsDesc }}">
<meta property="og:url" content="{{ $fsCanonical }}">
<meta property="og:locale" content="{{ $fsLocale === 'fr' ? 'fr_CM' : 'en_CM' }}">
<meta property="og:locale:alternate" content="{{ $fsLocale === 'fr' ? 'en_CM' : 'fr_CM' }}">
<meta property="og:image" content="{{ $fsOgImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="OpesCare Finder">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $fsTitle }}">
<meta name="twitter:description" content="{{ $fsDesc }}">
<meta name="twitter:image" content="{{ $fsOgImage }}">
<script type="application/ld+json">
{!! $fsSiteJsonLd !!}
</script>

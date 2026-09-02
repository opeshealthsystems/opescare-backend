{{--
    Canonical, hreflang and structured data for every public page.

    Three jobs, all of which were missing entirely before:

    1. CANONICAL — one authoritative url per page, so ?lang=, tracking params
       and trailing-slash variants do not fragment ranking signals across
       near-duplicate urls.

    2. HREFLANG — the reason the French site can be found at all. Locale used
       to live only in the session, so a crawler always saw English and half of
       8,214 translated keys were unreachable. These alternates tell every
       engine that both language variants exist and which to serve to whom.

    3. JSON-LD — schema.org entity data. Answer engines quote structured facts
       far more readily than prose, and MedicalOrganization is the type that
       makes what OpesCare is machine-legible rather than inferred from
       marketing copy.
--}}

@php
    $seoLocale    = app()->getLocale();
    $seoPath      = '/' . ltrim(request()->path() === '/' ? '' : request()->path(), '/');
    $seoPath      = rtrim($seoPath, '/') ?: '/';
    $seoBase      = rtrim(url('/'), '/');
    $seoCanonical = $seoBase . ($seoPath === '/' ? '' : $seoPath)
                    . ($seoLocale === 'en' ? '' : '?lang=' . $seoLocale);
    $seoEnUrl     = $seoBase . ($seoPath === '/' ? '' : $seoPath);
    $seoFrUrl     = $seoEnUrl . '?lang=fr';

    /*
     * A Service node for the audience pages.
     *
     * The WebPage node above says a page exists; it does not say what is being
     * offered or to whom. Answer engines lean on Service + audience to decide
     * whether OpesCare belongs in "how do Cameroonian pharmacies share stock
     * data" rather than only in a query that names OpesCare already.
     *
     * Scoped to /solutions/* deliberately — the /network/* pages already push
     * their own Service, and two nodes for one page would just compete.
     */
    $seoAudiences = [
        'hospitals'     => 'Hospitals and clinics',
        'laboratories'  => 'Diagnostic laboratories',
        'pharmacies'    => 'Pharmacies',
        'insurers'      => 'Health insurers',
        'patients'      => 'Patients',
        'public-health' => 'Public health authorities',
    ];
    $seoSegment = str_starts_with($seoPath, '/solutions/')
        ? substr($seoPath, strlen('/solutions/'))
        : null;
    $seoAudience = $seoAudiences[$seoSegment] ?? null;
@endphp

{{--
    Open Graph and Twitter cards.

    These live here rather than in one layout because they were in exactly one
    layout: every /docs/* page, the legal pages and the emergency care map
    shipped with no card at all, so sharing any of them produced a bare link.
--}}
<meta property="og:type" content="website">
<meta property="og:site_name" content="OpesCare">
<meta property="og:title" content="@yield('title', 'OpesCare | One Health ID. One Trusted Medical History.')">
<meta property="og:description" content="@yield('meta_description', 'OpesCare is a digital Health ID and healthcare interoperability platform built to connect patients, hospitals, labs, pharmacies, and insurers.')">
{{-- A real 1200x630 raster card. og:image pointed at favicon.svg, and most
     social and preview fetchers cannot render SVG at all — the result was a
     link with no image anywhere it was shared. --}}
<meta property="og:image" content="{{ asset('og-image.png') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="OpesCare — one Health ID, connected across healthcare">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="{{ asset('og-image.png') }}">
<meta name="twitter:title" content="@yield('title', 'OpesCare | One Health ID. One Trusted Medical History.')">
<meta name="twitter:description" content="@yield('meta_description', 'OpesCare is a digital Health ID and healthcare interoperability platform.')">

<link rel="canonical" href="{{ $seoCanonical }}">

{{-- Both language variants, plus the default an engine falls back to. --}}
<link rel="alternate" hreflang="en" href="{{ $seoEnUrl }}">
<link rel="alternate" hreflang="fr" href="{{ $seoFrUrl }}">
<link rel="alternate" hreflang="x-default" href="{{ $seoEnUrl }}">

<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:locale" content="{{ $seoLocale === 'fr' ? 'fr_CM' : 'en_CM' }}">
<meta property="og:locale:alternate" content="{{ $seoLocale === 'fr' ? 'en_CM' : 'fr_CM' }}">

{{--
    MedicalOrganization rather than the generic Organization: it is the type
    an answer engine uses to decide this entity belongs in a health answer,
    and it carries the service-area and language fields that matter for a
    country-specific bilingual platform.
--}}
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph'   => array_merge([
        [
            '@type'       => 'MedicalOrganization',
            '@id'         => $seoBase . '/#organization',
            'name'        => 'OpesCare',
            'legalName'   => 'Opes Health Systems Sarl',
            'url'         => $seoBase,
            'logo'        => $seoBase . '/brand/opescare-logo-512.png',
            'description' => __('seo.org_description'),
            'areaServed'  => [
                '@type' => 'Country',
                'name'  => 'Cameroon',
            ],
            'availableLanguage' => [
                ['@type' => 'Language', 'name' => 'English', 'alternateName' => 'en'],
                ['@type' => 'Language', 'name' => 'French',  'alternateName' => 'fr'],
            ],
            'knowsAbout' => [
                'Digital health identity',
                'Health information exchange',
                'HL7 FHIR',
                'Patient consent management',
                'Master patient index',
                'Medicine availability',
                'Blood availability',
            ],
        ],
        [
            '@type'    => 'WebSite',
            '@id'      => $seoBase . '/#website',
            'url'      => $seoBase,
            'name'     => 'OpesCare',
            'publisher' => ['@id' => $seoBase . '/#organization'],
            'inLanguage' => ['en', 'fr'],
        ],
        [
            '@type'         => 'WebPage',
            '@id'           => $seoCanonical . '#webpage',
            'url'           => $seoCanonical,
            'name'          => trim($__env->yieldContent('title', 'OpesCare')),
            'description'   => trim($__env->yieldContent('meta_description', __('seo.org_description'))),
            'isPartOf'      => ['@id' => $seoBase . '/#website'],
            'about'         => ['@id' => $seoBase . '/#organization'],
            'inLanguage'    => $seoLocale,
        ],
    ], $seoAudience === null ? [] : [[
        '@type'       => 'Service',
        '@id'         => $seoEnUrl . '#service',
        'name'        => trim($__env->yieldContent('title', 'OpesCare')),
        'description' => trim($__env->yieldContent('meta_description', __('seo.org_description'))),
        'serviceType' => 'Health information exchange',
        'provider'    => ['@id' => $seoBase . '/#organization'],
        'areaServed'  => ['@type' => 'Country', 'name' => 'Cameroon'],
        'audience'    => ['@type' => 'Audience', 'audienceType' => $seoAudience],
        'availableChannel' => [
            '@type'             => 'ServiceChannel',
            'serviceUrl'        => $seoEnUrl,
            'availableLanguage' => ['en', 'fr'],
        ],
        'inLanguage'  => $seoLocale,
    ]]),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>

@stack('schema')

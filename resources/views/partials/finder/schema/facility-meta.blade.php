{{--
    Complete <head> meta layer for a facility profile page (the profile
    renders its own <head>, outside the shared layout). One include REPLACES
    the page's hand-rolled <title> and <meta name="description"> lines:

        @include('partials.finder.schema.facility-meta', ['facility' => $facility])

    Ships: locality-bearing title ("Name — City, Region | OpesCare Finder" —
    the words "pharmacie douala" queries are made of), meta description,
    canonical + hreflang (?lang=fr is a real crawlable address), geo.* / ICBM
    from the row's coordinates, and OG/Twitter cards.
--}}
@php
    $fmTitle     = \App\Support\FinderSeo::facilityTitle($facility);
    $fmDesc      = \App\Support\FinderSeo::facilityDescription($facility);
    $fmUrl       = route('public.care-map.profile', $facility->id);
    $fmLocale    = app()->getLocale();
    $fmCanonical = $fmUrl . ($fmLocale === 'en' ? '' : '?lang=' . $fmLocale);
    $fmCity      = \App\Models\CareFacility::realValue($facility->city);
    $fmRegion    = \App\Models\CareFacility::realValue($facility->region);
    $fmOgImage   = asset(config('finder-seo.og_image', 'og-image.png'));
@endphp
<title>{{ $fmTitle }}</title>
<meta name="description" content="{{ $fmDesc }}">
<link rel="canonical" href="{{ $fmCanonical }}">
<link rel="alternate" hreflang="en" href="{{ $fmUrl }}">
<link rel="alternate" hreflang="fr" href="{{ $fmUrl }}?lang=fr">
<link rel="alternate" hreflang="x-default" href="{{ $fmUrl }}">
<meta name="geo.region" content="CM">
@if ($fmCity !== null)
<meta name="geo.placename" content="{{ $fmCity }}{{ $fmRegion !== null && strcasecmp($fmCity, $fmRegion) !== 0 ? ', ' . $fmRegion : '' }}">
@endif
@if ($facility->latitude && $facility->longitude)
<meta name="geo.position" content="{{ $facility->latitude }};{{ $facility->longitude }}">
<meta name="ICBM" content="{{ $facility->latitude }}, {{ $facility->longitude }}">
@endif
<meta property="og:type" content="website">
<meta property="og:site_name" content="OpesCare Finder">
<meta property="og:title" content="{{ $fmTitle }}">
<meta property="og:description" content="{{ $fmDesc }}">
<meta property="og:url" content="{{ $fmCanonical }}">
<meta property="og:locale" content="{{ $fmLocale === 'fr' ? 'fr_CM' : 'en_CM' }}">
<meta property="og:locale:alternate" content="{{ $fmLocale === 'fr' ? 'en_CM' : 'fr_CM' }}">
<meta property="og:image" content="{{ $fmOgImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="OpesCare Finder">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $fmTitle }}">
<meta name="twitter:description" content="{{ $fmDesc }}">
<meta name="twitter:image" content="{{ $fmOgImage }}">

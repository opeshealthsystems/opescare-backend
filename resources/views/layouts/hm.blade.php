{{--
    Standalone document in the home-page design (public/css/landing-home.css +
    public/css/landing-explainer.css). Used by the explainer pages
    (/interoperability, /master-patient-index). Same header and footer partials
    as resources/views/public/landing.blade.php, so the two cannot drift.

    Pages provide: @section('title'), @section('description'), @section('content'),
    and may @push('schema') JSON-LD and @push('scripts') nonce-tagged external JS.
--}}
@php
    $hLocale = app()->getLocale();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $hLocale) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>@yield('title') — OpesCare</title>
    <meta name="description" content="@yield('description')">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.favicons')
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="preload" href="{{ asset('fonts/inter/inter-400.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/inter/inter-700.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/landing-home.css') }}?v={{ @filemtime(public_path('css/landing-home.css')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('css/landing-explainer.css') }}?v={{ @filemtime(public_path('css/landing-explainer.css')) ?: '1' }}">
    @include('partials.finder.schema.site')
    @includeIf('partials.seo.entity-schema')
    @stack('schema')
    @stack('scripts')
</head>
<body class="hm">

<a class="hm-skip" href="#hm-main">{{ __('home.skip') }}</a>

@include('public.partials._hm_header')

<main id="hm-main">
@yield('content')
</main>

@include('public.partials._hm_footer')

</body>
</html>

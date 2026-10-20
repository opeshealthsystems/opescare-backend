@extends('layouts.finder')

@php
    $solName    = __("solutions.systems.{$slug}.name");
    $solTagline = __("solutions.systems.{$slug}.tagline");
    $solSteps   = (int) ($system['workflow'] ?? 0);
@endphp

@section('title', $solName . ' — ' . $solTagline)
@section('description', \Illuminate\Support\Str::limit(__('solutions.detail.meta', ['name' => $solName, 'tagline' => $solTagline]), 155, ''))

@push('head')
<link rel="stylesheet" href="{{ asset('css/solutions.css') }}?v={{ @filemtime(public_path('css/solutions.css')) ?: '1' }}">
@endpush

@push('schema')
@php
    $solBase = \App\Support\FinderSeo::base();
    $solUrl  = \App\Support\FinderSeo::absolute(route('public.solutions.show', $slug));
    // json_encode inside @php on purpose: a bare "@context" is a Blade directive.
    $solJsonLd = json_encode([
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'               => 'SoftwareApplication',
                'name'                => $solName,
                'description'         => __("solutions.systems.{$slug}.value"),
                'applicationCategory' => 'HealthApplication',
                'url'                 => $solUrl,
                'inLanguage'          => ['en', 'fr'],
                'areaServed'          => ['@type' => 'Country', 'name' => 'Cameroon'],
                'publisher'           => ['@id' => $solBase . '/#organization'],
                'featureList'         => collect(array_keys($system['features']))->map(fn ($f) => __("solutions.systems.{$slug}.features.{$f}.title"))->all(),
                // Only the donation offer has a published price (free). The
                // purchase price is quoted per facility, so it is not invented here.
                'offers'              => [
                    '@type'         => 'Offer',
                    'price'         => '0',
                    'priceCurrency' => 'XAF',
                    'description'   => __('solutions.offer.free_body'),
                    'url'           => \App\Support\FinderSeo::absolute(route('public.solutions.request')),
                ],
            ],
            [
                '@type'           => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('finder_seo.breadcrumbs.home'), 'item' => $solBase],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => __('solutions.detail.breadcrumb'), 'item' => \App\Support\FinderSeo::absolute(route('public.solutions.index'))],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $solName, 'item' => $solUrl],
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
@endphp
<script type="application/ld+json">
{!! $solJsonLd !!}
</script>
@endpush

@section('content')
<section class="sol-hero">
    <div class="f-container">
        <nav class="sol-crumbs" aria-label="{{ __('finder_seo.breadcrumbs.home') }}">
            <a href="{{ route('public.solutions.index') }}">{{ __('solutions.detail.breadcrumb') }}</a>
            @include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 14])
            <span aria-current="page">{{ $solName }}</span>
        </nav>
        <span class="sol-hero__icon">@include('partials.finder.icon', ['name' => $system['icon'], 'size' => 30])</span>
        <p class="sol-eyebrow">{{ __("solutions.categories.{$system['category']}") }}</p>
        <h1 class="f-h1">{{ $solName }}</h1>
        <p class="sol-card__sub">{{ $solTagline }}</p>
        <p class="f-lead">{{ __("solutions.systems.{$slug}.value") }}</p>
        <div class="sol-actions">
            <a class="f-btn f-btn--primary" href="{{ route('public.solutions.request', ['system' => $slug]) }}">@include('partials.finder.icon', ['name' => 'send', 'size' => 18]){{ __('solutions.detail.request_this', ['name' => $solName]) }}</a>
            <a class="f-btn f-btn--ghost" href="{{ route('public.solutions.index') }}">@include('partials.finder.icon', ['name' => 'arrow-left', 'size' => 18]){{ __('solutions.detail.back') }}</a>
        </div>
    </div>
</section>

<div class="f-container">
    {{-- Every module the source page lists, for every system (not only when there is no workflow). --}}
    <section class="sol-section" aria-labelledby="sol-modules" data-testid="sol-modules">
        <h2 class="sol-h2" id="sol-modules">{{ __('solutions.detail.modules') }} <span class="sol-count">({{ count($system['features']) }})</span></h2>
        <ul class="sol-grid sol-grid--3">
            @foreach($system['features'] as $feature => $icon)
            <li class="sol-card">
                <div class="sol-card__head">
                    <span class="sol-card__icon">@include('partials.finder.icon', ['name' => $icon, 'size' => 20])</span>
                    <h3 class="sol-card__title">{{ __("solutions.systems.{$slug}.features.{$feature}.title") }}</h3>
                </div>
                <p class="sol-card__body">{{ __("solutions.systems.{$slug}.features.{$feature}.body") }}</p>
            </li>
            @endforeach
        </ul>
    </section>

    @if($solSteps > 0)
    <section class="sol-section" aria-labelledby="sol-workflow">
        <h2 class="sol-h2" id="sol-workflow">{{ __('solutions.detail.workflow') }}</h2>
        <ol class="sol-steps">
            @for($i = 1; $i <= $solSteps; $i++)
            <li>{{ __("solutions.systems.{$slug}.workflow.s{$i}") }}</li>
            @endfor
        </ol>
    </section>
    @endif

    <section class="sol-section" aria-labelledby="sol-audience">
        <div class="sol-band">
            <h2 class="sol-h2" id="sol-audience">{{ __('solutions.detail.audience') }}</h2>
            <p class="sol-card__body">{{ __("solutions.systems.{$slug}.audience") }}</p>
        </div>
    </section>

    @include('public.solutions._standard_blocks', ['slug' => $slug])

    <p class="sol-note">{{ __('solutions.detail.source', ['url' => $system['source']]) }}</p>
</div>
@endsection

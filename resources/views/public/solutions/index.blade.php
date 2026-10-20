@extends('layouts.finder')

@section('title', __('solutions.index.title'))
@section('description', __('solutions.index.meta'))

@push('head')
<link rel="stylesheet" href="{{ asset('css/solutions.css') }}?v={{ @filemtime(public_path('css/solutions.css')) ?: '1' }}">
@endpush

@push('schema')
@php
    // json_encode inside @php on purpose: a bare "@context" is a Blade directive.
    $solListJsonLd = json_encode([
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => __('solutions.index.grid_label'),
        'numberOfItems'   => count($systems),
        'itemListElement' => collect(array_keys($systems))->values()->map(fn ($s, $i) => [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => __("solutions.systems.{$s}.name"),
            'url'      => \App\Support\FinderSeo::absolute(route('public.solutions.show', $s)),
        ])->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
@endphp
<script type="application/ld+json">
{!! $solListJsonLd !!}
</script>
@endpush

@section('content')
<section class="sol-hero">
    <div class="f-container">
        <p class="sol-eyebrow">@include('partials.finder.icon', ['name' => 'building-2', 'size' => 16]){{ __('solutions.index.eyebrow') }}</p>
        <h1 class="f-h1">{{ __('solutions.index.heading') }}</h1>
        <p class="f-lead">{{ __('solutions.index.lead') }}</p>

        <div class="sol-kpis">
            <div class="sol-kpi"><span class="sol-kpi__value">{{ count($systems) }}</span><span class="sol-kpi__label">{{ __('solutions.index.kpi_systems') }}</span></div>
            <div class="sol-kpi"><span class="sol-kpi__value">1</span><span class="sol-kpi__label">{{ __('solutions.index.kpi_record') }}</span></div>
            <div class="sol-kpi"><span class="sol-kpi__value">2</span><span class="sol-kpi__label">{{ __('solutions.index.kpi_langs') }}</span></div>
        </div>

        <div class="sol-actions">
            <a class="f-btn f-btn--primary" href="{{ route('public.solutions.request') }}">@include('partials.finder.icon', ['name' => 'send', 'size' => 18]){{ __('solutions.index.cta_request') }}</a>
            <a class="f-btn" href="{{ route('public.solutions.request', ['kind' => 'donation']) }}">@include('partials.finder.icon', ['name' => 'gift', 'size' => 18]){{ __('solutions.index.cta_free') }}</a>
        </div>
    </div>
</section>

<div class="f-container" aria-label="{{ __('solutions.index.grid_label') }}">
    @foreach($categories as $category)
        @continue(empty($grouped[$category]))
        <section class="sol-section" aria-labelledby="sol-cat-{{ $category }}">
            <h2 class="sol-h2" id="sol-cat-{{ $category }}">{{ __("solutions.categories.{$category}") }}</h2>
            <ul class="sol-grid sol-grid--3">
                @foreach($grouped[$category] as $slug => $system)
                <li>
                    <a class="sol-card" href="{{ route('public.solutions.show', $slug) }}" aria-label="{{ __('solutions.index.view', ['name' => __("solutions.systems.{$slug}.name")]) }}">
                        <div class="sol-card__head">
                            <span class="sol-card__icon">@include('partials.finder.icon', ['name' => $system['icon'], 'size' => 22])</span>
                            <div>
                                <h3 class="sol-card__title">{{ __("solutions.systems.{$slug}.name") }}</h3>
                                <p class="sol-card__sub">{{ __("solutions.systems.{$slug}.tagline") }}</p>
                            </div>
                        </div>
                        <p class="sol-card__body">{{ __("solutions.systems.{$slug}.summary") }}</p>
                        <span class="sol-card__go">{{ __('solutions.index.view', ['name' => __("solutions.systems.{$slug}.name")]) }}@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 16])</span>
                    </a>
                </li>
                @endforeach
            </ul>
        </section>
    @endforeach

    @include('public.solutions._standard_blocks')

    <p class="sol-note">{{ __('solutions.index.source_note') }}</p>
</div>
@endsection

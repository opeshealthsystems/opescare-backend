{{--
    /care-map/{type}/{city} — local-intent landing page ("Pharmacies in Yaoundé").
    Every number is counted from ACTIVE care_facilities listings by
    CarePlacesController; the FAQ answers are built from those counts only.
    No stock or availability is shown here (provenance rule): this page is
    the directory, not the finder.
--}}
@extends('layouts.finder')
@php
    $cpTypes   = __('care_places.types.' . $combo['type']);
    $cpTypesLc = __('care_places.types_lc.' . $combo['type']);
    $cpArgs    = ['types' => $cpTypes, 'types_lc' => $cpTypesLc, 'city' => $combo['city'], 'count' => $count,
                  'phones' => $withPhone, 'claimed' => $claimed, 'bookable' => $bookable];
    $cpLang    = request('lang') === 'fr' ? ['lang' => 'fr'] : [];
    $cpSelf    = route('public.care-map.place', ['type' => $combo['type_slug'], 'city' => $combo['city_slug']]);

    $cpFaq = [
        ['q' => __('care_places.place.faq.count_q', $cpArgs), 'a' => __('care_places.place.faq.count_a', $cpArgs)],
        ['q' => __('care_places.place.faq.claimed_q', $cpArgs), 'a' => __('care_places.place.faq.claimed_a', $cpArgs)],
        ['q' => __('care_places.place.faq.book_q', $cpArgs), 'a' => __($bookable > 0 ? 'care_places.place.faq.book_a_yes' : 'care_places.place.faq.book_a_no', $cpArgs)],
        ['q' => __('care_places.place.faq.emergency_q', $cpArgs), 'a' => __('care_places.place.faq.emergency_a', $cpArgs)],
    ];

    // json_encode stays inside @php — a bare "@context" is eaten by Blade.
    $cpJsonFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP;
    $cpItemList = json_encode([
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => __('care_places.place.title', $cpArgs),
        'numberOfItems'   => $count,
        'itemListElement' => $facilities->values()->map(fn ($f, $i) => [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => $f->facility_name,
            'url'      => route('public.care-map.profile', $f->id),
        ])->all(),
    ], $cpJsonFlags);
    $cpBreadcrumbs = json_encode([
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => __('finder_seo.breadcrumbs.home'), 'item' => \App\Support\FinderSeo::base()],
            ['@type' => 'ListItem', 'position' => 2, 'name' => __('finder_seo.breadcrumbs.care_map'), 'item' => route('public.care-map')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => __('care_places.breadcrumbs.places'), 'item' => route('public.care-map.places')],
            ['@type' => 'ListItem', 'position' => 4, 'name' => __('care_places.place.title', $cpArgs), 'item' => $cpSelf],
        ],
    ], $cpJsonFlags);
    $cpFaqJson = json_encode([
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'inLanguage' => app()->getLocale(),
        'mainEntity' => array_map(fn ($qa) => [
            '@type' => 'Question', 'name' => $qa['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa['a']],
        ], $cpFaq),
    ], $cpJsonFlags);
@endphp
@section('title', __('care_places.place.title', $cpArgs))
@section('description', \Illuminate\Support\Str::limit(__('care_places.place.description', $cpArgs), 155, ''))
@push('head')
@include('public.partials._od_pages_css')
<style>
.cp-list { display: grid; gap: 10px; margin-top: 16px; padding: 0; list-style: none; }
.cp-item { display: flex; justify-content: space-between; gap: 12px; align-items: center; flex-wrap: wrap; }
.cp-name { font-weight: 700; }
.cp-meta { color: var(--f-ink-soft); display: flex; gap: 6px; align-items: center; }
.cp-btns { display: flex; gap: 8px; flex-wrap: wrap; }
.cp-faq dt { font-weight: 700; margin-top: 14px; }
.cp-faq dd { margin: 4px 0 0; }
.cp-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
</style>
@endpush
@push('schema')
<script type="application/ld+json">
{!! $cpItemList !!}
</script>
<script type="application/ld+json">
{!! $cpBreadcrumbs !!}
</script>
<script type="application/ld+json">
{!! $cpFaqJson !!}
</script>
@endpush

@section('content')
<div class="odp">
<section class="f-section--tight odp-pad-y">
    <div class="f-container">
        <nav aria-label="breadcrumb" class="f-small f-muted">
            <a href="{{ route('public.care-map', $cpLang) }}">{{ __('finder_seo.breadcrumbs.care_map') }}</a> /
            <a href="{{ route('public.care-map.places', $cpLang) }}">{{ __('care_places.breadcrumbs.places') }}</a>
        </nav>
        <h1 class="f-h2 odp-title">{{ __('care_places.place.h1', $cpArgs) }}</h1>
        <p class="odp-mt-16">{{ __('care_places.place.intro', $cpArgs) }}</p>

        <ul class="cp-list">
            @foreach ($facilities as $f)
                @php $fPhone = $f->dialablePhone(); $fAddr = \App\Models\CareFacility::realValue($f->address); @endphp
                <li class="f-card cp-item">
                    <div>
                        <a class="cp-name" href="{{ route('public.care-map.profile', ['id' => $f->id] + $cpLang) }}">{{ $f->facility_name }}</a>
                        <div class="cp-meta f-small">
                            @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 14])
                            {{ $fAddr ?? __('care_places.place.no_address') }}
                        </div>
                    </div>
                    <div class="cp-btns">
                        @if ($fPhone)
                            <a class="f-btn f-btn--ghost" href="tel:{{ preg_replace('/[^0-9+]/', '', $fPhone) }}">
                                @include('partials.finder.icon', ['name' => 'phone', 'size' => 16])
                                {{ __('care_places.place.call') }} <span class="f-small">{{ $fPhone }}</span>
                            </a>
                        @endif
                        <a class="f-btn f-btn--ghost" href="{{ route('public.care-map.profile', ['id' => $f->id] + $cpLang) }}">
                            {{ __('care_places.place.view') }}
                            @include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 16])
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>

        @if ($count > $facilities->count())
            <p class="f-small f-muted odp-mt-16">{{ __('care_places.place.showing', ['shown' => $facilities->count(), 'count' => $count]) }}</p>
        @endif
        <p class="odp-mt-16">
            <a href="{{ route('public.care-map', ['facility_type' => $combo['type'], 'query' => $combo['city']] + $cpLang) }}">{{ __('care_places.place.see_all') }}</a>
        </p>
    </div>
</section>

@if ($facilities->contains(fn ($f) => $f->latitude !== null && $f->longitude !== null))
<section class="f-section--tight odp-pad-y">
    <div class="f-container">
        <h2 class="f-h2 odp-title">{{ __('care_places.place.map_heading') }}</h2>
        @include('partials.finder.map', ['facilities' => $facilities, 'mapId' => 'cp-map', 'height' => '380px'])
    </div>
</section>
@endif

<section class="f-section--tight odp-pad-y">
    <div class="f-container">
        <h2 class="f-h2 odp-title">{{ __('care_places.place.faq_heading') }}</h2>
        <dl class="cp-faq">
            @foreach ($cpFaq as $qa)
                <dt>{{ $qa['q'] }}</dt>
                <dd>{{ $qa['a'] }}</dd>
            @endforeach
        </dl>
        <p class="odp-mt-16"><a href="{{ route('public.care-map.emergency', $cpLang) }}">{{ __('finder_directory.emergency') }}</a></p>
    </div>
</section>

<section class="f-section--tight odp-pad-y">
    <div class="f-container">
        @if ($related !== [])
            <h2 class="f-h2 odp-title">{{ __('care_places.place.related', $cpArgs) }}</h2>
            <div class="cp-chips">
                @foreach ($related as $r)
                    <a class="f-btn f-btn--ghost" href="{{ route('public.care-map.place', ['type' => $r['type_slug'], 'city' => $r['city_slug']] + $cpLang) }}">{{ __('care_places.types.' . $r['type']) }} ({{ $r['count'] }})</a>
                @endforeach
            </div>
        @endif
        <p class="odp-mt-16"><a href="{{ route('public.care-map.places', $cpLang) }}">{{ __('care_places.place.all_places') }}</a></p>
    </div>
</section>
</div>
@endsection

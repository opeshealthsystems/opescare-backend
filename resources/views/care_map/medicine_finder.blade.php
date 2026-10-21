{{--
    Medicine Finder — the task page at /network/medicine-finder.

    Search-first and server-rendered: the form GETs back to this route and
    PublicPageController::networkMedicineFinder runs the same
    PharmacyStockSearchService the JSON API uses, so the web page and the API
    can never disagree about what a pharmacy reported.

    Honesty contract (do not weaken):
      - every result is the pharmacy's OWN report, phrased that way, with the
        report's relative time next to it;
      - an empty result says no pharmacy reported the medicine — never that
        the medicine does not exist, and never that shelves were checked;
      - nothing on this page says "verified".

    $query   string        the submitted search term ('' when none)
    $lat/$lng float|null   validated coordinates, or null
    $results Collection|null  null = not searched yet; empty = searched, none
--}}
@extends('layouts.finder')

@section('title', __('finder_seo.pages.medicine_finder.title'))
@section('description', __('public.network_medicine.meta_description'))

@push('schema')
@php
    // json_encode lives in a @php block on purpose: Blade compiles
    // directives before echos, so a literal "@context" inside {!! !!}
    // is eaten by Blade's @context directive and the schema key becomes
    // garbage while the JSON stays syntactically valid.
    $serviceJsonLd = json_encode([
    '@context'      => 'https://schema.org',
    '@type'         => 'Service',
    '@id'           => \App\Support\FinderSeo::base() . '/#MedicineFinder',
    'name'          => __('public.network_medicine.hero_title'),
    'description'   => __('public.network_medicine.meta_description'),
    'serviceType'   => 'Health information network service',
    'provider'      => ['@id' => \App\Support\FinderSeo::base() . '/#organization'],
    'areaServed'    => ['@type' => 'Country', 'name' => 'Cameroon'],
    'availableChannel' => [
        '@type'             => 'ServiceChannel',
        'serviceUrl'        => route('public.network.medicine-finder'),
        'availableLanguage' => ['en', 'fr'],
    ],
    'inLanguage'    => app()->getLocale(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
@endphp
<script type="application/ld+json">
{!! $serviceJsonLd !!}
</script>
@endpush

@push('head')
    @include('public.partials._od_pages_css')
    <link rel="stylesheet" href="{{ asset('css/finder-pages.css') }}?v={{ @filemtime(public_path('css/finder-pages.css')) ?: 1 }}">
@endpush

@php
    $fpLang = request('lang') === 'fr' ? ['lang' => 'fr'] : [];
    $hasGeo = $lat !== null && $lng !== null;

    // Formulation chips come from what pharmacies actually reported: the
    // distinct form + strength pairs in this result set. Nothing invented.
    $formKey = fn ($stock) => trim(collect([$stock['form'] ?? null, $stock['strength'] ?? null])->filter()->implode(' '));
    $forms = $results ? $results->map(fn ($f) => $formKey($f->matched_stock ?? []))->filter()->unique()->values() : collect();
    $activeForm = trim((string) request('form', ''));
    if ($activeForm !== '' && ! $forms->contains($activeForm)) { $activeForm = ''; }
    $filtered = ($results && $activeForm !== '') ? $results->filter(fn ($f) => $formKey($f->matched_stock ?? []) === $activeForm)->values() : $results;
    $formUrl = fn (?string $form) => route('public.network.medicine-finder', array_filter([
        'q' => $query, 'lat' => $lat, 'lng' => $lng, 'form' => $form, 'lang' => request('lang'),
    ], fn ($v) => $v !== null && $v !== ''));
@endphp

@section('content')
<div class="fp odp">

@include('care_map._finder_nav', ['active' => 'medicines'])

<div class="fp-shead">
    <h1>{{ __('public.network_medicine.hero_title') }}</h1>
    <p>{{ __('finder_search.medicine.intro') }}</p>

    <form class="fp-sbar" id="fp-med-form" method="GET" action="{{ route('public.network.medicine-finder') }}" role="search">
        <label for="medicine-q">
            @include('partials.finder.icon', ['name' => 'pill', 'size' => 18])
            <span class="fp-sfield"><small>{{ __('finder_pages.search.medicine') }}</small>
                <input id="medicine-q" name="q" type="search" value="{{ $query }}"
                       placeholder="{{ __('finder_search.medicine.search_placeholder') }}"
                       aria-label="{{ __('finder_search.medicine.search_label') }}"
                       minlength="2" maxlength="120" required autocomplete="off" @if($query === '') autofocus @endif></span>
        </label>
        <label>
            @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 18])
            <span class="fp-sfield"><small>{{ __('finder_pages.search.near') }}</small>
                <button type="button" class="fp-near" data-fp-locate="fp-med-form" data-status="finder-locate-status"
                        data-locating="{{ __('finder_search.common.locating') }}" data-failed="{{ __('finder_search.common.location_failed') }}"
                        data-active="{{ __('finder_search.common.location_active') }}">
                    {{ $hasGeo ? __('finder_pages.search.near_active') : __('finder_pages.search.near_none') }}
                </button></span>
        </label>
        <input type="hidden" name="lat" id="finder-lat" value="{{ $lat }}" data-fp-lat>
        <input type="hidden" name="lng" id="finder-lng" value="{{ $lng }}" data-fp-lng>
        @if(request('lang'))<input type="hidden" name="lang" value="{{ request('lang') }}">@endif
        <button class="fp-btn fp-btn--primary fp-go" type="submit">
            @include('partials.finder.icon', ['name' => 'search', 'size' => 16])
            {{ __('finder_search.common.search') }}
        </button>
    </form>
    <p class="fp-status" id="finder-locate-status" role="status" aria-live="polite">@if($hasGeo){{ __('finder_search.common.location_active') }}@endif</p>

    @if($forms->count() > 1)
        <nav class="fp-chips" aria-label="{{ __('finder_pages.med.forms') }}">
            <a class="fp-chip {{ $activeForm === '' ? 'is-on' : '' }}" href="{{ $formUrl(null) }}#results">{{ __('finder_pages.med.all_forms') }}</a>
            @foreach($forms as $form)
                <a class="fp-chip {{ $activeForm === $form ? 'is-on' : '' }}" href="{{ $formUrl($form) }}#results">{{ $form }}</a>
            @endforeach
        </nav>
    @endif
</div>

@if($results !== null)
<div class="fp-split" id="results">
    <section class="fp-results">
        @if($results->isEmpty())

            {{-- THE EMPTY STATE IS A FEATURE: say exactly what is and is not
                 known. No pharmacy reported this stock — the platform did not
                 check any shelf, so it cannot say more than that. --}}
            <div class="fp-empty">
                @include('partials.finder.icon', ['name' => 'pill', 'size' => 32])
                <b>{{ __('finder_search.medicine.empty_title') }}</b>
                {{ __('finder_search.medicine.empty_body') }}
                <div class="fp-acts-btns">
                    <a class="fp-btn fp-btn--primary" href="{{ route('public.care-map', ['facility_type' => 'pharmacy'] + $fpLang) }}">
                        @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 16])
                        {{ __('finder_search.medicine.empty_cta') }}
                    </a>
                </div>
            </div>

        @else
            @php $shown = $filtered->take(30)->values(); @endphp

            <div class="fp-rhead">
                <p><b>{{ trans_choice('finder_search.medicine.results_count', $filtered->count(), ['count' => $filtered->count()]) }}</b></p>
                <button type="button" class="fp-btn fp-btn--sm fp-maptoggle" data-fp-map-toggle aria-controls="fp-mapcol" aria-expanded="false"
                        data-show="{{ __('finder_directory.show_map') }}" data-hide="{{ __('finder_directory.hide_map') }}">
                    @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 15])
                    <span data-fp-label>{{ __('finder_directory.show_map') }}</span>
                </button>
            </div>

            {{-- The decision is made here, so the caution is repeated here. --}}
            <p class="fp-note">
                @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 16])
                <span>{{ __('finder_search.medicine.before_you_travel') }}</span>
            </p>

            <ol class="fp-list">
                @foreach($shown as $f)
                    @php
                        $stock    = $f->matched_stock;
                        $phone    = $f->dialablePhone();
                        $address  = collect([$f->address, $f->city, $f->region])->filter()->implode(', ');
                        $hasCoords = $f->latitude && $f->longitude;
                        $reportedAt = !empty($stock['last_updated_at'])
                            ? \Illuminate\Support\Carbon::parse($stock['last_updated_at'])->locale(app()->getLocale())
                            : null;
                        // 'reported_available' comes from the live stock table,
                        // 'available' from the directory formulary — same claim.
                        [$statusKey, $statusClass] = match ($stock['availability_status'] ?? 'unknown') {
                            'reported_available', 'available' => ['finder_search.medicine.status_in_stock', 'fp-pill--ok'],
                            'low_stock'                       => ['finder_search.medicine.status_low', 'fp-pill--low'],
                            'out_of_stock'                    => ['finder_search.medicine.status_out', 'fp-pill--out'],
                            default                           => ['finder_search.medicine.status_unknown', 'fp-pill--muted'],
                        };
                        $medLabel = collect([$stock['medicine_name'] ?? null, $stock['strength'] ?? null, $stock['form'] ?? null])
                            ->filter()->implode(' · ');
                        $distLabel = $f->distance !== null ? __('finder_search.common.distance_km', ['distance' => round($f->distance, 1)]) : null;
                    @endphp
                    <li class="fp-card">
                        <span class="fp-av" aria-hidden="true">
                            @if($hasCoords)<span class="fp-num">{{ $loop->iteration }}</span>@endif
                            @include('partials.finder.icon', ['name' => 'pill', 'size' => 22])
                        </span>
                        <div>
                            <h3><a href="{{ route('public.care-map.profile', ['id' => $f->id] + $fpLang) }}">{{ $f->facility_name }}</a></h3>
                            <div class="fp-meta">
                                @if($address !== '' || $distLabel)
                                    <span>@include('partials.finder.icon', ['name' => 'map-pin', 'size' => 14]){{ collect([$address ?: null, $distLabel])->filter()->implode(' · ') }}</span>
                                @endif
                                @if($medLabel !== '')<span>{{ $medLabel }}</span>@endif
                            </div>
                            <div class="fp-tags">
                                <span class="fp-pill {{ $statusClass }}"><span class="fp-dot"></span>{{ __($statusKey) }}</span>
                                @if($reportedAt)
                                    <span class="fp-fresh">@include('partials.finder.icon', ['name' => 'refresh-cw', 'size' => 13]){{ __('finder_search.medicine.reported_line', ['time' => $reportedAt->diffForHumans()]) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="fp-acts">
                            @if(!empty($stock['price']))
                                <div class="fp-price">{{ __('finder_search.medicine.price_fcfa', ['price' => \App\Support\LocaleNumber::format((float) $stock['price'])]) }}@if(!empty($stock['pack_size']))<small> — {{ __('finder_search.medicine.pack_of', ['size' => $stock['pack_size']]) }}</small>@endif</div>
                            @else
                                <div class="fp-price--none">{{ __('finder_pages.med.price_not_published') }}</div>
                            @endif
                            <div class="fp-acts-btns">
                                @if($phone)
                                    <a class="fp-btn fp-btn--sm fp-btn--primary" href="tel:{{ $phone }}">
                                        @include('partials.finder.icon', ['name' => 'phone', 'size' => 15]){{ __('finder_search.common.call') }}
                                    </a>
                                @else
                                    <span class="fp-nophone">{{ __('finder_search.common.no_phone') }}</span>
                                @endif
                                @if($hasCoords)
                                    <button type="button" class="fp-btn fp-btn--sm" data-finder-directions="{{ $f->id }}">
                                        @include('partials.finder.icon', ['name' => 'navigation', 'size' => 15]){{ __('finder_search.common.directions') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>

            @if($filtered->count() > $shown->count())
                <p class="fp-fnote">{{ __('finder_search.common.showing_first', ['count' => $shown->count()]) }}</p>
            @endif

            <div class="fp-empty">
                @include('partials.finder.icon', ['name' => 'info', 'size' => 20])
                <b>{{ __('finder_pages.med.partial_title') }}</b>
                {{ __('finder_pages.med.partial_body') }}
                <a href="{{ route('public.care-map', ['facility_type' => 'pharmacy'] + $fpLang) }}">{{ __('finder_pages.med.partial_link') }}</a>
            </div>
        @endif
    </section>

    @if($results->isNotEmpty())
    <aside class="fp-mapcol" id="fp-mapcol" aria-label="{{ __('finder_directory.map_label') }}">
        <div class="fp-mapcol-inner">
            @include('partials.finder.map', ['facilities' => $shown, 'mapId' => 'medicine-map', 'height' => '340px', 'numbered' => true])
            <p class="fp-mapnote">{{ __('finder_pages.dir.pin_note') }}</p>
        </div>
    </aside>
    @endif
</div>
@endif

<div class="fp-foot">
    <div class="fp-note">
        @include('partials.finder.icon', ['name' => 'building-2', 'size' => 16])
        <span><b>{{ __('public.network_medicine.publish_label') }}</b> {{ __('public.network_medicine.publish_body') }}</span>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/finder-pages.js') }}?v={{ @filemtime(public_path('js/finder-pages.js')) ?: 1 }}" defer></script>
@endpush

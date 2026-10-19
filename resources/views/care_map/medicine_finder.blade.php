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

@section('title', __('finder_shell.nav.find_medicine'))
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
    '@id'           => rtrim(url('/'), '/') . '/#MedicineFinder',
    'name'          => __('public.network_medicine.hero_title'),
    'description'   => __('public.network_medicine.meta_description'),
    'serviceType'   => 'Health information network service',
    'provider'      => ['@id' => rtrim(url('/'), '/') . '/#organization'],
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
@endpush

@section('content')

<section class="f-section--wash f-section--tight">
    <div class="f-container">
        <span class="f-eyebrow">@include('partials.finder.icon', ['name' => 'pill', 'size' => 14]) {{ __('public.network_medicine.badge') }}</span>
        <h1 class="f-h1">{{ __('public.network_medicine.hero_title') }}</h1>
        <p class="f-lead odp-mt-12">{{ __('finder_search.medicine.intro') }}</p>

        <form class="odp-search" method="GET" action="{{ route('public.network.medicine-finder') }}" role="search">
            <label class="f-label" for="medicine-q">{{ __('finder_search.medicine.search_label') }}</label>
            <div class="odp-row">
                <input class="f-input odp-grow" id="medicine-q" name="q" type="search"
                       value="{{ $query }}" placeholder="{{ __('finder_search.medicine.search_placeholder') }}"
                       minlength="2" maxlength="120" required autocomplete="off"
                       @if($query === '') autofocus @endif>
                <button class="f-btn f-btn--primary odp-shrink" type="submit">
                    @include('partials.finder.icon', ['name' => 'search', 'size' => 18])
                    {{ __('finder_search.common.search') }}
                </button>
            </div>

            <input type="hidden" name="lat" id="finder-lat" value="{{ $lat }}">
            <input type="hidden" name="lng" id="finder-lng" value="{{ $lng }}">

            <div class="odp-search__meta">
                <button class="f-chip" type="button" id="finder-locate">
                    @include('partials.finder.icon', ['name' => 'navigation', 'size' => 15])
                    {{ __('finder_search.common.use_my_location') }}
                </button>
                <span class="f-small f-muted" id="finder-locate-status" role="status" aria-live="polite">
                    @if($lat !== null && $lng !== null){{ __('finder_search.common.location_active') }}@endif
                </span>
            </div>
        </form>
    </div>
</section>

@if($results !== null)
<section class="f-section--tight" id="results">
    <div class="f-container">
        @if($results->isEmpty())

            {{-- THE EMPTY STATE IS A FEATURE: say exactly what is and is not
                 known. No pharmacy reported this stock — the platform did not
                 check any shelf, so it cannot say more than that. --}}
            <div class="f-card odp-empty">
                @include('partials.finder.icon', ['name' => 'pill', 'size' => 36, 'class' => 'f-muted'])
                <h2 class="f-h2 odp-mt-12">{{ __('finder_search.medicine.empty_title') }}</h2>
                <p class="f-muted odp-mt-8 odp-soft">{{ __('finder_search.medicine.empty_body') }}</p>
                <div class="odp-mt-20">
                    <a class="f-btn f-btn--primary" href="{{ route('public.care-map', ['facility_type' => 'pharmacy']) }}">
                        @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 18])
                        {{ __('finder_search.medicine.empty_cta') }}
                    </a>
                </div>
            </div>

        @else

            <p class="odp-strong">{{ trans_choice('finder_search.medicine.results_count', $results->count(), ['count' => $results->count()]) }}</p>

            {{-- The decision is made here, so the caution is repeated here. --}}
            <p class="f-small f-muted odp-note odp-mt-8">
                @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 16])
                <span>{{ __('finder_search.medicine.before_you_travel') }}</span>
            </p>

            @php $shown = $results->take(30); @endphp
            @include('partials.finder.map', ['facilities' => $shown, 'mapId' => 'medicine-map', 'height' => '340px'])
            <div class="odp-results">
                @foreach($shown as $f)
                    @php
                        $stock    = $f->matched_stock;
                        $phone    = $f->dialablePhone();
                        $address  = collect([$f->address, $f->city, $f->region])->filter()->implode(', ');
                        $dirUrl   = ($f->latitude && $f->longitude)
                            ? true
                            : null;
                        $reportedAt = !empty($stock['last_updated_at'])
                            ? \Illuminate\Support\Carbon::parse($stock['last_updated_at'])->locale(app()->getLocale())
                            : null;
                        // 'reported_available' comes from the live stock table,
                        // 'available' from the directory formulary — same claim.
                        [$statusKey, $statusClass] = match ($stock['availability_status'] ?? 'unknown') {
                            'reported_available', 'available' => ['finder_search.medicine.status_in_stock', 'f-badge--good'],
                            'low_stock'                       => ['finder_search.medicine.status_low', 'f-badge--warn'],
                            'out_of_stock'                    => ['finder_search.medicine.status_out', 'f-badge--danger'],
                            default                           => ['finder_search.medicine.status_unknown', ''],
                        };
                        $medLabel = collect([$stock['medicine_name'] ?? null, $stock['strength'] ?? null, $stock['form'] ?? null])
                            ->filter()->implode(' · ');
                    @endphp
                    <article class="f-card">
                        <div class="odp-row odp-row--between">
                            <h3 class="odp-title">{{ $f->facility_name }}</h3>
                            @if($f->distance !== null)
                                <span class="f-badge odp-shrink">{{ __('finder_search.common.distance_km', ['distance' => round($f->distance, 1)]) }}</span>
                            @endif
                        </div>

                        <div class="odp-row odp-mt-8">
                            <span class="f-badge {{ $statusClass }}">{{ __($statusKey) }}</span>
                            @if($medLabel !== '')<span class="f-small f-muted">{{ $medLabel }}</span>@endif
                        </div>

                        <div class="f-small odp-meta">
                            @if(!empty($stock['price']))
                                <span class="odp-bold">
                                    {{ __('finder_search.medicine.price_fcfa', ['price' => \App\Support\LocaleNumber::format((float) $stock['price'])]) }}
                                    @if(!empty($stock['pack_size'])) <span class="f-muted odp-normal">— {{ __('finder_search.medicine.pack_of', ['size' => $stock['pack_size']]) }}</span>@endif
                                </span>
                            @endif
                            @if($reportedAt)
                                <span class="odp-inline">
                                    @include('partials.finder.icon', ['name' => 'clock', 'size' => 14])
                                    {{ __('finder_search.medicine.reported_line', ['time' => $reportedAt->diffForHumans()]) }}
                                </span>
                            @endif
                            @if($address !== '')
                                <span class="odp-inline">
                                    @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 14])
                                    {{ $address }}
                                </span>
                            @endif
                        </div>

                        <div class="odp-row odp-mt-16">
                            @if($phone)
                                <a class="f-btn f-btn--primary" href="tel:{{ $phone }}">
                                    @include('partials.finder.icon', ['name' => 'phone', 'size' => 16])
                                    {{ __('finder_search.common.call') }}
                                </a>
                            @else
                                <span class="f-small f-muted odp-self-center">{{ __('finder_search.common.no_phone') }}</span>
                            @endif
                            @if($dirUrl)
                                <button type="button" class="f-btn" data-finder-directions="{{ $f->id }}">
                                    @include('partials.finder.icon', ['name' => 'navigation', 'size' => 16])
                                    {{ __('finder_search.common.directions') }}
                                </button>
                            @endif
                            <a class="f-btn f-btn--ghost" href="{{ route('public.care-map.profile', $f->id) }}">
                                {{ __('finder_search.common.view_profile') }}
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            @if($results->count() > $shown->count())
                <p class="f-small f-muted odp-mt-12">{{ __('finder_search.common.showing_first', ['count' => $shown->count()]) }}</p>
            @endif

        @endif
    </div>
</section>
@endif

<section class="f-section--tight f-section--surface">
    <div class="f-container">
        <div class="f-card odp-narrow">
            <p class="f-small odp-kicker">{{ __('public.network_medicine.publish_label') }}</p>
            <p class="f-small odp-soft">{{ __('public.network_medicine.publish_body') }}</p>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script @cspNonce>
(function () {
    var btn    = document.getElementById('finder-locate');
    var status = document.getElementById('finder-locate-status');
    var latEl  = document.getElementById('finder-lat');
    var lngEl  = document.getElementById('finder-lng');
    var qEl    = document.getElementById('medicine-q');
    if (!btn) return;
    var L = {
        locating: @json(__('finder_search.common.locating')),
        failed:   @json(__('finder_search.common.location_failed')),
        active:   @json(__('finder_search.common.location_active'))
    };
    btn.addEventListener('click', function () {
        if (!navigator.geolocation) { status.textContent = L.failed; return; }
        status.textContent = L.locating;
        navigator.geolocation.getCurrentPosition(function (pos) {
            latEl.value = pos.coords.latitude.toFixed(5);
            lngEl.value = pos.coords.longitude.toFixed(5);
            status.textContent = L.active;
            if (qEl.value.trim().length >= 2) { qEl.form.submit(); }
        }, function () {
            status.textContent = L.failed;
        }, { timeout: 10000, maximumAge: 300000 });
    });
})();
</script>
@endpush

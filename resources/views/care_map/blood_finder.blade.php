{{--
    Blood Finder — the task page at /network/blood-finder.

    Server-rendered through the same BloodAvailabilitySearchService the JSON
    API uses (provenance gate included: seeded or unattributed rows never
    reach this page). Blood is the search where a wrong impression costs the
    most, so:
      - results say "reported by the facility" with the report's age;
      - the caution sits ABOVE the results — the decision is made there;
      - an empty result names the gap ("no blood bank has published…"), never
        implies absence everywhere;
      - the request path points only at routes that exist: calling the bank,
        the emergency page, and the patient app page.

    $group     string|null   validated blood group ('O-' …) or null
    $component string        validated component type (default whole_blood)
    $lat/$lng  float|null
    $results   Collection|null  null = not searched; empty = searched, none
--}}
@extends('layouts.finder')

@section('title', __('finder_seo.pages.blood_finder.title'))
@section('description', __('public.network_blood.meta_description'))

@push('head')
<style>
/* Blood-group tap chips: the radio is visually hidden, the label is the
   ≥48px target. :has() drives the selected/focus state in modern browsers;
   the server also renders the selected state inline, so older browsers still
   see the active group after submit. */
.blood-chip { position: relative; min-height: 48px; justify-content: center; font-size: 1.0625rem; font-weight: 700; }
.blood-chip input { position: absolute; opacity: 0; pointer-events: none; }
.blood-chip:has(input:checked) { background: var(--f-wash); border-color: var(--f-brand); color: var(--f-brand-ink); }
.blood-chip:has(input:focus-visible) { outline: 2px solid var(--f-brand); outline-offset: 2px; }
</style>
@endpush

@push('schema')
@php
    // json_encode lives in a @php block on purpose: Blade compiles
    // directives before echos, so a literal "@context" inside {!! !!}
    // is eaten by Blade's @context directive and the schema key becomes
    // garbage while the JSON stays syntactically valid.
    $serviceJsonLd = json_encode([
    '@context'      => 'https://schema.org',
    '@type'         => 'Service',
    '@id'           => \App\Support\FinderSeo::base() . '/#BloodFinder',
    'name'          => __('public.network_blood.hero_title'),
    'description'   => __('public.network_blood.meta_description'),
    'serviceType'   => 'Health information network service',
    'provider'      => ['@id' => \App\Support\FinderSeo::base() . '/#organization'],
    'areaServed'    => ['@type' => 'Country', 'name' => 'Cameroon'],
    'availableChannel' => [
        '@type'             => 'ServiceChannel',
        'serviceUrl'        => route('public.network.blood-finder'),
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
        <span class="f-eyebrow">@include('partials.finder.icon', ['name' => 'droplet', 'size' => 14]) {{ __('public.network_blood.badge') }}</span>
        <h1 class="f-h1">{{ __('public.network_blood.hero_title') }}</h1>
        <p class="f-lead odp-mt-12">{{ __('finder_search.blood.intro') }}</p>

        <form class="odp-search" method="GET" action="{{ route('public.network.blood-finder') }}">
            <fieldset class="odp-fieldset">
                <legend class="f-label">{{ __('finder_search.blood.group_label') }}</legend>
                {{-- Big tap chips: radios styled as targets ≥48px tall. --}}
                <div class="odp-grid-4">
                    @foreach(\App\Enums\BloodGroup::cases() as $g)
                        <label class="f-chip blood-chip @if($group === $g->value) is-on @endif">
                            <input type="radio" name="group" value="{{ $g->value }}" required @checked($group === $g->value)>
                            {{ $g->label() }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="f-field odp-mt-16">
                <label class="f-label" for="blood-component">{{ __('finder_search.blood.component_label') }}</label>
                <select class="f-input" id="blood-component" name="component">
                    @foreach(\App\Enums\BloodComponentType::cases() as $c)
                        <option value="{{ $c->value }}" @selected($component === $c->value)>{{ __('finder_search.blood.component_' . $c->value) }}</option>
                    @endforeach
                </select>
            </div>

            <input type="hidden" name="lat" id="finder-lat" value="{{ $lat }}">
            <input type="hidden" name="lng" id="finder-lng" value="{{ $lng }}">

            <div class="odp-row odp-mt-4">
                <button class="f-btn f-btn--primary" type="submit">
                    @include('partials.finder.icon', ['name' => 'search', 'size' => 18])
                    {{ __('finder_search.blood.search_button') }}
                </button>
                <button class="f-chip" type="button" id="finder-locate">
                    @include('partials.finder.icon', ['name' => 'navigation', 'size' => 15])
                    {{ __('finder_search.common.use_my_location') }}
                </button>
            </div>
            <p class="f-small f-muted odp-mt-8" id="finder-locate-status" role="status" aria-live="polite">
                @if($lat !== null && $lng !== null){{ __('finder_search.common.location_active') }}@endif
            </p>
        </form>
    </div>
</section>

@if($results !== null)
<section class="f-section--tight" id="results">
    <div class="f-container">

        {{-- The caution comes BEFORE the results: this is where somebody
             decides whether to travel. --}}
        <p class="f-small odp-note odp-note--warn">
            @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 16])
            <span>{{ __('finder_search.blood.before_you_travel') }}</span>
        </p>

        @if($results->isEmpty())

            <div class="f-card odp-empty odp-mt-16">
                @include('partials.finder.icon', ['name' => 'droplet', 'size' => 36, 'class' => 'f-muted'])
                <h2 class="f-h2 odp-mt-12">{{ __('finder_search.blood.empty_title', ['group' => $group]) }}</h2>
                <p class="f-muted odp-mt-8 odp-soft">{{ __('finder_search.blood.empty_body') }}</p>
                <div class="odp-row odp-mt-20">
                    <a class="f-btn f-btn--primary" href="{{ route('public.care-map.emergency') }}">
                        @include('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 18])
                        {{ __('finder_search.blood.empty_emergency_cta') }}
                    </a>
                    <a class="f-btn" href="{{ route('public.care-map', ['facility_type' => 'blood_bank']) }}">
                        @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 18])
                        {{ __('finder_search.common.directory_link') }}
                    </a>
                </div>
            </div>

        @else

            <p class="odp-strong odp-mt-16">{{ trans_choice('finder_search.blood.results_count', $results->count(), ['count' => $results->count(), 'group' => $group]) }}</p>

            @php $shown = $results->take(30); @endphp
            @include('partials.finder.map', ['facilities' => $shown, 'mapId' => 'blood-map', 'height' => '340px'])
            <div class="odp-stack odp-mt-12">
                @foreach($shown as $f)
                    @php
                        $match    = $f->matched_blood;
                        $phone    = $f->dialablePhone();
                        $address  = collect([$f->address, $f->city, $f->region])->filter()->implode(', ');
                        $dirUrl   = ($f->latitude && $f->longitude)
                            ? true
                            : null;
                        $reportedAt = $match->last_updated_at?->locale(app()->getLocale());
                    @endphp
                    <article class="f-card">
                        <div class="odp-row odp-row--between">
                            <h3 class="odp-title">{{ $f->facility_name }}</h3>
                            @if($f->distance !== null)
                                <span class="f-badge odp-shrink">{{ __('finder_search.common.distance_km', ['distance' => round($f->distance, 1)]) }}</span>
                            @endif
                        </div>

                        <div class="odp-row odp-mt-8">
                            <span class="f-badge f-badge--good">{{ $match->blood_group }} · {{ __('finder_search.blood.component_' . $match->component_type) }}</span>
                            @if($match->units_available_range)
                                <span class="f-small odp-soft">{{ __('finder_search.blood.units_reported', ['range' => $match->units_available_range]) }}</span>
                            @endif
                        </div>

                        <div class="f-small odp-meta">
                            @if($reportedAt)
                                <span class="odp-inline">
                                    @include('partials.finder.icon', ['name' => 'clock', 'size' => 14])
                                    {{ __('finder_search.blood.reported_line', ['time' => $reportedAt->diffForHumans()]) }}
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

{{-- The request path: only routes that actually exist. A blood request is
     raised from the patient app or by phoning the bank — this page must not
     pretend to a web form the platform does not have. --}}
<section class="f-section--tight f-section--surface">
    <div class="f-container">
        <div class="f-card odp-narrow">
            <h2 class="f-h2 odp-title">{{ __('finder_search.blood.request_title') }}</h2>
            <p class="f-small odp-soft odp-mt-8">{{ __('finder_search.blood.request_body') }}</p>
            <div class="odp-row odp-mt-16">
                <a class="f-btn f-btn--primary" href="{{ route('public.care-map.emergency') }}">
                    @include('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 18])
                    {{ __('finder_search.blood.empty_emergency_cta') }}
                </a>
                <a class="f-btn" href="{{ route('public.solutions.patients') }}">
                    {{ __('finder_search.blood.request_app_cta') }}
                </a>
            </div>
            <p class="f-small f-muted odp-mt-16 odp-soft">{{ __('finder_search.blood.clinical_note') }}</p>
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
            var checked = document.querySelector('input[name="group"]:checked');
            if (checked) { checked.form.submit(); }
        }, function () {
            status.textContent = L.failed;
        }, { timeout: 10000, maximumAge: 300000 });
    });
})();
</script>
@endpush

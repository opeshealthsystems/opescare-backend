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
    @include('public.partials._od_pages_css')
    <link rel="stylesheet" href="{{ asset('css/finder-pages.css') }}?v={{ @filemtime(public_path('css/finder-pages.css')) ?: 1 }}">
@endpush

@php
    $fpLang   = request('lang') === 'fr' ? ['lang' => 'fr'] : [];
    $hasGeo   = $lat !== null && $lng !== null;
    $products = $products ?? [];
    $components = \App\Enums\BloodComponentType::cases();
    $bloodUrl = fn (array $over) => route('public.network.blood-finder', array_filter(array_merge([
        'group' => $group, 'component' => $component, 'lat' => $lat, 'lng' => $lng, 'lang' => request('lang'),
    ], $over), fn ($v) => $v !== null && $v !== ''));
@endphp

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

@section('content')
<div class="fp odp">

@include('care_map._finder_nav', ['active' => 'blood'])

<div class="fp-shead">
    <h1>{{ __('public.network_blood.hero_title') }}</h1>
    <p>{{ __('finder_search.blood.intro') }}</p>

    {{-- Blood group: big tap targets (48px), each a plain GET link. --}}
    <nav class="fp-btype" aria-label="{{ __('finder_search.blood.group_label') }}">
        @foreach(\App\Enums\BloodGroup::cases() as $g)
            <a href="{{ $bloodUrl(['group' => $g->value]) }}#results" class="{{ $group === $g->value ? 'is-on' : '' }}" @if($group === $g->value) aria-current="true" @endif>{{ $g->label() }}</a>
        @endforeach
    </nav>

    <nav class="fp-chips" aria-label="{{ __('finder_search.blood.component_label') }}">
        @foreach($components as $c)
            <a class="fp-chip {{ $component === $c->value ? 'is-on' : '' }}" href="{{ $bloodUrl(['component' => $c->value]) . ($group ? '#results' : '') }}" @if($component === $c->value) aria-current="true" @endif>{{ __('finder_search.blood.component_' . $c->value) }}</a>
        @endforeach
        <button type="button" class="fp-chip {{ $hasGeo ? 'is-on' : '' }}" data-fp-locate="fp-blood-form" data-status="finder-locate-status"
                data-locating="{{ __('finder_search.common.locating') }}" data-failed="{{ __('finder_search.common.location_failed') }}"
                data-active="{{ __('finder_search.common.location_active') }}">
            @include('partials.finder.icon', ['name' => 'navigation', 'size' => 15])
            {{ __('finder_search.common.use_my_location') }}
        </button>
    </nav>
    <form id="fp-blood-form" method="GET" action="{{ route('public.network.blood-finder') }}" hidden>
        @if($group)<input type="hidden" name="group" value="{{ $group }}">@endif
        <input type="hidden" name="component" value="{{ $component }}">
        <input type="hidden" name="lat" value="{{ $lat }}" data-fp-lat>
        <input type="hidden" name="lng" value="{{ $lng }}" data-fp-lng>
        @if(request('lang'))<input type="hidden" name="lang" value="{{ request('lang') }}">@endif
    </form>
    <p class="fp-status" id="finder-locate-status" role="status" aria-live="polite">
        @if($hasGeo){{ __('finder_search.common.location_active') }}@elseif($group === null){{ __('finder_pages.blood.pick_group') }}@endif
    </p>
</div>

@if($results !== null)
<div class="fp-split" id="results">
    <section class="fp-results">
        <div class="fp-alert" role="note">
            @include('partials.finder.icon', ['name' => 'alert-triangle', 'size' => 18])
            <div><b>{{ __('finder_pages.blood.alert_strong') }}</b> {{ __('finder_pages.blood.alert_rest') }}
                <a href="tel:112">112</a></div>
        </div>

        {{-- The caution comes BEFORE the results: this is where somebody
             decides whether to travel. --}}
        <p class="fp-note">
            @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 16])
            <span>{{ __('finder_search.blood.before_you_travel') }}</span>
        </p>

        @if($results->isEmpty())

            <div class="fp-empty">
                @include('partials.finder.icon', ['name' => 'droplet', 'size' => 32])
                <b>{{ __('finder_search.blood.empty_title', ['group' => $group]) }}</b>
                {{ __('finder_search.blood.empty_body') }}
                <div class="fp-acts-btns">
                    <a class="fp-btn fp-btn--primary" href="{{ route('public.care-map.emergency', $fpLang) }}">
                        @include('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 16])
                        {{ __('finder_search.blood.empty_emergency_cta') }}
                    </a>
                    <a class="fp-btn" href="{{ route('public.care-map', ['facility_type' => 'blood_bank'] + $fpLang) }}">
                        @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 16])
                        {{ __('finder_search.common.directory_link') }}
                    </a>
                </div>
            </div>

        @else
            @php $shown = $results->take(30)->values(); @endphp

            <div class="fp-rhead">
                <p><b>{{ trans_choice('finder_search.blood.results_count', $results->count(), ['count' => $results->count(), 'group' => $group]) }}</b></p>
                <button type="button" class="fp-btn fp-btn--sm fp-maptoggle" data-fp-map-toggle aria-controls="fp-mapcol" aria-expanded="false"
                        data-show="{{ __('finder_directory.show_map') }}" data-hide="{{ __('finder_directory.hide_map') }}">
                    @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 15])
                    <span data-fp-label>{{ __('finder_directory.show_map') }}</span>
                </button>
            </div>

            <ol class="fp-list">
                @foreach($shown as $f)
                    @php
                        $match    = $f->matched_blood;
                        $phone    = $f->dialablePhone();
                        $address  = collect([$f->address, $f->city, $f->region])->filter()->implode(', ');
                        $hasCoords = $f->latitude && $f->longitude;
                        $reportedAt = $match->last_updated_at?->locale(app()->getLocale());
                        $distLabel = $f->distance !== null ? __('finder_search.common.distance_km', ['distance' => round($f->distance, 1)]) : null;
                        $grid = $products[(string) $f->id] ?? [];
                    @endphp
                    <li class="fp-card">
                        <span class="fp-av fp-av--blood" aria-hidden="true">
                            @if($hasCoords)<span class="fp-num">{{ $loop->iteration }}</span>@endif
                            @include('partials.finder.icon', ['name' => 'droplet', 'size' => 22])
                        </span>
                        <div>
                            <h3><a href="{{ route('public.care-map.profile', ['id' => $f->id] + $fpLang) }}">{{ $f->facility_name }}</a></h3>
                            @if($address !== '' || $distLabel)
                                <div class="fp-meta"><span>@include('partials.finder.icon', ['name' => 'map-pin', 'size' => 14]){{ collect([$address ?: null, $distLabel])->filter()->implode(' · ') }}</span></div>
                            @endif

                            {{-- Product grid: what this facility reports for each
                                 product of the chosen group. Unreported stays
                                 "Not reported" — never inferred. --}}
                            <div class="fp-bgrid">
                                @foreach($components as $c)
                                    @php $cell = $grid[$c->value] ?? null; @endphp
                                    <div class="fp-bcell {{ $c->value === $component ? 'is-match' : '' }}">
                                        <small>{{ __('finder_search.blood.component_' . $c->value) }}</small>
                                        @if($cell === null)
                                            <b class="is-unknown">{{ __('finder_pages.blood.not_reported') }}</b>
                                        @elseif($cell['status'] === 'available')
                                            <b>{{ $cell['range'] ? __('finder_pages.blood.available_range', ['range' => $cell['range']]) : __('finder_pages.blood.available') }}</b>
                                        @else
                                            <b class="is-none">{{ __('finder_pages.blood.not_available') }}</b>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            @if($reportedAt)
                                <div class="fp-tags">
                                    <span class="fp-fresh">@include('partials.finder.icon', ['name' => 'refresh-cw', 'size' => 13]){{ __('finder_search.blood.reported_line', ['time' => $reportedAt->diffForHumans()]) }}</span>
                                </div>
                            @endif
                        </div>
                        <div class="fp-acts">
                            <span class="fp-pill fp-pill--ok"><span class="fp-dot"></span>{{ $match->blood_group }} · {{ __('finder_search.blood.component_' . $match->component_type) }}@if($match->units_available_range) · {{ __('finder_search.blood.units_reported', ['range' => $match->units_available_range]) }}@endif</span>
                            <div class="fp-acts-btns">
                                @if($phone)
                                    <a class="fp-btn fp-btn--sm fp-btn--primary" href="tel:{{ $phone }}">
                                        @include('partials.finder.icon', ['name' => 'phone', 'size' => 15]){{ __('finder_pages.blood.call_now') }}
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

            @if($results->count() > $shown->count())
                <p class="fp-fnote">{{ __('finder_search.common.showing_first', ['count' => $shown->count()]) }}</p>
            @endif
        @endif
    </section>

    @if($results->isNotEmpty())
    <aside class="fp-mapcol" id="fp-mapcol" aria-label="{{ __('finder_directory.map_label') }}">
        <div class="fp-mapcol-inner">
            @include('partials.finder.map', ['facilities' => $shown, 'mapId' => 'blood-map', 'height' => '340px', 'numbered' => true, 'pinColor' => '#B91C1C'])
            <p class="fp-mapnote">{{ __('finder_pages.dir.pin_note') }}</p>
        </div>
    </aside>
    @endif
</div>
@endif

{{-- The request path: only routes that actually exist. A blood request is
     raised from the patient app or by phoning the bank — this page must not
     pretend to a web form the platform does not have. --}}
<div class="fp-foot">
    <div class="fp-note fp-note--block">
        <b>{{ __('finder_search.blood.request_title') }}</b>
        <p class="odp-mt-8">{{ __('finder_search.blood.request_body') }}</p>
        <div class="fp-acts-btns odp-mt-12">
            <a class="fp-btn fp-btn--primary" href="{{ route('public.care-map.emergency', $fpLang) }}">
                @include('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 16])
                {{ __('finder_search.blood.empty_emergency_cta') }}
            </a>
            <a class="fp-btn" href="{{ route('public.solutions.patients') }}">
                {{ __('finder_search.blood.request_app_cta') }}
            </a>
        </div>
        <p class="fp-fnote odp-mt-12">{{ __('finder_search.blood.clinical_note') }}</p>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/finder-pages.js') }}?v={{ @filemtime(public_path('js/finder-pages.js')) ?: 1 }}" defer></script>
@endpush

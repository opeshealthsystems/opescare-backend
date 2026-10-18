{{--
    Imaging-exam Finder — /network/imaging-finder (ImagingExamFinderController).
    Same honesty contract as the lab-test finder: every result is the centre's
    own publication; a missing price is 'no price published'; an empty result
    means no centre PUBLISHED the exam, never that none performs it.
    Out of scope here: reports, images, PACS/DICOM viewing.
--}}
@extends('layouts.finder')

@section('title', __('imaging_finder.title'))
@section('description', __('imaging_finder.meta_description'))

@push('head')
    @include('public.partials._od_pages_css')
@endpush

@section('content')

<section class="f-section--wash f-section--tight">
    <div class="f-container">
        <span class="f-eyebrow">@include('partials.finder.icon', ['name' => 'eye', 'size' => 14]) {{ __('imaging_finder.badge') }}</span>
        <h1 class="f-h1">{{ __('imaging_finder.title') }}</h1>
        <p class="f-lead odp-mt-12">{{ __('imaging_finder.intro') }}</p>

        <form class="odp-search" method="GET" action="{{ route('public.network.imaging-finder') }}" role="search">
            <label class="f-label" for="imaging-q">{{ __('imaging_finder.search_label') }}</label>
            <div class="odp-row">
                <input class="f-input odp-grow" id="imaging-q" name="q" type="search"
                       value="{{ $query }}" placeholder="{{ __('imaging_finder.search_placeholder') }}"
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
            <div class="f-card odp-empty">
                @include('partials.finder.icon', ['name' => 'eye', 'size' => 36, 'class' => 'f-muted'])
                <h2 class="f-h2 odp-mt-12">{{ __('imaging_finder.empty_title') }}</h2>
                <p class="f-muted odp-mt-8 odp-soft">{{ __('imaging_finder.empty_body') }}</p>
                <div class="odp-mt-20">
                    <a class="f-btn f-btn--primary" href="{{ route('public.care-map', ['facility_type' => 'imaging_center']) }}">
                        @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 18])
                        {{ __('imaging_finder.empty_cta') }}
                    </a>
                </div>
            </div>
        @else
            <p class="odp-strong">{{ trans_choice('imaging_finder.results_count', $results->count(), ['count' => $results->count()]) }}</p>

            <p class="f-small f-muted odp-note odp-mt-8">
                @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 16])
                <span>{{ __('imaging_finder.before_you_travel') }}</span>
            </p>

            @php $shown = $results->take(30); @endphp
            @include('partials.finder.map', ['facilities' => $shown, 'mapId' => 'imaging-exam-map', 'height' => '340px'])
            <div class="odp-results">
                @foreach($shown as $f)
                    @php
                        $test      = $f->matched_test;
                        $phone     = $f->dialablePhone();
                        $address   = collect([$f->address, $f->city, $f->region])->filter()->implode(', ');
                        $published = $test->last_published_at ?? $test->last_updated_at;
                        $published = $published ? \Illuminate\Support\Carbon::parse($published)->locale(app()->getLocale()) : null;
                    @endphp
                    <article class="f-card">
                        <div class="odp-row odp-row--between">
                            <h3 class="odp-title">{{ $f->facility_name }}</h3>
                            @if($f->distance !== null)
                                <span class="f-badge odp-shrink">{{ __('finder_search.common.distance_km', ['distance' => round($f->distance, 1)]) }}</span>
                            @endif
                        </div>

                        <div class="odp-row odp-mt-8">
                            <span class="f-small odp-strong">{{ $test->test_name }}</span>
                            @if($test->loinc_code)<span class="f-small f-muted">LOINC {{ $test->loinc_code }}</span>@endif
                        </div>

                        <div class="f-small odp-meta">
                            @if($test->price_xaf !== null)
                                <span class="odp-bold">{{ __('imaging_finder.price_fcfa', ['price' => number_format((int) $test->price_xaf, 0, '.', ' ')]) }}</span>
                            @else
                                <span class="f-muted">{{ __('imaging_finder.no_price') }}</span>
                            @endif
                            @if($test->turnaround_time)
                                <span class="odp-inline">
                                    @include('partials.finder.icon', ['name' => 'clock', 'size' => 14])
                                    {{ __('imaging_finder.turnaround', ['time' => $test->turnaround_time]) }}
                                </span>
                            @endif
                            @if($published)
                                <span class="odp-inline">
                                    @include('partials.finder.icon', ['name' => 'clock', 'size' => 14])
                                    {{ __('imaging_finder.published_line', ['time' => $published->diffForHumans()]) }}
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
                            @if($f->latitude && $f->longitude)
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

@endsection

@push('scripts')
<script @cspNonce>
(function () {
    var btn    = document.getElementById('finder-locate');
    var status = document.getElementById('finder-locate-status');
    var latEl  = document.getElementById('finder-lat');
    var lngEl  = document.getElementById('finder-lng');
    var qEl    = document.getElementById('imaging-q');
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

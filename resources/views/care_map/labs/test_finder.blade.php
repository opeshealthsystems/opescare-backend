{{--
    Lab-test Finder — the task page at /network/lab-test-finder.

    Search-first and server-rendered, like the medicine finder: the form GETs
    back to this route and LabTestFinderController runs the SAME
    LabTestSearchService the JSON API uses (portal-published rows only,
    retired tests hidden, active listings only).

    Honesty contract (do not weaken):
      - every result is the laboratory's OWN publication, phrased that way;
      - a missing price says "no price published", never "free" and never 0;
      - an empty result says no laboratory PUBLISHED the test — never that
        no laboratory performs it;
      - nothing on this page says "verified".

    $query   string            the submitted term ('' when none)
    $lat/$lng float|null       validated coordinates, or null
    $results Collection|null   null = not searched yet; empty = searched, none
--}}
@extends('layouts.finder')

@section('title', __('lab_manager.finder.title'))
@section('description', __('lab_manager.finder.meta_description'))

@section('content')

<section class="f-section--wash f-section--tight">
    <div class="f-container">
        <span class="f-eyebrow">@include('partials.finder.icon', ['name' => 'droplet', 'size' => 14]) {{ __('lab_manager.finder.badge') }}</span>
        <h1 class="f-h1">{{ __('lab_manager.finder.title') }}</h1>
        <p class="f-lead" style="margin-top:10px;">{{ __('lab_manager.finder.intro') }}</p>

        <form method="GET" action="{{ route('public.network.lab-test-finder') }}" role="search" style="margin-top:24px;max-width:640px;">
            <label class="f-label" for="lab-q">{{ __('lab_manager.finder.search_label') }}</label>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <input class="f-input" style="flex:1 1 240px;" id="lab-q" name="q" type="search"
                       value="{{ $query }}" placeholder="{{ __('lab_manager.finder.search_placeholder') }}"
                       minlength="2" maxlength="120" required autocomplete="off"
                       @if($query === '') autofocus @endif>
                <button class="f-btn f-btn--primary" type="submit" style="flex:0 0 auto;">
                    @include('partials.finder.icon', ['name' => 'search', 'size' => 18])
                    {{ __('finder_search.common.search') }}
                </button>
            </div>

            <input type="hidden" name="lat" id="finder-lat" value="{{ $lat }}">
            <input type="hidden" name="lng" id="finder-lng" value="{{ $lng }}">

            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:12px;">
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
            <div class="f-card" style="text-align:center;padding:36px 20px;max-width:560px;margin-inline:auto;">
                @include('partials.finder.icon', ['name' => 'droplet', 'size' => 36, 'class' => 'f-muted'])
                <h2 class="f-h2" style="margin-top:12px;">{{ __('lab_manager.finder.empty_title') }}</h2>
                <p class="f-muted" style="margin-top:10px;line-height:1.6;">{{ __('lab_manager.finder.empty_body') }}</p>
                <div style="margin-top:20px;">
                    <a class="f-btn f-btn--primary" href="{{ route('public.care-map', ['facility_type' => 'laboratory']) }}">
                        @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 18])
                        {{ __('lab_manager.finder.empty_cta') }}
                    </a>
                </div>
            </div>
        @else
            <p style="font-weight:650;">{{ trans_choice('lab_manager.finder.results_count', $results->count(), ['count' => $results->count()]) }}</p>

            <p class="f-small f-muted" style="display:flex;gap:8px;align-items:flex-start;margin-top:6px;max-width:62ch;">
                @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 16])
                <span>{{ __('lab_manager.finder.before_you_travel') }}</span>
            </p>

            @php $shown = $results->take(30); @endphp
            @include('partials.finder.map', ['facilities' => $shown, 'mapId' => 'lab-test-map', 'height' => '340px'])
            <div style="display:grid;gap:12px;margin-top:16px;">
                @foreach($shown as $f)
                    @php
                        $test      = $f->matched_test;
                        $phone     = $f->dialablePhone();
                        $address   = collect([$f->address, $f->city, $f->region])->filter()->implode(', ');
                        $published = $test->last_published_at ?? $test->last_updated_at;
                        $published = $published ? \Illuminate\Support\Carbon::parse($published)->locale(app()->getLocale()) : null;
                    @endphp
                    <article class="f-card">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                            <h3 style="font-size:1.0625rem;font-weight:700;line-height:1.35;">{{ $f->facility_name }}</h3>
                            @if($f->distance !== null)
                                <span class="f-badge" style="flex-shrink:0;">{{ __('finder_search.common.distance_km', ['distance' => round($f->distance, 1)]) }}</span>
                            @endif
                        </div>

                        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:8px;">
                            <span class="f-small" style="font-weight:600;">{{ $test->test_name }}</span>
                            @if($test->loinc_code)<span class="f-small f-muted">LOINC {{ $test->loinc_code }}</span>@endif
                        </div>

                        <div class="f-small" style="display:grid;gap:5px;margin-top:10px;color:var(--f-ink-soft);">
                            @if($test->price_xaf !== null)
                                <span style="font-weight:700;color:var(--f-ink);">{{ __('lab_manager.finder.price_fcfa', ['price' => number_format((int) $test->price_xaf, 0, '.', ' ')]) }}</span>
                            @else
                                <span class="f-muted">{{ __('lab_manager.finder.no_price') }}</span>
                            @endif
                            @if($test->turnaround_time)
                                <span style="display:flex;gap:6px;align-items:center;">
                                    @include('partials.finder.icon', ['name' => 'clock', 'size' => 14])
                                    {{ __('lab_manager.finder.turnaround', ['time' => $test->turnaround_time]) }}
                                </span>
                            @endif
                            @if($published)
                                <span style="display:flex;gap:6px;align-items:center;">
                                    @include('partials.finder.icon', ['name' => 'clock', 'size' => 14])
                                    {{ __('lab_manager.finder.published_line', ['time' => $published->diffForHumans()]) }}
                                </span>
                            @endif
                            @if($address !== '')
                                <span style="display:flex;gap:6px;align-items:center;">
                                    @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 14])
                                    {{ $address }}
                                </span>
                            @endif
                        </div>

                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;">
                            @if($phone)
                                <a class="f-btn f-btn--primary" href="tel:{{ $phone }}">
                                    @include('partials.finder.icon', ['name' => 'phone', 'size' => 16])
                                    {{ __('finder_search.common.call') }}
                                </a>
                            @else
                                <span class="f-small f-muted" style="align-self:center;">{{ __('finder_search.common.no_phone') }}</span>
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
                <p class="f-small f-muted" style="margin-top:12px;">{{ __('finder_search.common.showing_first', ['count' => $shown->count()]) }}</p>
            @endif
        @endif
    </div>
</section>
@endif

@endsection

@push('scripts')
<script>
(function () {
    var btn    = document.getElementById('finder-locate');
    var status = document.getElementById('finder-locate-status');
    var latEl  = document.getElementById('finder-lat');
    var lngEl  = document.getElementById('finder-lng');
    var qEl    = document.getElementById('lab-q');
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

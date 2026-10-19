{{--
    /care-map/emergency — the page a person opens when someone is bleeding.

    Deliberately NOT layouts.finder: no nav panel, no footer columns, nothing
    between the visitor and the dial buttons. It keeps the foundation contract
    another way — finder.css tokens/classes and the partials/finder/icon set.

    Order of the page IS the design:
      1. two giant national dial buttons (the only --f-danger on the page —
         red means "dial", nothing else);
      2. the nearest hospitals, server-rendered from CareMapSearchService::
         searchEmergency() — the same query the JSON API serves.

    Honesty contract (pinned by EmergencyFinderTest):
      - nothing here asserts a working emergency department ("24/7 A&E" is
        banned copy) — the capability note says plainly that OpesCare cannot
        confirm one;
      - facility phones come from callable_phone (resolved by the service;
        'N/A' placeholders resolve to null), so no dead tel: links.

    "Use my location" reloads with latitude/longitude/radius, which the
    service uses to bound and sort the list — distance comes from the server,
    never from a client-side guess.
--}}
@extends('layouts.finder')
@section('title', __('finder_seo.pages.emergency.title'))
@section('description', __('finder_seo.pages.emergency.description'))
@push('head')
@include('public.partials._od_pages_css')
<style>
/* Page-local styles on top of finder.css tokens. Calm, high contrast,
   list-first. --f-danger appears ONLY on the two dial actions. */
.em-back { padding-top: 12px; }
.em-dial-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px; }
.em-dial {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    min-height: 72px; padding: 12px 16px;
    background: var(--f-danger); color: #FFFFFF;
    border-radius: var(--f-r); text-decoration: none;
    font-size: 1.25rem; font-weight: 800; letter-spacing: -0.01em;
    -webkit-tap-highlight-color: transparent;
}
.em-dial:active { transform: translateY(1px); }
/* "Appeler le 112" never breaks mid-label: the label is nowrap and the grid
   stacks to one full-width button per row below 520px (two halves of a phone
   screen are too narrow for the French label at this size). */
.em-dial__label { white-space: nowrap; }
.em-dial svg { flex: none; }
@media (max-width: 520px) { .em-dial-grid { grid-template-columns: 1fr; } .em-dial { min-height: 64px; font-size: 1.1875rem; } }
.em-note { display: flex; gap: 8px; align-items: flex-start; background: var(--f-wash); border-radius: var(--f-r); padding: 10px 14px; }
.em-cards { display: grid; gap: 12px; margin-top: 16px; }
/* Facility card internals as classes, not inline styles: the card repeats up
   to 40 times and every byte inside the loop is paid 40 times over. Same
   reason the per-row icons are text-only — one icon per card (Call). */
.em-fc-top { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; }
.em-fc-name { font-size: 1.0625rem; font-weight: 700; line-height: 1.35; }
.em-fc-dist { flex-shrink: 0; }
.em-fc-badges { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: 8px; }
.em-fc-meta { display: grid; gap: 5px; margin-top: 10px; color: var(--f-ink-soft); }
.em-fc-btns { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px; }
.em-fc-call { flex: 1 1 auto; }
</style>
@endpush

@section('content')
<div class="odp">
<div class="f-container em-back">
    <a href="{{ route('public.care-map') }}" class="f-btn f-btn--ghost">
        @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 16])
        {{ __('finder_search.emergency.back_to_directory') }}
    </a>
</div>


    {{-- 1 ── Dial first. --}}
    <section class="f-section--tight odp-pad-y">
        <div class="f-container">
            <h1 class="f-h2 odp-title">{{ __('public.care_map_emergency.h1') }}</h1>
            <p class="odp-bold odp-mt-16">{{ __('finder_search.emergency.call_now_title') }}</p>
            <p class="f-small f-muted odp-mt-4">{{ __('finder_search.emergency.call_now_sub') }}</p>

            <div class="em-dial-grid">
                <a href="tel:112" class="em-dial">
                    @include('partials.finder.icon', ['name' => 'phone', 'size' => 26])
                    <span class="em-dial__label">{{ __('finder_search.emergency.dial_112') }}</span>
                </a>
                <a href="tel:119" class="em-dial">
                    @include('partials.finder.icon', ['name' => 'phone', 'size' => 26])
                    <span class="em-dial__label">{{ __('finder_search.emergency.dial_119') }}</span>
                </a>
            </div>
            <p class="f-small f-muted odp-mt-8">{{ __('finder_search.emergency.dial_hint') }}</p>
        </div>
    </section>

    {{-- 2 ── Then the nearest hospitals. --}}
    <section class="f-section--tight f-section--surface odp-pad-y--line">
        <div class="f-container">
            <div class="odp-row odp-row--top odp-row--spread">
                <div>
                    <h2 class="f-h2 odp-title">{{ __('finder_search.emergency.nearest_title') }}</h2>
                    <p class="f-small f-muted odp-mt-2">
                        {{ count($facilities) === 1
                            ? __('public.care_map_emergency.count_hospitals_one')
                            : __('public.care_map_emergency.count_hospitals', ['count' => count($facilities)]) }}
                    </p>
                </div>
                <button class="f-chip" type="button" id="em-locate">
                    @include('partials.finder.icon', ['name' => 'navigation', 'size' => 15])
                    {{ __('finder_search.common.use_my_location') }}
                </button>
            </div>
            <p class="f-small f-muted odp-mt-6" role="status" aria-live="polite" id="em-locate-status">
                @if(request()->filled('latitude') && request()->filled('longitude')){{ __('finder_search.common.location_active') }}@endif
            </p>

            {{-- No column in care_facilities records A&E capability, so the
                 page says exactly that instead of guessing. --}}
            <p class="em-note f-small odp-mt-12 odp-soft">
                @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 16])
                <span>{{ __('public.care_map_emergency.capability_unknown') }}</span>
            </p>

            @php $shown = collect($facilities)->take(40); @endphp
            @include('partials.finder.map', ['facilities' => $shown, 'mapId' => 'emergency-map', 'height' => '340px'])
            <div class="em-cards">
                @forelse($shown as $f)
                    @php
                        $phone   = $f->callable_phone ?? null;
                        $dist    = isset($f->distance) ? round($f->distance, 1) : null;
                        $address = collect([$f->address, $f->city, $f->region])->filter()->implode(', ');
                        $dirUrl  = ($f->latitude && $f->longitude)
                            ? true
                            : null;
                    @endphp
                    <article class="f-card">
                        <div class="em-fc-top">
                            <h3 class="em-fc-name">{{ $f->facility_name }}</h3>
                            @if($dist !== null)
                                <span class="f-badge em-fc-dist">{{ __('finder_search.common.distance_km', ['distance' => $dist]) }}</span>
                            @endif
                        </div>

                        <div class="em-fc-badges">
                            <span class="f-badge">{{ __('public.care_map_emergency.badge_hospital') }}</span>
                            <span class="f-badge f-badge--warn">{{ __('public.care_map_emergency.call_before_travelling') }}</span>
                            @if($f->emergency_contact)
                                <span class="f-badge">{{ __('public.care_map_emergency.badge_emergency_line') }}</span>
                            @endif
                        </div>

                        <div class="f-small em-fc-meta">
                            @if($address !== '')
                                <span>{{ $address }}</span>
                            @endif
                            <span>@if($phone){{ $phone }}@else<em class="f-muted">{{ __('public.care_map_emergency.no_phone_on_record') }}</em>@endif</span>
                        </div>

                        <div class="em-fc-btns">
                            @if($phone)
                                <a class="f-btn f-btn--primary em-fc-call" href="tel:{{ $phone }}">
                                    @include('partials.finder.icon', ['name' => 'phone', 'size' => 16])
                                    {{ __('public.care_map_emergency.action_call') }}
                                </a>
                            @endif
                            @if($dirUrl)
                                <button type="button" class="f-btn" data-finder-directions="{{ $f->id }}">{{ __('public.care_map_emergency.action_directions') }}</button>
                            @endif
                            <a class="f-btn f-btn--ghost" href="{{ route('public.care-map.profile', $f->id) }}">{{ __('public.care_map_emergency.action_profile') }}</a>
                        </div>
                    </article>
                @empty
                    <div class="f-card odp-empty">
                        @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 32, 'class' => 'f-muted'])
                        <p class="odp-bold odp-mt-12">{{ __('public.care_map_emergency.empty_title') }}</p>
                        <p class="f-small f-muted odp-mt-6">{{ __('public.care_map_emergency.empty_sub') }}</p>
                    </div>
                @endforelse
            </div>

            @if(count($facilities) > $shown->count())
                <p class="f-small f-muted odp-mt-12">{{ __('finder_search.common.showing_first', ['count' => $shown->count()]) }}</p>
            @endif
        </div>
    </section>

    <div class="f-section--tight odp-pad-y">
        <div class="f-container">
            <p class="f-small f-muted">{{ __('finder_search.common.info_may_change') }}</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script @cspNonce>
(function () {
    var btn    = document.getElementById('em-locate');
    var status = document.getElementById('em-locate-status');
    if (!btn) return;
    var L = {
        locating: @json(__('finder_search.common.locating')),
        failed:   @json(__('finder_search.common.location_failed'))
    };
    btn.addEventListener('click', function () {
        if (!navigator.geolocation) { status.textContent = L.failed; return; }
        status.textContent = L.locating;
        navigator.geolocation.getCurrentPosition(function (pos) {
            // The server sorts and bounds by distance — reload with coords.
            var p = new URLSearchParams(window.location.search);
            p.set('latitude',  pos.coords.latitude.toFixed(5));
            p.set('longitude', pos.coords.longitude.toFixed(5));
            if (!p.has('radius')) { p.set('radius', '50'); }
            window.location.search = p.toString();
        }, function () {
            status.textContent = L.failed;
        }, { timeout: 10000, maximumAge: 300000 });
    });
})();
</script>
@endpush

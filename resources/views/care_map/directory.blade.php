{{--
  Public Care Map directory — server-paginated (24/page), mobile-first.
  Layout follows the approved design (docs/design/onboarding-2026-10-21/finders.html):
  section nav, search header (What / Where), type chips, filter sidebar,
  result cards, and the shared Leaflet map with numbered pins matching the list.

  Honesty rules for this page:
    - every filter reads a column the listing really holds (hours, region,
      phone, verification_status). Services / emergency department / online
      booking are not offered as filters: there is no data behind them.
    - the verified pill renders only for license_verified / government_verified.
    - distance only when the visitor's coordinates are in the query.
    - "Book" only when the listing is backed by a live tenant ($bookableIds).
  The map receives ONLY the current page's markers.
--}}
@extends('layouts.finder')
@section('title', __('finder_seo.pages.care_map.title'))
@section('description', __('finder_seo.pages.care_map.description'))
@push('head')
@include('public.partials._od_pages_css')
<link rel="stylesheet" href="{{ asset('css/finder-pages.css') }}?v={{ @filemtime(public_path('css/finder-pages.css')) ?: 1 }}">
@endpush

@php
  $now = now();
  $filters      = $filters ?? ['open_now' => false, 'open_24h' => false, 'has_phone' => false, 'verified' => false];
  $regionCounts = $regionCounts ?? [];
  $bookableIds  = $bookableIds ?? [];

  $typeIcons = [
    'pharmacy'       => 'pill',
    'laboratory'     => 'flask-conical',
    'blood_bank'     => 'droplet',
    'clinic'         => 'stethoscope',
    'dental'         => 'smile',
    'maternity'      => 'baby',
    'imaging_center' => 'scan-line',
    'eye_clinic'     => 'eye',
  ];

  $typeLabel = fn (string $slug) => \App\Support\FacilityTypeLabel::label($slug);
  $cityText  = trim((string) request('city', ''));

  // Current filter params, re-emitted on every chip / filter / page link.
  $baseParams = array_filter([
      'query'         => $queryText !== '' ? $queryText : null,
      'facility_type' => $activeType !== '' ? $activeType : null,
      'region'        => $activeRegion !== '' ? $activeRegion : null,
      'city'          => $cityText !== '' ? $cityText : null,
      'latitude'      => request('latitude') ?: null,
      'longitude'     => request('longitude') ?: null,
      'radius'        => request('radius') ?: null,
      'open_now'      => $filters['open_now'] ? 1 : null,
      'open_24h'      => $filters['open_24h'] ? 1 : null,
      'has_phone'     => $filters['has_phone'] ? 1 : null,
      'verified'      => $filters['verified'] ? 1 : null,
      'lang'          => request('lang') ?: null,
  ], fn ($v) => $v !== null && $v !== '');

  $chipUrl = function (array $overrides) use ($baseParams) {
      $params = array_filter(array_merge($baseParams, $overrides), fn ($v) => $v !== null && $v !== '');
      return route('public.care-map', $params);
  };
  $toggleUrl = fn (string $key) => $chipUrl([$key => ($baseParams[$key] ?? null) ? null : 1]);

  $hasGeo = request('latitude') !== null && request('longitude') !== null;
  $anyFilter = $activeRegion !== '' || in_array(true, $filters, true);

  // Directory-wide chip counts are only honest while no other filter narrows
  // the set they describe.
  $showTypeCounts = $queryText === '' && !$anyFilter && !$hasGeo && $cityText === '';

  $lastPage = max(1, $facilities->lastPage());
  $directoryTotal = array_sum($typeCounts);
@endphp

@section('content')
<div class="fp odp">

@include('care_map._finder_nav', ['active' => 'facilities'])

{{-- ── Search header ───────────────────────────────────────────────── --}}
<div class="fp-shead">
  <h1>{{ __('finder_seo.pages.care_map.title') }}</h1>
  <p>{{ __('finder_pages.dir.count_line', ['count' => \App\Support\LocaleNumber::format($directoryTotal)]) }}
    <a class="fp-clear" href="{{ route('public.care-map.places', request('lang') === 'fr' ? ['lang' => 'fr'] : []) }}">{{ __('care_places.hub.link') }}</a></p>

  <form class="fp-sbar" id="fp-dir-form" data-finder-search method="GET" action="{{ route('public.care-map') }}" role="search" aria-label="{{ __('finder_directory.search_label') }}">
    <label>
      @include('partials.finder.icon', ['name' => 'search', 'size' => 18])
      <span class="fp-sfield"><small>{{ __('finder_pages.search.what') }}</small>
        <input type="search" name="query" value="{{ $queryText }}" placeholder="{{ __('finder_pages.search.what_placeholder') }}"
               aria-label="{{ __('finder_directory.search_label') }}" autocomplete="off"></span>
    </label>
    <label>
      @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 18])
      <span class="fp-sfield"><small>{{ __('finder_pages.search.where') }}</small>
        <input type="text" name="city" value="{{ $cityText }}" placeholder="{{ __('finder_pages.search.where_placeholder') }}" autocomplete="address-level2"></span>
    </label>
    @foreach(['facility_type' => $activeType, 'region' => $activeRegion, 'lang' => request('lang'),
              'open_now' => $baseParams['open_now'] ?? null, 'open_24h' => $baseParams['open_24h'] ?? null,
              'has_phone' => $baseParams['has_phone'] ?? null, 'verified' => $baseParams['verified'] ?? null] as $k => $v)
      @if($v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
    @endforeach
    <input type="hidden" name="latitude" value="{{ request('latitude') }}" data-fp-lat @disabled(!$hasGeo)>
    <input type="hidden" name="longitude" value="{{ request('longitude') }}" data-fp-lng @disabled(!$hasGeo)>
    <input type="hidden" name="radius" value="{{ request('radius', 50) }}" data-fp-radius @disabled(!$hasGeo)>
    <button class="fp-btn fp-btn--primary fp-go" type="submit">
      @include('partials.finder.icon', ['name' => 'search', 'size' => 16])
      {{ __('finder_pages.search.go') }}
    </button>
  </form>

  <nav class="fp-chips" aria-label="{{ __('finder_directory.filter_by_type') }}">
    <a class="fp-chip {{ $activeType === '' ? 'is-on' : '' }}" href="{{ $chipUrl(['facility_type' => null]) }}">
      {{ __('finder_directory.all_types') }}
      @if($showTypeCounts)<b>{{ \App\Support\LocaleNumber::format($directoryTotal) }}</b>@endif
    </a>
    @foreach($typeCounts as $slug => $n)
      <a class="fp-chip {{ $activeType === $slug ? 'is-on' : '' }}" href="{{ $chipUrl(['facility_type' => $slug]) }}">
        {{ $typeLabel($slug) }}
        @if($showTypeCounts)<b>{{ \App\Support\LocaleNumber::format($n) }}</b>@endif
      </a>
    @endforeach
    <button type="button" class="fp-chip {{ $hasGeo ? 'is-on' : '' }}" data-fp-locate="fp-dir-form" data-status="fp-dir-status"
            data-locating="{{ __('finder_search.common.locating') }}" data-failed="{{ __('finder_directory.geo_denied') }}"
            data-active="{{ __('finder_search.common.location_active') }}" aria-label="{{ __('finder_directory.locate_me') }}">
      @include('partials.finder.icon', ['name' => 'navigation', 'size' => 15])
      {{ __('finder_directory.near_me') }}
    </button>
  </nav>
  <p class="fp-status" id="fp-dir-status" role="status" aria-live="polite"></p>
</div>

<div class="fp-split fp-split--filters">

  {{-- ── Filters: only what the data supports ───────────────────────── --}}
  <details class="fp-filters" data-fp-filters>
    <summary>
      @include('partials.finder.icon', ['name' => 'sliders-horizontal', 'size' => 16])
      {{ __('finder_pages.dir.filters') }}
    </summary>
    @if($anyFilter)
      <a class="fp-clear" href="{{ $chipUrl(['region' => null, 'open_now' => null, 'open_24h' => null, 'has_phone' => null, 'verified' => null]) }}">{{ __('finder_pages.dir.clear') }}</a>
    @endif
    <section>
      <h3>{{ __('finder_pages.dir.filters_open') }}</h3>
      <a class="fp-check {{ $filters['open_now'] ? 'is-on' : '' }}" href="{{ $toggleUrl('open_now') }}" aria-pressed="{{ $filters['open_now'] ? 'true' : 'false' }}"><i></i>{{ __('finder_pages.dir.open_now') }}</a>
      <a class="fp-check {{ $filters['open_24h'] ? 'is-on' : '' }}" href="{{ $toggleUrl('open_24h') }}" aria-pressed="{{ $filters['open_24h'] ? 'true' : 'false' }}"><i></i>{{ __('finder_pages.dir.open_24h') }}</a>
      <p class="fp-fnote">{{ __('finder_pages.dir.hours_note') }}</p>
    </section>
    <section>
      <h3>{{ __('finder_pages.dir.region') }}</h3>
      <a class="fp-check is-radio {{ $activeRegion === '' ? 'is-on' : '' }}" href="{{ $chipUrl(['region' => null]) }}"><i></i>{{ __('finder_pages.dir.all_regions') }}</a>
      @foreach($regions as $r)
        <a class="fp-check is-radio {{ strcasecmp($activeRegion, $r) === 0 ? 'is-on' : '' }}" href="{{ $chipUrl(['region' => $r]) }}">
          <i></i>{{ $r }}<em>{{ \App\Support\LocaleNumber::format($regionCounts[$r] ?? 0) }}</em>
        </a>
      @endforeach
    </section>
    <section>
      <h3>{{ __('finder_pages.dir.profile') }}</h3>
      <a class="fp-check {{ $filters['verified'] ? 'is-on' : '' }}" href="{{ $toggleUrl('verified') }}" aria-pressed="{{ $filters['verified'] ? 'true' : 'false' }}"><i></i>{{ __('finder_pages.dir.verified') }}</a>
      <a class="fp-check {{ $filters['has_phone'] ? 'is-on' : '' }}" href="{{ $toggleUrl('has_phone') }}" aria-pressed="{{ $filters['has_phone'] ? 'true' : 'false' }}"><i></i>{{ __('finder_pages.dir.has_phone') }}</a>
    </section>
  </details>

  {{-- ── Results ─────────────────────────────────────────────────────── --}}
  <section class="fp-results" aria-label="{{ __('finder_directory.search_label') }}">
    <div class="fp-rhead">
      <p aria-live="polite">
        <b>{{ trans_choice('finder_directory.results_count', $total, ['count' => \App\Support\LocaleNumber::format($total)]) }}</b>
        @if($lastPage > 1)<span> · {{ __('finder_directory.page_of', ['current' => $facilities->currentPage(), 'last' => $lastPage]) }}</span>@endif
      </p>
      <button type="button" class="fp-btn fp-btn--sm fp-maptoggle" data-fp-map-toggle aria-controls="fp-mapcol" aria-expanded="false"
              data-show="{{ __('finder_directory.show_map') }}" data-hide="{{ __('finder_directory.hide_map') }}">
        @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 15])
        <span data-fp-label>{{ __('finder_directory.show_map') }}</span>
      </button>
    </div>

    @if($facilities->count())
    <ol class="fp-list">
      @foreach($facilities as $f)
        @php
          $slug  = strtolower(str_replace(' ', '_', $f->facility_type ?? 'other'));
          $dist  = ($hasGeo && isset($f->distance)) ? round($f->distance, 1) : null;
          $phone = $f->dialablePhone();
          $locationStr = collect([$f->city, $f->region])->filter()->unique()->implode(', ');
          $hasCoords = $f->latitude && $f->longitude;
          $profileUrl = route('public.care-map.profile', array_filter(['id' => $f->id, 'lang' => request('lang')]));
          $isOpen = \App\Http\Controllers\Api\V1\CareMapController::openState($f, $now);
          $todayH = \App\Http\Controllers\Api\V1\CareMapController::todayHours($f, $now);
          $hoursLine = null;
          if ($todayH) {
              if ($todayH->is_24_hours) { $hoursLine = __('finder_pages.dir.open_24h_tag'); }
              elseif ($todayH->is_closed) { $hoursLine = __('finder_pages.dir.closed_today'); }
              elseif ($todayH->opens_at && $todayH->closes_at) {
                  $hoursLine = $isOpen
                      ? __('finder_pages.dir.open_closes', ['time' => substr($todayH->closes_at, 0, 5)])
                      : (($now->format('H:i') < substr($todayH->opens_at, 0, 5))
                          ? __('finder_pages.dir.opens_at', ['time' => substr($todayH->opens_at, 0, 5)])
                          : __('finder_directory.closed_now'));
              }
          }
          $govVerif = ($f->verification_status ?? '') === 'government_verified';
          $licVerif = ($f->verification_status ?? '') === 'license_verified';
          $bookable = in_array((string) $f->id, $bookableIds, true);
        @endphp
        <li class="fp-card">
          <span class="fp-av" aria-hidden="true">
            @if($hasCoords)<span class="fp-num">{{ $loop->iteration }}</span>@endif
            @include('partials.finder.icon', ['name' => $typeIcons[$slug] ?? 'building-2', 'size' => 22])
          </span>
          <div>
            <h2><a href="{{ $profileUrl }}">{{ $f->facility_name }}</a></h2>
            <div class="fp-meta">
              @if($locationStr)
                <span>@include('partials.finder.icon', ['name' => 'map-pin', 'size' => 14]){{ $locationStr }}</span>
              @endif
              @if($hoursLine)
                <span>@include('partials.finder.icon', ['name' => 'clock', 'size' => 14]){{ $hoursLine }}</span>
              @endif
            </div>
            <div class="fp-tags">
              @if($govVerif)
                <span class="fp-pill fp-pill--info">@include('partials.finder.icon', ['name' => 'shield-check', 'size' => 13]){{ __('finder_directory.government_verified') }}</span>
              @elseif($licVerif)
                <span class="fp-pill fp-pill--info">@include('partials.finder.icon', ['name' => 'shield-check', 'size' => 13]){{ __('finder_directory.license_verified') }}</span>
              @endif
              <span class="fp-tag">{{ $typeLabel($slug) }}</span>
              @if($isOpen === true && !($todayH->is_24_hours ?? false))
                <span class="fp-pill fp-pill--ok"><span class="fp-dot"></span>{{ __('finder_directory.open_now') }}</span>
              @endif
            </div>
          </div>
          <div class="fp-acts">
            @if($dist !== null)
              <div class="fp-dist">{{ __('finder_directory.distance_km', ['km' => $dist]) }}</div>
            @endif
            <div class="fp-acts-btns">
              @if($phone)
                <a href="tel:{{ $phone }}" class="fp-btn fp-btn--sm" aria-label="{{ __('finder_directory.call') }} — {{ $f->facility_name }}">
                  @include('partials.finder.icon', ['name' => 'phone', 'size' => 15]){{ __('finder_directory.call') }}
                </a>
              @endif
              @if($hasCoords)
                <button type="button" class="fp-btn fp-btn--sm" data-finder-directions="{{ $f->id }}" aria-label="{{ __('finder_directory.directions') }} — {{ $f->facility_name }}">
                  @include('partials.finder.icon', ['name' => 'navigation', 'size' => 15]){{ __('finder_directory.directions') }}
                </button>
              @endif
              @if($bookable)
                <a href="{{ route('portals.patient.appointments.book') }}" class="fp-btn fp-btn--sm fp-btn--primary" aria-label="{{ __('finder_directory.book_appointment') }} — {{ $f->facility_name }}">
                  {{ __('finder_pages.dir.book') }}
                </a>
              @endif
              <a href="{{ $profileUrl }}" class="fp-btn fp-btn--sm {{ $bookable ? '' : 'fp-btn--primary' }}" aria-label="{{ __('finder_directory.view_profile') }} — {{ $f->facility_name }}">
                {{ __('finder_pages.dir.profile_btn') }}
              </a>
            </div>
          </div>
        </li>
      @endforeach
    </ol>
    @else
      <div class="fp-empty" role="status">
        @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 28])
        <b>{{ __('finder_directory.no_results_title') }}</b>
        {{ __('finder_directory.no_results_sub') }}
        <div class="fp-acts-btns">
          <a class="fp-btn fp-btn--primary" href="{{ route('public.care-map', array_filter(['lang' => request('lang')])) }}">{{ __('finder_directory.clear_filters') }}</a>
        </div>
      </div>
    @endif

    @if($lastPage > 1)
      <nav class="fp-pager" aria-label="{{ __('finder_directory.page_of', ['current' => $facilities->currentPage(), 'last' => $lastPage]) }}">
        <span>{{ __('finder_pages.dir.showing', ['from' => \App\Support\LocaleNumber::format($facilities->firstItem()), 'to' => \App\Support\LocaleNumber::format($facilities->lastItem()), 'total' => \App\Support\LocaleNumber::format($total)]) }}</span>
        <span class="fp-acts-btns">
          <a class="fp-btn fp-btn--sm" aria-label="{{ __('finder_pages.dir.previous') }}"
             @if($facilities->onFirstPage()) aria-disabled="true" @else href="{{ $facilities->previousPageUrl() }}" rel="prev" @endif>
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 15]){{ __('finder_directory.previous') }}
          </a>
          <span class="fp-btn fp-btn--sm fp-btn--primary" aria-current="page">{{ $facilities->currentPage() }}</span>
          <a class="fp-btn fp-btn--sm" aria-label="{{ __('finder_pages.dir.next') }}"
             @if(!$facilities->hasMorePages()) aria-disabled="true" @else href="{{ $facilities->nextPageUrl() }}" rel="next" @endif>
            {{ __('finder_directory.next') }}@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 15])
          </a>
        </span>
      </nav>
    @endif

    <p class="fp-note odp-mt-12" role="note">
      @include('partials.finder.icon', ['name' => 'clock', 'size' => 16])
      <span>{{ __('finder_directory.disclaimer') }}</span>
    </p>
  </section>

  {{-- ── Map: this page's facilities, pins numbered like the list ───── --}}
  <aside class="fp-mapcol" id="fp-mapcol" aria-label="{{ __('finder_directory.map_label') }}">
    <div class="fp-mapcol-inner">
      @include('partials.finder.map', ['facilities' => $facilities->getCollection(), 'mapId' => 'directory-map', 'height' => '360px', 'numbered' => true])
      <p class="fp-mapnote">{{ __('finder_directory.map_page_note', ['count' => $facilities->count()]) }} {{ __('finder_pages.dir.pin_note') }}</p>
    </div>
  </aside>
</div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/finder-pages.js') }}?v={{ @filemtime(public_path('js/finder-pages.js')) ?: 1 }}" defer></script>
@endpush

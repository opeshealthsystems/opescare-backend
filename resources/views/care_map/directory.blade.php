{{--
  Public Care Map directory — server-paginated (24/page), mobile-first.

  This page used to render all 2,728 facilities into ~9.2 MB of HTML. It now
  renders one page of cards; search / type / region / geo params carry across
  pages via the paginator. The map receives ONLY the current page's markers.

  Foundation contract: tokens/classes from /assets/finder.css are adopted where
  present; the page keeps its own shell because of the Leaflet map wiring.
  Icons come from partials/finder/icon (Lucide); @includeIf so the page renders
  even if the foundation partial has not landed yet in this checkout.
--}}
<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('finder_seo.pages.care_map.title') }} — OpesCare Finder</title>
<meta name="description" content="{{ __('finder_seo.pages.care_map.description') }}">
<link rel="canonical" href="{{ route('public.care-map') . (app()->getLocale() === 'en' ? '' : '?lang=' . app()->getLocale()) }}">
<link rel="alternate" hreflang="en" href="{{ route('public.care-map') }}">
<link rel="alternate" hreflang="fr" href="{{ route('public.care-map', ['lang' => 'fr']) }}">
<link rel="alternate" hreflang="x-default" href="{{ route('public.care-map') }}">
<link rel="stylesheet" href="{{ asset('assets/finder.css') }}">
<link rel="stylesheet" href="{{ asset('css/leaflet.css') }}">
<script src="{{ asset('js/leaflet.js') }}" defer></script>
<script src="{{ asset('js/op-actions.js') }}?v={{ @filemtime(public_path('js/op-actions.js')) ?: '1' }}" defer></script>

@php
  $now         = now();
  $currentDay  = (int) $now->format('w');
  $currentTime = $now->format('H:i');

  $typeColors = [
    'hospital'          => '#0891B2',
    'clinic'            => '#059669',
    'pharmacy'          => '#D97706',
    'health_center'     => '#0D9488',
    'laboratory'        => '#1D4ED8',
    'dental'            => '#DB2777',
    'imaging_center'    => '#475569',
    'eye_clinic'        => '#0EA5E9',
    'specialist'        => '#0B3A66',
    'diagnostic_center' => '#0F4C81',
    'maternity'         => '#BE185D',
    'dispensary'        => '#65A30D',
    'blood_bank'        => '#DC2626',
  ];

  $typeIcons = [
    'pharmacy'   => 'pill',
    'laboratory' => 'droplet',
    'blood_bank' => 'droplet',
  ];

  $typeLabel = fn (string $slug) => \Illuminate\Support\Facades\Lang::has("finder_directory.types.{$slug}")
      ? __("finder_directory.types.{$slug}")
      : ucwords(str_replace('_', ' ', $slug));

  // Current filter params, re-emitted on every chip / form / page link.
  $baseParams = array_filter([
      'query'     => $queryText !== '' ? $queryText : null,
      'facility_type' => $activeType !== '' ? $activeType : null,
      'region'    => $activeRegion !== '' ? $activeRegion : null,
      'city'      => request('city') ?: null,
      'latitude'  => request('latitude') ?: null,
      'longitude' => request('longitude') ?: null,
      'radius'    => request('radius') ?: null,
      'lang'      => request('lang') ?: null,
  ], fn ($v) => $v !== null && $v !== '');

  $chipUrl = function (array $overrides) use ($baseParams) {
      $params = array_filter(
          array_merge($baseParams, $overrides),
          fn ($v) => $v !== null && $v !== ''
      );
      return route('public.care-map', $params);
  };

  $hasGeo = request('latitude') !== null && request('longitude') !== null;

  // Directory-wide chip counts are only honest while no other filter narrows
  // the set they describe.
  $showTypeCounts = $queryText === '' && $activeRegion === '' && !$hasGeo && !request('city');

  $lastPage = max(1, $facilities->lastPage());
@endphp

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --brand:        var(--f-brand, #0F4C81);
  --brand-hover:  var(--f-brand-ink, #1A6AAF);
  --brand-subtle: var(--f-wash, #EFF6FF);
  --bg:           var(--f-ground, #F1F5F9);
  --surface:      var(--f-surface, #FFFFFF);
  --border:       var(--f-line, #E2E8F0);
  --text-1:       var(--f-ink, #0F172A);
  --text-2:       var(--f-ink-soft, #475569);
  --text-3:       var(--f-muted, #94A3B8);
  --danger:       var(--f-danger, #DC2626);
  --radius:    0.625rem;
  --radius-lg: 1rem;
  --radius-full: 9999px;
  --shadow-sm: 0 1px 3px rgba(0,0,0,.07), 0 1px 2px rgba(0,0,0,.04);
  --shadow-md: 0 8px 24px rgba(0,0,0,.10), 0 2px 6px rgba(0,0,0,.05);
}

html { -webkit-text-size-adjust: 100%; }
body {
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  background: var(--bg); color: var(--text-1);
  -webkit-font-smoothing: antialiased;
}
.icon svg, .icon { display: inline-flex; }
.icon svg { width: 1em; height: 1em; }

/* ── Sticky header: brand row + search ─────────────────────────────── */
.hdr {
  position: sticky; top: 0; z-index: 300;
  background: var(--f-ink, #0A1628);
  border-bottom: 1px solid rgba(255,255,255,.08);
}
.hdr-row {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.6rem 1rem 0.25rem;
  max-width: 1400px; margin: 0 auto; width: 100%;
}
.brand { display: flex; align-items: center; gap: 0.6rem; text-decoration: none; min-width: 0; }
.brand-mark {
  width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
  background: linear-gradient(135deg, var(--f-brand, #1a6fb5), var(--f-brand-ink, #0aab9a));
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 15px;
}
.brand-name { font-family: 'Outfit', sans-serif; font-size: 0.95rem; font-weight: 700; color: #fff; line-height: 1.1; }
.brand-sub  { display: block; font-size: 0.55rem; font-weight: 500; color: rgba(255,255,255,.45); letter-spacing: .07em; text-transform: uppercase; }
.hdr-spacer { flex: 1; }
.btn-emergency {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.4rem 0.8rem; border-radius: var(--radius-full);
  background: rgba(220,38,38,.9); color: #fff;
  font-size: 0.75rem; font-weight: 700; text-decoration: none; white-space: nowrap;
}
.btn-emergency:hover { background: var(--danger); }
.search-row {
  display: flex; gap: 0.5rem; align-items: center;
  padding: 0.45rem 1rem 0.65rem;
  max-width: 1400px; margin: 0 auto; width: 100%;
}
.search-form { flex: 1; position: relative; display: flex; }
.search-form .icon {
  position: absolute; left: 0.8rem; top: 50%; transform: translateY(-50%);
  color: rgba(255,255,255,.4); font-size: 0.95rem; pointer-events: none;
}
.search-form input {
  width: 100%; font: inherit; font-size: 16px; /* 16px stops iOS zoom */
  padding: 0.55rem 0.9rem 0.55rem 2.4rem;
  border: 1.5px solid rgba(255,255,255,.14); border-radius: var(--radius-full);
  background: rgba(255,255,255,.08); color: #fff; outline: none;
}
.search-form input::placeholder { color: rgba(255,255,255,.4); }
.search-form input:focus { border-color: rgba(255,255,255,.4); background: rgba(255,255,255,.12); }
.btn-near {
  flex-shrink: 0; display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.5rem 0.8rem; border-radius: var(--radius-full);
  border: 1.5px solid rgba(255,255,255,.16); background: transparent;
  color: rgba(255,255,255,.75); font: inherit; font-size: 0.78rem; font-weight: 600;
  cursor: pointer; white-space: nowrap;
}
.btn-near:hover { border-color: rgba(255,255,255,.4); color: #fff; }

/* ── Filter chips ──────────────────────────────────────────────────── */
.chips-wrap { background: var(--surface); border-bottom: 1px solid var(--border); }
.chips {
  display: flex; gap: 0.4rem; align-items: center;
  overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch;
  padding: 0.5rem 1rem;
  max-width: 1400px; margin: 0 auto;
}
.chips::-webkit-scrollbar { display: none; }
.chips + .chips { padding-top: 0; }
.chip {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.35rem 0.8rem; border-radius: var(--radius-full);
  border: 1.5px solid var(--border); background: var(--surface);
  color: var(--text-2); font-size: 0.78rem; font-weight: 500;
  text-decoration: none; white-space: nowrap; flex-shrink: 0;
}
.chip:hover { border-color: var(--brand); color: var(--brand); }
.chip.on { background: var(--f-ink, #0A1628); border-color: var(--f-ink, #0A1628); color: #fff; font-weight: 600; }
.chip .dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.chip .n { font-size: 0.68rem; font-weight: 700; opacity: .65; }
.chips-label {
  font-size: 0.65rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
  color: var(--text-3); flex-shrink: 0; padding-right: 0.1rem;
}

/* ── Layout: list + map ────────────────────────────────────────────── */
.main { max-width: 1400px; margin: 0 auto; }
.results-meta {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.75rem 1rem 0.25rem;
}
.results-count { font-size: 0.82rem; color: var(--text-2); flex: 1; min-width: 0; }
.results-count strong { color: var(--text-1); }
.results-page { font-size: 0.72rem; color: var(--text-3); white-space: nowrap; }
.btn-map-toggle {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.4rem 0.9rem; border-radius: var(--radius-full);
  border: none; background: var(--brand); color: #fff;
  font: inherit; font-size: 0.78rem; font-weight: 600; cursor: pointer;
  white-space: nowrap; flex-shrink: 0;
}
.btn-map-toggle:hover { background: var(--brand-hover); }

.map-panel { display: none; padding: 0.5rem 1rem 0; }
.map-panel.open { display: block; }
#map {
  width: 100%; height: 320px;
  border-radius: var(--radius-lg); border: 1.5px solid var(--border);
  background: var(--surface);
}
.map-note { font-size: 0.7rem; color: var(--text-3); padding: 0.35rem 0.25rem 0; }

.cards {
  display: grid; grid-template-columns: 1fr; gap: 0.75rem;
  padding: 0.75rem 1rem;
}
@media (min-width: 640px) { .cards { grid-template-columns: 1fr 1fr; } }

/* ── Facility card ─────────────────────────────────────────────────── */
.card {
  background: var(--surface); border: 1.5px solid var(--border);
  border-radius: var(--radius-lg); padding: 0.95rem;
  display: flex; flex-direction: column; gap: 0.6rem;
  position: relative; overflow: hidden;
}
.card::before {
  content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
  background: var(--tc, var(--brand));
}
.card-top { display: flex; gap: 0.7rem; align-items: flex-start; }
.card-ic {
  width: 40px; height: 40px; border-radius: var(--radius); flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: color-mix(in srgb, var(--tc, var(--brand)) 12%, #fff);
  color: var(--tc, var(--brand)); font-size: 1.1rem;
}
.card-main { flex: 1; min-width: 0; }
.card-name-row { display: flex; justify-content: space-between; gap: 0.5rem; align-items: flex-start; }
.card-name { font-size: 0.9rem; font-weight: 600; line-height: 1.3; }
.card-name a { color: inherit; text-decoration: none; }
.card-name a:hover { color: var(--brand); }
.card-dist {
  font-size: 0.7rem; font-weight: 700; color: var(--brand);
  background: var(--brand-subtle); padding: 0.12rem 0.5rem;
  border-radius: var(--radius-full); white-space: nowrap; flex-shrink: 0;
}
.badges { display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.3rem; }
.badge {
  display: inline-flex; align-items: center; gap: 0.2rem;
  font-size: 0.66rem; font-weight: 600; padding: 0.14rem 0.5rem;
  border-radius: var(--radius-full); white-space: nowrap;
}
.badge-type { background: color-mix(in srgb, var(--tc, var(--brand)) 12%, #fff); color: var(--tc, var(--brand)); }
.badge-open   { background: var(--f-good-wash, #F0FDF4); color: var(--f-good, #15803D); }
.badge-closed { background: var(--f-danger-wash, #FEF2F2); color: var(--danger); }
.badge-24h    { background: var(--f-danger-wash, #FEF2F2); color: var(--danger); font-weight: 700; }
.badge-lic    { background: var(--f-good-wash, #DCFCE7); color: var(--f-good, #15803D); }
.badge-gov    { background: var(--f-warn-wash, #FEF3C7); color: var(--f-warn, #B45309); }
.card-loc {
  display: flex; align-items: flex-start; gap: 0.4rem;
  font-size: 0.78rem; color: var(--text-2); line-height: 1.4;
}
.card-loc .icon { color: var(--text-3); margin-top: 2px; flex-shrink: 0; font-size: 0.8rem; }
.card-actions { display: flex; gap: 0.5rem; align-items: center; margin-top: auto; }
.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem;
  padding: 0.5rem 0.8rem; border-radius: var(--radius);
  font-size: 0.78rem; font-weight: 600; text-decoration: none;
  white-space: nowrap; border: 1.5px solid transparent;
  min-height: 40px; /* touch target */
}
.btn-call { background: var(--f-good-wash, #ECFDF5); color: var(--f-good, #059669); border-color: var(--f-good, #A7F3D0); }
.btn-call:hover { background: var(--f-good, #059669); color: #fff; }
.btn-dir { background: var(--bg); color: var(--text-2); border-color: var(--border); }
.btn-dir:hover { border-color: var(--text-2); color: var(--text-1); }
.btn-view { background: var(--brand); color: #fff; margin-left: auto; }
.btn-view:hover { background: var(--brand-hover); }

/* ── Empty state ───────────────────────────────────────────────────── */
.empty {
  grid-column: 1 / -1;
  display: flex; flex-direction: column; align-items: center; text-align: center;
  padding: 3rem 1.5rem; gap: 0.75rem;
}
.empty .icon { font-size: 1.8rem; color: var(--text-3); }
.empty-title { font-size: 1rem; font-weight: 600; }
.empty-sub { font-size: 0.82rem; color: var(--text-3); max-width: 280px; line-height: 1.55; }
.btn-reset {
  padding: 0.5rem 1.25rem; border-radius: var(--radius-full);
  background: var(--brand); color: #fff; text-decoration: none;
  font-size: 0.82rem; font-weight: 600;
}
.btn-reset:hover { background: var(--brand-hover); }

/* ── Pagination ────────────────────────────────────────────────────── */
.pager {
  display: flex; align-items: center; justify-content: center; gap: 0.75rem;
  padding: 0.75rem 1rem 1.25rem;
}
.pager-btn {
  display: inline-flex; align-items: center; gap: 0.3rem;
  padding: 0.55rem 1rem; border-radius: var(--radius);
  background: var(--surface); border: 1.5px solid var(--border);
  color: var(--text-1); font-size: 0.82rem; font-weight: 600;
  text-decoration: none; min-height: 44px;
}
.pager-btn:hover { border-color: var(--brand); color: var(--brand); }
.pager-btn[aria-disabled="true"] { opacity: .4; pointer-events: none; }
.pager-state { font-size: 0.78rem; color: var(--text-2); white-space: nowrap; }

/* ── Disclaimer footer ─────────────────────────────────────────────── */
.disclaimer {
  margin: 0 1rem 1.5rem;
  background: var(--f-warn-wash, #FFFBEB); border: 1px solid var(--f-warn, #FDE68A); border-radius: var(--radius);
  padding: 0.7rem 0.9rem;
  font-size: 0.74rem; color: var(--f-warn, #92400E); line-height: 1.55;
  display: flex; gap: 0.5rem; align-items: flex-start;
}
.disclaimer .icon { color: var(--f-warn, #D97706); flex-shrink: 0; margin-top: 2px; }

/* ── Desktop: list + persistent map side by side ───────────────────── */
@media (min-width: 960px) {
  .main {
    display: grid;
    grid-template-columns: minmax(420px, 520px) 1fr;
    grid-template-areas:
      "meta  map"
      "cards map"
      "pager map"
      "disc  map";
    align-items: start;
    height: auto;
  }
  .results-meta { grid-area: meta; }
  .cards        { grid-area: cards; grid-template-columns: 1fr; }
  .pager        { grid-area: pager; justify-content: flex-start; }
  .disclaimer   { grid-area: disc; }
  .map-panel {
    grid-area: map; display: block !important;
    position: sticky; top: 118px;
    padding: 0.75rem 1rem 0.75rem 0;
  }
  #map { height: calc(100vh - 150px); min-height: 420px; }
  .btn-map-toggle { display: none; }
}
</style>
</head>
<body>

{{-- ── Header: brand + search (sticky) ─────────────────────────────── --}}
<header class="hdr">
  <div class="hdr-row">
    <a href="{{ route('public.landing') }}" class="brand">
      <span class="brand-mark icon" aria-hidden="true">@includeIf('partials.finder.icon', ['name' => 'map-pin'])</span>
      <span>
        <span class="brand-name">OpesCare</span>
        <span class="brand-sub">{{ __('finder_directory.brand_sub') }}</span>
      </span>
    </a>
    <span class="hdr-spacer"></span>
    <a href="{{ route('public.care-map.emergency') }}" class="btn-emergency">
      <span class="icon" aria-hidden="true">@includeIf('partials.finder.icon', ['name' => 'phone'])</span>
      {{ __('finder_directory.emergency') }}
    </a>
  </div>
  <div class="search-row" role="search" aria-label="{{ __('finder_directory.search_label') }}">
    <form class="search-form" method="GET" action="{{ route('public.care-map') }}">
      <span class="icon" aria-hidden="true">@includeIf('partials.finder.icon', ['name' => 'search'])</span>
      <input type="search" name="query" value="{{ $queryText }}"
             placeholder="{{ __('finder_directory.search_placeholder') }}"
             aria-label="{{ __('finder_directory.search_label') }}" autocomplete="off">
      @foreach(['facility_type' => $activeType, 'region' => $activeRegion, 'lang' => request('lang')] as $k => $v)
        @if($v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
      @endforeach
    </form>
    <button type="button" class="btn-near" data-call="locateMe" data-call-self aria-label="{{ __('finder_directory.locate_me') }}">
      <span class="icon" aria-hidden="true">@includeIf('partials.finder.icon', ['name' => 'navigation'])</span>
      {{ __('finder_directory.near_me') }}
    </button>
  </div>
</header>

{{-- ── Filter chips: type, then region ─────────────────────────────── --}}
<div class="chips-wrap">
  <nav class="chips" aria-label="{{ __('finder_directory.filter_by_type') }}">
    <a class="chip {{ $activeType === '' ? 'on' : '' }}" href="{{ $chipUrl(['facility_type' => null]) }}">
      {{ __('finder_directory.all_types') }}
      @if($showTypeCounts)<span class="n">{{ array_sum($typeCounts) }}</span>@endif
    </a>
    @foreach($typeCounts as $slug => $n)
      <a class="chip {{ $activeType === $slug ? 'on' : '' }}" href="{{ $chipUrl(['facility_type' => $slug]) }}">
        <span class="dot" style="background: {{ $typeColors[$slug] ?? '#64748B' }}"></span>
        {{ $typeLabel($slug) }}
        @if($showTypeCounts)<span class="n">{{ $n }}</span>@endif
      </a>
    @endforeach
  </nav>
  <nav class="chips" aria-label="{{ __('finder_directory.filter_by_region') }}">
    <span class="chips-label">{{ __('finder_directory.region_label') }}</span>
    <a class="chip {{ $activeRegion === '' ? 'on' : '' }}" href="{{ $chipUrl(['region' => null]) }}">
      {{ __('finder_directory.all_regions') }}
    </a>
    @foreach($regions as $r)
      <a class="chip {{ strcasecmp($activeRegion, $r) === 0 ? 'on' : '' }}" href="{{ $chipUrl(['region' => $r]) }}">
        {{ $r }}
      </a>
    @endforeach
  </nav>
</div>

<div class="main">

  {{-- ── Results meta + map toggle ──────────────────────────────────── --}}
  <div class="results-meta">
    <p class="results-count" aria-live="polite">
      <strong>{{ trans_choice('finder_directory.results_count', $total, ['count' => number_format($total)]) }}</strong>
      @if($lastPage > 1)
        <span class="results-page"> · {{ __('finder_directory.page_of', ['current' => $facilities->currentPage(), 'last' => $lastPage]) }}</span>
      @endif
    </p>
    <button type="button" class="btn-map-toggle" id="mapToggle" data-call="toggleMap" aria-expanded="false" aria-controls="mapPanel">
      <span class="icon" aria-hidden="true">@includeIf('partials.finder.icon', ['name' => 'map-pin'])</span>
      <span id="mapToggleLabel">{{ __('finder_directory.show_map') }}</span>
    </button>
  </div>

  {{-- ── Map (collapsible on mobile, persistent pane ≥960px) ───────── --}}
  <div class="map-panel" id="mapPanel">
    <div id="map" role="application" aria-label="{{ __('finder_directory.map_label') }}"></div>
    <p class="map-note">{{ __('finder_directory.map_page_note', ['count' => $facilities->count()]) }}</p>
  </div>

  {{-- ── Facility cards (this page only) ────────────────────────────── --}}
  <div class="cards" role="list">
    @forelse($facilities as $f)
      @php
        $slug  = strtolower(str_replace(' ', '_', $f->facility_type ?? 'other'));
        $tc    = $typeColors[$slug] ?? '#64748B';
        $dist  = isset($f->distance) ? round($f->distance, 1) : null;
        $phone = $f->dialablePhone();
        $locationStr = collect([$f->city, $f->region])->filter()->unique()->implode(', ');
        $dirUrl = ($f->latitude && $f->longitude)
            ? true
            : null;
        $profileUrl = route('public.care-map.profile', array_filter(['id' => $f->id, 'lang' => request('lang')]));

        // Open/closed, from this facility's own reported hours only.
        $isOpen = null; $is24h = false;
        if ($f->hours && $f->hours->isNotEmpty()) {
          $todayH = $f->hours->first(fn ($h) => (int) $h->day_of_week === $currentDay);
          if ($todayH) {
            if ($todayH->is_24_hours)   { $isOpen = true; $is24h = true; }
            elseif ($todayH->is_closed) { $isOpen = false; }
            elseif ($todayH->opens_at && $todayH->closes_at) {
              $isOpen = $currentTime >= substr($todayH->opens_at, 0, 5)
                     && $currentTime <= substr($todayH->closes_at, 0, 5);
            }
          }
        }
        $govVerif = ($f->verification_status ?? '') === 'government_verified';
        $licVerif = ($f->verification_status ?? '') === 'license_verified';
      @endphp

      <article class="card" role="listitem" style="--tc: {{ $tc }}">
        <div class="card-top">
          <span class="card-ic icon" aria-hidden="true">@includeIf('partials.finder.icon', ['name' => $typeIcons[$slug] ?? 'building-2'])</span>
          <div class="card-main">
            <div class="card-name-row">
              <h2 class="card-name"><a href="{{ $profileUrl }}">{{ $f->facility_name }}</a></h2>
              @if($dist !== null)
                <span class="card-dist">{{ __('finder_directory.distance_km', ['km' => $dist]) }}</span>
              @endif
            </div>
            <div class="badges">
              <span class="badge badge-type">{{ $typeLabel($slug) }}</span>
              @if($govVerif)
                <span class="badge badge-gov">{{ __('finder_directory.government_verified') }}</span>
              @elseif($licVerif)
                <span class="badge badge-lic">{{ __('finder_directory.license_verified') }}</span>
              @endif
              @if($is24h)
                <span class="badge badge-24h">{{ __('finder_directory.open_24h') }}</span>
              @elseif($isOpen === true)
                <span class="badge badge-open">{{ __('finder_directory.open_now') }}</span>
              @elseif($isOpen === false)
                <span class="badge badge-closed">{{ __('finder_directory.closed_now') }}</span>
              @endif
            </div>
          </div>
        </div>

        @if($locationStr)
          <div class="card-loc">
            <span class="icon" aria-hidden="true">@includeIf('partials.finder.icon', ['name' => 'map-pin'])</span>
            <span>{{ $locationStr }}</span>
          </div>
        @endif

        <div class="card-actions">
          @if($phone)
            <a href="tel:{{ $phone }}" class="btn btn-call" aria-label="{{ __('finder_directory.call') }} — {{ $f->facility_name }}">
              <span class="icon" aria-hidden="true">@includeIf('partials.finder.icon', ['name' => 'phone'])</span>
              {{ __('finder_directory.call') }}
            </a>
          @endif
          @if($dirUrl)
            <button type="button" class="btn btn-dir" data-call="directionsTo" data-args="[&quot;{{ $f->id }}&quot;]" aria-label="{{ __('finder_directory.directions') }} — {{ $f->facility_name }}">
              <span class="icon" aria-hidden="true">@includeIf('partials.finder.icon', ['name' => 'navigation'])</span>
              {{ __('finder_directory.directions') }}
            </button>
          @endif
          <a href="{{ $profileUrl }}" class="btn btn-view" aria-label="{{ __('finder_directory.view_profile') }} — {{ $f->facility_name }}">
            {{ __('finder_directory.view_profile') }}
            <span class="icon" aria-hidden="true">@includeIf('partials.finder.icon', ['name' => 'chevron-right'])</span>
          </a>
        </div>
      </article>
    @empty
      <div class="empty" role="status">
        <span class="icon" aria-hidden="true">@includeIf('partials.finder.icon', ['name' => 'map-pin'])</span>
        <p class="empty-title">{{ __('finder_directory.no_results_title') }}</p>
        <p class="empty-sub">{{ __('finder_directory.no_results_sub') }}</p>
        <a class="btn-reset" href="{{ route('public.care-map', array_filter(['lang' => request('lang')])) }}">{{ __('finder_directory.clear_filters') }}</a>
      </div>
    @endforelse
  </div>

  {{-- ── Pagination ─────────────────────────────────────────────────── --}}
  @if($lastPage > 1)
    <nav class="pager" aria-label="{{ __('finder_directory.page_of', ['current' => $facilities->currentPage(), 'last' => $lastPage]) }}">
      <a class="pager-btn"
         @if($facilities->onFirstPage()) aria-disabled="true" @else href="{{ $facilities->previousPageUrl() }}" rel="prev" @endif>
        {{ __('finder_directory.previous') }}
      </a>
      <span class="pager-state">{{ __('finder_directory.page_of', ['current' => $facilities->currentPage(), 'last' => $lastPage]) }}</span>
      <a class="pager-btn"
         @if(!$facilities->hasMorePages()) aria-disabled="true" @else href="{{ $facilities->nextPageUrl() }}" rel="next" @endif>
        {{ __('finder_directory.next') }}
      </a>
    </nav>
  @endif

  {{-- ── May-change disclaimer ──────────────────────────────────────── --}}
  <div class="disclaimer" role="note">
    <span class="icon" aria-hidden="true">@includeIf('partials.finder.icon', ['name' => 'clock'])</span>
    {{ __('finder_directory.disclaimer') }}
  </div>
</div>

@php
  // Markers for the CURRENT PAGE only — the whole point of the rewrite.
  $_markers = $facilities->getCollection()->map(fn ($f) => [
      'id'   => $f->id,
      'name' => $f->facility_name,
      'type' => strtolower(str_replace(' ', '_', $f->facility_type ?? 'other')),
      'city' => $f->city,
      'lat'  => (float) ($f->latitude ?? 0),
      'lng'  => (float) ($f->longitude ?? 0),
      'url'  => route('public.care-map.profile', array_filter(['id' => $f->id, 'lang' => request('lang')])),
  ])->values();
@endphp
<script @cspNonce>
const MARKERS     = @json($_markers, JSON_HEX_TAG | JSON_HEX_AMP);
const TYPE_COLORS = @json(collect($typeColors), JSON_HEX_TAG | JSON_HEX_AMP);
const I18N = {
  view:           @json(__('finder_directory.view_profile')),
  showMap:        @json(__('finder_directory.show_map')),
  hideMap:        @json(__('finder_directory.hide_map')),
  youAreHere:     @json(__('finder_directory.you_are_here')),
  geoUnsupported: @json(__('finder_directory.geo_unsupported')),
  geoDenied:      @json(__('finder_directory.geo_denied')),
};
const BASE_PARAMS = @json((object) $baseParams, JSON_HEX_TAG | JSON_HEX_AMP);
const DIRECTORY_URL = @json(route('public.care-map'));

let map = null, mapOpen = false;

function initMap() {
  if (map || typeof L === 'undefined') return;
  const el = document.getElementById('map');
  if (!el) return;

  map = L.map(el, { zoomControl: true }).setView([5.5, 12.0], 6);
  // Standard OSM tiles: the CARTO basemap now stamps "API KEY REQUIRED"
  // over every tile for keyless referers.
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19,
  }).addTo(map);

  const pts = MARKERS.filter(f => f.lat && f.lng);
  pts.forEach(f => {
    const color = TYPE_COLORS[f.type] || '#64748B';
    const icon = L.divIcon({
      html: `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="36" viewBox="0 0 28 36">
        <circle cx="14" cy="14" r="12.5" fill="${color}" stroke="#fff" stroke-width="2.5"/>
        <circle cx="14" cy="14" r="4" fill="rgba(255,255,255,.9)"/>
        <polygon points="9,27 19,27 14,35" fill="${color}"/></svg>`,
      className: '', iconSize: [28, 36], iconAnchor: [14, 35], popupAnchor: [0, -34],
    });
    const name = f.name.replace(/&/g,'&amp;').replace(/</g,'&lt;');
    const city = (f.city || '').replace(/&/g,'&amp;').replace(/</g,'&lt;');
    L.marker([f.lat, f.lng], { icon })
      .bindPopup(`<div style="padding:.75rem;font-family:Inter,sans-serif;min-width:170px">
        <div style="font-size:.85rem;font-weight:700;margin-bottom:.2rem">${name}</div>
        ${city ? `<div style="font-size:.72rem;color:var(--f-muted, #94A3B8);margin-bottom:.5rem">${city}</div>` : ''}
        <a href="${f.url}" style="font-size:.75rem;font-weight:600;color:#0F4C81;text-decoration:none">${I18N.view} &rarr;</a>
      </div>`, { maxWidth: 240 })
      .addTo(map);
  });
  if (pts.length) map.fitBounds(L.latLngBounds(pts.map(f => [f.lat, f.lng])).pad(0.2));
}

function toggleMap() {
  const panel = document.getElementById('mapPanel');
  const btn   = document.getElementById('mapToggle');
  mapOpen = !mapOpen;
  panel.classList.toggle('open', mapOpen);
  btn.setAttribute('aria-expanded', mapOpen ? 'true' : 'false');
  document.getElementById('mapToggleLabel').textContent = mapOpen ? I18N.hideMap : I18N.showMap;
  if (mapOpen) { initMap(); setTimeout(() => map && map.invalidateSize(), 60); }
}

// "Near me" re-runs the SEARCH with coordinates so the server sorts by real
// distance — no client-side re-sorting of a partial page.
function locateMe(btn) {
  if (!navigator.geolocation) { alert(I18N.geoUnsupported); return; }
  btn.disabled = true; btn.style.opacity = '.6';
  navigator.geolocation.getCurrentPosition(
    pos => {
      const p = new URLSearchParams(BASE_PARAMS);
      p.set('latitude',  pos.coords.latitude.toFixed(5));
      p.set('longitude', pos.coords.longitude.toFixed(5));
      p.set('radius', '50');
      window.location.href = DIRECTORY_URL + '?' + p.toString();
    },
    () => { btn.disabled = false; btn.style.opacity = ''; alert(I18N.geoDenied); }
  );
}

// In-app directions: show this facility + the visitor's location on OUR map,
// draw the straight line, label the distance — never opens an external map app.
var _userMarker = null, _routeLine = null;
function directionsTo(id) {
  var f = MARKERS.filter(function (m) { return String(m.id) === String(id); })[0];
  if (!f || !f.lat) return;
  initMap();
  if (window.innerWidth < 960 && !mapOpen && document.getElementById('mapToggle')) toggleMap();
  setTimeout(function () { map && map.invalidateSize(); }, 80);
  var dest = [f.lat, f.lng];
  document.getElementById('mapPanel').scrollIntoView({ behavior: 'smooth', block: 'center' });
  if (!navigator.geolocation) { map.setView(dest, 15); return; }
  navigator.geolocation.getCurrentPosition(function (pos) {
    var u = [pos.coords.latitude, pos.coords.longitude];
    if (_userMarker) map.removeLayer(_userMarker);
    _userMarker = L.marker(u).addTo(map).bindPopup(I18N.youAreHere || 'You are here');
    if (_routeLine) map.removeLayer(_routeLine);
    _routeLine = L.polyline([u, dest], { color: '#0F4C81', weight: 3, opacity: 0.75, dashArray: '6 6' }).addTo(map);
    map.fitBounds(L.latLngBounds([u, dest]).pad(0.25));
    var R = 6371, dLat = (dest[0]-u[0])*Math.PI/180, dLng = (dest[1]-u[1])*Math.PI/180;
    var a = Math.sin(dLat/2)*Math.sin(dLat/2) + Math.cos(u[0]*Math.PI/180)*Math.cos(dest[0]*Math.PI/180)*Math.sin(dLng/2)*Math.sin(dLng/2);
    var km = (R*2*Math.atan2(Math.sqrt(a), Math.sqrt(1-a))).toFixed(1);
    _routeLine.bindPopup(km + ' km').openPopup();
  }, function () { map.setView(dest, 15); });
}
window.directionsTo = directionsTo;

// Desktop pane is always visible: init once Leaflet is ready.
window.addEventListener('load', () => {
  if (window.innerWidth >= 960) initMap();
});
window.addEventListener('resize', () => {
  if (window.innerWidth >= 960) initMap();
  if (map) map.invalidateSize();
});
@if($hasGeo)
// A geo search is active: show the searched point on the map.
window.addEventListener('load', () => {
  if (window.innerWidth >= 960 && map) {
    L.marker([{{ (float) request('latitude') }}, {{ (float) request('longitude') }}], {
      icon: L.divIcon({ html: '<div style="width:14px;height:14px;border-radius:50%;background:#0F4C81;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.35)"></div>', className: '', iconSize: [14,14], iconAnchor: [7,7] })
    }).addTo(map).bindPopup(I18N.youAreHere);
  }
});
@endif
</script>
</body>
</html>

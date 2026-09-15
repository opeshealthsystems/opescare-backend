{{--
  Public facility profile — mobile-first, built on the Finder foundation
  (/assets/finder.css tokens + components, Lucide icons via partials/finder/icon).

  Truthfulness contract:
   - Phone renders ONLY via CareFacility::dialablePhone() / realValue() — the
     registry left the literal 'N/A' in phone_primary on most rows and nothing
     may offer a tel: link to it.
   - Availability is REPORTED data. Sections render only for rows a facility
     actually reported; an empty state says "not reported", never "not
     available". No freshness is asserted for a row without a report date.
   - Provenance is stated (national registry / source_attribution verbatim,
     which carries the © OpenStreetMap contributors credit), and the
     may-change disclaimer is always present.
   - The "Book appointment" CTA renders only when the controller proved the
     listing is backed by a live operational facility ($bookable).
--}}
<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@include('partials.finder.schema.facility-meta', ['facility' => $facility])
@include('partials.finder.schema.facility', ['facility' => $facility])
@include('partials.finder.schema.breadcrumbs', ['facility' => $facility])
<link rel="stylesheet" href="{{ asset('assets/finder.css') }}">

@php
  use App\Models\CareFacility;
  use Illuminate\Support\Facades\Lang;

  \Illuminate\Support\Carbon::setLocale(app()->getLocale());

  $slug = strtolower(str_replace(' ', '_', $facility->facility_type ?? 'other'));
  $typeLabel = Lang::has("finder_directory.types.{$slug}")
      ? __("finder_directory.types.{$slug}")
      : ucwords(str_replace('_', ' ', $slug));
  $typeIcon = ['pharmacy' => 'pill', 'laboratory' => 'droplet', 'blood_bank' => 'droplet'][$slug] ?? 'building-2';

  $phone   = $facility->dialablePhone();
  $email   = CareFacility::realValue($facility->email);
  $website = CareFacility::realValue($facility->website);
  $websiteHref = $website && !preg_match('#^https?://#i', $website) ? 'https://' . $website : $website;

  $placeLine = collect([$facility->city, $facility->region])->filter()->unique()->implode(', ');
  $dirUrl = ($facility->latitude && $facility->longitude)
      ? true
      : null;

  $backUrl = route('public.care-map', array_filter(['lang' => request('lang')]));

  // Open / closed — from this facility's own reported hours only.
  $currentDay  = (int) now()->format('w');
  $currentTime = now()->format('H:i');
  $isOpen = null; $is24h = false;
  if ($facility->hours && $facility->hours->isNotEmpty()) {
      $todayH = $facility->hours->first(fn ($h) => (int) $h->day_of_week === $currentDay);
      if ($todayH) {
          if ($todayH->is_24_hours)   { $isOpen = true; $is24h = true; }
          elseif ($todayH->is_closed) { $isOpen = false; }
          elseif ($todayH->opens_at && $todayH->closes_at) {
              $isOpen = $currentTime >= substr($todayH->opens_at, 0, 5)
                     && $currentTime <= substr($todayH->closes_at, 0, 5);
          }
      }
  }

  $hasHours    = ($facility->hours?->count() ?? 0) > 0;
  $hasServices = ($facility->services?->count() ?? 0) > 0;
  $hasIns      = ($facility->insurances?->count() ?? 0) > 0;
  $stockRows   = $facility->pharmacyStock ?? collect();
  $bloodRows   = $facility->bloodAvailability ?? collect();
  $labRows     = $facility->labTests ?? collect();
  $hasAvail    = $stockRows->isNotEmpty() || $bloodRows->isNotEmpty() || $labRows->isNotEmpty();

  $statusLabel = fn ($s) => Lang::has('finder_directory.statuses.' . $s)
      ? __('finder_directory.statuses.' . $s)
      : ucfirst(str_replace('_', ' ', (string) $s));
  $statusClass = fn ($s) => match ($s) {
      'in_stock', 'available'        => 'f-badge--good',
      'low_stock', 'limited'         => 'f-badge--warn',
      'out_of_stock', 'unavailable'  => 'f-badge--danger',
      default                        => '',
  };
  $reportedLine = fn ($row) => $row->last_updated_at
      ? __('finder_directory.reported_time', ['time' => \Illuminate\Support\Carbon::parse($row->last_updated_at)->diffForHumans()])
      : __('finder_directory.not_dated');

  $provenance = trim((string) $facility->source_attribution) !== ''
      ? __('finder_directory.provenance_source', ['source' => $facility->source_attribution])
      : __('finder_directory.provenance_registry');

  $langParam = fn (string $l) => request()->fullUrlWithQuery(['lang' => $l]);
@endphp

<style>
/* Page-specific layer on top of finder.css — tokens only, no new colors. */
.p-wrap { max-width: 760px; margin-inline: auto; padding: 16px 16px 48px; }
.p-stack { display: flex; flex-direction: column; gap: 14px; }

.p-head .f-badge { margin-bottom: 10px; }
.p-name { font-size: clamp(1.35rem, 4vw + 0.6rem, 1.9rem); line-height: 1.2; font-weight: 800; letter-spacing: -0.015em; margin: 0 0 6px; }
.p-place { display: flex; align-items: center; gap: 6px; color: var(--f-muted); font-size: 0.9rem; margin: 0; }

.p-actions { display: flex; flex-wrap: wrap; gap: 10px; }
.p-actions .f-btn { flex: 1 1 auto; }
@media (min-width: 640px) { .p-actions .f-btn { flex: 0 1 auto; } }

.p-card-title { display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 750; letter-spacing: 0.06em; text-transform: uppercase; color: var(--f-muted); margin: 0 0 12px; }
.p-row { display: flex; align-items: flex-start; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--f-line); font-size: 0.9rem; }
.p-row:last-child { border-bottom: none; }
.p-row-label { color: var(--f-muted); font-size: 0.8rem; min-width: 78px; flex-shrink: 0; padding-top: 1px; }
.p-row-value { flex: 1; min-width: 0; overflow-wrap: anywhere; margin: 0; }
.p-row-value a { text-decoration: none; font-weight: 600; }
.p-row-value a:hover { text-decoration: underline; }

.p-hours-row { display: flex; justify-content: space-between; gap: 10px; padding: 7px 0; border-bottom: 1px solid var(--f-line); font-size: 0.9rem; }
.p-hours-row:last-child { border-bottom: none; }
.p-hours-row.is-today { font-weight: 750; color: var(--f-brand-ink); }
.p-hours-closed { color: var(--f-muted); font-style: italic; }
.p-hours-24 { color: var(--f-good); font-weight: 650; }

.p-svc { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--f-line); font-size: 0.9rem; }
.p-svc:last-child { border-bottom: none; }

.p-avail-item { display: flex; flex-wrap: wrap; align-items: baseline; gap: 6px 10px; padding: 10px 0; border-bottom: 1px solid var(--f-line); }
.p-avail-item:last-child { border-bottom: none; }
.p-avail-name { font-weight: 650; font-size: 0.92rem; }
.p-avail-sub { width: 100%; display: flex; flex-wrap: wrap; gap: 6px 12px; align-items: center; }

.p-chips { display: flex; flex-wrap: wrap; gap: 8px; }

.p-note { display: flex; gap: 8px; align-items: flex-start; background: var(--f-warn-wash); border: 1px solid var(--f-line); border-radius: var(--f-r); padding: 10px 12px; font-size: 0.85rem; color: var(--f-warn); line-height: 1.55; }
.p-note .f-icon { flex-shrink: 0; margin-top: 2px; }
.p-provenance { font-size: 0.85rem; color: var(--f-ink-soft); line-height: 1.6; margin: 0 0 8px; }
.p-claim { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--f-line); font-size: 0.88rem; }
.p-back { display: inline-flex; align-items: center; gap: 4px; text-decoration: none; font-weight: 650; font-size: 0.9rem; color: var(--f-brand); white-space: nowrap; }
.p-emergency { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 999px; background: var(--f-danger-wash); color: var(--f-danger); font-weight: 750; font-size: 0.8rem; text-decoration: none; white-space: nowrap; }
</style>
</head>
<body>

<header class="f-header">
  <div class="f-container f-header__row">
    <a class="p-back" href="{{ $backUrl }}">
      @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
      {{ __('finder_directory.back_to_directory') }}
    </a>
    <div class="f-header__actions">
      <nav class="f-lang" aria-label="EN / FR">
        <a href="{{ $langParam('en') }}" @if(app()->getLocale() === 'en') class="is-active" aria-current="true" @endif>EN</a>
        <a href="{{ $langParam('fr') }}" @if(app()->getLocale() === 'fr') class="is-active" aria-current="true" @endif>FR</a>
      </nav>
      <a class="p-emergency" href="{{ route('public.care-map.emergency', array_filter(['lang' => request('lang')])) }}">
        @include('partials.finder.icon', ['name' => 'phone', 'size' => 13])
        {{ __('finder_directory.emergency') }}
      </a>
    </div>
  </div>
</header>

<main class="p-wrap p-stack">

  {{-- ── Identity ─────────────────────────────────────────────────────── --}}
  <section class="f-card p-head">
    <span class="f-badge">
      @include('partials.finder.icon', ['name' => $typeIcon, 'size' => 13])
      {{ $typeLabel }}
    </span>
    @if($is24h)
      <span class="f-badge f-badge--good">{{ __('finder_directory.open_24h') }}</span>
    @elseif($isOpen === true)
      <span class="f-badge f-badge--good">{{ __('finder_directory.open_now') }}</span>
    @elseif($isOpen === false)
      <span class="f-badge f-badge--danger">{{ __('finder_directory.closed_now') }}</span>
    @endif

    <h1 class="p-name">{{ $facility->facility_name }}</h1>
    @if($placeLine)
      <p class="p-place">
        @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 15])
        {{ $placeLine }}
      </p>
    @endif
  </section>

  @include('partials.finder.map', ['facilities' => [$facility], 'single' => true, 'mapId' => 'facility-map', 'height' => '300px'])

  {{-- ── Actions ──────────────────────────────────────────────────────── --}}
  @if($phone || $dirUrl || $bookable)
  <div class="p-actions">
    @if($phone)
      <a class="f-btn f-btn--primary" href="tel:{{ $phone }}">
        @include('partials.finder.icon', ['name' => 'phone', 'size' => 16])
        {{ __('finder_directory.call') }}
      </a>
    @endif
    @if($bookable)
      <a class="f-btn {{ $phone ? '' : 'f-btn--primary' }}" href="{{ route('portals.patient.appointments.book') }}">
        @include('partials.finder.icon', ['name' => 'calendar', 'size' => 16])
        {{ __('finder_directory.book_appointment') }}
      </a>
    @endif
    @if($dirUrl)
      <button type="button" class="f-btn" data-finder-directions="{{ $facility->id }}">
        @include('partials.finder.icon', ['name' => 'navigation', 'size' => 16])
        {{ __('finder_directory.directions') }}
      </button>
    @endif
  </div>
  @if($bookable)
    <p class="f-small f-muted" style="margin:-4px 0 0 2px;">{{ __('finder_directory.booking_hint') }}</p>
  @endif
  @endif

  {{-- ── About ────────────────────────────────────────────────────────── --}}
  @if(trim((string) $facility->description) !== '')
  <section class="f-card">
    <h2 class="p-card-title">
      @include('partials.finder.icon', ['name' => 'info', 'size' => 14])
      {{ __('finder_directory.about_heading') }}
    </h2>
    <p style="margin:0; font-size:0.92rem; line-height:1.65;">{{ $facility->description }}</p>
  </section>
  @endif

  {{-- ── Contact & location ───────────────────────────────────────────── --}}
  <section class="f-card">
    <h2 class="p-card-title">
      @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 14])
      {{ __('finder_directory.contact_heading') }}
    </h2>

    @if(trim((string) $facility->address) !== '')
      <div class="p-row">
        <span class="p-row-label">{{ __('finder_directory.address_label') }}</span>
        <span class="p-row-value">{{ collect([$facility->address, $facility->city, $facility->region])->map(fn ($v) => trim((string) $v))->filter()->unique(fn ($v) => mb_strtolower($v))->implode(', ') }}</span>
      </div>
    @elseif($placeLine)
      <div class="p-row">
        <span class="p-row-label">{{ __('finder_directory.region_label') }}</span>
        <span class="p-row-value">{{ $placeLine }}</span>
      </div>
    @endif

    @if($phone)
      <div class="p-row">
        <span class="p-row-label">{{ __('finder_directory.phone_label') }}</span>
        <span class="p-row-value"><a href="tel:{{ $phone }}">{{ $phone }}</a></span>
      </div>
    @endif

    @if($email)
      <div class="p-row">
        <span class="p-row-label">{{ __('finder_directory.email_label') }}</span>
        <span class="p-row-value"><a href="mailto:{{ $email }}">{{ $email }}</a></span>
      </div>
    @endif

    @if($website)
      <div class="p-row">
        <span class="p-row-label">{{ __('finder_directory.website_label') }}</span>
        <span class="p-row-value">
          <a href="{{ $websiteHref }}" target="_blank" rel="noopener noreferrer">
            {{ $website }}
            @include('partials.finder.icon', ['name' => 'external-link', 'size' => 12])
          </a>
        </span>
      </div>
    @endif
  </section>

  {{-- ── Opening hours (only if reported) ─────────────────────────────── --}}
  @if($hasHours)
  <section class="f-card">
    <h2 class="p-card-title">
      @include('partials.finder.icon', ['name' => 'clock', 'size' => 14])
      {{ __('finder_directory.hours_heading') }}
    </h2>
    @foreach($facility->hours->sortBy('day_of_week') as $h)
      <div class="p-hours-row {{ (int) $h->day_of_week === $currentDay ? 'is-today' : '' }}">
        <span>{{ __('finder_directory.days.' . (int) $h->day_of_week) }}</span>
        @if($h->is_24_hours)
          <span class="p-hours-24">{{ __('finder_directory.open_24h') }}</span>
        @elseif($h->is_closed)
          <span class="p-hours-closed">{{ __('finder_directory.closed_day') }}</span>
        @elseif($h->opens_at && $h->closes_at)
          <span>{{ substr($h->opens_at, 0, 5) }} – {{ substr($h->closes_at, 0, 5) }}</span>
        @else
          <span class="p-hours-closed">—</span>
        @endif
      </div>
    @endforeach
  </section>
  @endif

  {{-- ── Services (only if listed) ────────────────────────────────────── --}}
  @if($hasServices)
  <section class="f-card">
    <h2 class="p-card-title">
      @include('partials.finder.icon', ['name' => 'building-2', 'size' => 14])
      {{ __('finder_directory.services_heading') }}
    </h2>
    @foreach($facility->services as $svc)
      <div class="p-svc">
        <span>{{ $svc->service_name }}</span>
        @if($svc->appointment_required)
          <span class="f-badge f-badge--warn">{{ __('finder_directory.appointment_required') }}</span>
        @endif
      </div>
    @endforeach
  </section>
  @endif

  {{-- ── Published prices (facility service catalogue, non-lab) ───────── --}}
  @php
    $priceList = app(\App\Modules\CareMap\Services\FacilityPriceListService::class)->forListing($facility);
  @endphp
  @if($priceList->isNotEmpty())
  <section class="f-card" id="price-list">
    <h2 class="p-card-title">
      @include('partials.finder.icon', ['name' => 'list-ordered', 'size' => 14])
      {{ __('finder_directory.price_list_heading') }}
    </h2>
    @foreach($priceList as $row)
      <div class="p-svc">
        <span>{{ $row['name'] }}</span>
        @if($row['price_xaf'] !== null)
          <span class="f-badge">{{ __('finder_directory.price_xaf', ['amount' => number_format($row['price_xaf'], 0, ',', ' ')]) }}</span>
        @else
          <span class="f-small f-muted">{{ __('finder_directory.price_not_published') }}</span>
        @endif
      </div>
    @endforeach
    <p class="f-small f-muted" style="margin:8px 0 0; line-height:1.6;">{{ __('finder_directory.price_list_note') }}</p>
  </section>
  @endif

  {{-- ── Reported availability — rendered ONLY from reported rows ─────── --}}
  <section class="f-card">
    <h2 class="p-card-title">
      @include('partials.finder.icon', ['name' => 'refresh-cw', 'size' => 14])
      {{ __('finder_directory.availability_heading') }}
    </h2>

    @if(!$hasAvail)
      <p class="f-small f-muted" style="margin:0; line-height:1.6;">{{ __('finder_directory.no_availability') }}</p>
    @else

      @if($stockRows->isNotEmpty())
        <h3 class="p-card-title" style="margin-top:6px;">
          @include('partials.finder.icon', ['name' => 'pill', 'size' => 13])
          {{ __('finder_directory.pharmacy_stock_heading') }}
        </h3>
        @foreach($stockRows as $row)
          <div class="p-avail-item">
            <span class="p-avail-name">{{ $row->medicine_name }}</span>
            @if(collect([$row->strength, $row->form])->filter()->isNotEmpty())
              <span class="f-small f-muted">{{ collect([$row->strength, $row->form])->filter()->implode(' · ') }}</span>
            @endif
            <span class="p-avail-sub">
              <span class="f-badge {{ $statusClass($row->availability_status) }}">{{ $statusLabel($row->availability_status) }}</span>
              <span class="f-small f-muted">{{ $reportedLine($row) }}</span>
            </span>
          </div>
        @endforeach
      @endif

      @if($bloodRows->isNotEmpty())
        <h3 class="p-card-title" style="margin-top:6px;">
          @include('partials.finder.icon', ['name' => 'droplet', 'size' => 13])
          {{ __('finder_directory.blood_heading') }}
        </h3>
        @foreach($bloodRows as $row)
          <div class="p-avail-item">
            <span class="p-avail-name">{{ $row->blood_group }}</span>
            @if($row->component_type)
              <span class="f-small f-muted">{{ ucfirst(str_replace('_', ' ', $row->component_type)) }}</span>
            @endif
            <span class="p-avail-sub">
              <span class="f-badge {{ $statusClass($row->availability_status) }}">{{ $statusLabel($row->availability_status) }}</span>
              <span class="f-small f-muted">{{ $reportedLine($row) }}</span>
            </span>
          </div>
        @endforeach
      @endif

      @if($labRows->isNotEmpty())
        <h3 class="p-card-title" style="margin-top:6px;">
          @include('partials.finder.icon', ['name' => 'droplet', 'size' => 13])
          {{ __('finder_directory.lab_tests_heading') }}
        </h3>
        @foreach($labRows as $row)
          <div class="p-avail-item">
            <span class="p-avail-name">{{ $row->test_name }}</span>
            @if($row->turnaround_time)
              <span class="f-small f-muted">{{ __('finder_directory.turnaround_label', ['time' => $row->turnaround_time]) }}</span>
            @endif
            <span class="p-avail-sub">
              <span class="f-badge {{ $statusClass($row->availability_status) }}">{{ $statusLabel($row->availability_status) }}</span>
              @if($row->requires_doctor_order)
                <span class="f-badge f-badge--warn">{{ __('finder_directory.requires_doctor_order') }}</span>
              @endif
              <span class="f-small f-muted">{{ $reportedLine($row) }}</span>
            </span>
          </div>
        @endforeach
      @endif

    @endif
  </section>

  {{-- ── Insurance (only if listed) ───────────────────────────────────── --}}
  @if($hasIns)
  <section class="f-card">
    <h2 class="p-card-title">
      @include('partials.finder.icon', ['name' => 'info', 'size' => 14])
      {{ __('finder_directory.insurance_heading') }}
    </h2>
    <div class="p-chips">
      @foreach($facility->insurances as $ins)
        <span class="f-chip" style="pointer-events:none;">{{ $ins->insurance_name }}</span>
      @endforeach
    </div>
  </section>
  @endif

  {{-- ── Provenance + may-change disclaimer ───────────────────────────── --}}
  <section class="f-card">
    <h2 class="p-card-title">
      @include('partials.finder.icon', ['name' => 'info', 'size' => 14])
      {{ __('finder_directory.provenance_heading') }}
    </h2>
    <p class="p-provenance">{{ $provenance }}</p>
    <div class="p-note" role="note">
      @include('partials.finder.icon', ['name' => 'clock', 'size' => 15])
      <span>{{ __('finder_directory.disclaimer') }}</span>
    </div>
    @unless($facility->isClaimed())
      <div class="p-claim">
        <span class="f-muted">{{ __('finder_directory.claim_question') }}</span>
        <a class="p-back" href="{{ route('public.care-map.claim', array_filter(['id' => $facility->id, 'lang' => request('lang')])) }}">
          {{ __('finder_directory.claim_cta') }}
          @include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 15])
        </a>
      </div>
    @endunless
  </section>

</main>
</body>
</html>

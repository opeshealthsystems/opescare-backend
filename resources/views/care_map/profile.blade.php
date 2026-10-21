{{--
  Public facility profile. Approved design: docs/design/onboarding-2026-10-21/profile.html.
  Built on the public finder shell (care_map/_finder_nav + css/finder-pages.css,
  .fp scope) plus css/finder-profile.css. Lucide icons via partials/finder/icon.

  Truthfulness contract — every block below renders ONLY from real data:
   - Phone renders only via CareFacility::dialablePhone() / realValue(): the
     registry left the literal 'N/A' in phone_primary on most rows.
   - Badges: type always; licence/government verified only from
     verification_status; "Managed by the facility" only when isClaimed();
     "Books online" only when the controller proved $bookable.
   - Availability (medicines, lab tests, blood) goes through the SAME
     source_system allow-list the finders use (AvailabilityProvenance::isLive
     with each model's LIVE_SOURCE_SYSTEMS). Seeded/demo rows never show.
     "Reported X ago" comes from the row's own last_updated_at, never invented.
   - Open/closed is read from this facility's own reported hours only.
   - Empty availability means "not reported", never "not available".
   - Provenance (registry / source_attribution verbatim) and the may-change
     disclaimer are always present.
--}}
@extends('layouts.finder')
@section('seo_head')
@include('partials.finder.schema.facility-meta', ['facility' => $facility])
@include('partials.finder.schema.facility', ['facility' => $facility])
@include('partials.finder.schema.breadcrumbs', ['facility' => $facility])
@endsection
@push('head')
@include('public.partials._od_pages_css')
<link rel="stylesheet" href="{{ asset('css/finder-pages.css') }}?v={{ @filemtime(public_path('css/finder-pages.css')) ?: 1 }}">
<link rel="stylesheet" href="{{ asset('css/finder-profile.css') }}?v={{ @filemtime(public_path('css/finder-profile.css')) ?: 1 }}">
@endpush

@php
  use App\Models\BloodAvailability;
  use App\Models\CareFacility;
  use App\Models\LabTestAvailability;
  use App\Models\PharmacyStockAvailability;
  use App\Support\AvailabilityProvenance;
  use App\Support\CarePlaces;
  use App\Support\LocaleNumber;
  use Illuminate\Support\Carbon;
  use Illuminate\Support\Facades\Lang;

  Carbon::setLocale(app()->getLocale());
  $nearby = $nearby ?? collect();

  $langQ = request('lang') === 'fr' ? ['lang' => 'fr'] : [];

  $typeIcons = [
    'pharmacy' => 'pill', 'laboratory' => 'flask-conical', 'blood_bank' => 'droplet',
    'clinic' => 'stethoscope', 'health_center' => 'stethoscope', 'dispensary' => 'stethoscope',
    'dental' => 'smile', 'maternity' => 'baby', 'imaging_center' => 'scan-line',
    'diagnostic_center' => 'scan-line', 'eye_clinic' => 'eye', 'specialist' => 'stethoscope',
    'hospital' => 'building-2',
  ];
  $slug      = strtolower(str_replace(' ', '_', $facility->facility_type ?? 'other'));
  $typeLabel = \App\Support\FacilityTypeLabel::label($slug);
  $typeIcon  = $typeIcons[$slug] ?? 'building-2';

  // ── Contact ─────────────────────────────────────────────────────────
  $phone   = $facility->dialablePhone();
  $email   = CareFacility::realValue($facility->email);
  $website = CareFacility::realValue($facility->website);
  $websiteHref = $website && !preg_match('#^https?://#i', $website) ? 'https://' . $website : $website;
  $city    = trim((string) $facility->city);
  $region  = trim((string) $facility->region);
  $placeLine   = collect([$city, $region])->filter()->unique(fn ($v) => mb_strtolower($v))->implode(', ');
  $addressLine = collect([$facility->address, $city, $region])->map(fn ($v) => trim((string) $v))->filter()
      ->unique(fn ($v) => mb_strtolower($v))->implode(', ');
  $hasCoords = $facility->latitude !== null && $facility->longitude !== null
      && !((float) $facility->latitude === 0.0 && (float) $facility->longitude === 0.0);

  // ── Breadcrumbs: Directory › Region › City › Name ───────────────────
  $crumbs = [['label' => __('finder_pages.profile.directory'), 'url' => route('public.care-map', $langQ)]];
  if ($region !== '') {
      $crumbs[] = ['label' => $region, 'url' => route('public.care-map', $langQ + ['region' => $region])];
  }
  if ($city !== '' && mb_strtolower($city) !== mb_strtolower($region)) {
      $cityUrl = route('public.care-map', $langQ + ['city' => $city]);
      if (in_array($slug, CarePlaces::TYPES, true)) {
          $combo = CarePlaces::find(CarePlaces::typeSlug($slug), CarePlaces::citySlug($city));
          if ($combo !== null) {
              $cityUrl = route('public.care-map.place', ['type' => $combo['type_slug'], 'city' => $combo['city_slug']] + $langQ);
          }
      }
      $crumbs[] = ['label' => $city, 'url' => $cityUrl];
  }

  // ── Hours: today's status from this facility's own reported hours ──
  $hours       = ($facility->hours ?? collect())->sortBy('day_of_week')->values();
  $hasHours    = $hours->isNotEmpty();
  $currentDay  = (int) now()->format('w');
  $currentTime = now()->format('H:i');
  $openState = null; // ['class' => ..., 'text' => ...]
  $todayH = $hours->first(fn ($h) => (int) $h->day_of_week === $currentDay);
  if ($todayH) {
      $o = $todayH->opens_at ? substr($todayH->opens_at, 0, 5) : null;
      $c = $todayH->closes_at ? substr($todayH->closes_at, 0, 5) : null;
      if ($todayH->is_24_hours) {
          $openState = ['open' => true, 'text' => __('finder_pages.dir.open_24h_tag')];
      } elseif ($todayH->is_closed) {
          $openState = ['open' => false, 'text' => __('finder_pages.dir.closed_today')];
      } elseif ($o && $c) {
          $isOpen = $c > $o
              ? ($currentTime >= $o && $currentTime <= $c)
              : ($currentTime >= $o || $currentTime <= $c);   // overnight shift
          $openState = $isOpen
              ? ['open' => true,  'text' => __('finder_pages.dir.open_closes', ['time' => $c])]
              : ['open' => false, 'text' => $currentTime < $o
                    ? __('finder_pages.dir.opens_at', ['time' => $o])
                    : __('finder_directory.closed_now')];
      }
  }
  $is247 = $hours->count() === 7 && $hours->every(fn ($h) => (bool) $h->is_24_hours);

  // ── Availability, gated by the same allow-list the finders use ─────
  $stockRows = ($facility->pharmacyStock ?? collect())
      ->filter(fn ($r) => AvailabilityProvenance::isLive($r->source_system, PharmacyStockAvailability::LIVE_SOURCE_SYSTEMS))
      ->sortBy(fn ($r) => mb_strtolower((string) $r->medicine_name))->values();
  $labRows = ($facility->labTests ?? collect())
      ->filter(fn ($r) => AvailabilityProvenance::isLive($r->source_system, LabTestAvailability::LIVE_SOURCE_SYSTEMS))
      ->sortBy(fn ($r) => mb_strtolower((string) $r->test_name))->values();
  $bloodRows = ($facility->bloodAvailability ?? collect())
      ->filter(fn ($r) => $r->isReportedByRealSource())->values();
  $services   = ($facility->services ?? collect())->values();
  $insurances = ($facility->insurances ?? collect())->values();
  $bloodRelevant = in_array($slug, ['blood_bank', 'hospital'], true);

  $priceList = app(\App\Modules\CareMap\Services\FacilityPriceListService::class)->forListing($facility);

  $statusLabel = fn ($s) => Lang::has('finder_directory.statuses.' . $s)
      ? __('finder_directory.statuses.' . $s)
      : ucfirst(str_replace('_', ' ', (string) $s));
  $statusClass = fn ($s) => match ((string) $s) {
      'in_stock', 'available'       => 'fp-pill--ok',
      'low_stock', 'limited'        => 'fp-pill--low',
      'out_of_stock', 'unavailable' => 'fp-pill--out',
      default                       => 'fp-pill--muted',
  };
  $reportedLine = fn ($row) => $row->last_updated_at
      ? __('finder_directory.reported_time', ['time' => Carbon::parse($row->last_updated_at)->diffForHumans()])
      : __('finder_directory.not_dated');
  $fcfa = fn ($amount) => __('finder_directory.price_xaf', ['amount' => LocaleNumber::format($amount)]);
  $stockPrice = fn ($r) => ($r->price !== null && (float) $r->price > 0 && in_array(strtoupper((string) ($r->currency ?: 'XAF')), ['XAF', 'FCFA'], true))
      ? $fcfa($r->price) : null;
  $labPrice = fn ($r) => $r->price_xaf !== null && (int) $r->price_xaf > 0
      ? $fcfa($r->price_xaf)
      : (($r->price !== null && (float) $r->price > 0 && in_array(strtoupper((string) ($r->currency ?: 'XAF')), ['XAF', 'FCFA'], true)) ? $fcfa($r->price) : null);

  $svcIcons = [
    'emergency' => 'alert-triangle', 'maternal' => 'baby', 'paediatric' => 'baby', 'pediatric' => 'baby',
    'diagnostic' => 'flask-conical', 'laboratory' => 'flask-conical', 'imaging' => 'scan-line',
    'pharmacy' => 'pill', 'preventive' => 'shield-check', 'blood_bank' => 'droplet',
    'inpatient' => 'building-2', 'dental' => 'smile', 'eye' => 'eye', 'mental_health' => 'heart',
  ];

  // ── At a glance: only facts with real data behind them ─────────────
  $facts = [['label' => __('finder_pages.profile.fact_type'), 'value' => $typeLabel]];
  $own = (string) $facility->ownership_type;
  if ($own !== '' && Lang::has('finder_pages.profile.ownership.' . $own)) {
      $facts[] = ['label' => __('finder_pages.profile.fact_ownership'), 'value' => __('finder_pages.profile.ownership.' . $own)];
  }
  if ($is247) {
      $facts[] = ['label' => __('finder_pages.profile.fact_hours'), 'value' => __('finder_pages.profile.fact_24_7')];
  }
  if ($services->contains(fn ($s) => $s->service_category === 'emergency')) {
      $facts[] = ['label' => __('finder_pages.profile.fact_emergency'), 'value' => __('finder_pages.profile.fact_emergency_listed')];
  }
  if ($bookable) {
      $facts[] = ['label' => __('finder_pages.profile.fact_booking'), 'value' => __('finder_pages.profile.fact_booking_online')];
  }
  if ($services->isNotEmpty()) {
      $facts[] = ['label' => __('finder_pages.profile.fact_services'), 'value' => LocaleNumber::format($services->count())];
  }

  // ── Tabs: only sections that exist, with real counts ───────────────
  $tabs = array_filter([
      $services->isNotEmpty()   ? ['id' => 'services',  'label' => __('finder_pages.profile.tab_services'),  'count' => $services->count()] : null,
      $priceList->isNotEmpty()  ? ['id' => 'price-list','label' => __('finder_pages.profile.tab_prices'),    'count' => $priceList->count()] : null,
      $hasHours                 ? ['id' => 'hours',     'label' => __('finder_pages.profile.tab_hours'),     'count' => null] : null,
      $stockRows->isNotEmpty()  ? ['id' => 'medicines', 'label' => __('finder_pages.profile.tab_medicines'), 'count' => $stockRows->count()] : null,
      $labRows->isNotEmpty()    ? ['id' => 'lab-tests', 'label' => __('finder_pages.profile.tab_lab_tests'), 'count' => $labRows->count()] : null,
      $bloodRows->isNotEmpty()  ? ['id' => 'blood',     'label' => __('finder_pages.profile.tab_blood'),     'count' => $bloodRows->count()] : null,
      $insurances->isNotEmpty() ? ['id' => 'insurance', 'label' => __('finder_pages.profile.tab_insurance'), 'count' => $insurances->count()] : null,
  ]);

  $provenance = trim((string) $facility->source_attribution) !== ''
      ? __('finder_directory.provenance_source', ['source' => $facility->source_attribution])
      : __('finder_directory.provenance_registry');

  $verif = (string) $facility->verification_status;
  $rowLimit = 10;
@endphp

@section('content')
<div class="fp odp fpp">

@include('care_map._finder_nav', ['active' => 'facilities'])

<div class="fpp-wrap">

  <nav class="fpp-crumbs" aria-label="{{ __('finder_pages.profile.breadcrumb_label') }}">
    <ol>
      @foreach($crumbs as $crumb)
        <li><a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 14])</li>
      @endforeach
      <li aria-current="page"><span>{{ $facility->facility_name }}</span></li>
    </ol>
  </nav>

  {{-- ── Hero ─────────────────────────────────────────────────────────── --}}
  <section class="fpp-hero">
    <div class="fpp-cover" aria-hidden="true"></div>
    <div class="fpp-hbody">
      <div class="fpp-avatar" aria-hidden="true"><span>@include('partials.finder.icon', ['name' => $typeIcon, 'size' => 40])</span></div>
      <div class="fpp-id">
        <div class="fpp-badges">
          <span class="fp-pill fp-pill--info">@include('partials.finder.icon', ['name' => $typeIcon, 'size' => 13]){{ $typeLabel }}</span>
          @if($verif === 'government_verified')
            <span class="fp-pill fp-pill--ok">@include('partials.finder.icon', ['name' => 'shield-check', 'size' => 13]){{ __('finder_directory.government_verified') }}</span>
          @elseif($verif === 'license_verified')
            <span class="fp-pill fp-pill--ok">@include('partials.finder.icon', ['name' => 'shield-check', 'size' => 13]){{ __('finder_directory.license_verified') }}</span>
          @endif
          @if($facility->isClaimed())
            <span class="fp-pill fpp-pill--line">@include('partials.finder.icon', ['name' => 'user-check', 'size' => 13]){{ __('finder_pages.profile.badge_managed') }}</span>
          @endif
          @if($bookable)
            <span class="fp-pill fp-pill--info">@include('partials.finder.icon', ['name' => 'calendar-plus', 'size' => 13]){{ __('finder_pages.profile.badge_books') }}</span>
          @endif
        </div>
        <h1 class="fpp-name">{{ $facility->facility_name }}</h1>
        <div class="fpp-meta">
          @if($placeLine)
            <span>@include('partials.finder.icon', ['name' => 'map-pin', 'size' => 15]){{ $placeLine }}</span>
          @endif
          @if($openState)
            <span class="{{ $openState['open'] ? 'fpp-open' : 'fpp-closed' }}">@include('partials.finder.icon', ['name' => 'clock', 'size' => 15]){{ $openState['text'] }}</span>
          @endif
          @if($phone)
            <span>@include('partials.finder.icon', ['name' => 'phone', 'size' => 15])<a href="tel:{{ $phone }}">{{ $phone }}</a></span>
          @endif
        </div>
      </div>
      <div class="fpp-hacts">
        <button type="button" class="fp-btn" data-fpp-share data-url="{{ route('public.care-map.profile', $facility->id) }}" data-title="{{ $facility->facility_name }}" data-done="{{ __('finder_pages.profile.link_copied') }}" data-fail="{{ __('finder_pages.profile.copy_failed') }}">
          @include('partials.finder.icon', ['name' => 'share-2', 'size' => 16])<span data-fpp-label>{{ __('finder_pages.profile.share') }}</span>
        </button>
        @if($hasCoords)
          <button type="button" class="fp-btn" data-finder-directions="{{ $facility->id }}" data-fpp-to-map>
            @include('partials.finder.icon', ['name' => 'navigation', 'size' => 16]){{ __('finder_directory.directions') }}
          </button>
        @endif
        @if($phone)
          <a class="fp-btn" href="tel:{{ $phone }}">@include('partials.finder.icon', ['name' => 'phone', 'size' => 16]){{ __('finder_directory.call') }}</a>
        @endif
        @if($bookable)
          <a class="fp-btn fp-btn--primary" href="{{ route('portals.patient.appointments.book') }}">@include('partials.finder.icon', ['name' => 'calendar-plus', 'size' => 16]){{ __('finder_directory.book_appointment') }}</a>
        @endif
      </div>
    </div>
    @if(count($tabs))
      <nav class="fpp-tabs" aria-label="{{ __('finder_pages.profile.tabs_label') }}">
        @foreach($tabs as $tab)
          <a href="#{{ $tab['id'] }}">{{ $tab['label'] }}@if($tab['count'] !== null)<b>{{ LocaleNumber::format($tab['count']) }}</b>@endif</a>
        @endforeach
      </nav>
    @endif
  </section>
  @if($bookable)
    <p class="fpp-hint">{{ __('finder_directory.booking_hint') }}</p>
  @endif

  <div class="fpp-cols">
    <main class="fpp-main">

      {{-- At a glance --}}
      <section class="fpp-card">
        <h2>@include('partials.finder.icon', ['name' => 'info', 'size' => 18]){{ __('finder_pages.profile.glance') }}</h2>
        <dl class="fpp-facts">
          @foreach($facts as $fact)
            <div class="fpp-fact"><dt>{{ $fact['label'] }}</dt><dd>{{ $fact['value'] }}</dd></div>
          @endforeach
        </dl>
        @if(trim((string) $facility->description) !== '')
          <p class="fpp-desc">{{ $facility->description }}</p>
        @endif
      </section>

      {{-- Services --}}
      @if($services->isNotEmpty())
      <section class="fpp-card" id="services">
        <h2>@include('partials.finder.icon', ['name' => 'stethoscope', 'size' => 18]){{ __('finder_directory.services_heading') }}</h2>
        <ul class="fpp-svcs">
          @foreach($services as $svc)
            @php
              $svcSub = collect([
                  $svc->appointment_required ? __('finder_directory.appointment_required') : null,
                  $svc->walk_in_allowed ? __('finder_pages.profile.walk_in') : null,
                  $svc->telemedicine_available ? __('finder_pages.profile.telemedicine') : null,
                  trim((string) $svc->price_range) !== '' ? $svc->price_range : null,
              ])->filter()->implode(' · ');
            @endphp
            <li class="fpp-svc">
              <span class="fpp-ic">@include('partials.finder.icon', ['name' => $svcIcons[$svc->service_category] ?? 'stethoscope', 'size' => 18])</span>
              <div><b>{{ $svc->service_name }}</b>@if($svcSub !== '')<small>{{ $svcSub }}</small>@endif</div>
            </li>
          @endforeach
        </ul>
      </section>
      @endif

      {{-- Published prices (facility service catalogue, non-lab) --}}
      @if($priceList->isNotEmpty())
      <section class="fpp-card" id="price-list">
        <h2>@include('partials.finder.icon', ['name' => 'list-ordered', 'size' => 18]){{ __('finder_directory.price_list_heading') }}</h2>
        <table class="fpp-tbl">
          <tbody>
          @foreach($priceList as $row)
            <tr>
              <td>{{ $row['name'] }}</td>
              @if($row['price_xaf'] !== null)
                <td class="r"><b>{{ __('finder_directory.price_xaf', ['amount' => LocaleNumber::format($row['price_xaf'])]) }}</b></td>
              @else
                <td class="r fpp-muted">{{ __('finder_directory.price_not_published') }}</td>
              @endif
            </tr>
          @endforeach
          </tbody>
        </table>
        <p class="fpp-fresh">{{ __('finder_directory.price_list_note') }}</p>
      </section>
      @endif

      {{-- Medicines (allow-listed stock only) --}}
      @if($stockRows->isNotEmpty())
      <section class="fpp-card" id="medicines">
        <h2>@include('partials.finder.icon', ['name' => 'pill', 'size' => 18]){{ __('finder_directory.pharmacy_stock_heading') }}</h2>
        <table class="fpp-tbl">
          <thead><tr><th>{{ __('finder_pages.profile.col_medicine') }}</th><th>{{ __('finder_pages.profile.col_status') }}</th><th class="r">{{ __('finder_pages.profile.col_price') }}</th></tr></thead>
          @foreach($stockRows->chunk($rowLimit) as $ci => $chunk)
            @if($ci === 1)</table><details class="fpp-more"><summary>{{ __('finder_pages.profile.show_all', ['count' => LocaleNumber::format($stockRows->count())]) }}</summary><table class="fpp-tbl">@endif
            <tbody>
            @foreach($chunk as $row)
              <tr>
                <td>
                  {{ collect([$row->medicine_name, $row->strength, $row->form])->filter()->implode(' · ') }}
                  <small class="fpp-sub">{{ $reportedLine($row) }}</small>
                </td>
                <td><span class="fp-pill {{ $statusClass($row->availability_status) }}"><i class="fp-dot"></i>{{ $statusLabel($row->availability_status) }}</span></td>
                @if($p = $stockPrice($row))
                  <td class="r"><b>{{ $p }}</b></td>
                @else
                  <td class="r fpp-muted">{{ __('finder_pages.med.price_not_published') }}</td>
                @endif
              </tr>
            @endforeach
            </tbody>
          @endforeach
        </table>@if($stockRows->count() > $rowLimit)</details>@endif
      </section>
      @endif

      {{-- Lab tests (allow-listed only) --}}
      @if($labRows->isNotEmpty())
      <section class="fpp-card" id="lab-tests">
        <h2>@include('partials.finder.icon', ['name' => 'flask-conical', 'size' => 18]){{ __('finder_directory.lab_tests_heading') }}</h2>
        <table class="fpp-tbl">
          <thead><tr><th>{{ __('finder_pages.profile.col_test') }}</th><th>{{ __('finder_pages.profile.col_status') }}</th><th class="r">{{ __('finder_pages.profile.col_price') }}</th></tr></thead>
          @foreach($labRows->chunk($rowLimit) as $ci => $chunk)
            @if($ci === 1)</table><details class="fpp-more"><summary>{{ __('finder_pages.profile.show_all', ['count' => LocaleNumber::format($labRows->count())]) }}</summary><table class="fpp-tbl">@endif
            <tbody>
            @foreach($chunk as $row)
              <tr>
                <td>
                  {{ $row->test_name }}
                  <small class="fpp-sub">
                    {{ collect([
                        $row->turnaround_time ? __('finder_directory.turnaround_label', ['time' => $row->turnaround_time]) : null,
                        $row->requires_doctor_order ? __('finder_directory.requires_doctor_order') : null,
                        $reportedLine($row),
                    ])->filter()->implode(' · ') }}
                  </small>
                </td>
                <td><span class="fp-pill {{ $statusClass($row->availability_status) }}"><i class="fp-dot"></i>{{ $statusLabel($row->availability_status) }}</span></td>
                @if($p = $labPrice($row))
                  <td class="r"><b>{{ $p }}</b></td>
                @else
                  <td class="r fpp-muted">{{ __('finder_pages.med.price_not_published') }}</td>
                @endif
              </tr>
            @endforeach
            </tbody>
          @endforeach
        </table>@if($labRows->count() > $rowLimit)</details>@endif
      </section>
      @endif

      {{-- Blood (allow-listed only; honest empty state for blood banks / hospitals) --}}
      @if($bloodRows->isNotEmpty())
      <section class="fpp-card" id="blood">
        <h2>@include('partials.finder.icon', ['name' => 'droplet', 'size' => 18]){{ __('finder_directory.blood_heading') }}</h2>
        <table class="fpp-tbl">
          <thead><tr><th>{{ __('finder_pages.profile.col_group') }}</th><th>{{ __('finder_pages.profile.col_status') }}</th></tr></thead>
          <tbody>
          @foreach($bloodRows as $row)
            <tr>
              <td>
                <b>{{ $row->blood_group }}</b>@if($row->component_type) · {{ ucfirst(str_replace('_', ' ', $row->component_type)) }}@endif
                <small class="fpp-sub">{{ collect([
                    trim((string) $row->units_available_range) !== '' ? __('finder_pages.blood.available_range', ['range' => $row->units_available_range]) : null,
                    $reportedLine($row),
                ])->filter()->implode(' · ') }}</small>
              </td>
              <td><span class="fp-pill {{ $statusClass($row->availability_status) }}"><i class="fp-dot"></i>{{ $statusLabel($row->availability_status) }}</span></td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </section>
      @elseif($bloodRelevant)
      <section class="fpp-card">
        <h2>@include('partials.finder.icon', ['name' => 'droplet', 'size' => 18]){{ __('finder_directory.blood_heading') }}</h2>
        <div class="fpp-empty">@include('partials.finder.icon', ['name' => 'info', 'size' => 18])<p>{{ __('finder_pages.profile.blood_empty') }}</p></div>
      </section>
      @endif

      {{-- Insurance --}}
      @if($insurances->isNotEmpty())
      <section class="fpp-card" id="insurance">
        <h2>@include('partials.finder.icon', ['name' => 'shield', 'size' => 18]){{ __('finder_directory.insurance_heading') }}</h2>
        <ul class="fpp-chips">
          @foreach($insurances as $ins)
            <li>{{ $ins->insurance_name }}</li>
          @endforeach
        </ul>
      </section>
      @endif

      {{-- Caution + listing source (always) --}}
      <div class="fpp-note" role="note">
        @include('partials.finder.icon', ['name' => 'alert-triangle', 'size' => 18])
        <div>
          <p>{{ __('finder_directory.disclaimer') }}</p>
          <p class="fpp-prov"><b>{{ __('finder_directory.provenance_heading') }}:</b> {{ $provenance }}</p>
        </div>
      </div>
    </main>

    <aside class="fpp-side">
      {{-- Map + contact --}}
      <section class="fpp-card fpp-card--flush" id="contact">
        @if($hasCoords)
          <div class="fpp-map" id="fpp-map">
            @include('partials.finder.map', ['facilities' => [$facility], 'single' => true, 'mapId' => 'facility-map', 'height' => '220px'])
          </div>
        @endif
        <div class="fpp-in">
          <h2 class="fp-sr">{{ __('finder_directory.contact_heading') }}</h2>
          @if($addressLine !== '')
            <div class="fpp-row">@include('partials.finder.icon', ['name' => 'map-pin', 'size' => 18])<div><small>{{ __('finder_directory.address_label') }}</small><b>{{ $addressLine }}</b></div></div>
          @endif
          @if($phone)
            <div class="fpp-row">@include('partials.finder.icon', ['name' => 'phone', 'size' => 18])<div><small>{{ __('finder_directory.phone_label') }}</small><a href="tel:{{ $phone }}">{{ $phone }}</a></div></div>
          @endif
          @if($email)
            <div class="fpp-row">@include('partials.finder.icon', ['name' => 'mail', 'size' => 18])<div><small>{{ __('finder_directory.email_label') }}</small><a href="mailto:{{ $email }}">{{ $email }}</a></div></div>
          @endif
          @if($website)
            <div class="fpp-row">@include('partials.finder.icon', ['name' => 'globe', 'size' => 18])<div><small>{{ __('finder_directory.website_label') }}</small><a href="{{ $websiteHref }}" target="_blank" rel="noopener noreferrer">{{ $website }}</a></div></div>
          @endif
        </div>
        @if($hasCoords || $addressLine !== '')
          <div class="fpp-grid2">
            @if($hasCoords)
              <button type="button" class="fp-btn fp-btn--sm" data-finder-directions="{{ $facility->id }}">@include('partials.finder.icon', ['name' => 'navigation', 'size' => 15]){{ __('finder_directory.directions') }}</button>
            @endif
            @if($addressLine !== '')
              <button type="button" class="fp-btn fp-btn--sm" data-fpp-copy="{{ $addressLine }}" data-done="{{ __('finder_pages.profile.address_copied') }}" data-fail="{{ __('finder_pages.profile.copy_failed') }}">@include('partials.finder.icon', ['name' => 'link', 'size' => 15])<span data-fpp-label>{{ __('finder_pages.profile.copy_address') }}</span></button>
            @endif
          </div>
        @endif
      </section>

      {{-- Opening hours --}}
      @if($hasHours)
      <section class="fpp-card" id="hours">
        <h2>@include('partials.finder.icon', ['name' => 'clock', 'size' => 18]){{ __('finder_directory.hours_heading') }}</h2>
        <table class="fpp-hours">
          <tbody>
          @foreach($hours as $h)
            @php $isToday = (int) $h->day_of_week === $currentDay; $dayName = __('finder_directory.days.' . (int) $h->day_of_week); @endphp
            <tr @class(['is-today' => $isToday])>
              <td>{{ $isToday ? __('finder_pages.profile.today', ['day' => $dayName]) : $dayName }}</td>
              <td>
                @if($h->is_24_hours)
                  {{ __('finder_pages.dir.open_24h_tag') }}
                @elseif($h->is_closed)
                  <span class="fpp-muted">{{ __('finder_directory.closed_day') }}</span>
                @elseif($h->opens_at && $h->closes_at)
                  {{ substr($h->opens_at, 0, 5) }} – {{ substr($h->closes_at, 0, 5) }}
                @else
                  <span class="fpp-muted">—</span>
                @endif
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </section>
      @endif

      {{-- Claim (unclaimed listings only — same rule as before) --}}
      @unless($facility->isClaimed() || $facility->facility_id)
      <section class="fpp-card fpp-claim">
        <b>{{ __('finder_directory.claim_question') }}</b>
        <p>{{ __('finder_pages.profile.claim_body') }}</p>
        <a class="fp-btn fp-btn--sm fpp-claim-btn" href="{{ route('public.care-map.claim', array_filter(['id' => $facility->id, 'lang' => request('lang')])) }}">
          @include('partials.finder.icon', ['name' => 'shield-check', 'size' => 15]){{ __('finder_directory.claim_cta') }}
        </a>
      </section>
      @endunless

      {{-- Nearby (only when this listing has coordinates) --}}
      @if($nearby->isNotEmpty())
      <section class="fpp-card fpp-card--flush fpp-near">
        <h2 class="fpp-in">{{ __('finder_pages.profile.nearby') }}</h2>
        <ul>
          @foreach($nearby as $n)
            @php $nSlug = strtolower(str_replace(' ', '_', $n->facility_type ?? 'other')); @endphp
            <li>
              <a href="{{ route('public.care-map.profile', ['id' => $n->id] + $langQ) }}">
                <span class="fpp-ic">@include('partials.finder.icon', ['name' => $typeIcons[$nSlug] ?? 'building-2', 'size' => 18])</span>
                <div>
                  <b>{{ $n->facility_name }}</b>
                  <small>{{ collect([
                      \App\Support\FacilityTypeLabel::label($nSlug),
                      __('finder_pages.profile.nearby_km', ['km' => LocaleNumber::format((float) $n->distance_km, 1)]),
                  ])->implode(' · ') }}</small>
                </div>
              </a>
            </li>
          @endforeach
        </ul>
      </section>
      @endif
    </aside>
  </div>
</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/finder-profile.js') }}?v={{ @filemtime(public_path('js/finder-profile.js')) ?: 1 }}" defer @cspNonce></script>
@endpush

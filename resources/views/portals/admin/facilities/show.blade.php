@extends('layouts.portal')
@section('title', $facility->name)
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection
@php
    $gcTones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gcTone = $gcTones[crc32((string) $facility->name) % count($gcTones)];
    $listing = $overview['listing'];
    $claims = $overview['claims'];
    $staff = $overview['staff'];
    $kpis = $overview['kpis'];
    $readiness = $overview['readiness'];
    $activity = $overview['activity'];
    $wards = $overview['wards'];
    $subscription = $overview['subscription'];
    $status = (string) ($facility->status ?? '');
    $typeLabel = $facility->type ? \App\Support\FacilityTypeLabel::label($facility->type) : '—';
    $region = $listing['region'] ?? null;
    $address = $listing ? collect([$listing['address'], $listing['city']])->filter()->implode(', ') : '';
    $roleLabel = fn ($r) => $r ? \App\Services\Facility\StaffInviteService::roleLabel($r) : __('admin_facility.role_unassigned');
    $hasStaffSection = count($staff['rows']) > 0;
    $hasListingSection = $listing !== null || $claims->isNotEmpty();
    $hasActivity = $activity->isNotEmpty();
    $hasBilling = $subscription !== null || $kpis['open_invoices'] > 0;
@endphp
@section('content')
<div class="od-page od-stack">

<nav class="oda-gc-crumbs" aria-label="{{ __('od.breadcrumb') }}">
    <a href="{{ route('admin.facilities.index') }}">{{ __('public.adm_fac_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ $facility->name }}</span>
</nav>

@if(session('success'))<div class="oda-gc-flash oda-gc-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gc-flash oda-gc-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="od-card oda-gc-detail" id="overview">
  <div class="od-card__body oda-fac-hero">
    <span class="od-avatar od-avatar--lg od-tint--{{ $gcTone }}" aria-hidden="true"><i data-lucide="building-2"></i></span>
    <div class="od-detail__main oda-fac-hero__main">
        <h1 class="od-detail__name">{{ $facility->name }}</h1>
        <div class="od-detail__ref"><span>{{ $typeLabel }}</span>@if($region)<span>{{ $region }}</span>@endif @if($facility->license_number)<span class="od-mono">{{ $facility->license_number }}</span>@endif</div>
        <div class="oda-fac-pills">
            @if($status === 'active')<span class="od-pill od-pill--ready od-pill--dot">{{ __('public.adm_fac_badge_active') }}</span>
            @elseif($status === 'suspended')<span class="od-pill od-pill--urgent od-pill--dot">{{ __('public.adm_fac_badge_suspended') }}</span>
            @elseif($status === 'pending_approval')<span class="od-pill od-pill--pending od-pill--dot">{{ __('public.adm_fac_badge_pending') }}</span>
            @else<span class="od-pill od-pill--neutral od-pill--dot">@enum($facility->status ?? 'pending')</span>@endif
            @if($listing && $listing['verification_status'] === 'verified')<span class="od-pill od-pill--info" data-pill="listing-verified">{{ __('admin_facility.pill_listing_verified') }}</span>@endif
            @if($listing && $listing['claimed'])<span class="od-pill od-pill--teal" data-pill="claimed">{{ __('admin_facility.pill_claimed') }}</span>@endif
            @if($staff['mfa_required'] > 0)
                <span class="od-pill {{ $staff['mfa_confirmed'] >= $staff['mfa_required'] ? 'od-pill--ok' : 'od-pill--warning' }}" data-pill="mfa">{{ __('admin_facility.pill_mfa', ['done' => $staff['mfa_confirmed'], 'total' => $staff['mfa_required']]) }}</span>
            @endif
            @if($subscription && $subscription['plan'])<span class="od-pill od-pill--neutral" data-pill="plan">{{ __('admin_facility.pill_plan', ['plan' => $subscription['plan']]) }}</span>@endif
        </div>
        <div class="oda-fac-facts">
            @if($address !== '')<span><i data-lucide="map-pin"></i> {{ $address }}</span>@endif
            @if($listing && $listing['phone'])<span><i data-lucide="phone"></i> {{ $listing['phone'] }}</span>@endif
            @if($listing && $listing['email'])<span><i data-lucide="mail"></i> {{ $listing['email'] }}</span>@endif
            @if($facility->created_at)<span><i data-lucide="calendar-clock"></i> {{ __('admin_facility.created_on', ['date' => $facility->created_at->translatedFormat('d M Y')]) }}</span>@endif
        </div>
    </div>
    <div class="oda-fac-actions">
        <a href="#edit" class="od-btn od-btn--secondary"><i data-lucide="sliders-horizontal"></i> {{ __('public.adm_fac_show_btn_edit') }}</a>
        @if($listing)
        <a href="{{ route('public.care-map.profile', $listing['id']) }}" class="od-btn od-btn--secondary" target="_blank" rel="noopener" data-action="public-listing"><i data-lucide="external-link"></i> {{ __('admin_facility.btn_public_listing') }}</a>
        @endif
        <a href="{{ route('portals.admin.facilities.readiness', $facility->id) }}" class="od-btn od-btn--secondary"><i data-lucide="list-ordered"></i> {{ __('admin_facility.btn_readiness') }}</a>
        @if($status === 'pending_approval' || $status === 'suspended')
        <form method="POST" action="{{ route('admin.facilities.approve', $facility->id) }}" class="oda-inline-form">@csrf
            <button type="submit" class="od-btn od-btn--primary"><i data-lucide="check-circle"></i> {{ __('public.adm_fac_show_btn_approve') }}</button>
        </form>
        @else
        <button type="button" class="od-btn od-btn--danger" data-call="opOpenModal" data-args="[&quot;suspend-modal&quot;]"><i data-lucide="ban"></i> {{ __('public.adm_fac_show_btn_suspend') }}</button>
        @endif
    </div>
  </div>
</div>

<div class="od-kpis">
    <div class="od-kpi" data-kpi="staff">
        <span class="od-kpi__icon od-tint--blue"><i data-lucide="users"></i></span>
        <div class="od-kpi__body">
            <span class="od-kpi__label">{{ __('public.adm_fac_show_lbl_staff') }}</span>
            <span class="od-kpi__value">@num($kpis['staff'])</span>
            @if($kpis['invites'] > 0)<span class="od-kpi__sub">{{ trans_choice('admin_facility.kpi_invites_pending', $kpis['invites'], ['n' => $kpis['invites']]) }}</span>@endif
        </div>
    </div>
    <div class="od-kpi" data-kpi="patients">
        <span class="od-kpi__icon od-tint--teal"><i data-lucide="heart-pulse"></i></span>
        <div class="od-kpi__body">
            <span class="od-kpi__label">{{ __('admin_facility.kpi_active_patients') }}</span>
            <span class="od-kpi__value">@num($kpis['active_patients'])</span>
            <span class="od-kpi__sub">{{ __('admin_facility.kpi_registered', ['n' => \App\Support\LocaleNumber::format($kpis['registered'])]) }}</span>
        </div>
    </div>
    <div class="od-kpi" data-kpi="appointments">
        <span class="od-kpi__icon od-tint--sky"><i data-lucide="calendar"></i></span>
        <div class="od-kpi__body">
            <span class="od-kpi__label">{{ __('admin_facility.kpi_appointments') }}</span>
            <span class="od-kpi__value">@num($kpis['appointments'])</span>
            @if($kpis['no_show_rate'] !== null)<span class="od-kpi__sub">{{ __('admin_facility.kpi_no_show', ['pct' => $kpis['no_show_rate']]) }}</span>@endif
        </div>
    </div>
    <div class="od-kpi" data-kpi="invoices">
        <span class="od-kpi__icon od-tint--amber"><i data-lucide="receipt"></i></span>
        <div class="od-kpi__body">
            <span class="od-kpi__label">{{ __('admin_facility.kpi_open_invoices') }}</span>
            <span class="od-kpi__value">@num($kpis['open_invoices'])</span>
            @if($kpis['open_invoices'] > 0)<span class="od-kpi__sub od-kpi__sub--alert">{{ __('admin_facility.kpi_due', ['amount' => $kpis['balance_due']]) }}</span>@endif
        </div>
    </div>
</div>

<nav class="od-tabs oda-fac-tabs" aria-label="{{ __('admin_facility.tabs_label') }}">
    <a href="#overview" class="od-tab is-active">{{ __('public.adm_fac_show_tab_overview') }}</a>
    @if($hasStaffSection)<a href="#staff" class="od-tab">{{ __('admin_facility.tab_staff') }} <span class="od-tab__count">{{ $staff['total'] }}</span></a>@endif
    @if($hasListingSection)<a href="#listing" class="od-tab">{{ __('admin_facility.tab_listing') }}</a>@endif
    <a href="#readiness" class="od-tab">{{ __('admin_facility.tab_readiness') }} @if($readiness['done'] < $readiness['total'])<span class="od-tab__dot od-tab__dot--pending"></span>@endif</a>
    @if($hasActivity)<a href="#activity" class="od-tab">{{ __('admin_facility.tab_activity') }}</a>@endif
    @if($hasBilling)<a href="#billing" class="od-tab">{{ __('admin_facility.tab_billing') }}</a>@endif
    <a href="#edit" class="od-tab">{{ __('public.adm_fac_show_btn_edit') }}</a>
</nav>

<div class="oda-fac-cols">
 <div class="od-stack">
  <div class="oda-fac-cols oda-fac-cols--half">
    <section class="od-card" data-section="profile">
        <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="building-2"></i> {{ __('admin_facility.profile_title') }}</h2></div>
        <div class="od-card__body">
            <dl class="oda-dl">
                <dt>{{ __('public.adm_fac_show_lbl_type') }}</dt><dd>{{ $typeLabel }}</dd>
                <dt>{{ __('public.adm_fac_show_lbl_status') }}</dt><dd>@enum($facility->status ?? 'pending')</dd>
                <dt>{{ __('public.adm_fac_show_lbl_license') }}</dt><dd class="oda-mono">{{ $facility->license_number ?? '—' }}</dd>
                <dt>{{ __('public.adm_fac_show_lbl_region') }}</dt><dd>{{ $region ?? '—' }}</dd>
                <dt>{{ __('public.adm_fac_show_lbl_created') }}</dt><dd>{{ $facility->created_at?->translatedFormat('d M Y') ?? '—' }}</dd>
                @if($wards)
                <dt>{{ __('admin_facility.wards') }}</dt><dd data-wards>{{ __('admin_facility.wards_value', ['wards' => $wards['wards'], 'beds' => $wards['beds'], 'occupied' => $wards['occupied']]) }}</dd>
                @endif
            </dl>
        </div>
    </section>
    <section class="od-card" data-section="location">
        <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="map-pin"></i> {{ __('admin_facility.location_title') }}</h2></div>
        <div class="od-card__body">
            @if($listing && $listing['has_coordinates'])
                @include('partials.finder.map', ['facilities' => [$listing['model']], 'single' => true, 'mapId' => 'admin-facility-map', 'height' => '200px'])
                <p class="od-muted oda-fac-caption">{{ __('admin_facility.coords_from_listing', ['lat' => $listing['latitude'], 'lng' => $listing['longitude']]) }}</p>
            @else
                <p class="od-muted">{{ $listing ? __('admin_facility.no_coordinates') : __('admin_facility.no_listing_location') }}</p>
            @endif
        </div>
    </section>
  </div>

  @if($hasStaffSection)
  <section class="od-card" id="staff" data-section="staff">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="users"></i> {{ __('admin_facility.staff_title') }}</h2></div>
    <div class="od-card__body--flush"><div class="od-table-wrap"><table class="od-table">
        <thead><tr><th>{{ __('admin_facility.col_role') }}</th><th class="od-table__num">{{ __('admin_facility.col_active') }}</th><th class="od-table__num">{{ __('admin_facility.col_invited') }}</th><th>{{ __('admin_facility.col_mfa') }}</th></tr></thead>
        <tbody>
        @foreach($staff['rows'] as $row)
            <tr data-role="{{ $row['role'] }}">
                <td class="od-table__name">{{ $roleLabel($row['role']) }}</td>
                <td class="od-table__num">@num($row['active'])</td>
                <td class="od-table__num">@num($row['invited'])</td>
                <td>@if($row['mfa'] === null)<span class="od-pill od-pill--neutral">{{ __('admin_facility.mfa_na') }}</span>
                    @else<span class="od-pill {{ $row['mfa'] >= $row['active'] ? 'od-pill--ok' : 'od-pill--warning' }}">{{ $row['mfa'] }} / {{ $row['active'] }}</span>@endif</td>
            </tr>
        @endforeach
        </tbody>
    </table></div></div>
  </section>
  @endif

  <section class="od-card" id="activity" data-section="activity">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="activity"></i> {{ __('admin_facility.activity_title') }}</h2></div>
    <div class="od-card__body">
        @if($hasActivity)
        <ul class="oda-fac-tl">
            @foreach($activity as $e)
            <li>
                <span class="oda-fac-tl__dot"><i data-lucide="{{ $e['kind'] === 'golive' ? 'list-ordered' : ($e['kind'] === 'update' ? 'pencil' : 'file-check') }}"></i></span>
                <div>
                    <b>@enum($e['action'], 'audit_action')</b>@if($e['detail']) — {{ \App\Support\Enums::label($e['detail'], 'resource_type') }}@endif
                    <div class="oda-fac-tl__meta">{{ $e['actor_name'] ?? __('admin_facility.actor_system') }}@if($e['when']) · {{ $e['when']->diffForHumans() }}@endif</div>
                </div>
            </li>
            @endforeach
        </ul>
        @else
        <p class="od-muted">{{ __('admin_facility.activity_empty') }}</p>
        @endif
    </div>
  </section>

  <section class="od-card" id="edit">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="pencil"></i> {{ __('public.adm_fac_show_edit_title') }}</h2></div>
    <div class="od-card__body">
        <form method="POST" action="{{ route('admin.facilities.update', $facility->id) }}" class="oda-form">
            @csrf @method('PUT')
            <label class="oda-label">
                <span class="oda-label__text">{{ __('public.adm_fac_show_lbl_name') }} *</span>
                <input type="text" name="name" class="od-input" value="{{ old('name', $facility->name) }}" required>
                @error('name')<span class="oda-error">{{ $message }}</span>@enderror
            </label>
            <label class="oda-label">
                <span class="oda-label__text">{{ __('public.adm_fac_show_lbl_type') }} *</span>
                <select name="type" class="od-select" required>
                    @foreach(['hospital','clinic','pharmacy','laboratory','radiology','specialist','other'] as $t)
                    <option value="{{ $t }}" @selected(old('type',$facility->type)===$t)>{{ \App\Support\FacilityTypeLabel::label($t) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="oda-label">
                <span class="oda-label__text">{{ __('public.adm_fac_show_lbl_region') }}</span>
                <input type="text" name="region" class="od-input" value="{{ old('region', $facility->region) }}">
            </label>
            <label class="oda-label">
                <span class="oda-label__text">{{ __('public.adm_fac_show_lbl_country') }}</span>
                <input type="text" name="country_code" class="od-input" value="{{ old('country_code', $facility->country_code) }}" maxlength="3">
            </label>
            <div class="oda-form__actions">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="save"></i> {{ __('public.adm_fac_show_btn_save') }}</button>
            </div>
        </form>
    </div>
  </section>

  <section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="alert-triangle"></i> {{ __('public.adm_fac_show_danger_title') }}</h2></div>
    <div class="od-card__body od-row">
        @if($status !== 'suspended')
        <button type="button" class="od-btn od-btn--secondary" data-call="opOpenModal" data-args="[&quot;suspend-modal&quot;]"><i data-lucide="pause-circle"></i> {{ __('public.adm_fac_show_btn_suspend') }}</button>
        @endif
        <button type="button" class="od-btn od-btn--danger" data-call="opOpenModal" data-args="[&quot;delete-modal&quot;]"><i data-lucide="trash-2"></i> {{ __('public.adm_fac_show_btn_delete') }}</button>
    </div>
  </section>
 </div>

 <div class="od-stack">
  <section class="od-card" id="readiness" data-section="readiness" data-readiness-score="{{ $readiness['score'] }}">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="list-ordered"></i> {{ __('admin_facility.readiness_title') }}</h2>
        @if($readiness['done'] >= $readiness['total'])<span class="od-pill od-pill--ok">{{ __('admin_facility.readiness_complete') }}</span>
        @else<span class="od-pill od-pill--pending">{{ __('admin_facility.readiness_in_progress') }}</span>@endif
    </div>
    <div class="od-card__body">
        <div class="oda-fac-bar__head"><b>{{ $readiness['score'] }} / 100</b><span class="od-muted">{{ __('admin_facility.readiness_items', ['done' => $readiness['done'], 'total' => $readiness['total']]) }}</span></div>
        <div class="oda-fac-bar" role="progressbar" aria-valuenow="{{ $readiness['score'] }}" aria-valuemin="0" aria-valuemax="100"><i data-width="{{ $readiness['score'] }}" class="oda-fac-bar__fill"></i></div>
        <ul class="oda-fac-checks">
            @foreach($readiness['checks'] as $key => $ok)
            <li class="{{ $ok ? 'is-ok' : 'is-no' }}" data-check="{{ $key }}"><i data-lucide="{{ $ok ? 'circle-check' : 'circle-x' }}"></i><span>{{ __('readiness.check_' . $key) }}</span></li>
            @endforeach
        </ul>
        @if($readiness['latest'])
        <p class="od-muted oda-fac-caption">{{ __('admin_facility.readiness_snapshot', ['score' => (int) $readiness['latest']->overall_score, 'date' => $readiness['latest']->calculated_at?->translatedFormat('d M Y')]) }}</p>
        @endif
    </div>
    <div class="od-card__foot">
        <a href="{{ route('portals.admin.facilities.readiness', $facility->id) }}" class="od-btn od-btn--ghost od-btn--sm">{{ __('admin_facility.readiness_open') }}</a>
        <form method="POST" action="{{ route('portals.admin.facilities.readiness.score', $facility->id) }}" class="oda-inline-form">@csrf
            <button type="submit" class="od-btn od-btn--primary od-btn--sm"><i data-lucide="refresh-cw"></i> {{ __('admin_facility.readiness_recompute') }}</button>
        </form>
    </div>
  </section>

  @if($hasListingSection)
  <section class="od-card" id="listing" data-section="listing">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="globe"></i> {{ __('admin_facility.listing_title') }}</h2>
        @if($listing)<a href="{{ route('public.care-map.profile', $listing['id']) }}" class="od-card__link" target="_blank" rel="noopener">{{ __('admin_facility.open') }}</a>@endif
    </div>
    <div class="od-card__body">
        @if($listing)
        <dl class="oda-dl">
            <dt>{{ __('admin_facility.listing_status') }}</dt><dd><span class="od-pill od-pill--neutral">@enum($listing['listing_status'])</span></dd>
            <dt>{{ __('admin_facility.verification') }}</dt><dd><span class="od-pill {{ $listing['verification_status'] === 'verified' ? 'od-pill--info' : 'od-pill--neutral' }}">@enum($listing['verification_status'], 'verification')</span></dd>
            @if($listing['license_status'])<dt>{{ __('public.adm_fac_show_lbl_license') }}</dt><dd>@enum($listing['license_status'])</dd>@endif
            <dt>{{ __('admin_facility.claim') }}</dt><dd>{{ $listing['claimed'] ? __('admin_facility.claimed_on', ['date' => $listing['claimed_at'] ? \Illuminate\Support\Carbon::parse($listing['claimed_at'])->translatedFormat('d M Y') : '—']) : __('admin_facility.unclaimed') }}</dd>
            @if($listing['integration_status'])<dt>{{ __('admin_facility.integration') }}</dt><dd>@enum($listing['integration_status'])</dd>@endif
            <dt>{{ __('admin_facility.live_stock') }}</dt><dd data-live-stock="{{ $listing['live_stock'] }}">{{ $listing['live_stock'] > 0 ? trans_choice('admin_facility.live_stock_items', $listing['live_stock'], ['n' => $listing['live_stock']]) : __('admin_facility.live_stock_none') }}</dd>
        </dl>
        @else
        <p class="od-muted">{{ __('admin_facility.no_listing') }}</p>
        @endif

        @if($claims->isNotEmpty())
        <h3 class="oda-fac-subtitle">{{ __('admin_facility.claims_title') }}</h3>
        <ul class="oda-fac-tl">
            @foreach($claims as $c)
            <li data-claim="{{ $c['status'] }}">
                <span class="oda-fac-tl__dot"><i data-lucide="file-check"></i></span>
                <div>
                    <b>{{ $c['status_label'] ?? '—' }}</b>@if($c['claimant']) — {{ $c['claimant'] }}@endif
                    <div class="oda-fac-tl__meta">
                        {{ $c['has_document'] ? __('admin_facility.claim_doc_yes') : __('admin_facility.claim_doc_no') }} ·
                        {{ $c['phone_verified'] ? __('admin_facility.claim_phone_yes') : __('admin_facility.claim_phone_no') }}
                        @if($c['reviewer']) · {{ __('admin_facility.claim_reviewed_by', ['name' => $c['reviewer']]) }}@endif
                        @if($c['reviewed_at'] ?? $c['submitted_at']) · {{ \Illuminate\Support\Carbon::parse($c['reviewed_at'] ?? $c['submitted_at'])->translatedFormat('d M Y') }}@endif
                    </div>
                </div>
            </li>
            @endforeach
        </ul>
        @endif
    </div>
  </section>
  @endif

  @if($hasBilling)
  <section class="od-card" id="billing" data-section="billing">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="credit-card"></i> {{ __('admin_facility.billing_title') }}</h2></div>
    <div class="od-card__body">
        <dl class="oda-dl">
            @if($subscription)
            <dt>{{ __('admin_facility.plan') }}</dt><dd>{{ $subscription['plan'] ?? '—' }}@if($subscription['interval']), @enum($subscription['interval'])@endif</dd>
            <dt>{{ __('public.adm_fac_show_lbl_status') }}</dt><dd>@enum($subscription['status'])</dd>
            @if($subscription['period_end'])<dt>{{ __('admin_facility.period_ends') }}</dt><dd>{{ \Illuminate\Support\Carbon::parse($subscription['period_end'])->translatedFormat('d M Y') }}</dd>@endif
            @endif
            @if($kpis['open_invoices'] > 0)
            <dt>{{ __('admin_facility.kpi_open_invoices') }}</dt><dd>@num($kpis['open_invoices']) · {{ $kpis['balance_due'] }}</dd>
            @endif
        </dl>
    </div>
  </section>
  @endif
 </div>
</div>

<div id="suspend-modal" class="modal-backdrop" hidden>
    <div class="od-card oda-modal" role="dialog" aria-modal="true" aria-labelledby="suspend-modal-title">
        <div class="od-card__head"><h3 class="od-card__title" id="suspend-modal-title"><i data-lucide="pause-circle"></i> {{ __('public.adm_fac_show_modal_suspend_title') }}</h3></div>
        <form method="POST" action="{{ route('admin.facilities.suspend', $facility->id) }}" class="od-card__body od-stack">
            @csrf
            <p>{{ __('public.adm_fac_show_modal_suspend_body', ['name' => $facility->name]) }}</p>
            <textarea name="reason" class="oda-textarea" rows="3" placeholder="{{ __('public.adm_fac_show_modal_suspend_ph') }}" required></textarea>
            <div class="oda-form__actions">
                <button type="button" class="od-btn od-btn--ghost" data-call="opCloseModal" data-args="[&quot;suspend-modal&quot;]">{{ __('public.adm_fac_show_btn_cancel') }}</button>
                <button type="submit" class="od-btn od-btn--danger">{{ __('public.adm_fac_show_btn_suspend_confirm') }}</button>
            </div>
        </form>
    </div>
</div>

<div id="delete-modal" class="modal-backdrop" hidden>
    <div class="od-card oda-modal" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
        <div class="od-card__head"><h3 class="od-card__title" id="delete-modal-title"><i data-lucide="alert-triangle"></i> {{ __('public.adm_fac_show_modal_delete_title') }}</h3></div>
        <form method="POST" action="{{ route('admin.facilities.destroy', $facility->id) }}" class="od-card__body od-stack">
            @csrf @method('DELETE')
            <p>{{ __('public.adm_fac_show_modal_delete_body', ['name' => $facility->name]) }}</p>
            <textarea name="reason" class="oda-textarea" rows="3" placeholder="{{ __('public.adm_fac_show_modal_delete_ph') }}" required></textarea>
            <div class="oda-form__actions">
                <button type="button" class="od-btn od-btn--ghost" data-call="opCloseModal" data-args="[&quot;delete-modal&quot;]">{{ __('public.adm_fac_show_btn_cancel') }}</button>
                <button type="submit" class="od-btn od-btn--danger">{{ __('public.adm_fac_show_btn_delete_confirm') }}</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
@section('scripts')
<script @cspNonce>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
document.querySelectorAll('.oda-fac-bar__fill[data-width]').forEach(function(el){ el.style.width = Math.max(0, Math.min(100, parseInt(el.getAttribute('data-width'), 10) || 0)) + '%'; });
</script>
@endsection

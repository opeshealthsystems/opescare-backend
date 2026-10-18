@extends('layouts.portal')

@section('title', __('diagnostic_desk.title'))
@section('breadcrumb_home', __('diagnostic_desk.title'))
@section('breadcrumb_home_url', route('portals.diagnostic.dashboard'))
@section('breadcrumb_section', __('diagnostic_desk.nav'))

@section('head')
    @include('portals.lab.partials._head')
@endsection

@php
    // Whole minutes → the shortest honest unit, reusing the lab desk's own
    // strings. A null (no data) is the "not enough data" sentence, never 0.
    $duration = function (?int $minutes): string {
        if ($minutes === null) {
            return __('diagnostic_desk.lab_turnaround_none');
        }
        if ($minutes < 60) {
            return __('lab_manager.tat_minutes', ['minutes' => $minutes]);
        }
        if ($minutes < 60 * 24) {
            return __('lab_manager.tat_hours', ['hours' => intdiv($minutes, 60), 'minutes' => $minutes % 60]);
        }

        return __('lab_manager.tat_days', ['days' => intdiv($minutes, 60 * 24), 'hours' => intdiv($minutes % (60 * 24), 60)]);
    };

    $typeLabel = match ($facilityType) {
        'imaging_center' => __('diagnostic_desk.type_imaging'),
        default          => __('diagnostic_desk.type_diagnostic'),
    };

    $me       = auth()->user();
    $roleSlug = $me ? \App\Support\StaffCapabilities::roleModelFor($me, \App\Support\StaffCapabilities::contextFacilityId($me))?->name : null;
    $roleName = $roleSlug && \Illuminate\Support\Facades\Lang::has('portal.role_name.' . $roleSlug)
        ? __('portal.role_name.' . $roleSlug)
        : ($me?->roleName() ?? '');
@endphp

@section('content')
<div class="od-page odl">

@if(session('success'))<div class="od-flash od-flash--ok" role="status"><i data-lucide="check-circle"></i><span>{{ session('success') }}</span></div>@endif
@if(session('info'))<div class="od-flash od-flash--info" role="status"><i data-lucide="info"></i><span>{{ session('info') }}</span></div>@endif
@if(session('error'))<div class="od-flash od-flash--err" role="alert"><i data-lucide="alert-circle"></i><span>{{ session('error') }}</span></div>@endif

{{-- ── Welcome ──────────────────────────────────────────────────────────── --}}
<section class="odl-top odl-top--diag">
    <div class="od-greeting odl-welcome">
        <p class="od-greeting__hello">{{ __('lab_desk_ui.welcome_back') }}</p>
        <h1 class="od-greeting__name">{{ $me?->name }}</h1>
        <p class="od-greeting__meta">{{ $roleName }}<span class="odl-sep" aria-hidden="true">|</span>{{ __('diagnostic_desk.title') }}</p>
        <p class="od-greeting__meta"><i data-lucide="calendar-days"></i> {{ now()->translatedFormat('l, j F Y') }}</p>
        <p class="od-greeting__meta odl-small">{{ __('diagnostic_desk.subtitle', ['facility' => $typeLabel]) }}</p>
    </div>

    {{-- ── Quick actions — real routes only, gated like the pages they open --}}
    <div class="od-card">
        <div class="od-card__head"><h2 class="od-card__title">{{ __('lab_desk_ui.quick_actions') }}</h2></div>
        <div class="od-card__body">
            <div class="od-quick">
                @if($canLab)
                <a class="od-quick__tile" href="{{ route('portals.lab.collection') }}"><i data-lucide="barcode"></i><span class="od-quick__label">{{ __('lab_desk_ui.qa_register') }}</span></a>
                <a class="od-quick__tile" href="{{ route('portals.lab.index') }}"><i data-lucide="list-checks"></i><span class="od-quick__label">{{ __('diagnostic_desk.lab_open_worklist') }}</span></a>
                <a class="od-quick__tile" href="{{ route('portals.lab_manager.dashboard') }}"><i data-lucide="flask-conical"></i><span class="od-quick__label">{{ __('diagnostic_desk.lab_open_dashboard') }}</span></a>
                @endif
                @if($canImaging)
                <a class="od-quick__tile" href="{{ route('portals.imaging.index') }}"><i data-lucide="scan"></i><span class="od-quick__label">{{ __('diagnostic_desk.imaging_open_worklist') }}</span></a>
                <a class="od-quick__tile" href="{{ route('portals.imaging.create') }}"><i data-lucide="file-plus-2"></i><span class="od-quick__label">{{ __('diagnostic_desk.imaging_open_new') }}</span></a>
                @endif
                @if($canCatalogue)
                <a class="od-quick__tile" href="{{ route('portals.facility.catalogue') }}"><i data-lucide="clipboard-list"></i><span class="od-quick__label">{{ __('diagnostic_desk.pub_open_catalogue') }}</span></a>
                @endif
            </div>
        </div>
    </div>
</section>

<div class="od-grid od-grid--2">

{{-- ── Laboratory half ──────────────────────────────────────────────────── --}}
<section class="od-card" id="lab" data-half="lab">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('diagnostic_desk.lab_title') }}</h2>
        @if($canLab)
        <a href="{{ route('portals.lab_manager.dashboard') }}" class="od-card__link">{{ __('diagnostic_desk.lab_open_dashboard') }}</a>
        @endif
    </div>
    <div class="od-card__body">
        @unless($lab['has_work'])
            <div class="od-empty" data-empty="lab">
                <span class="od-empty__icon"><i data-lucide="test-tube-2"></i></span>
                <p class="od-empty__title">{{ __('diagnostic_desk.lab_empty_title') }}</p>
                <p class="od-empty__text">{{ __('diagnostic_desk.lab_empty_body') }}</p>
                @if($canLab)
                <a href="{{ route('portals.lab.collection') }}" class="od-btn od-btn--primary">{{ __('diagnostic_desk.lab_empty_action') }}</a>
                @endif
            </div>
        @else
            <ul class="od-list">
                <li class="od-list__row">
                    <span class="od-list__icon od-tint--blue"><i data-lucide="flask-conical"></i></span>
                    <div class="od-list__text"><span class="od-list__title">{{ __('diagnostic_desk.lab_open') }}</span><span class="od-list__sub">{{ __('diagnostic_desk.lab_open_hint') }}</span></div>
                    <strong class="od-list__value odl-big" data-kpi="lab_open">{{ $lab['open'] }}</strong>
                </li>
                <li class="od-list__row">
                    <span class="od-list__icon od-tint--green"><i data-lucide="send"></i></span>
                    <div class="od-list__text"><span class="od-list__title">{{ __('diagnostic_desk.lab_resulted_today') }}</span><span class="od-list__sub">{{ __('diagnostic_desk.lab_resulted_hint') }}</span></div>
                    <strong class="od-list__value odl-big" data-kpi="lab_resulted_today">{{ $lab['resulted_today'] }}</strong>
                </li>
                <li class="od-list__row">
                    <span class="od-list__icon od-tint--teal"><i data-lucide="timer"></i></span>
                    <div class="od-list__text"><span class="od-list__title">{{ __('diagnostic_desk.lab_turnaround') }}</span><span class="od-list__sub">{{ __('diagnostic_desk.lab_turnaround_hint', ['days' => $lab['turnaround']['window_days']]) }}</span></div>
                    <strong class="od-list__value" data-kpi="lab_turnaround">{{ $duration($lab['turnaround']['median_minutes']) }}</strong>
                </li>
                <li class="od-list__row">
                    <span class="od-list__icon od-tint--red"><i data-lucide="siren"></i></span>
                    <div class="od-list__text"><span class="od-list__title">{{ __('diagnostic_desk.lab_critical_open') }}</span><span class="od-list__sub">{{ __('diagnostic_desk.lab_awaiting_validation') }}: {{ $lab['awaiting_validation'] }} · {{ __('diagnostic_desk.lab_requests_pending') }}: {{ $lab['requests_pending'] }}</span></div>
                    <strong class="od-list__value odl-big {{ $lab['critical_open'] > 0 ? 'is-alert' : '' }}" data-kpi="lab_critical_open">{{ $lab['critical_open'] }}</strong>
                </li>
            </ul>
        @endunless
    </div>
</section>

{{-- ── Imaging half ─────────────────────────────────────────────────────── --}}
<section class="od-card" id="imaging" data-half="imaging">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('diagnostic_desk.imaging_title') }}</h2>
        @if($canImaging)
        <a href="{{ route('portals.imaging.create') }}" class="od-card__link">{{ __('diagnostic_desk.imaging_open_new') }}</a>
        @endif
    </div>
    <div class="od-card__body">
        @unless($imaging['has_work'])
            <div class="od-empty" data-empty="imaging">
                <span class="od-empty__icon"><i data-lucide="scan-line"></i></span>
                <p class="od-empty__title">{{ __('diagnostic_desk.imaging_empty_title') }}</p>
                <p class="od-empty__text">{{ __('diagnostic_desk.imaging_empty_body') }}</p>
                @if($canImaging)
                <a href="{{ route('portals.imaging.create') }}" class="od-btn od-btn--primary">{{ __('diagnostic_desk.imaging_empty_action') }}</a>
                @endif
            </div>
        @else
            <ul class="od-list">
                <li><a class="od-list__row" href="{{ route('portals.imaging.index', ['tab' => 'pending']) }}">
                    <span class="od-list__icon od-tint--amber"><i data-lucide="inbox"></i></span>
                    <span class="od-list__text"><span class="od-list__title">{{ __('diagnostic_desk.imaging_pending') }}</span><span class="od-list__sub">{{ __('diagnostic_desk.imaging_pending_hint') }}</span></span>
                    <strong class="od-list__value odl-big" data-kpi="imaging_pending">{{ $imaging['pending'] }}</strong>
                </a></li>
                <li class="od-list__row">
                    <span class="od-list__icon od-tint--blue"><i data-lucide="calendar-plus"></i></span>
                    <div class="od-list__text"><span class="od-list__title">{{ __('diagnostic_desk.imaging_today') }}</span><span class="od-list__sub">{{ __('diagnostic_desk.imaging_today_hint') }}</span></div>
                    <strong class="od-list__value odl-big" data-kpi="imaging_today">{{ $imaging['today'] }}</strong>
                </li>
                <li><a class="od-list__row" href="{{ route('portals.imaging.index', ['tab' => 'awaiting_report']) }}">
                    <span class="od-list__icon od-tint--rose"><i data-lucide="file-clock"></i></span>
                    <span class="od-list__text"><span class="od-list__title">{{ __('diagnostic_desk.imaging_awaiting_report') }}</span><span class="od-list__sub">{{ __('diagnostic_desk.imaging_awaiting_hint') }}</span></span>
                    <strong class="od-list__value odl-big" data-kpi="imaging_awaiting_report">{{ $imaging['awaiting_report'] }}</strong>
                </a></li>
                <li><a class="od-list__row" href="{{ route('portals.imaging.index', ['tab' => 'reported']) }}">
                    <span class="od-list__icon od-tint--green"><i data-lucide="file-check-2"></i></span>
                    <span class="od-list__text"><span class="od-list__title">{{ __('diagnostic_desk.imaging_reported') }}</span><span class="od-list__sub">{{ __('diagnostic_desk.imaging_scheduled') }}: {{ $imaging['scheduled'] }} · {{ __('diagnostic_desk.imaging_in_progress') }}: {{ $imaging['in_progress'] }}</span></span>
                    <strong class="od-list__value odl-big" data-kpi="imaging_reported">{{ $imaging['reported'] }}</strong>
                </a></li>
            </ul>
        @endunless
    </div>
</section>

</div>

{{-- ── Publication: lab finder / imaging finder ─────────────────────────── --}}
<section class="od-card" id="publication" data-panel="publication">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('diagnostic_desk.pub_title') }}</h2>
    </div>
    <div class="od-card__body">
        <p class="od-muted odl-lead">{{ __('diagnostic_desk.pub_subtitle') }}</p>
        @if(! $publication['linked'])
            <div class="od-flash od-flash--warn"><i data-lucide="link-2-off"></i><span>{{ __('diagnostic_desk.pub_unlinked') }}</span></div>
        @else
            @unless($publication['listing_active'])
                <div class="od-flash od-flash--warn"><i data-lucide="eye-off"></i><span>{{ __('diagnostic_desk.pub_listing_inactive', ['listing' => $publication['listing_name']]) }}</span></div>
            @endunless
            <p class="odl-listing"><span class="od-muted">{{ __('diagnostic_desk.pub_listing') }}</span> <strong>{{ $publication['listing_name'] }}</strong></p>
        @endif

        <div class="od-grid od-grid--2">
            <div class="odl-finder" data-finder="lab">
                <span class="od-list__icon od-list__icon--solid od-tint--blue"><i data-lucide="flask-conical"></i></span>
                <div class="od-list__text">
                    <span class="od-list__title">{{ __('diagnostic_desk.pub_lab') }}</span>
                    <span class="od-list__sub">{{ __('diagnostic_desk.pub_in_catalogue') }}: {{ $publication['lab_catalogue'] }} · {{ __('diagnostic_desk.pub_last') }}: {{ $publication['lab_last_published_at'] ? $publication['lab_last_published_at']->translatedFormat('d M Y H:i') : __('diagnostic_desk.pub_never') }}</span>
                </div>
                <strong class="od-list__value odl-big" data-kpi="lab_published">{{ $publication['lab_published'] }}</strong>
            </div>
            <div class="odl-finder" data-finder="imaging">
                <span class="od-list__icon od-list__icon--solid od-tint--teal"><i data-lucide="scan-line"></i></span>
                <div class="od-list__text">
                    <span class="od-list__title">{{ __('diagnostic_desk.pub_imaging') }}</span>
                    <span class="od-list__sub">{{ __('diagnostic_desk.pub_in_catalogue') }}: {{ $publication['imaging_catalogue'] }} · {{ __('diagnostic_desk.pub_last') }}: {{ $publication['imaging_last_published_at'] ? $publication['imaging_last_published_at']->translatedFormat('d M Y H:i') : __('diagnostic_desk.pub_never') }}</span>
                </div>
                <strong class="od-list__value odl-big" data-kpi="imaging_published">{{ $publication['imaging_published'] }}</strong>
            </div>
        </div>

        @if($publication['lab_catalogue'] === 0)
            <p class="od-muted odl-small" data-empty="lab_catalogue">{{ __('diagnostic_desk.pub_lab_none') }}</p>
        @endif
        @if($publication['imaging_catalogue'] === 0)
            <p class="od-muted odl-small" data-empty="imaging_catalogue">{{ __('diagnostic_desk.pub_imaging_none') }}</p>
        @endif

        <div class="od-row odl-actions">
            @if($canCatalogue)
                <a href="{{ route('portals.facility.catalogue') }}" class="od-btn od-btn--secondary"><i data-lucide="clipboard-list"></i> {{ __('diagnostic_desk.pub_open_catalogue') }}</a>
            @endif
            @if(Route::has('public.network.lab-test-finder'))
                <a href="{{ route('public.network.lab-test-finder') }}" class="od-btn od-btn--ghost" target="_blank" rel="noopener"><i data-lucide="external-link"></i> {{ __('diagnostic_desk.pub_open_lab_finder') }}</a>
            @endif
            @if(Route::has('public.network.imaging-finder'))
                <a href="{{ route('public.network.imaging-finder') }}" class="od-btn od-btn--ghost" target="_blank" rel="noopener"><i data-lucide="external-link"></i> {{ __('diagnostic_desk.pub_open_imaging_finder') }}</a>
            @endif
        </div>
    </div>
</section>

</div>
@endsection

@extends('layouts.portal')

@section('title', __('diagnostic_desk.title'))
@section('breadcrumb_home', __('diagnostic_desk.title'))
@section('breadcrumb_home_url', route('portals.diagnostic.dashboard'))
@section('breadcrumb_section', __('diagnostic_desk.nav'))

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
@endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('diagnostic_desk.title') }}</h1>
        <p class="page-subtitle">{{ __('diagnostic_desk.subtitle', ['facility' => $typeLabel]) }}</p>
    </div>
    <div class="row-actions-inline">
        @if($canLab)
        <a href="{{ route('portals.lab.index') }}" class="btn btn-secondary"><i data-lucide="flask-conical"></i> {{ __('diagnostic_desk.lab_open_worklist') }}</a>
        @endif
        @if($canImaging)
        <a href="{{ route('portals.imaging.index') }}" class="btn btn-primary"><i data-lucide="scan"></i> {{ __('diagnostic_desk.imaging_open_worklist') }}</a>
        @endif
    </div>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('info'))<div class="alert alert-info mb-6"><i data-lucide="info"></i><div>{{ session('info') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- ── Laboratory half ──────────────────────────────────────────────────── --}}
<div class="panel mb-4" id="lab" data-half="lab">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="flask-conical"></i> {{ __('diagnostic_desk.lab_title') }}</h2>
        @if($canLab)
        <a href="{{ route('portals.lab_manager.dashboard') }}" class="btn btn-ghost btn-sm"><i data-lucide="layout-dashboard"></i> {{ __('diagnostic_desk.lab_open_dashboard') }}</a>
        @endif
    </div>
    <div class="panel-body">
        @unless($lab['has_work'])
            <div class="empty-state" data-empty="lab">
                <i data-lucide="test-tube-2"></i>
                <p class="td-strong">{{ __('diagnostic_desk.lab_empty_title') }}</p>
                <p class="text-muted">{{ __('diagnostic_desk.lab_empty_body') }}</p>
                @if($canLab)
                <a href="{{ route('portals.lab.collection') }}" class="btn btn-primary btn-sm"><i data-lucide="syringe"></i> {{ __('diagnostic_desk.lab_empty_action') }}</a>
                @endif
            </div>
        @else
            <div class="stat-grid">
                <div class="stat-card stat-card--primary">
                    <div class="stat-card__head"><i data-lucide="beaker"></i></div>
                    <div class="stat-card__label">{{ __('diagnostic_desk.lab_open') }}</div>
                    <div class="stat-card__value" data-kpi="lab_open">{{ $lab['open'] }}</div>
                    <div class="stat-card__hint">{{ __('diagnostic_desk.lab_open_hint') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__head"><i data-lucide="send"></i></div>
                    <div class="stat-card__label">{{ __('diagnostic_desk.lab_resulted_today') }}</div>
                    <div class="stat-card__value" data-kpi="lab_resulted_today">{{ $lab['resulted_today'] }}</div>
                    <div class="stat-card__hint">{{ __('diagnostic_desk.lab_resulted_hint') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__head"><i data-lucide="timer"></i></div>
                    <div class="stat-card__label">{{ __('diagnostic_desk.lab_turnaround') }}</div>
                    <div class="stat-card__value" data-kpi="lab_turnaround">{{ $duration($lab['turnaround']['median_minutes']) }}</div>
                    <div class="stat-card__hint">{{ __('diagnostic_desk.lab_turnaround_hint', ['days' => $lab['turnaround']['window_days']]) }}</div>
                </div>
                <div class="stat-card {{ $lab['critical_open'] > 0 ? 'stat-card--danger' : '' }}">
                    <div class="stat-card__head"><i data-lucide="siren"></i></div>
                    <div class="stat-card__label">{{ __('diagnostic_desk.lab_critical_open') }}</div>
                    <div class="stat-card__value" data-kpi="lab_critical_open">{{ $lab['critical_open'] }}</div>
                    <div class="stat-card__hint">{{ __('diagnostic_desk.lab_awaiting_validation') }}: {{ $lab['awaiting_validation'] }} · {{ __('diagnostic_desk.lab_requests_pending') }}: {{ $lab['requests_pending'] }}</div>
                </div>
            </div>
        @endunless
    </div>
</div>

{{-- ── Imaging half ─────────────────────────────────────────────────────── --}}
<div class="panel mb-4" id="imaging" data-half="imaging">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="scan"></i> {{ __('diagnostic_desk.imaging_title') }}</h2>
        @if($canImaging)
        <a href="{{ route('portals.imaging.create') }}" class="btn btn-ghost btn-sm"><i data-lucide="plus"></i> {{ __('diagnostic_desk.imaging_open_new') }}</a>
        @endif
    </div>
    <div class="panel-body">
        @unless($imaging['has_work'])
            <div class="empty-state" data-empty="imaging">
                <i data-lucide="scan-line"></i>
                <p class="td-strong">{{ __('diagnostic_desk.imaging_empty_title') }}</p>
                <p class="text-muted">{{ __('diagnostic_desk.imaging_empty_body') }}</p>
                @if($canImaging)
                <a href="{{ route('portals.imaging.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('diagnostic_desk.imaging_empty_action') }}</a>
                @endif
            </div>
        @else
            <div class="stat-grid">
                <a href="{{ route('portals.imaging.index', ['tab' => 'pending']) }}" class="stat-card stat-card--primary">
                    <div class="stat-card__head"><i data-lucide="inbox"></i></div>
                    <div class="stat-card__label">{{ __('diagnostic_desk.imaging_pending') }}</div>
                    <div class="stat-card__value" data-kpi="imaging_pending">{{ $imaging['pending'] }}</div>
                    <div class="stat-card__hint">{{ __('diagnostic_desk.imaging_pending_hint') }}</div>
                </a>
                <div class="stat-card">
                    <div class="stat-card__head"><i data-lucide="calendar-plus"></i></div>
                    <div class="stat-card__label">{{ __('diagnostic_desk.imaging_today') }}</div>
                    <div class="stat-card__value" data-kpi="imaging_today">{{ $imaging['today'] }}</div>
                    <div class="stat-card__hint">{{ __('diagnostic_desk.imaging_today_hint') }}</div>
                </div>
                <a href="{{ route('portals.imaging.index', ['tab' => 'awaiting_report']) }}" class="stat-card {{ $imaging['awaiting_report'] > 0 ? 'stat-card--warning' : '' }}">
                    <div class="stat-card__head"><i data-lucide="file-clock"></i></div>
                    <div class="stat-card__label">{{ __('diagnostic_desk.imaging_awaiting_report') }}</div>
                    <div class="stat-card__value" data-kpi="imaging_awaiting_report">{{ $imaging['awaiting_report'] }}</div>
                    <div class="stat-card__hint">{{ __('diagnostic_desk.imaging_awaiting_hint') }}</div>
                </a>
                <a href="{{ route('portals.imaging.index', ['tab' => 'reported']) }}" class="stat-card stat-card--success">
                    <div class="stat-card__head"><i data-lucide="file-check-2"></i></div>
                    <div class="stat-card__label">{{ __('diagnostic_desk.imaging_reported') }}</div>
                    <div class="stat-card__value" data-kpi="imaging_reported">{{ $imaging['reported'] }}</div>
                    <div class="stat-card__hint">{{ __('diagnostic_desk.imaging_scheduled') }}: {{ $imaging['scheduled'] }} · {{ __('diagnostic_desk.imaging_in_progress') }}: {{ $imaging['in_progress'] }}</div>
                </a>
            </div>
        @endunless
    </div>
</div>

{{-- ── Publication: lab finder / imaging finder ─────────────────────────── --}}
<div class="panel mb-4" id="publication" data-panel="publication">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="globe"></i> {{ __('diagnostic_desk.pub_title') }}</h2>
    </div>
    <div class="panel-body">
        <p class="page-subtitle">{{ __('diagnostic_desk.pub_subtitle') }}</p>
        @if(! $publication['linked'])
            <div class="alert alert-warning"><i data-lucide="link-2-off"></i><div>{{ __('diagnostic_desk.pub_unlinked') }}</div></div>
        @else
            @unless($publication['listing_active'])
                <div class="alert alert-warning"><i data-lucide="eye-off"></i><div>{{ __('diagnostic_desk.pub_listing_inactive', ['listing' => $publication['listing_name']]) }}</div></div>
            @endunless
            <div class="form-grid">
                <div class="form-group">
                    <div class="form-label">{{ __('diagnostic_desk.pub_listing') }}</div>
                    <div class="td-strong">{{ $publication['listing_name'] }}</div>
                </div>
            </div>
        @endif

        <div class="stat-grid mt-3">
            <div class="stat-card" data-finder="lab">
                <div class="stat-card__head"><i data-lucide="flask-conical"></i></div>
                <div class="stat-card__label">{{ __('diagnostic_desk.pub_lab') }}</div>
                <div class="stat-card__value" data-kpi="lab_published">{{ $publication['lab_published'] }}</div>
                <div class="stat-card__hint">
                    {{ __('diagnostic_desk.pub_in_catalogue') }}: {{ $publication['lab_catalogue'] }} ·
                    {{ __('diagnostic_desk.pub_last') }}: {{ $publication['lab_last_published_at'] ? $publication['lab_last_published_at']->translatedFormat('d M Y H:i') : __('diagnostic_desk.pub_never') }}
                </div>
            </div>
            <div class="stat-card" data-finder="imaging">
                <div class="stat-card__head"><i data-lucide="scan-line"></i></div>
                <div class="stat-card__label">{{ __('diagnostic_desk.pub_imaging') }}</div>
                <div class="stat-card__value" data-kpi="imaging_published">{{ $publication['imaging_published'] }}</div>
                <div class="stat-card__hint">
                    {{ __('diagnostic_desk.pub_in_catalogue') }}: {{ $publication['imaging_catalogue'] }} ·
                    {{ __('diagnostic_desk.pub_last') }}: {{ $publication['imaging_last_published_at'] ? $publication['imaging_last_published_at']->translatedFormat('d M Y H:i') : __('diagnostic_desk.pub_never') }}
                </div>
            </div>
        </div>

        @if($publication['lab_catalogue'] === 0)
            <p class="form-hint mt-3" data-empty="lab_catalogue">{{ __('diagnostic_desk.pub_lab_none') }}</p>
        @endif
        @if($publication['imaging_catalogue'] === 0)
            <p class="form-hint mt-1" data-empty="imaging_catalogue">{{ __('diagnostic_desk.pub_imaging_none') }}</p>
        @endif

        <div class="row-actions-inline mt-3">
            @if($canCatalogue)
                <a href="{{ route('portals.facility.catalogue') }}" class="btn btn-secondary btn-sm"><i data-lucide="clipboard-list"></i> {{ __('diagnostic_desk.pub_open_catalogue') }}</a>
            @endif
            @if(Route::has('public.network.lab-test-finder'))
                <a href="{{ route('public.network.lab-test-finder') }}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener"><i data-lucide="external-link"></i> {{ __('diagnostic_desk.pub_open_lab_finder') }}</a>
            @endif
            @if(Route::has('public.network.imaging-finder'))
                <a href="{{ route('public.network.imaging-finder') }}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener"><i data-lucide="external-link"></i> {{ __('diagnostic_desk.pub_open_imaging_finder') }}</a>
            @endif
        </div>
    </div>
</div>

@endsection

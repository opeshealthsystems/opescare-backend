@extends('layouts.portal')
@section('title', __('public.adm_kpi_page_title'))
@section('breadcrumb_home', __('public.adm_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_kpi_page_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_kpi_h2') }}</h1>
        <p class="od-page__sub"><a href="{{ route('portals.admin.kpi.index') }}">{{ __('public.adm_kpi_page_title') }}</a> · {{ __('public.adm_kpi_breadcrumb_dashboard') }}</p>
        <p class="od-page__sub">{{ __('public.adm_kpi_subtitle') }}</p>
    </div>
    <div class="od-row">
        <form method="POST" action="{{ route('portals.admin.kpi.recompute') }}" class="oda-inline-form">
            @csrf
            <input type="hidden" name="date" value="{{ now()->toDateString() }}">
            <button type="submit" class="od-btn od-btn--secondary">
                <i data-lucide="refresh-cw"></i> {{ __('public.adm_kpi_btn_refresh') }}
            </button>
        </form>
        <a href="{{ route('portals.admin.kpi.trend') }}" class="od-btn od-btn--primary">
            <i data-lucide="trending-up"></i> {{ __('public.adm_kpi_btn_trends') }}
        </a>
    </div>
</div>

@if(session('success'))<div class="oda-gd-flash oda-gd-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

{{-- Platform Summary Strip --}}
<div class="od-kpis oda-gd-kpis-6">
    <div class="od-kpi od-tint--blue"><span class="od-kpi__icon"><i data-lucide="users"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_kpi_stat_patients_today') }}</span><span class="od-kpi__value">{{ number_format($platformSummary['total_patients']) }}</span></span></div>
    <div class="od-kpi od-tint--green"><span class="od-kpi__icon"><i data-lucide="stethoscope"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_kpi_stat_visits_today') }}</span><span class="od-kpi__value">{{ number_format($platformSummary['total_visits']) }}</span></span></div>
    <div class="od-kpi od-tint--amber"><span class="od-kpi__icon"><i data-lucide="flask-conical"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_kpi_stat_lab_orders') }}</span><span class="od-kpi__value">{{ number_format($platformSummary['total_lab_orders']) }}</span></span></div>
    <div class="od-kpi od-tint--teal"><span class="od-kpi__icon"><i data-lucide="pill"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_kpi_stat_prescriptions') }}</span><span class="od-kpi__value">{{ number_format($platformSummary['total_prescriptions']) }}</span></span></div>
    <div class="od-kpi od-tint--sky"><span class="od-kpi__icon"><i data-lucide="receipt"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_kpi_stat_invoices') }}</span><span class="od-kpi__value">{{ number_format($platformSummary['total_invoices']) }}</span></span></div>
    <div class="od-kpi od-tint--rose"><span class="od-kpi__icon"><i data-lucide="building-2"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_kpi_stat_active_facilities') }}</span><span class="od-kpi__value">{{ $platformSummary['active_facilities'] }}</span></span></div>
</div>

{{-- Today's activity bar chart --}}
@php
    $chartData = [
        [__('public.adm_kpi_stat_patients_today'), $platformSummary['total_patients'] ?? 0],
        [__('public.adm_kpi_stat_visits_today'), $platformSummary['total_visits'] ?? 0],
        [__('public.adm_kpi_stat_lab_orders'), $platformSummary['total_lab_orders'] ?? 0],
        [__('public.adm_kpi_stat_prescriptions'), $platformSummary['total_prescriptions'] ?? 0],
        [__('public.adm_kpi_stat_invoices'), $platformSummary['total_invoices'] ?? 0],
    ];
    $chartMax = max(1, max(array_map(fn($r) => $r[1], $chartData)));
    $gdSnapPill = fn ($s) => match ($s) { 'critical' => 'od-pill--urgent', 'warning' => 'od-pill--pending', default => 'od-pill--ready' };
@endphp
<div class="od-card">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="bar-chart-3"></i> {{ __('public.adm_kpi_chart_title') }}</h2></div>
    <div class="od-card__body">
        <div class="oda-gd-bars">
            @foreach($chartData as [$label, $value])
            <div class="oda-gd-bars__row">
                <span class="oda-gd-bars__label">{{ $label }}</span>
                <meter class="oda-gd-meter" min="0" max="{{ $chartMax }}" value="{{ $value }}"></meter>
                <span class="oda-gd-bars__value">{{ number_format($value) }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="gauge"></i> {{ __('public.adm_kpi_page_title') }}</h2>
        <span class="od-card__count">{{ $snapshots->count() }}</span>
    </div>
    {{-- Category Filter --}}
    <div class="od-card__body">
        <nav class="od-tabs">
            <a href="{{ route('portals.admin.kpi.index') }}"
               class="od-tab {{ !$category ? 'is-active' : '' }}">{{ __('public.adm_kpi_filter_all') }}</a>
            @foreach($categories as $cat)
            <a href="{{ route('portals.admin.kpi.index', ['category' => $cat]) }}"
               class="od-tab {{ $category === $cat ? 'is-active' : '' }}">{{ __('kpi.category_' . $cat) }}</a>
            @endforeach
        </nav>
    </div>

    {{-- KPI Metric Cards --}}
    <div class="od-card__body">
    @if($snapshots->isEmpty())
        <div class="od-empty">
            <span class="od-empty__icon"><i data-lucide="bar-chart-3"></i></span>
            <h3 class="od-empty__title">{{ __('public.adm_kpi_empty_h3') }}</h3>
            <p class="od-empty__text">{{ __('public.adm_kpi_empty_p') }}</p>
        </div>
    @else
        <div class="od-grid od-grid--3">
            @foreach($snapshots as $snapshot)
            @php $def = $snapshot->metricDefinition; @endphp
            <div class="oda-gd-metric">
                <div class="oda-gd-metric__head">
                    <span class="oda-gd-metric__label">{{ __('kpi.metric_' . $def->slug) }}</span>
                    <span class="od-pill {{ $gdSnapPill($snapshot->status) }}">@enum($snapshot->status)</span>
                </div>
                <div class="oda-gd-metric__value">{{ $snapshot->formattedValue() }}</div>
                @if($snapshot->change_pct !== null)
                <div class="od-kpi__trend {{ $snapshot->change_pct >= 0 ? 'od-kpi__trend--up' : 'od-kpi__trend--down' }}">
                    <i data-lucide="{{ $snapshot->change_pct >= 0 ? 'arrow-up' : 'arrow-down' }}"></i> {{ abs($snapshot->change_pct) }}% {{ __('public.adm_kpi_vs_yesterday') }}
                </div>
                @endif
                @if($def->target_value)
                <div class="oda-help">
                    {{ __('public.adm_kpi_target_label') }} {{ number_format($def->target_value, $def->display_format === 'percentage' ? 1 : 0) }}{{ $def->display_format === 'percentage' ? '%' : '' }}
                </div>
                @endif
                <a href="{{ route('portals.admin.kpi.trend', ['metric' => $def->slug]) }}" class="od-card__link">{{ __('public.adm_kpi_view_trend') }}</a>
            </div>
            @endforeach
        </div>
    @endif
    </div>
</div>

{{-- Export Panel — CSV of platform-wide daily aggregates (no patient identifiers) --}}
<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="download"></i> {{ __('public.adm_kpi_export_title') }}</h2>
    </div>
    <div class="od-card__body">
        <p class="oda-help oda-gd-gap-b">{{ __('kpi.export_hint') }}</p>
        <form method="GET" action="{{ route('portals.admin.kpi.export') }}" class="oda-form oda-form--3">
            <label class="oda-label oda-form__full"><span class="oda-label__text">{{ __('public.adm_kpi_export_label_metrics') }}</span>
                <select name="metric_slugs[]" multiple class="od-select oda-gd-multi">
                    @foreach(\App\Models\MetricDefinition::active()->orderBy('category')->get() as $def)
                    <option value="{{ $def->slug }}">{{ __('kpi.metric_' . $def->slug) }}</option>
                    @endforeach
                </select></label>
            <label class="oda-label"><span class="oda-label__text">{{ __('public.adm_kpi_export_label_from') }}</span>
                <input type="date" name="period_from" value="{{ now()->subDays(30)->toDateString() }}" class="od-input"></label>
            <label class="oda-label"><span class="oda-label__text">{{ __('public.adm_kpi_export_label_to') }}</span>
                <input type="date" name="period_to" value="{{ now()->toDateString() }}" class="od-input"></label>
            <div class="oda-form__actions">
                <button type="submit" class="od-btn od-btn--primary od-btn--sm">
                    <i data-lucide="download"></i> {{ __('kpi.export_csv') }}
                </button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection

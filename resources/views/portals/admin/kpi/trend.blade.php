@extends('layouts.portal')
@section('title', __('kpi.trend_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ $definition ? __('kpi.metric_' . $definition->slug) : __('kpi.trend_title') }}</h1>
        <p class="od-page__sub"><a href="{{ route('portals.admin.kpi.index') }}">{{ __('public.adm_kpi_trend_breadcrumb') }}</a></p>
        @if($definition)
        <p class="od-page__sub">
            {{ __('kpi.metric_' . $definition->slug . '_desc') }}
            <span class="od-pill od-pill--normal">{{ __('kpi.category_' . $definition->category) }}</span>
        </p>
        @endif
    </div>
</div>

{{-- Controls --}}
<div class="od-card">
    <div class="od-card__body">
        <form method="GET" action="{{ route('portals.admin.kpi.trend') }}" class="oda-form">
            <label class="oda-label"><span class="oda-label__text">{{ __('public.adm_kpi_trend_label_metric') }}</span>
                <select name="metric" class="od-select" data-autosubmit>
                    @foreach($allMetrics->groupBy(fn($m) => __('kpi.category_' . $m->category)) as $cat => $group)
                    <optgroup label="{{ $cat }}">
                        @foreach($group as $m)
                        <option value="{{ $m->slug }}" {{ $slug === $m->slug ? 'selected' : '' }}>{{ __('kpi.metric_' . $m->slug) }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select></label>
            <label class="oda-label"><span class="oda-label__text">{{ __('public.adm_kpi_trend_label_period') }}</span>
                <select name="period" class="od-select" data-autosubmit>
                    <option value="7d" {{ $period === '7d' ? 'selected' : '' }}>{{ __('public.adm_kpi_trend_period_7d') }}</option>
                    <option value="30d" {{ $period === '30d' ? 'selected' : '' }}>{{ __('public.adm_kpi_trend_period_30d') }}</option>
                    <option value="90d" {{ $period === '90d' ? 'selected' : '' }}>{{ __('public.adm_kpi_trend_period_90d') }}</option>
                </select></label>
        </form>
    </div>
</div>

@if($definition && $trendData)
@php
    $values = array_values(array_filter($trendData, fn($v) => $v !== null));
    $avg = count($values) > 0 ? array_sum($values) / count($values) : null;
    $max = count($values) > 0 ? max($values) : null;
    $min = count($values) > 0 ? min($values) : null;
    $latest = count($values) > 0 ? end($values) : null;
    $isPct = $definition->display_format === 'percentage';
@endphp
{{-- Metric Info --}}
<div class="od-kpis">
    <div class="od-kpi od-tint--blue"><span class="od-kpi__icon"><i data-lucide="activity"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_kpi_trend_stat_latest') }}</span><span class="od-kpi__value">{{ $latest !== null ? number_format($latest, $isPct ? 1 : 0) . ($isPct ? '%' : '') : '—' }}</span></span></div>
    <div class="od-kpi od-tint--sky"><span class="od-kpi__icon"><i data-lucide="sigma"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_kpi_trend_stat_average') }}</span><span class="od-kpi__value">{{ $avg !== null ? number_format($avg, 1) : '—' }}</span></span></div>
    <div class="od-kpi od-tint--green"><span class="od-kpi__icon"><i data-lucide="arrow-up"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_kpi_trend_stat_peak') }}</span><span class="od-kpi__value">{{ $max !== null ? number_format($max, 1) : '—' }}</span></span></div>
    <div class="od-kpi od-tint--amber"><span class="od-kpi__icon"><i data-lucide="arrow-down"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_kpi_trend_stat_minimum') }}</span><span class="od-kpi__value">{{ $min !== null ? number_format($min, 1) : '—' }}</span></span></div>
    @if($definition->target_value)
    <div class="od-kpi od-tint--teal"><span class="od-kpi__icon"><i data-lucide="target"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_kpi_trend_stat_target') }}</span><span class="od-kpi__value">{{ number_format($definition->target_value, 1) }}</span></span></div>
    @endif
</div>

{{-- Trend breakdown --}}
<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="calendar"></i> {{ __('kpi.daily_values', ['from' => $from->format('d/m/Y'), 'to' => $to->format('d/m/Y')]) }}</h2>
    </div>
    <div class="od-card__body">
        @if(empty($trendData))
        <div class="od-empty"><p class="od-empty__text">{{ __('public.adm_kpi_trend_no_data') }}</p></div>
        @else
        @php $maxVal = $max ?: 1; @endphp
        <div class="oda-gd-bars">
            @foreach($trendData as $date => $value)
            <div class="oda-gd-bars__row">
                <span class="oda-gd-bars__label">{{ \Carbon\Carbon::parse($date)->translatedFormat('d M Y') }}</span>
                <meter class="oda-gd-meter" min="0" max="{{ $maxVal }}" value="{{ $value !== null ? min($value, $maxVal) : 0 }}"></meter>
                <span class="oda-gd-bars__value">
                    @if($value !== null)
                        {{ number_format($value, $isPct ? 1 : 0) }}{{ $isPct ? '%' : '' }}
                        @if($definition->target_value)
                            @php $diff = $value - $definition->target_value; @endphp
                            <span class="od-kpi__trend {{ $diff >= 0 ? 'od-kpi__trend--up' : 'od-kpi__trend--down' }}">({{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 1) }})</span>
                        @endif
                    @else
                        <span class="oda-help">{{ __('public.adm_kpi_trend_no_data_inline') }}</span>
                    @endif
                </span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@else
<div class="od-card">
    <div class="od-card__body">
        <div class="od-empty">
            <span class="od-empty__icon"><i data-lucide="trending-up"></i></span>
            <h3 class="od-empty__title">{{ __('public.adm_kpi_trend_empty_h3') }}</h3>
            <p class="od-empty__text">{{ __('public.adm_kpi_trend_empty_p') }}</p>
        </div>
    </div>
</div>
@endif

</div>
@endsection

@extends('layouts.portal')
@section('title', 'KPI Trend — ' . ($definition?->name ?? 'Metric'))
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection

@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.admin.kpi.index') }}">{{ __('public.adm_kpi_trend_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ $definition?->name ?? 'Metric Trend' }}</span>
</div>

<div class="entity-head">
    <div class="entity-head__icon"><i data-lucide="trending-up"></i></div>
    <div>
        <h2 class="entity-head__title">{{ $definition?->name ?? 'Metric Trend' }}</h2>
        @if($definition)
        <div class="entity-head__sub">
            <span class="td-muted text-sm">{{ $definition->description }}</span>
            <span class="badge badge--info badge-sm">{{ ucfirst($definition->category) }}</span>
        </div>
        @endif
    </div>
</div>

{{-- Controls --}}
<div class="panel mb-4">
    <div class="panel-body">
        <form method="GET" action="{{ route('portals.admin.kpi.trend') }}" class="filter-bar">
            <div class="form-group">
                <label class="form-label">{{ __('public.adm_kpi_trend_label_metric') }}</label>
                <select name="metric" class="form-control" onchange="this.form.submit()">
                    @foreach($allMetrics->groupBy(fn($m) => ucfirst($m->category)) as $cat => $group)
                    <optgroup label="{{ $cat }}">
                        @foreach($group as $m)
                        <option value="{{ $m->slug }}" {{ $slug === $m->slug ? 'selected' : '' }}>{{ $m->name }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.adm_kpi_trend_label_period') }}</label>
                <select name="period" class="form-control" onchange="this.form.submit()">
                    <option value="7d" {{ $period === '7d' ? 'selected' : '' }}>{{ __('public.adm_kpi_trend_period_7d') }}</option>
                    <option value="30d" {{ $period === '30d' ? 'selected' : '' }}>{{ __('public.adm_kpi_trend_period_30d') }}</option>
                    <option value="90d" {{ $period === '90d' ? 'selected' : '' }}>{{ __('public.adm_kpi_trend_period_90d') }}</option>
                </select>
            </div>
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
<div class="stat-grid mb-4">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $latest !== null ? number_format($latest, $isPct ? 1 : 0) . ($isPct ? '%' : '') : '—' }}</div>
        <div class="stat-card__label">{{ __('public.adm_kpi_trend_stat_latest') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value">{{ $avg !== null ? number_format($avg, 1) : '—' }}</div>
        <div class="stat-card__label">{{ __('public.adm_kpi_trend_stat_average') }}</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $max !== null ? number_format($max, 1) : '—' }}</div>
        <div class="stat-card__label">{{ __('public.adm_kpi_trend_stat_peak') }}</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $min !== null ? number_format($min, 1) : '—' }}</div>
        <div class="stat-card__label">{{ __('public.adm_kpi_trend_stat_minimum') }}</div>
    </div>
    @if($definition->target_value)
    <div class="stat-card stat-card--teal">
        <div class="stat-card__value">{{ number_format($definition->target_value, 1) }}</div>
        <div class="stat-card__label">{{ __('public.adm_kpi_trend_stat_target') }}</div>
    </div>
    @endif
</div>

{{-- Trend breakdown --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="calendar"></i> Daily values — {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}</h3>
    </div>
    <div class="panel-body">
        @if(empty($trendData))
        <div class="td-muted empty-cell">{{ __('public.adm_kpi_trend_no_data') }}</div>
        @else
        @php $maxVal = $max ?: 1; @endphp
        <div class="breakdown">
            @foreach($trendData as $date => $value)
            <div class="breakdown__row">
                <span class="breakdown__label">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</span>
                <div class="breakdown__track">
                    @if($value !== null)
                    <div class="breakdown__fill" style="width: {{ min(100, ($value / $maxVal) * 100) }}%"></div>
                    @endif
                </div>
                <span class="breakdown__value">
                    @if($value !== null)
                        {{ number_format($value, $isPct ? 1 : 0) }}{{ $isPct ? '%' : '' }}
                        @if($definition->target_value)
                            @php $diff = $value - $definition->target_value; @endphp
                            <span class="text-sm {{ $diff >= 0 ? 'trend-up' : 'trend-down' }}">({{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 1) }})</span>
                        @endif
                    @else
                        <span class="td-muted">{{ __('public.adm_kpi_trend_no_data_inline') }}</span>
                    @endif
                </span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@else
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="trending-up"></i></div>
        <h3>{{ __('public.adm_kpi_trend_empty_h3') }}</h3>
        <p>{{ __('public.adm_kpi_trend_empty_p') }}</p>
    </div>
</div>
@endif

@endsection

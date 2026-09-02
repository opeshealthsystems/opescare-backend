@extends('layouts.portal')
@section('title', __('public.adm_kpi_page_title'))
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection

@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.admin.kpi.index') }}">{{ __('public.adm_kpi_page_title') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_kpi_breadcrumb_dashboard') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_kpi_h2') }}</h2>
    <div class="page-head__spacer"></div>
    <form method="POST" action="{{ route('portals.admin.kpi.recompute') }}" class="inline-form">
        @csrf
        <input type="hidden" name="date" value="{{ now()->toDateString() }}">
        <button type="submit" class="btn btn-secondary btn-sm">
            <i data-lucide="refresh-cw"></i> {{ __('public.adm_kpi_btn_refresh') }}
        </button>
    </form>
    <a href="{{ route('portals.admin.kpi.trend') }}" class="btn btn-secondary btn-sm">
        <i data-lucide="trending-up"></i> {{ __('public.adm_kpi_btn_trends') }}
    </a>
</div>

<p class="td-muted mb-6">{{ __('public.adm_kpi_subtitle') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

{{-- Platform Summary Strip --}}
<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="users"></i><span class="stat-card__label">{{ __('public.adm_kpi_stat_patients_today') }}</span></div>
        <div class="stat-card__value">{{ number_format($platformSummary['total_patients']) }}</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__head"><i data-lucide="stethoscope"></i><span class="stat-card__label">{{ __('public.adm_kpi_stat_visits_today') }}</span></div>
        <div class="stat-card__value">{{ number_format($platformSummary['total_visits']) }}</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__head"><i data-lucide="flask-conical"></i><span class="stat-card__label">{{ __('public.adm_kpi_stat_lab_orders') }}</span></div>
        <div class="stat-card__value">{{ number_format($platformSummary['total_lab_orders']) }}</div>
    </div>
    <div class="stat-card stat-card--teal">
        <div class="stat-card__head"><i data-lucide="pill"></i><span class="stat-card__label">{{ __('public.adm_kpi_stat_prescriptions') }}</span></div>
        <div class="stat-card__value">{{ number_format($platformSummary['total_prescriptions']) }}</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__head"><i data-lucide="receipt"></i><span class="stat-card__label">{{ __('public.adm_kpi_stat_invoices') }}</span></div>
        <div class="stat-card__value">{{ number_format($platformSummary['total_invoices']) }}</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="building-2"></i><span class="stat-card__label">{{ __('public.adm_kpi_stat_active_facilities') }}</span></div>
        <div class="stat-card__value">{{ $platformSummary['active_facilities'] }}</div>
    </div>
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
@endphp
<div class="panel mb-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="bar-chart-3"></i> {{ __('public.adm_kpi_chart_title') }}</h3></div>
    <div class="panel-body">
        <div class="bar-chart">
            @foreach($chartData as [$label, $value])
            <div class="bar-chart__col">
                <span class="bar-chart__val">{{ number_format($value) }}</span>
                <div class="bar-chart__bar" style="height: {{ max(2, ($value / $chartMax) * 100) }}%"></div>
                <span class="bar-chart__label">{{ $label }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Category Filter --}}
<div class="filter-bar">
    <a href="{{ route('portals.admin.kpi.index') }}"
       class="btn {{ !$category ? 'btn-primary' : 'btn-secondary' }} btn-sm">{{ __('public.adm_kpi_filter_all') }}</a>
    @foreach($categories as $cat)
    <a href="{{ route('portals.admin.kpi.index', ['category' => $cat]) }}"
       class="btn {{ $category === $cat ? 'btn-primary' : 'btn-secondary' }} btn-sm">
       {{ ucfirst($cat) }}
    </a>
    @endforeach
</div>

{{-- KPI Metric Cards --}}
@if($snapshots->isEmpty())
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="bar-chart-3"></i></div>
        <h3>{{ __('public.adm_kpi_empty_h3') }}</h3>
        <p>{{ __('public.adm_kpi_empty_p') }}</p>
    </div>
</div>
@else
<div class="card-grid mb-6">
    @foreach($snapshots as $snapshot)
    @php
        $def = $snapshot->metricDefinition;
        $accent = $snapshot->status === 'critical' ? 'nav-card--danger' : '';
    @endphp
    <div class="nav-card {{ $accent }}">
        <div class="flex-between mb-3">
            <span class="stat-card__label">{{ $def->name }}</span>
            <span class="badge {{ $snapshot->statusBadgeClass() }} badge-sm">@enum($snapshot->status)</span>
        </div>
        <div class="stat-card__value">{{ $snapshot->formattedValue() }}</div>
        @if($snapshot->change_pct !== null)
        <div class="text-sm {{ $snapshot->change_pct >= 0 ? 'trend-up' : 'trend-down' }}">
            {{ $snapshot->change_pct >= 0 ? '▲' : '▼' }} {{ abs($snapshot->change_pct) }}% {{ __('public.adm_kpi_vs_yesterday') }}
        </div>
        @endif
        @if($def->target_value)
        <div class="td-muted text-sm mt-1">
            {{ __('public.adm_kpi_target_label') }} {{ number_format($def->target_value, $def->display_format === 'percentage' ? 1 : 0) }}{{ $def->display_format === 'percentage' ? '%' : '' }}
        </div>
        @endif
        <div class="mt-3">
            <a href="{{ route('portals.admin.kpi.trend', ['metric' => $def->slug]) }}" class="link-action">{{ __('public.adm_kpi_view_trend') }}</a>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Export Panel --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="download"></i> {{ __('public.adm_kpi_export_title') }}</h3>
    </div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.admin.kpi.export') }}" class="filter-bar">
            @csrf
            <div class="form-group">
                <label class="form-label">{{ __('public.adm_kpi_export_label_metrics') }}</label>
                <select name="metric_slugs[]" multiple class="form-control export-multi">
                    @foreach(\App\Models\MetricDefinition::active()->orderBy('name')->get() as $def)
                    <option value="{{ $def->slug }}">{{ $def->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.adm_kpi_export_label_from') }}</label>
                <input type="date" name="period_from" value="{{ now()->subDays(30)->toDateString() }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.adm_kpi_export_label_to') }}</label>
                <input type="date" name="period_to" value="{{ now()->toDateString() }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.adm_kpi_export_label_format') }}</label>
                <select name="export_type" class="form-control">
                    <option value="csv">CSV</option>
                    <option value="json">JSON</option>
                </select>
            </div>
            <div class="form-group form-actions-end">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="download"></i> {{ __('public.adm_kpi_export_btn') }}
                </button>
            </div>
        </form>

        @if($recentExports->isNotEmpty())
        <div class="detail-divider">
            <div class="kv-strong mb-3">{{ __('public.adm_kpi_recent_exports') }}</div>
            <div class="table-wrapper">
            <table class="data-table">
                <thead><tr><th>{{ __('public.adm_kpi_col_date') }}</th><th>{{ __('public.adm_kpi_col_type') }}</th><th>{{ __('public.adm_kpi_col_period') }}</th><th>{{ __('public.adm_kpi_col_status') }}</th></tr></thead>
                <tbody>
                @foreach($recentExports as $exp)
                <tr>
                    <td data-label="{{ __('public.adm_kpi_col_date') }}">{{ $exp->requested_at->format('d M Y H:i') }}</td>
                    <td data-label="{{ __('public.adm_kpi_col_type') }}">{{ strtoupper($exp->export_type) }}</td>
                    <td data-label="{{ __('public.adm_kpi_col_period') }}">{{ $exp->period_from->format('d M') }} – {{ $exp->period_to->format('d M Y') }}</td>
                    <td data-label="{{ __('public.adm_kpi_col_status') }}"><span class="badge badge--{{ $exp->status === 'ready' ? 'success' : ($exp->status === 'failed' ? 'danger' : 'warning') }} badge-sm">@enum($exp->status)</span></td>
                </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

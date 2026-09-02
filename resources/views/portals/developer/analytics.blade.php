@extends('layouts.portal')
@section('title', __('public.developer_portal.page_analytics', [], app()->getLocale()) ?: 'API Usage Analytics')
@section('sidebar_nav') @include('portals.developer._sidebar') @endsection
@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-head">
    <h2>{{ __('public.developer_portal.nav_analytics', [], $l) ?: 'API Analytics' }}</h2>
    <p class="portal-page-subtitle">{{ __('public.developer_portal.analytics_subtitle', [], $l) ?: 'Request volume, error rates, and response times across your apps — last 30 days.' }}</p>
    <div class="page-head__spacer"></div>
    <form method="GET" class="filter-bar filter-bar--flush">
        <select name="period" class="filter-select" aria-label="{{ __('public.developer_portal.period_aria', [], $l) ?: 'Period' }}" onchange="this.form.submit()">
            <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>{{ __('public.developer_portal.period_daily', [], $l) ?: 'Daily' }}</option>
            <option value="hourly" {{ $period === 'hourly' ? 'selected' : '' }}>{{ __('public.developer_portal.period_hourly', [], $l) ?: 'Hourly' }}</option>
            <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>{{ __('public.developer_portal.period_monthly', [], $l) ?: 'Monthly' }}</option>
        </select>
    </form>
</div>

{{-- Totals --}}
<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__label">{{ __('public.developer_portal.stat_total_requests', [], $l) ?: 'Total requests' }}</div>
        <div class="stat-card__value">{{ number_format($totals['requests']) }}</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__label">{{ __('public.developer_portal.stat_total_errors', [], $l) ?: 'Total errors' }}</div>
        <div class="stat-card__value">{{ number_format($totals['errors']) }}</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__label">{{ __('public.developer_portal.stat_error_rate', [], $l) ?: 'Error rate' }}</div>
        <div class="stat-card__value">{{ $totals['requests'] > 0 ? round(($totals['errors'] / $totals['requests']) * 100, 1) : 0 }}%</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__label">{{ __('public.developer_portal.stat_avg_response', [], $l) ?: 'Avg response' }}</div>
        <div class="stat-card__value">{{ round($totals['avg_ms']) }}ms</div>
    </div>
    <div class="stat-card stat-card--teal">
        <div class="stat-card__label">{{ __('public.developer_portal.stat_active_apps', [], $l) ?: 'Active apps' }}</div>
        <div class="stat-card__value">{{ $clients->count() }}</div>
    </div>
</div>

@if($metrics->isEmpty())
<div class="alert alert-info mb-6">
    <i data-lucide="info"></i>
    <div>{{ __('public.developer_portal.analytics_no_data', [], $l) ?: 'No usage data yet for the selected period. Make your first API call to see metrics here.' }}</div>
</div>
@endif

<div class="field-grid mb-6">

    {{-- Top Endpoints --}}
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="list"></i> {{ __('public.developer_portal.analytics_top_endpoints', [], $l) ?: 'Top endpoints' }}</h3></div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('public.developer_portal.col_endpoint', [], $l) ?: 'Endpoint' }}</th>
                        <th>{{ __('public.developer_portal.col_requests', [], $l) ?: 'Requests' }}</th>
                        <th>{{ __('public.developer_portal.col_errors', [], $l) ?: 'Errors' }}</th>
                        <th>{{ __('public.developer_portal.col_avg_ms', [], $l) ?: 'Avg ms' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($byEndpoint as $row)
                    <tr>
                        <td data-label="{{ __('public.developer_portal.col_endpoint', [], $l) ?: 'Endpoint' }}" class="mono">{{ $row['endpoint'] }}</td>
                        <td data-label="{{ __('public.developer_portal.col_requests', [], $l) ?: 'Requests' }}" class="td-strong">{{ number_format($row['requests']) }}</td>
                        <td data-label="{{ __('public.developer_portal.col_errors', [], $l) ?: 'Errors' }}">
                            @if($row['errors'] > 0)<span class="badge badge-danger">{{ number_format($row['errors']) }}</span>
                            @else<span class="td-muted">0</span>@endif
                        </td>
                        <td data-label="{{ __('public.developer_portal.col_avg_ms', [], $l) ?: 'Avg ms' }}" class="td-muted">{{ $row['avg_ms'] }}ms</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="td-muted empty-cell">{{ __('public.developer_portal.analytics_no_data_cell', [], $l) ?: 'No data yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Your Apps --}}
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="cpu"></i> {{ __('public.developer_portal.analytics_your_apps', [], $l) ?: 'Your apps' }}</h3>
            <a href="{{ route('portals.developer.apps') }}" class="btn btn-secondary btn-sm">{{ __('public.developer_portal.btn_manage', [], $l) ?: 'Manage' }}</a>
        </div>
        @if($clients->isEmpty())
        <div class="panel-body empty-state">
            <p>{{ __('public.developer_portal.analytics_no_apps', [], $l) ?: 'No apps yet.' }} <a href="{{ route('portals.developer.apps.create') }}">{{ __('public.developer_portal.lnk_create_first_app', [], $l) ?: 'Create your first app' }}</a>.</p>
        </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.developer_portal.col_app', [], $l) ?: 'App' }}</th>
                    <th>{{ __('public.portal.col_status', [], $l) ?: 'Status' }}</th>
                </tr></thead>
                <tbody>
                @foreach($clients as $client)
                <tr>
                    <td data-label="{{ __('public.developer_portal.col_app', [], $l) ?: 'App' }}">
                        <span class="td-strong">{{ $client->name }}</span>
                        <div class="mono">{{ $client->client_id }}</div>
                    </td>
                    <td data-label="{{ __('public.portal.col_status', [], $l) ?: 'Status' }}">
                        <span class="badge badge-{{ $client->status === 'active' ? 'success' : ($client->status === 'pending' ? 'warning' : 'neutral') }}">@enum($client->status)</span>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>

{{-- Recent metrics table --}}
@if($metrics->isNotEmpty())
<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="history"></i> {{ __('public.developer_portal.analytics_recent_metrics', [], $l) ?: 'Recent metric records' }}</h3></div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.developer_portal.col_endpoint', [], $l) ?: 'Endpoint' }}</th>
                    <th>{{ __('public.developer_portal.col_method', [], $l) ?: 'Method' }}</th>
                    <th>{{ __('public.developer_portal.col_period_start', [], $l) ?: 'Period start' }}</th>
                    <th>{{ __('public.developer_portal.col_requests', [], $l) ?: 'Requests' }}</th>
                    <th>{{ __('public.developer_portal.col_errors', [], $l) ?: 'Errors' }}</th>
                    <th>{{ __('public.developer_portal.col_avg_ms', [], $l) ?: 'Avg ms' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($metrics->take(20) as $m)
                <tr>
                    <td data-label="{{ __('public.developer_portal.col_endpoint', [], $l) ?: 'Endpoint' }}" class="mono">{{ $m->endpoint }}</td>
                    <td data-label="{{ __('public.developer_portal.col_method', [], $l) ?: 'Method' }}"><span class="badge badge-neutral">{{ $m->method }}</span></td>
                    <td data-label="{{ __('public.developer_portal.col_period_start', [], $l) ?: 'Period start' }}" class="td-muted">{{ $m->period_start?->format('d M Y H:i') }}</td>
                    <td data-label="{{ __('public.developer_portal.col_requests', [], $l) ?: 'Requests' }}">{{ number_format($m->request_count) }}</td>
                    <td data-label="{{ __('public.developer_portal.col_errors', [], $l) ?: 'Errors' }}">
                        @if($m->error_count > 0)<span class="badge badge-danger">{{ $m->error_count }}</span>
                        @else<span class="td-muted">0</span>@endif
                    </td>
                    <td data-label="{{ __('public.developer_portal.col_avg_ms', [], $l) ?: 'Avg ms' }}" class="td-muted">{{ round($m->avg_response_ms) }}ms</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection

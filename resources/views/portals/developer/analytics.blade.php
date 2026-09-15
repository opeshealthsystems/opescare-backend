@extends('layouts.portal')
@section('title', __('developer_ext.an_page_title'))
@php $l = app()->getLocale(); @endphp

@section('content')

    <style>
    .an-chart{display:flex;align-items:flex-end;gap:3px;height:160px;padding:8px 0 4px;border-bottom:1px solid #e5e7eb}
    .an-chart__col{flex:1 1 0;display:flex;flex-direction:column;justify-content:flex-end;height:100%;min-width:0}
    .an-chart__bar{position:relative;width:100%;background:#0F4C81;border-radius:2px 2px 0 0;min-height:0;overflow:hidden}
    .an-chart__err{position:absolute;left:0;right:0;bottom:0;background:#b91c1c}
    .an-chart__lbl{font-size:10px;text-align:center;color:#6b7280;margin-top:4px;overflow:hidden}
    @media (max-width:640px){.an-chart__lbl{display:none}}
    </style>

    <div class="breadcrumb">
        <a href="{{ route('portals.developer.dashboard') }}">{{ __('public.developer_portal.page_heading', [], $l) }}</a>
        <i data-lucide="chevron-right"></i>
        <span>{{ __('developer_ext.an_page_title') }}</span>
    </div>

    <div class="page-head">
        <h2>{{ __('developer_ext.an_page_title') }}</h2>
        <p class="portal-page-subtitle">{{ __('developer_ext.an_subtitle') }}</p>
        <div class="page-head__spacer"></div>
        @if($clients->count() > 1)
        <form method="GET" class="filter-bar filter-bar--flush">
            <select name="app" class="filter-select" aria-label="{{ __('developer_ext.act_filter_aria') }}" onchange="this.form.submit()">
                <option value="">{{ __('developer_ext.act_filter_all') }}</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" {{ $selected && $selected->id === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </form>
        @endif
    </div>

    {{-- Every figure below is a count over api_usage_logs for this developer's own
         client_ids, attributed from the bearer token. Nothing is estimated. --}}
    <div class="stat-grid mb-6">
        <div class="stat-card stat-card--primary">
            <div class="stat-card__label">{{ __('developer_ext.an_stat_requests') }}</div>
            <div class="stat-card__value">{{ number_format($totals['requests']) }}</div>
            <div class="stat-card__sub">{{ __('developer_ext.act_since_30d') }}</div>
        </div>
        <div class="stat-card stat-card--danger">
            <div class="stat-card__label">{{ __('developer_ext.an_stat_error_rate') }}</div>
            <div class="stat-card__value">{{ $totals['error_rate'] === null ? '—' : $totals['error_rate'] . '%' }}</div>
            <div class="stat-card__sub">{{ __('developer_ext.an_stat_errors_sub', ['errors' => number_format($totals['errors']), 'server' => number_format($totals['server_errors'])]) }}</div>
        </div>
        <div class="stat-card stat-card--success">
            <div class="stat-card__label">{{ __('developer_ext.an_stat_p50') }}</div>
            <div class="stat-card__value">{{ $totals['p50_ms'] === null ? '—' : $totals['p50_ms'] . ' ms' }}</div>
            <div class="stat-card__sub">{{ __('developer_ext.an_stat_latency_sub') }}</div>
        </div>
        <div class="stat-card stat-card--warning">
            <div class="stat-card__label">{{ __('developer_ext.an_stat_p95') }}</div>
            <div class="stat-card__value">{{ $totals['p95_ms'] === null ? '—' : $totals['p95_ms'] . ' ms' }}</div>
            <div class="stat-card__sub">{{ __('developer_ext.an_stat_latency_sub') }}</div>
        </div>
    </div>

    {{-- Requests per day --}}
    <div class="panel mb-6">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="bar-chart-3"></i> {{ __('developer_ext.an_chart_title') }}</h3>
            <span class="td-muted">{{ __('developer_ext.an_chart_range', ['from' => $since->format('d M'), 'to' => now()->format('d M Y')]) }}</span>
        </div>
        @if($totals['requests'] === 0)
            <div class="panel-body empty-state">
                <div class="empty-state-icon"><i data-lucide="bar-chart-3"></i></div>
                <p>{{ __('developer_ext.an_empty') }}</p>
                <p class="td-muted">{{ __('developer_ext.an_empty_hint') }} <a href="{{ route('portals.developer.quickstart') }}">{{ __('developer_ext.nav_quickstart') }}</a></p>
            </div>
        @else
            <div class="panel-body">
                <div class="an-chart" role="img" aria-label="{{ __('developer_ext.an_chart_title') }}">
                    @foreach($perDay as $d)
                        @php $h = $d['requests'] > 0 ? max(2, (int) round($d['requests'] / $maxPerDay * 100)) : 0; @endphp
                        <div class="an-chart__col" title="{{ $d['day'] }}: {{ number_format($d['requests']) }} / {{ number_format($d['errors']) }}">
                            <div class="an-chart__bar" style="height: {{ $h }}%">
                                @if($d['requests'] > 0 && $d['errors'] > 0)
                                    <div class="an-chart__err" style="height: {{ (int) round($d['errors'] / $d['requests'] * 100) }}%"></div>
                                @endif
                            </div>
                            <div class="an-chart__lbl">{{ \Carbon\Carbon::parse($d['day'])->format('d') }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="td-muted">{{ __('developer_ext.an_chart_legend') }}</div>
            </div>
        @endif
    </div>

    <div class="field-grid mb-6">

        {{-- Top endpoints --}}
        <div class="panel">
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="list"></i> {{ __('developer_ext.an_top_endpoints') }}</h3></div>
            @if($byEndpoint->isEmpty())
                <div class="panel-body empty-state"><p>{{ __('developer_ext.an_empty') }}</p></div>
            @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th>{{ __('developer_ext.act_col_method') }}</th>
                        <th>{{ __('developer_ext.act_col_endpoint') }}</th>
                        <th>{{ __('developer_ext.an_col_requests') }}</th>
                        <th>{{ __('developer_ext.an_col_errors') }}</th>
                        <th>{{ __('developer_ext.an_col_avg_ms') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($byEndpoint as $row)
                        <tr>
                            <td data-label="{{ __('developer_ext.act_col_method') }}"><span class="badge badge-neutral">{{ $row['method'] }}</span></td>
                            <td data-label="{{ __('developer_ext.act_col_endpoint') }}" class="mono">/{{ ltrim($row['endpoint'], '/') }}</td>
                            <td data-label="{{ __('developer_ext.an_col_requests') }}" class="td-strong">{{ number_format($row['requests']) }}</td>
                            <td data-label="{{ __('developer_ext.an_col_errors') }}">
                                @if($row['errors'] > 0)<span class="badge badge-danger">{{ number_format($row['errors']) }}</span>@else<span class="td-muted">0</span>@endif
                            </td>
                            <td data-label="{{ __('developer_ext.an_col_avg_ms') }}" class="td-muted">{{ $row['avg_ms'] === null ? '—' : $row['avg_ms'] . ' ms' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- Per app --}}
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title"><i data-lucide="cpu"></i> {{ __('developer_ext.an_by_app') }}</h3>
                <a href="{{ route('portals.developer.apps') }}" class="btn btn-secondary btn-sm">{{ __('public.developer_portal.btn_manage', [], $l) }}</a>
            </div>
            @if($clients->isEmpty())
                <div class="panel-body empty-state">
                    <p>{{ __('developer_ext.qs_no_apps') }} <a href="{{ route('portals.developer.apps.create') }}">{{ __('developer_ext.qs_btn_create_app') }}</a></p>
                </div>
            @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th>{{ __('developer_ext.wh_col_app') }}</th>
                        <th>{{ __('developer_ext.wh_col_status') }}</th>
                        <th>{{ __('developer_ext.an_col_requests') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($clients as $client)
                        <tr>
                            <td data-label="{{ __('developer_ext.wh_col_app') }}">
                                <span class="td-strong">{{ $client->name }}</span>
                                <div class="mono">{{ $client->client_id }}</div>
                            </td>
                            <td data-label="{{ __('developer_ext.wh_col_status') }}">
                                <span class="badge badge-{{ $client->status === 'active' ? 'success' : ($client->status === 'pending' ? 'warning' : 'neutral') }}">@enum($client->status)</span>
                            </td>
                            <td data-label="{{ __('developer_ext.an_col_requests') }}">
                                @if(($byApp[$client->client_id] ?? 0) > 0)
                                    <a href="{{ route('portals.developer.analytics', ['app' => $client->id]) }}">{{ number_format($byApp[$client->client_id]) }}</a>
                                @else
                                    <span class="td-muted">{{ __('developer_ext.an_no_calls_app') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

    </div>

    <p class="td-muted"><i data-lucide="info"></i> {{ __('developer_ext.act_note_attribution') }}</p>

@endsection

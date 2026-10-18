@extends('layouts.portal')
@section('title', __('developer_ext.act_page_title'))
@php $l = app()->getLocale(); @endphp

@section('head')
    <link rel="stylesheet" href="{{ asset('css/od-pro.css') }}?v={{ @filemtime(public_path('css/od-pro.css')) ?: '1' }}">
@endsection

@section('content')

    <div class="breadcrumb">
        <a href="{{ route('portals.developer.dashboard') }}">{{ __('public.developer_portal.page_heading', [], $l) }}</a>
        <i data-lucide="chevron-right"></i>
        <span>{{ __('developer_ext.act_page_title') }}</span>
    </div>

    <div class="page-head">
        <h2>{{ __('developer_ext.act_page_title') }}</h2>
        <p class="portal-page-subtitle">{{ __('developer_ext.act_subtitle') }}</p>
        <div class="page-head__spacer"></div>
        @if($clients->count() > 1)
        <form method="GET" class="filter-bar filter-bar--flush">
            <select name="app" class="filter-select" aria-label="{{ __('developer_ext.act_filter_aria') }}" data-autosubmit>
                <option value="">{{ __('developer_ext.act_filter_all') }}</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" {{ $selected && $selected->id === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </form>
        @endif
    </div>

    {{-- 30-day counts --}}
    <div class="stat-grid mb-6">
        <div class="stat-card stat-card--success">
            <div class="stat-card__label">{{ __('developer_ext.act_stat_accepted') }}</div>
            <div class="stat-card__value">{{ number_format($counts['accepted']) }}</div>
            <div class="stat-card__sub">{{ __('developer_ext.act_since_30d') }}</div>
        </div>
        <div class="stat-card stat-card--danger">
            <div class="stat-card__label">{{ __('developer_ext.act_stat_refused') }}</div>
            <div class="stat-card__value">{{ number_format($counts['refused']) }}</div>
            <div class="stat-card__sub">{{ __('developer_ext.act_since_30d') }}</div>
        </div>
        <div class="stat-card stat-card--primary">
            <div class="stat-card__label">{{ __('developer_ext.act_stat_requests') }}</div>
            <div class="stat-card__value">{{ number_format($counts['requests']) }}</div>
            <div class="stat-card__sub">{{ __('developer_ext.act_since_30d') }}</div>
        </div>
        <div class="stat-card stat-card--warning">
            <div class="stat-card__label">{{ __('developer_ext.act_stat_errors') }}</div>
            <div class="stat-card__value">{{ number_format($counts['errors']) }}</div>
            <div class="stat-card__sub">{{ __('developer_ext.act_since_30d') }}</div>
        </div>
    </div>

    {{-- FHIR ingest events --}}
    <div class="panel mb-6">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="activity"></i> {{ __('developer_ext.act_ingest_title') }}</h3></div>
        @if($ingest->isEmpty())
            <div class="panel-body empty-state"><p>{{ __('developer_ext.act_no_ingest') }}</p></div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th>{{ __('developer_ext.act_col_when') }}</th>
                        <th>{{ __('developer_ext.act_col_app') }}</th>
                        <th>{{ __('developer_ext.act_col_resource') }}</th>
                        <th>{{ __('developer_ext.act_col_outcome') }}</th>
                        <th>{{ __('developer_ext.act_col_http') }}</th>
                        <th>{{ __('developer_ext.act_col_reason') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($ingest as $e)
                        @php $outcome = $outcomeFor($e); @endphp
                        <tr>
                            <td data-label="{{ __('developer_ext.act_col_when') }}" class="td-muted">{{ $e->created_at?->translatedFormat('d M Y H:i:s') }}</td>
                            <td data-label="{{ __('developer_ext.act_col_app') }}">{{ $clientNames[$e->integration_client_id] ?? $e->integration_client_id }}</td>
                            <td data-label="{{ __('developer_ext.act_col_resource') }}" class="mono">{{ $e->resource_type }}</td>
                            <td data-label="{{ __('developer_ext.act_col_outcome') }}">
                                @if($e->outcome === \App\Models\FhirIngestEvent::OUTCOME_ACCEPTED)
                                    <span class="badge badge-success">{{ __('developer_ext.act_outcome_accepted') }}</span>
                                @else
                                    <span class="badge badge-danger">{{ __('developer_ext.act_outcome_refused') }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('developer_ext.act_col_http') }}" class="mono">{{ $e->http_status }}</td>
                            <td data-label="{{ __('developer_ext.act_col_reason') }}">
                                @if($e->refusal_code)
                                    <span class="code-token">{{ $e->refusal_code }}</span>
                                    <div class="td-muted">{{ $e->refusal_detail }}</div>
                                @endif
                                @if($outcome)
                                    <details class="mt-2">
                                        <summary class="btn btn-ghost btn-sm"><i data-lucide="file-json"></i> {{ __('developer_ext.act_btn_outcome') }}</summary>
                                        <pre class="code-block mono ods-d07753c">{{ $outcome }}</pre>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="panel-body">{{ $ingest->links() }}</div>
        @endif
        <div class="panel-body td-muted"><i data-lucide="shield"></i> {{ __('developer_ext.act_identifier_note') }}</div>
    </div>

    {{-- API requests --}}
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="arrow-left-right"></i> {{ __('developer_ext.act_requests_title') }}</h3></div>
        @if($requests->isEmpty())
            <div class="panel-body empty-state"><p>{{ __('developer_ext.act_no_requests') }}</p></div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th>{{ __('developer_ext.act_col_when') }}</th>
                        <th>{{ __('developer_ext.act_col_app') }}</th>
                        <th>{{ __('developer_ext.act_col_method') }}</th>
                        <th>{{ __('developer_ext.act_col_endpoint') }}</th>
                        <th>{{ __('developer_ext.act_col_status_code') }}</th>
                        <th>{{ __('developer_ext.act_col_latency') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($requests as $r)
                        <tr>
                            <td data-label="{{ __('developer_ext.act_col_when') }}" class="td-muted">{{ $r->logged_at?->translatedFormat('d M Y H:i:s') }}</td>
                            <td data-label="{{ __('developer_ext.act_col_app') }}">{{ $clientNames[$r->integration_client_id] ?? $r->integration_client_id }}</td>
                            <td data-label="{{ __('developer_ext.act_col_method') }}" class="mono">{{ $r->method }}</td>
                            <td data-label="{{ __('developer_ext.act_col_endpoint') }}" class="mono">/{{ ltrim($r->endpoint, '/') }}</td>
                            <td data-label="{{ __('developer_ext.act_col_status_code') }}">
                                <span class="badge {{ $r->response_status >= 500 ? 'badge-danger' : ($r->response_status >= 400 ? 'badge-warning' : 'badge-success') }}">{{ $r->response_status }}</span>
                            </td>
                            <td data-label="{{ __('developer_ext.act_col_latency') }}" class="td-muted">{{ $r->response_time_ms !== null ? $r->response_time_ms . ' ms' : '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="panel-body td-muted">{{ __('developer_ext.act_note_attribution') }}</div>
    </div>

@endsection

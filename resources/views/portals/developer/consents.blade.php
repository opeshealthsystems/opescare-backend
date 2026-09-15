@extends('layouts.portal')
@section('title', __('developer_ext.cs_page_title'))
@php $l = app()->getLocale(); @endphp

@section('content')

    <div class="breadcrumb">
        <a href="{{ route('portals.developer.dashboard') }}">{{ __('public.developer_portal.page_heading', [], $l) }}</a>
        <i data-lucide="chevron-right"></i>
        <span>{{ __('developer_ext.cs_page_title') }}</span>
    </div>

    <div class="page-head">
        <h2>{{ __('developer_ext.cs_page_title') }}</h2>
        <p class="portal-page-subtitle">{{ __('developer_ext.cs_subtitle') }}</p>
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

    {{-- How consent works for a partner app. The request flow itself is API-side. --}}
    <div class="panel mb-6">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="shield-check"></i> {{ __('developer_ext.cs_explainer_title') }}</h3></div>
        <div class="panel-body">
            <p>{{ __('developer_ext.cs_explainer_p1') }}</p>
            <p>{{ __('developer_ext.cs_explainer_p2') }}</p>
            <p>{{ __('developer_ext.cs_explainer_p3') }}</p>
            <p>
                <a href="{{ route('docs.api') }}#consent" class="btn btn-secondary btn-sm" target="_blank" rel="noopener"><i data-lucide="book-open"></i> {{ __('developer_ext.cs_lnk_docs_consent') }}</a>
                <a href="{{ route('docs.webhooks') }}" class="btn btn-ghost btn-sm" target="_blank" rel="noopener"><i data-lucide="webhook"></i> {{ __('developer_ext.cs_lnk_docs_webhooks') }}</a>
            </p>
        </div>
    </div>

    @if($clients->isEmpty())
        <div class="panel"><div class="panel-body empty-state">
            <p>{{ __('developer_ext.qs_no_apps') }} <a href="{{ route('portals.developer.apps.create') }}">{{ __('developer_ext.qs_btn_create_app') }}</a></p>
        </div></div>
    @endif

    @foreach($shown as $client)
        <div class="panel mb-6">
            <div class="panel-header">
                <h3 class="panel-title"><i data-lucide="app-window"></i> {{ $client->name }} <span class="mono td-muted">{{ $client->client_id }}</span></h3>
                <span class="badge badge-{{ $client->environment === 'production' ? 'success' : 'neutral' }}">@enum($client->environment)</span>
            </div>
            @if(! $client->facility_id)
                <div class="panel-body">
                    <div class="alert alert-info">
                        <i data-lucide="info"></i>
                        <div>
                            <strong>{{ __('developer_ext.cs_facilityless_title') }}</strong>
                            <div>{{ __('developer_ext.cs_facilityless_body') }}</div>
                        </div>
                    </div>
                </div>
            @else
                @php $rows = $grants->get($client->facility_id, collect()); @endphp
                <div class="panel-body td-muted">
                    {{ __('developer_ext.cs_acts_for', ['facility' => $facilityNames[$client->facility_id] ?? $client->facility_id]) }}
                </div>
                @if($rows->isEmpty())
                    <div class="panel-body empty-state"><p>{{ __('developer_ext.cs_no_grants') }}</p></div>
                @else
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead><tr>
                                <th>{{ __('developer_ext.cs_col_health_id') }}</th>
                                <th>{{ __('developer_ext.cs_col_scope') }}</th>
                                <th>{{ __('developer_ext.cs_col_granted') }}</th>
                                <th>{{ __('developer_ext.cs_col_expires') }}</th>
                                <th>{{ __('developer_ext.cs_col_status') }}</th>
                            </tr></thead>
                            <tbody>
                            @foreach($rows as $g)
                                <tr>
                                    <td data-label="{{ __('developer_ext.cs_col_health_id') }}" class="mono">{{ $healthIds[$g->patient_id] ?? '—' }}</td>
                                    <td data-label="{{ __('developer_ext.cs_col_scope') }}">
                                        @forelse((array) $g->scope as $s)<span class="code-token">{{ $s }}</span> @empty<span class="td-muted">—</span>@endforelse
                                    </td>
                                    <td data-label="{{ __('developer_ext.cs_col_granted') }}" class="td-muted">{{ $g->created_at?->format('d M Y H:i') ?? '—' }}</td>
                                    <td data-label="{{ __('developer_ext.cs_col_expires') }}" class="td-muted">{{ $g->expires_at?->format('d M Y H:i') ?? __('developer_ext.cs_no_expiry') }}</td>
                                    <td data-label="{{ __('developer_ext.cs_col_status') }}"><span class="badge badge-success">@enum($g->status)</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </div>
    @endforeach

    <p class="td-muted"><i data-lucide="lock"></i> {{ __('developer_ext.cs_privacy_note') }}</p>

@endsection

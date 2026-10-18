@extends('layouts.portal')
@section('title', __('public.developer_portal.page_title', [], app()->getLocale()))
@section('head')
    <link rel="stylesheet" href="{{ asset('css/od-pro.css') }}?v={{ @filemtime(public_path('css/od-pro.css')) ?: '1' }}">
@endsection

@section('content')

    <div class="page-head">
        <h2>{{ __('public.developer_portal.page_heading', [], app()->getLocale()) }}</h2>
        <p class="portal-page-subtitle">{{ __('public.developer_portal.welcome_back', [], app()->getLocale()) }} {{ $developer->display_name }}</p>
        <div class="page-head__spacer"></div>
        @if($developer->isSandboxOnly())
            <span class="badge badge-warning">{{ __('public.developer_portal.badge_sandbox', [], app()->getLocale()) }}</span>
            <a href="{{ route('portals.developer.production_requests.create') }}" class="btn btn-primary btn-sm">
                <i data-lucide="rocket"></i> {{ __('public.developer_portal.btn_request_prod', [], app()->getLocale()) }}
            </a>
        @else
            <span class="badge badge-success">{{ __('public.developer_portal.badge_production', [], app()->getLocale()) }}</span>
        @endif
    </div>

    @if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

    @if(!$developer->isEmailVerified())
    <div class="alert alert-warning mb-6">
        <i data-lucide="alert-triangle"></i>
        <div>{{ __('public.developer_portal.email_unverified', [], app()->getLocale()) }}</div>
    </div>
    @endif

    {{-- Stats strip --}}
    <div class="stat-grid mb-6">
        <div class="stat-card stat-card--primary">
            <div class="stat-card__label">{{ __('public.developer_portal.stat_apps', [], app()->getLocale()) }}</div>
            <div class="stat-card__value">{{ $clients->count() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__label">{{ __('public.developer_portal.stat_api_requests', [], app()->getLocale()) }}</div>
            <div class="stat-card__value">{{ number_format($totalRequests) }}</div>
        </div>
        <div class="stat-card {{ $totalErrors > 0 ? 'stat-card--danger' : 'stat-card--success' }}">
            <div class="stat-card__label">{{ __('public.developer_portal.stat_errors', [], app()->getLocale()) }}</div>
            <div class="stat-card__value">{{ number_format($totalErrors) }}</div>
        </div>
        <div class="stat-card stat-card--warning">
            <div class="stat-card__label">{{ __('public.developer_portal.stat_pending_requests', [], app()->getLocale()) }}</div>
            <div class="stat-card__value">{{ $productionRequests->where('status','pending')->count() }}</div>
        </div>
    </div>

    <div class="field-grid mb-6">

        {{-- Apps --}}
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title"><i data-lucide="plug"></i> {{ __('public.developer_portal.panel_apps', [], app()->getLocale()) }}</h3>
                <a href="{{ route('portals.developer.apps.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('public.developer_portal.btn_new_app', [], app()->getLocale()) }}</a>
            </div>
            @if($clients->isEmpty())
            <div class="panel-body empty-state">
                <p>{{ __('public.developer_portal.no_apps', [], app()->getLocale()) }}
                   <a href="{{ route('portals.developer.apps.create') }}">{{ __('public.developer_portal.lnk_create_first_app', [], app()->getLocale()) }}</a>
                   {{ __('public.developer_portal.lnk_create_first_app_suffix', [], app()->getLocale()) }}</p>
            </div>
            @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th>{{ __('public.developer_portal.col_app', [], app()->getLocale()) }}</th>
                        <th>{{ __('public.developer_portal.col_environment', [], app()->getLocale()) }}</th>
                        <th>{{ __('public.portal.col_status', [], app()->getLocale()) }}</th>
                        <th class="row-actions"></th>
                    </tr></thead>
                    <tbody>
                    @foreach($clients as $client)
                    <tr>
                        <td data-label="{{ __('public.developer_portal.col_app', [], app()->getLocale()) }}">
                            <span class="td-strong">{{ $client->name ?? (__('public.developer_portal.lbl_unnamed_app', [], app()->getLocale())) }}</span>
                            <div class="mono">{{ Str::limit($client->client_id, 20) }}</div>
                        </td>
                        <td data-label="{{ __('public.developer_portal.col_environment', [], app()->getLocale()) }}">
                            <span class="badge {{ $client->environment === 'production' ? 'badge-success' : 'badge-info' }}">@enum($client->environment ?? 'sandbox', 'environment')</span>
                        </td>
                        <td data-label="{{ __('public.portal.col_status', [], app()->getLocale()) }}">
                            <span class="badge {{ ($client->status ?? 'active') === 'active' ? 'badge-success' : 'badge-neutral' }}">@enum($client->status ?? 'active')</span>
                        </td>
                        <td class="row-actions" data-label="">
                            <a href="{{ route('portals.developer.apps.show', $client->id) }}" class="icon-btn" aria-label="{{ __('public.developer_portal.lbl_view_app', [], app()->getLocale()) }}" title="{{ __('public.developer_portal.lbl_view', [], app()->getLocale()) }}"><i data-lucide="eye"></i></a>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- Production Requests --}}
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title"><i data-lucide="rocket"></i> {{ __('public.developer_portal.panel_prod_requests', [], app()->getLocale()) }}</h3>
                <a href="{{ route('portals.developer.production_requests.create') }}" class="btn btn-secondary btn-sm">{{ __('public.developer_portal.btn_request_access', [], app()->getLocale()) }}</a>
            </div>
            @if($productionRequests->isEmpty())
            <div class="panel-body empty-state">
                <p>{{ __('public.developer_portal.no_prod_requests', [], app()->getLocale()) }}</p>
            </div>
            @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th>{{ __('public.developer_portal.col_use_case', [], app()->getLocale()) }}</th>
                        <th>{{ __('public.portal.col_status', [], app()->getLocale()) }}</th>
                        <th>{{ __('public.portal.col_date', [], app()->getLocale()) }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($productionRequests as $req)
                    <tr>
                        <td data-label="{{ __('public.developer_portal.col_use_case', [], app()->getLocale()) }}">{{ Str::limit($req->use_case, 40) }}</td>
                        <td data-label="{{ __('public.portal.col_status', [], app()->getLocale()) }}"><span class="{{ $req->statusBadgeClass() }}">@enum($req->status)</span></td>
                        <td data-label="{{ __('public.portal.col_date', [], app()->getLocale()) }}" class="td-muted">{{ $req->created_at->translatedFormat('d M Y') }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

    </div>

    {{-- Quick Links --}}
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="link"></i> {{ __('public.developer_portal.panel_quick_links', [], app()->getLocale()) }}</h3></div>
        <div class="panel-body stat-grid">
            <a href="{{ route('portals.developer.apps') }}" class="stat-card">
                <i data-lucide="key"></i>
                <div class="stat-card__label">{{ __('public.developer_portal.lnk_api_keys', [], app()->getLocale()) }}</div>
            </a>
            <a href="{{ route('portals.developer.production_requests') }}" class="stat-card">
                <i data-lucide="rocket"></i>
                <div class="stat-card__label">{{ __('public.developer_portal.lnk_prod_access', [], app()->getLocale()) }}</div>
            </a>
            <a href="{{ route('portals.developer.analytics') }}" class="stat-card">
                <i data-lucide="bar-chart-3"></i>
                <div class="stat-card__label">{{ __('public.developer_portal.lnk_analytics', [], app()->getLocale()) }}</div>
            </a>
            <a href="{{ route('portals.developer.apps') }}" class="stat-card">
                <i data-lucide="webhook"></i>
                <div class="stat-card__label">{{ __('public.developer_portal.lnk_webhooks', [], app()->getLocale()) }}</div>
            </a>
        </div>
    </div>

@endsection

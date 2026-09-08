@extends('layouts.portal')
@section('title', __('partners.title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('partners.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('partners.title'))

@section('content')
<div class="page-head">
    <h2>{{ __('partners.title') }}</h2>
    <a href="{{ route('portals.admin.partners.create') }}" class="btn btn-primary btn-sm">
        <i data-lucide="key-round"></i> {{ __('partners.create_nav') }}
    </a>
</div>
<p class="page-subtitle mb-6">{{ __('partners.subtitle') }}</p>

@if(session('success'))
<div class="alert alert-success mb-6"><i data-lucide="check"></i><div>{{ session('success') }}</div></div>
@endif

@if($total === 0)
    {{-- Zero clients is the honest current state of this platform, not a
         failed query and not a filter that matched nothing. It gets words,
         not an empty grid a reader would mistake for a bug. --}}
    <div class="alert alert-info">
        <i data-lucide="info"></i>
        <div>
            <strong>{{ __('partners.none_title') }}</strong>
            <p class="mb-0">{{ __('partners.none_body') }}</p>
        </div>
    </div>
@else
<div class="stat-grid mb-6">
    <div class="stat-card">
        <div class="stat-card__head"><i data-lucide="plug"></i><span class="stat-card__label">{{ __('partners.status_active') }}</span></div>
        <div class="stat-card__value">{{ number_format($counts['active'] ?? 0) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__head"><i data-lucide="pause"></i><span class="stat-card__label">{{ __('partners.status_suspended') }}</span></div>
        <div class="stat-card__value">{{ number_format($counts['suspended'] ?? 0) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__head"><i data-lucide="ban"></i><span class="stat-card__label">{{ __('partners.status_revoked') }}</span></div>
        <div class="stat-card__value">{{ number_format($counts['revoked'] ?? 0) }}</div>
    </div>
</div>

<form method="GET" action="{{ route('portals.admin.partners.index') }}" class="filter-bar mb-6">
    <select name="status" class="form-control">
        <option value="">{{ __('partners.filter_all') }}</option>
        @foreach($statuses as $s)
        <option value="{{ $s }}" @selected($status === $s)>{{ __('partners.status_' . $s) }}</option>
        @endforeach
    </select>
    <select name="environment" class="form-control">
        <option value="">{{ __('partners.filter_all_envs') }}</option>
        <option value="sandbox" @selected($environment === 'sandbox')>{{ __('partners.env_sandbox') }}</option>
        <option value="production" @selected($environment === 'production')>{{ __('partners.env_production') }}</option>
    </select>
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('partners.filter_apply') }}</button>
</form>

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('partners.col_name') }}</th>
                <th>{{ __('partners.col_client_id') }}</th>
                <th>{{ __('partners.col_organisation') }}</th>
                <th>{{ __('partners.col_environment') }}</th>
                <th>{{ __('partners.col_scopes') }}</th>
                <th>{{ __('partners.col_status') }}</th>
                <th>{{ __('partners.col_last_used') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($clients as $client)
            <tr>
                <td><a href="{{ route('portals.admin.partners.show', $client->id) }}">{{ $client->name ?: $client->client_id }}</a></td>
                <td><code>{{ $client->client_id }}</code></td>
                <td>{{ $client->facility_id ? ($facilities[$client->facility_id] ?? $client->facility_id) : __('partners.no_organisation') }}</td>
                <td>{{ __('partners.env_' . ($client->environment === 'production' ? 'production' : 'sandbox')) }}</td>
                <td>{{ count($client->scopes ?? []) }}</td>
                <td>
                    <span class="pill {{ $client->status === 'active' ? 'pill--success' : 'pill--muted' }}">
                        {{ __('partners.status_' . $client->status) }}
                    </span>
                </td>
                {{-- Never used is a fact worth stating; a blank cell reads as
                     missing data rather than as "nothing has ever called". --}}
                <td>{{ $client->last_used_at ? $client->last_used_at->diffForHumans() : __('partners.never_used') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<p class="text-muted text-sm mt-4">{{ __('partners.total_clients', ['count' => number_format($total)]) }}</p>

{{ $clients->links() }}
@endif
@endsection

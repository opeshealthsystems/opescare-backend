@extends('layouts.portal')
@section('title', __('partners.title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection
@php
    $gcTones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gcTone = fn ($s) => $gcTones[crc32((string) $s) % count($gcTones)];
    $gcInit = fn ($n) => mb_strtoupper(collect(preg_split('/\s+/', trim((string) $n)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: '?';
@endphp

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('partners.title') }}</h1>
        <p class="od-page__sub">{{ __('partners.subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.partners.create') }}" class="od-btn od-btn--primary">
            <i data-lucide="key-round"></i> {{ __('partners.create_nav') }}
        </a>
    </div>
</div>

@if(session('success'))
<div class="oda-gc-flash oda-gc-flash--ok"><i data-lucide="check"></i><div>{{ session('success') }}</div></div>
@endif

@if($total === 0)
    {{-- Zero clients is the honest current state of this platform, not a
         failed query and not a filter that matched nothing. It gets words,
         not an empty grid a reader would mistake for a bug. --}}
    <section class="od-card">
        <div class="od-empty">
            <span class="od-empty__icon"><i data-lucide="info"></i></span>
            <p class="od-empty__title">{{ __('partners.none_title') }}</p>
            <p class="od-empty__text">{{ __('partners.none_body') }}</p>
        </div>
    </section>
@else
<div class="od-kpis oda-gc-kpis--3">
    <div class="od-kpi">
        <span class="od-kpi__icon od-tint--green"><i data-lucide="plug"></i></span>
        <div class="od-kpi__body"><span class="od-kpi__label">{{ __('partners.status_active') }}</span><span class="od-kpi__value">{{ number_format($counts['active'] ?? 0) }}</span></div>
    </div>
    <div class="od-kpi">
        <span class="od-kpi__icon od-tint--amber"><i data-lucide="pause"></i></span>
        <div class="od-kpi__body"><span class="od-kpi__label">{{ __('partners.status_suspended') }}</span><span class="od-kpi__value">{{ number_format($counts['suspended'] ?? 0) }}</span></div>
    </div>
    <div class="od-kpi">
        <span class="od-kpi__icon od-tint--rose"><i data-lucide="ban"></i></span>
        <div class="od-kpi__body"><span class="od-kpi__label">{{ __('partners.status_revoked') }}</span><span class="od-kpi__value">{{ number_format($counts['revoked'] ?? 0) }}</span></div>
    </div>
</div>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('partners.title') }} <span class="od-card__count">({{ number_format($total) }})</span></h2>
    </div>

    <form method="GET" action="{{ route('portals.admin.partners.index') }}" class="oda-toolbar">
        <select name="status" class="od-select" aria-label="{{ __('partners.col_status') }}">
            <option value="">{{ __('partners.filter_all') }}</option>
            @foreach($statuses as $s)
            <option value="{{ $s }}" @selected($status === $s)>{{ __('partners.status_' . $s) }}</option>
            @endforeach
        </select>
        <select name="environment" class="od-select" aria-label="{{ __('partners.col_environment') }}">
            <option value="">{{ __('partners.filter_all_envs') }}</option>
            <option value="sandbox" @selected($environment === 'sandbox')>{{ __('partners.env_sandbox') }}</option>
            <option value="production" @selected($environment === 'production')>{{ __('partners.env_production') }}</option>
        </select>
        <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="filter"></i> {{ __('partners.filter_apply') }}</button>
    </form>

    <div class="od-table-wrap">
        <table class="od-table">
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
                    <td>
                        <div class="od-table__person">
                            <span class="od-avatar od-tint--{{ $gcTone($client->client_id) }}" aria-hidden="true">{{ $gcInit($client->name ?: $client->client_id) }}</span>
                            <a href="{{ route('portals.admin.partners.show', $client->id) }}" class="od-table__name">{{ $client->name ?: $client->client_id }}</a>
                        </div>
                    </td>
                    <td><code class="oda-mono">{{ $client->client_id }}</code></td>
                    <td>{{ $client->facility_id ? ($facilities[$client->facility_id] ?? $client->facility_id) : __('partners.no_organisation') }}</td>
                    <td><span class="od-pill {{ $client->environment === 'production' ? 'od-pill--normal' : 'od-pill--neutral' }}">{{ __('partners.env_' . ($client->environment === 'production' ? 'production' : 'sandbox')) }}</span></td>
                    <td>{{ count($client->scopes ?? []) }}</td>
                    <td>
                        <span class="od-pill od-pill--dot {{ $client->status === 'active' ? 'od-pill--ready' : 'od-pill--neutral' }}">
                            {{ __('partners.status_' . $client->status) }}
                        </span>
                    </td>
                    {{-- Never used is a fact worth stating; a blank cell reads as
                         missing data rather than as "nothing has ever called". --}}
                    <td class="oda-gc-muted">{{ $client->last_used_at ? $client->last_used_at->diffForHumans() : __('partners.never_used') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="od-card__foot">
        <p class="oda-help">{{ __('partners.total_clients', ['count' => number_format($total)]) }}</p>
        {{ $clients->links() }}
    </div>
</section>
@endif

</div>
@endsection

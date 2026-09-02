@extends('layouts.portal')

@section('title', __('public.adm_clin_rx_title'))

@include('portals.admin.clinical._sidebar')

@section('breadcrumb_home', __('public.adm_clin_rx_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_clin_rx_breadcrumb_section'))

@section('content')

<div class="page-head">
    <h2>{{ __('public.adm_clin_rx_heading') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.pharmacy.prescriptions') }}" class="btn btn-secondary btn-sm">
        <i data-lucide="pill"></i> {{ __('public.adm_clin_rx_btn_queue') }}
    </a>
</div>
<p class="td-muted mb-6">{{ __('public.adm_clin_rx_desc') }}</p>

{{-- Summary chips --}}
<div class="stat-grid mb-6">
    <div class="stat-card stat-card--warning">
        <div class="stat-card__head"><i data-lucide="clock"></i></div>
        <div class="stat-card__value">{{ $summary['active'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_clin_rx_stat_active') }}</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="loader"></i></div>
        <div class="stat-card__value">{{ $summary['partially_dispensed'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_clin_rx_stat_partial') }}</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__head"><i data-lucide="check-circle-2"></i></div>
        <div class="stat-card__value">{{ $summary['dispensed_today'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_clin_rx_stat_dispensed_today') }}</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__head"><i data-lucide="alert-triangle"></i></div>
        <div class="stat-card__value">{{ $summary['expired'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_clin_rx_stat_expired') }}</div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="filter-bar">
    <select name="status" class="filter-select" aria-label="{{ __('public.aria_status') }}" onchange="this.form.submit()">
        <option value="">{{ __('public.adm_clin_rx_filter_all_statuses') }}</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('public.adm_clin_rx_filter_active') }}</option>
        <option value="partially_dispensed" {{ request('status') === 'partially_dispensed' ? 'selected' : '' }}>{{ __('public.adm_clin_rx_filter_partially') }}</option>
        <option value="dispensed" {{ request('status') === 'dispensed' ? 'selected' : '' }}>{{ __('public.adm_clin_rx_filter_dispensed') }}</option>
        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>{{ __('public.adm_clin_rx_filter_expired') }}</option>
        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('public.adm_clin_rx_filter_cancelled') }}</option>
    </select>
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="search" placeholder="{{ __('public.adm_clin_rx_ph_search') }}" value="{{ request('search') }}" aria-label="{{ __('public.aria_patient') }}">
    </label>
    <label class="filter-search">
        <i data-lucide="calendar"></i>
        <input type="date" name="from" value="{{ request('from') }}" aria-label="{{ __('public.aria_from_date') }}">
    </label>
    <label class="filter-search">
        <i data-lucide="calendar"></i>
        <input type="date" name="to" value="{{ request('to') }}" aria-label="{{ __('public.aria_to_date') }}">
    </label>
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('public.adm_clin_rx_btn_filter') }}</button>
    @if(request()->hasAny(['status','search','from','to']))
        <a href="{{ route('portals.admin.clinical.prescriptions') }}" class="btn btn-ghost btn-sm">{{ __('public.adm_clin_rx_btn_clear') }}</a>
    @endif
</form>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_clin_rx_col_patient') }}</th>
                    <th>{{ __('public.adm_clin_rx_col_items') }}</th>
                    <th>{{ __('public.adm_clin_rx_col_prescribed') }}</th>
                    <th>{{ __('public.adm_clin_rx_col_expires') }}</th>
                    <th>{{ __('public.adm_clin_rx_col_status') }}</th>
                    <th>{{ __('public.adm_clin_rx_col_dispensed_at') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($prescriptions as $rx)
                <tr>
                    <td data-label="{{ __('public.adm_clin_rx_col_patient') }}">
                        <div class="td-strong">{{ $rx->patient?->full_name ?? '—' }}</div>
                        <div class="td-muted">{{ $rx->patient?->health_id ?? '' }}</div>
                    </td>
                    <td data-label="{{ __('public.adm_clin_rx_col_items') }}">{{ $rx->items->count() }} item(s)</td>
                    <td data-label="{{ __('public.adm_clin_rx_col_prescribed') }}">{{ $rx->prescribed_at?->format('d M Y') ?? $rx->created_at?->format('d M Y') }}</td>
                    <td data-label="{{ __('public.adm_clin_rx_col_expires') }}">
                        @if($rx->expires_at)
                            <span class="{{ $rx->expires_at->isPast() ? 'text-danger' : '' }}">{{ $rx->expires_at->format('d M Y') }}</span>
                        @else
                            <span class="td-muted">—</span>
                        @endif
                    </td>
                    <td data-label="{{ __('public.adm_clin_rx_col_status') }}"><span class="badge badge-{{ $rx->statusColor() }}">@enum($rx->status)</span></td>
                    <td data-label="{{ __('public.adm_clin_rx_col_dispensed_at') }}">{{ $rx->dispensed_at?->format('d M Y H:i') ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="td-muted empty-cell">{{ __('public.adm_clin_rx_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $prescriptions->links() }}</div>
</div>

@endsection

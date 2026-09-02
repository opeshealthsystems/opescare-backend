@extends('layouts.portal')

@section('title', __('public.adm_clin_lab_title'))

@include('portals.admin.clinical._sidebar')

@section('breadcrumb_home', __('public.adm_clin_lab_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_clin_lab_breadcrumb_section'))

@section('content')

<div class="page-head">
    <h2>{{ __('public.adm_clin_lab_heading') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.lab.orders') }}" class="btn btn-secondary btn-sm">
        <i data-lucide="microscope"></i> {{ __('public.adm_clin_lab_btn_queue') }}
    </a>
</div>
<p class="td-muted mb-6">{{ __('public.adm_clin_lab_desc') }}</p>

{{-- Summary chips --}}
<div class="stat-grid mb-6">
    <div class="stat-card stat-card--warning">
        <div class="stat-card__head"><i data-lucide="clock"></i></div>
        <div class="stat-card__value">{{ $summary['pending'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_clin_lab_stat_pending') }}</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="loader"></i></div>
        <div class="stat-card__value">{{ $summary['processing'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_clin_lab_stat_processing') }}</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__head"><i data-lucide="check-circle-2"></i></div>
        <div class="stat-card__value">{{ $summary['resulted'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_clin_lab_stat_resulted_today') }}</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__head"><i data-lucide="alert-triangle"></i></div>
        <div class="stat-card__value">{{ $summary['urgent'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_clin_lab_stat_urgent') }}</div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="filter-bar">
    <select name="status" class="filter-select" aria-label="{{ __('public.aria_status') }}" onchange="this.form.submit()">
        <option value="">{{ __('public.adm_clin_lab_filter_all_statuses') }}</option>
        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('public.adm_clin_lab_filter_pending') }}</option>
        <option value="collected" {{ request('status') === 'collected' ? 'selected' : '' }}>{{ __('public.adm_clin_lab_filter_collected') }}</option>
        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>{{ __('public.adm_clin_lab_filter_processing') }}</option>
        <option value="resulted" {{ request('status') === 'resulted' ? 'selected' : '' }}>{{ __('public.adm_clin_lab_filter_resulted') }}</option>
        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('public.adm_clin_lab_filter_cancelled') }}</option>
    </select>
    <select name="urgency" class="filter-select" aria-label="{{ __('public.aria_urgency') }}" onchange="this.form.submit()">
        <option value="">{{ __('public.adm_clin_lab_filter_all_urgencies') }}</option>
        <option value="urgent" {{ request('urgency') === 'urgent' ? 'selected' : '' }}>{{ __('public.adm_clin_lab_filter_urgent') }}</option>
        <option value="routine" {{ request('urgency') === 'routine' ? 'selected' : '' }}>{{ __('public.adm_clin_lab_filter_routine') }}</option>
    </select>
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="search" placeholder="{{ __('public.adm_clin_lab_ph_search') }}" value="{{ request('search') }}" aria-label="{{ __('public.aria_search') }}">
    </label>
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('public.adm_clin_lab_btn_filter') }}</button>
    @if(request()->hasAny(['status','urgency','search']))
        <a href="{{ route('portals.admin.clinical.lab_orders') }}" class="btn btn-ghost btn-sm">{{ __('public.adm_clin_lab_btn_clear') }}</a>
    @endif
</form>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_clin_lab_col_test') }}</th>
                    <th>{{ __('public.adm_clin_lab_col_patient') }}</th>
                    <th>{{ __('public.adm_clin_lab_col_urgency') }}</th>
                    <th>{{ __('public.adm_clin_lab_col_ordered') }}</th>
                    <th>{{ __('public.adm_clin_lab_col_collected') }}</th>
                    <th>{{ __('public.adm_clin_lab_col_status') }}</th>
                    <th>{{ __('public.adm_clin_lab_col_resulted') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td data-label="{{ __('public.adm_clin_lab_col_test') }}">
                        <div class="td-strong">{{ $order->test_name }}</div>
                        @if($order->test_code)<div class="td-muted">{{ $order->test_code }}</div>@endif
                    </td>
                    <td data-label="{{ __('public.adm_clin_lab_col_patient') }}">
                        <div class="td-strong">{{ $order->patient?->full_name ?? '—' }}</div>
                        <div class="td-muted">{{ $order->patient?->health_id ?? '' }}</div>
                    </td>
                    <td data-label="{{ __('public.adm_clin_lab_col_urgency') }}">
                        <span class="badge {{ $order->urgency === 'urgent' ? 'badge-danger' : 'badge-neutral' }}">@enum($order->urgency ?? 'routine', 'urgency')</span>
                    </td>
                    <td data-label="{{ __('public.adm_clin_lab_col_ordered') }}">{{ $order->ordered_at?->format('d M Y H:i') ?? $order->created_at?->format('d M Y') }}</td>
                    <td data-label="{{ __('public.adm_clin_lab_col_collected') }}">{{ $order->collected_at?->format('d M Y H:i') ?? '—' }}</td>
                    <td data-label="{{ __('public.adm_clin_lab_col_status') }}"><span class="badge badge-{{ $order->statusColor() }}">@enum($order->status)</span></td>
                    <td data-label="{{ __('public.adm_clin_lab_col_resulted') }}">{{ $order->resulted_at?->format('d M Y H:i') ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="td-muted empty-cell">{{ __('public.adm_clin_lab_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $orders->links() }}</div>
</div>

@endsection

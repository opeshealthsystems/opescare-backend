@extends('layouts.portal')

@section('title', __('public.staff_portal.clin_lab_title'))

@section('breadcrumb_home', __('public.staff_portal.clin_lab_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.staff_portal.clin_lab_breadcrumb'))

@section('content')

<div class="page-head">
    <h2>{{ __('public.staff_portal.clin_lab_title') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.lab.orders') }}" class="btn btn-secondary btn-sm">
        <i data-lucide="microscope"></i>
        {{ __('public.staff_portal.clin_lab_btn_queue') }}
    </a>
</div>
<p class="page-subtitle mb-4">{{ __('public.staff_portal.clin_lab_subtitle') }}</p>

@if(session('success'))
    <div class="alert alert-success mb-4">
        <i data-lucide="check-circle"></i><div>{{ session('success') }}</div>
    </div>
@endif

{{-- Summary chips --}}
<div class="stat-grid">
    <a href="{{ route('portals.staff.lab_orders') }}?status=pending" class="stat-card stat-card--warning">
        <div class="stat-card__head"><i data-lucide="clock"></i></div>
        <div class="stat-card__value">{{ $summary['pending'] }}</div>
        <div class="stat-card__label">{{ __('public.staff_portal.clin_lab_chip_pending') }}</div>
    </a>
    <a href="{{ route('portals.staff.lab_orders') }}?status=processing" class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="loader"></i></div>
        <div class="stat-card__value">{{ $summary['processing'] }}</div>
        <div class="stat-card__label">{{ __('public.staff_portal.clin_lab_chip_processing') }}</div>
    </a>
    <div class="stat-card stat-card--success">
        <div class="stat-card__head"><i data-lucide="check-circle-2"></i></div>
        <div class="stat-card__value">{{ $summary['resulted'] }}</div>
        <div class="stat-card__label">{{ __('public.staff_portal.clin_lab_chip_resulted') }}</div>
    </div>
    <a href="{{ route('portals.staff.lab_orders') }}?urgency=urgent" class="stat-card stat-card--danger">
        <div class="stat-card__head"><i data-lucide="alert-triangle"></i></div>
        <div class="stat-card__value">{{ $summary['urgent'] }}</div>
        <div class="stat-card__label">{{ __('public.staff_portal.clin_lab_chip_urgent') }}</div>
    </a>
</div>

{{-- Filters --}}
<form method="GET" class="filter-bar mt-6">
    <select name="status" class="filter-select" onchange="this.form.submit()">
        <option value="">{{ __('public.staff_portal.clin_lab_filter_all_status') }}</option>
        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('public.staff_portal.clin_lab_chip_pending') }}</option>
        <option value="collected" {{ request('status') === 'collected' ? 'selected' : '' }}>{{ __('staff_data.opt_collected', [], app()->getLocale()) ?: 'Collected' }}</option>
        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>{{ __('public.staff_portal.clin_lab_chip_processing') }}</option>
        <option value="resulted" {{ request('status') === 'resulted' ? 'selected' : '' }}>{{ __('staff_data.opt_resulted', [], app()->getLocale()) ?: 'Resulted' }}</option>
        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('staff_data.opt_cancelled', [], app()->getLocale()) ?: 'Cancelled' }}</option>
    </select>
    <select name="urgency" class="filter-select" onchange="this.form.submit()">
        <option value="">{{ __('public.staff_portal.clin_lab_filter_all_urg') }}</option>
        <option value="urgent" {{ request('urgency') === 'urgent' ? 'selected' : '' }}>{{ __('staff_data.opt_urgent', [], app()->getLocale()) ?: 'Urgent' }}</option>
        <option value="routine" {{ request('urgency') === 'routine' ? 'selected' : '' }}>{{ __('staff_data.opt_routine', [], app()->getLocale()) ?: 'Routine' }}</option>
    </select>
    <input type="text" name="search" class="filter-search" placeholder="{{ __('public.staff_portal.clin_lab_ph_search') }}" value="{{ request('search') }}">
    <button type="submit" class="btn btn-primary btn-sm">{{ __('public.staff_portal.clin_lab_btn_filter') }}</button>
    @if(request()->hasAny(['status','urgency','search']))
        <a href="{{ route('portals.staff.lab_orders') }}" class="btn btn-secondary btn-sm">{{ __('public.staff_portal.clin_lab_btn_clear') }}</a>
    @endif
</form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('public.staff_portal.clin_lab_col_test') }}</th>
                        <th>{{ __('public.staff_portal.clin_lab_col_patient') }}</th>
                        <th>{{ __('public.staff_portal.clin_lab_col_urgency') }}</th>
                        <th>{{ __('public.staff_portal.clin_lab_col_ordered') }}</th>
                        <th>{{ __('public.staff_portal.clin_lab_col_status') }}</th>
                        <th>{{ __('public.staff_portal.clin_lab_col_resulted') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    @php
                        $orderBadge = match($order->statusColor()) {
                            'success' => 'success',
                            'info'    => 'info',
                            'warning' => 'warning',
                            'danger'  => 'danger',
                            default   => 'neutral',
                        };
                    @endphp
                    <tr>
                        <td data-label="{{ __('public.staff_portal.clin_lab_col_test') }}">
                            <div class="td-strong">{{ $order->test_name }}</div>
                            @if($order->test_code)
                            <div class="td-muted mono">{{ $order->test_code }}</div>
                            @endif
                        </td>
                        <td data-label="{{ __('public.staff_portal.clin_lab_col_patient') }}">
                            <div class="td-strong">{{ $order->patient?->full_name ?? '—' }}</div>
                            <div class="td-muted">{{ $order->patient?->health_id ?? '' }}</div>
                        </td>
                        <td data-label="{{ __('public.staff_portal.clin_lab_col_urgency') }}">
                            <span class="badge {{ $order->urgency === 'urgent' ? 'badge-danger' : 'badge-neutral' }}">
                                @enum($order->urgency ?? 'routine', 'urgency')
                            </span>
                        </td>
                        <td data-label="{{ __('public.staff_portal.clin_lab_col_ordered') }}" class="td-muted">{{ $order->ordered_at?->format('d M H:i') ?? $order->created_at?->format('d M') }}</td>
                        <td data-label="{{ __('public.staff_portal.clin_lab_col_status') }}">
                            <span class="badge badge-{{ $orderBadge }}">@enum($order->status)</span>
                        </td>
                        <td data-label="{{ __('public.staff_portal.clin_lab_col_resulted') }}" class="td-muted">{{ $order->resulted_at?->format('d M Y H:i') ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i data-lucide="flask-conical"></i></div>
                                <p>{{ __('public.staff_portal.clin_lab_empty') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel-body">{{ $orders->links() }}</div>
</div>

@endsection

@extends('layouts.portal')

@section('title', __('public.lab_portal.page_title', [], app()->getLocale()) ?: 'Laboratory Portal')

@section('sidebar_role_badge')
<div class="sidebar-role-badge">
    <i data-lucide="microscope"></i>
    {{ __('public.lab_portal.role_badge', [], app()->getLocale()) ?: 'Laboratory' }}
</div>
@endsection
@section('sidebar_user_role', __('public.lab_portal.role_label', [], app()->getLocale()) ?: 'Lab Technician')

@section('sidebar_nav')
@include('portals.lab._sidebar')
@endsection

@section('breadcrumb_home', __('public.lab_portal.breadcrumb_home', [], app()->getLocale()) ?: 'Lab Portal')
@section('breadcrumb_home_url', route('portals.lab.dashboard'))
@section('breadcrumb_section', __('public.lab_portal.nav_dashboard', [], app()->getLocale()) ?: 'Dashboard')

@section('content')

<div class="page-head">
    <h2>{{ __('public.lab_portal.page_heading', [], app()->getLocale()) ?: 'Laboratory dashboard' }}</h2>
    <p class="page-subtitle">{{ __('public.lab_portal.page_subtitle', [], app()->getLocale()) ?: "Today's work queue, urgent orders, and recent results." }}</p>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.lab.orders') }}" class="btn btn-primary btn-sm">
        <i data-lucide="clipboard-list"></i> {{ __('public.lab_portal.btn_work_queue', [], app()->getLocale()) ?: 'Work queue' }}
    </a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

{{-- Pipeline: order → sample → result --}}
<div class="stat-grid mb-6">
    <a href="{{ route('portals.lab.orders') }}?status=pending" class="stat-card stat-card--warning">
        <div class="stat-card__label">{{ __('public.lab_portal.stat_pending_orders', [], app()->getLocale()) ?: 'Pending orders' }}</div>
        <div class="stat-card__value">{{ $stats['pending'] ?? 0 }}</div>
    </a>
    <a href="{{ route('portals.lab.samples') }}" class="stat-card stat-card--teal">
        <div class="stat-card__label">{{ __('public.lab_portal.stat_samples_collected', [], app()->getLocale()) ?: 'Samples collected' }}</div>
        <div class="stat-card__value">{{ $stats['collected'] ?? 0 }}</div>
    </a>
    <a href="{{ route('portals.lab.orders') }}?status=processing" class="stat-card stat-card--primary">
        <div class="stat-card__label">{{ __('public.lab_portal.stat_processing', [], app()->getLocale()) ?: 'Processing' }}</div>
        <div class="stat-card__value">{{ $stats['processing'] ?? 0 }}</div>
    </a>
    <a href="{{ route('portals.lab.results') }}" class="stat-card stat-card--success">
        <div class="stat-card__label">{{ __('public.lab_portal.stat_resulted_today', [], app()->getLocale()) ?: 'Resulted today' }}</div>
        <div class="stat-card__value">{{ $stats['resulted'] ?? 0 }}</div>
    </a>
    <a href="{{ route('portals.lab.orders') }}?urgency=urgent" class="stat-card stat-card--danger">
        <div class="stat-card__label">{{ __('public.lab_portal.stat_urgent_pending', [], app()->getLocale()) ?: 'Urgent pending' }}</div>
        <div class="stat-card__value">{{ $stats['urgent'] ?? 0 }}</div>
    </a>
    <a href="{{ route('portals.lab.results') }}?flag=H" class="stat-card stat-card--danger">
        <div class="stat-card__label">{{ __('public.lab_portal.stat_abnormal_today', [], app()->getLocale()) ?: 'Abnormal today' }}</div>
        <div class="stat-card__value">{{ $stats['abnormal'] ?? 0 }}</div>
    </a>
</div>

<div class="field-grid">

    {{-- Urgent Orders --}}
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="alert-triangle"></i> {{ __('public.lab_portal.panel_urgent_orders', [], app()->getLocale()) ?: 'Urgent orders' }}</h3>
            <a href="{{ route('portals.lab.orders') }}?urgency=urgent" class="btn btn-secondary btn-sm">{{ __('public.portal.view_all', [], app()->getLocale()) ?: 'View all' }}</a>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.lab_portal.col_test', [], app()->getLocale()) ?: 'Test' }}</th>
                    <th>{{ __('public.portal.col_patient', [], app()->getLocale()) ?: 'Patient' }}</th>
                    <th>{{ __('public.portal.col_status', [], app()->getLocale()) ?: 'Status' }}</th>
                    <th class="row-actions"></th>
                </tr></thead>
                <tbody>
                @forelse($urgentOrders as $order)
                <tr>
                    <td data-label="{{ __('public.lab_portal.col_test', [], app()->getLocale()) ?: 'Test' }}">
                        <span class="td-strong">{{ $order->test_name ?? '—' }}</span>
                        <div class="td-muted">{{ $order->ordered_at?->diffForHumans() }}</div>
                    </td>
                    <td data-label="{{ __('public.portal.col_patient', [], app()->getLocale()) ?: 'Patient' }}">{{ $order->patient?->full_name ?? '—' }}</td>
                    <td data-label="{{ __('public.portal.col_status', [], app()->getLocale()) ?: 'Status' }}"><span class="badge badge-{{ $order->statusColor() }}">@enum($order->status)</span></td>
                    <td class="row-actions" data-label="">
                        @if($order->status === 'pending')
                        <form method="POST" action="{{ route('portals.lab.orders.collect', $order->id) }}" class="inline-form">@csrf
                            <button type="submit" class="btn btn-secondary btn-sm">{{ __('public.lab_portal.btn_collect', [], app()->getLocale()) ?: 'Collect' }}</button>
                        </form>
                        @elseif($order->status === 'collected')
                        <form method="POST" action="{{ route('portals.lab.orders.process', $order->id) }}" class="inline-form">@csrf
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('public.lab_portal.btn_process', [], app()->getLocale()) ?: 'Process' }}</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="td-muted empty-cell">{{ __('public.lab_portal.no_urgent_orders', [], app()->getLocale()) ?: 'No urgent pending orders.' }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Results --}}
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="file-bar-chart"></i> {{ __('public.lab_portal.panel_recent_results', [], app()->getLocale()) ?: 'Recent results' }}</h3>
            <a href="{{ route('portals.lab.results') }}" class="btn btn-secondary btn-sm">{{ __('public.portal.view_all', [], app()->getLocale()) ?: 'View all' }}</a>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.lab_portal.col_parameter', [], app()->getLocale()) ?: 'Parameter' }}</th>
                    <th>{{ __('public.lab_portal.col_value', [], app()->getLocale()) ?: 'Value' }}</th>
                    <th>{{ __('public.lab_portal.col_flag', [], app()->getLocale()) ?: 'Flag' }}</th>
                </tr></thead>
                <tbody>
                @forelse($recentResults as $result)
                <tr>
                    <td data-label="{{ __('public.lab_portal.col_parameter', [], app()->getLocale()) ?: 'Parameter' }}">
                        <span class="td-strong">{{ $result->parameter_name }}</span>
                        <div class="td-muted">{{ $result->patient?->full_name ?? '—' }}</div>
                    </td>
                    <td data-label="{{ __('public.lab_portal.col_value', [], app()->getLocale()) ?: 'Value' }}">{{ $result->value }} {{ $result->unit }}</td>
                    <td data-label="{{ __('public.lab_portal.col_flag', [], app()->getLocale()) ?: 'Flag' }}"><span class="badge badge-{{ $result->isAbnormal() ? 'danger' : 'success' }}">{{ $result->flagLabel() }}</span></td>
                </tr>
                @empty
                <tr><td colspan="3" class="td-muted empty-cell">{{ __('public.lab_portal.no_results_today', [], app()->getLocale()) ?: 'No results yet today.' }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection

@extends('layouts.portal')
@section('title', __('public.lab_portal.page_title', [], app()->getLocale()) ?: 'Lab Work Queue')
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
@section('breadcrumb_section', __('public.lab_portal.breadcrumb_section_orders', [], app()->getLocale()) ?: 'Work Queue')
@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-head">
    <h2>{{ __('public.lab_portal.page_heading_orders', [], $l) ?: 'Lab work queue' }}</h2>
    <p class="page-subtitle">{{ __('public.lab_portal.page_subtitle_orders', [], $l) ?: 'All incoming test orders — filter by status or urgency.' }}</p>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

<form method="GET" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="search" placeholder="{{ __('public.lab_portal.ph_search_orders', [], $l) ?: 'Test name or patient…' }}" value="{{ request('search') }}" aria-label="{{ __('public.aria_search_orders') }}">
    </label>
    <select name="status" class="filter-select" aria-label="{{ __('public.aria_status') }}" onchange="this.form.submit()">
        <option value="">{{ __('public.lab_portal.filter_all_active', [], $l) ?: 'Active (all)' }}</option>
        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('public.lab_portal.filter_status_pending', [], $l) ?: 'Pending' }}</option>
        <option value="collected" {{ request('status') === 'collected' ? 'selected' : '' }}>{{ __('public.lab_portal.filter_status_collected', [], $l) ?: 'Collected' }}</option>
        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>{{ __('public.lab_portal.filter_status_processing', [], $l) ?: 'Processing' }}</option>
        <option value="resulted" {{ request('status') === 'resulted' ? 'selected' : '' }}>{{ __('public.lab_portal.filter_status_resulted', [], $l) ?: 'Resulted' }}</option>
        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('public.lab_portal.filter_status_cancelled', [], $l) ?: 'Cancelled' }}</option>
    </select>
    <select name="urgency" class="filter-select" aria-label="{{ __('public.aria_urgency') }}" onchange="this.form.submit()">
        <option value="">{{ __('public.lab_portal.filter_all_urgency', [], $l) ?: 'All urgency' }}</option>
        <option value="urgent" {{ request('urgency') === 'urgent' ? 'selected' : '' }}>{{ __('public.lab_portal.filter_urgency_urgent', [], $l) ?: 'Urgent' }}</option>
        <option value="routine" {{ request('urgency') === 'routine' ? 'selected' : '' }}>{{ __('public.lab_portal.filter_urgency_routine', [], $l) ?: 'Routine' }}</option>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="filter"></i> {{ __('public.lab_portal.btn_filter', [], $l) ?: 'Filter' }}</button>
    @if(request()->hasAny(['status','urgency','search']))
        <a href="{{ route('portals.lab.orders') }}" class="btn btn-ghost btn-sm">{{ __('public.lab_portal.btn_clear', [], $l) ?: 'Clear' }}</a>
    @endif
</form>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.lab_portal.col_test', [], $l) ?: 'Test' }}</th>
                    <th>{{ __('public.lab_portal.col_patient', [], $l) ?: 'Patient' }}</th>
                    <th>{{ __('public.lab_portal.col_urgency', [], $l) ?: 'Urgency' }}</th>
                    <th>{{ __('public.lab_portal.col_ordered', [], $l) ?: 'Ordered' }}</th>
                    <th>{{ __('public.lab_portal.col_status', [], $l) ?: 'Status' }}</th>
                    <th class="row-actions">{{ __('public.lab_portal.col_action', [], $l) ?: 'Action' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td data-label="{{ __('public.lab_portal.col_test', [], $l) ?: 'Test' }}">
                        <span class="td-strong">{{ $order->test_name }}</span>
                        @if($order->test_code)<div class="td-muted">{{ $order->test_code }}</div>@endif
                    </td>
                    <td data-label="{{ __('public.lab_portal.col_patient', [], $l) ?: 'Patient' }}">
                        <span class="td-strong">{{ $order->patient?->full_name ?? '—' }}</span>
                        <div class="td-muted">{{ $order->patient?->health_id ?? '' }}</div>
                    </td>
                    <td data-label="{{ __('public.lab_portal.col_urgency', [], $l) ?: 'Urgency' }}">
                        <span class="badge {{ $order->urgency === 'urgent' ? 'badge-danger' : 'badge-neutral' }}">@enum($order->urgency ?? 'routine', 'urgency')</span>
                    </td>
                    <td data-label="{{ __('public.lab_portal.col_ordered', [], $l) ?: 'Ordered' }}" class="td-muted">{{ $order->ordered_at?->format('d M H:i') ?? $order->created_at?->format('d M') }}</td>
                    <td data-label="{{ __('public.lab_portal.col_status', [], $l) ?: 'Status' }}"><span class="badge badge-{{ $order->statusColor() }}">@enum($order->status)</span></td>
                    <td class="row-actions" data-label="{{ __('public.lab_portal.col_action', [], $l) ?: 'Action' }}">
                        @if($order->status === 'pending')
                        <form method="POST" action="{{ route('portals.lab.orders.collect', $order->id) }}" class="inline-form">@csrf
                            <button type="submit" class="btn btn-secondary btn-sm">{{ __('public.lab_portal.btn_collect', [], $l) ?: 'Collect' }}</button>
                        </form>
                        @elseif($order->status === 'collected')
                        <form method="POST" action="{{ route('portals.lab.orders.process', $order->id) }}" class="inline-form">@csrf
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('public.lab_portal.btn_process', [], $l) ?: 'Process' }}</button>
                        </form>
                        <a href="{{ route('portals.lab.orders.result', $order->id) }}" class="btn btn-secondary btn-sm">{{ __('public.lab_portal.btn_enter_result', [], $l) ?: 'Enter result' }}</a>
                        @elseif($order->status === 'processing')
                        <a href="{{ route('portals.lab.orders.result', $order->id) }}" class="btn btn-primary btn-sm"><i data-lucide="clipboard-pen-line"></i> {{ __('public.lab_portal.btn_enter_result', [], $l) ?: 'Enter result' }}</a>
                        @else
                        <span class="td-muted">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="td-muted empty-cell">{{ __('public.lab_portal.no_orders', [], $l) ?: 'No orders found.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $orders->links() }}</div>
</div>

@endsection

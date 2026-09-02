@extends('layouts.portal')
@section('title', __('public.lab_portal.page_title', [], app()->getLocale()) ?: 'Sample Tracking')
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
@section('breadcrumb_section', __('public.lab_portal.breadcrumb_section_samples', [], app()->getLocale()) ?: 'Sample Tracking')
@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-head">
    <h2>{{ __('public.lab_portal.page_heading_samples', [], $l) ?: 'Sample tracking' }}</h2>
    <p class="page-subtitle">{{ __('public.lab_portal.page_subtitle_samples', [], $l) ?: 'Track samples from order through collection to the bench.' }}</p>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

<div class="field-grid">

    {{-- Awaiting Collection --}}
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="clock"></i> {{ __('public.lab_portal.panel_awaiting_collection', [], $l) ?: 'Awaiting collection' }} ({{ $pending->count() }})</h3>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.lab_portal.col_test', [], $l) ?: 'Test' }}</th>
                    <th>{{ __('public.lab_portal.col_patient', [], $l) ?: 'Patient' }}</th>
                    <th class="row-actions"></th>
                </tr></thead>
                <tbody>
                @forelse($pending as $order)
                <tr>
                    <td data-label="{{ __('public.lab_portal.col_test', [], $l) ?: 'Test' }}">
                        <span class="td-strong">{{ $order->test_name }}</span>
                        <div class="td-muted">{{ __('public.lab_portal.lbl_ordered', [], $l) ?: 'Ordered' }} {{ $order->ordered_at?->diffForHumans() ?? '' }}</div>
                    </td>
                    <td data-label="{{ __('public.lab_portal.col_patient', [], $l) ?: 'Patient' }}">
                        {{ $order->patient?->full_name ?? '—' }}
                        @if($order->urgency === 'urgent') <span class="badge badge-danger badge-sm">{{ __('public.lab_portal.filter_urgency_urgent', [], $l) ?: 'Urgent' }}</span>@endif
                    </td>
                    <td class="row-actions" data-label="">
                        <form method="POST" action="{{ route('portals.lab.orders.collect', $order->id) }}" class="inline-form">@csrf
                            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="test-tube"></i> {{ __('public.lab_portal.btn_collect', [], $l) ?: 'Collect' }}</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="td-muted empty-cell">{{ __('public.lab_portal.no_awaiting_collection', [], $l) ?: 'No samples awaiting collection.' }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Collected — Ready to Process --}}
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="test-tube"></i> {{ __('public.lab_portal.panel_ready_to_process', [], $l) ?: 'Collected — ready to process' }} ({{ $collected->count() }})</h3>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.lab_portal.col_test', [], $l) ?: 'Test' }}</th>
                    <th>{{ __('public.lab_portal.col_patient', [], $l) ?: 'Patient' }}</th>
                    <th class="row-actions"></th>
                </tr></thead>
                <tbody>
                @forelse($collected as $order)
                <tr>
                    <td data-label="{{ __('public.lab_portal.col_test', [], $l) ?: 'Test' }}">
                        <span class="td-strong">{{ $order->test_name }}</span>
                        <div class="td-muted">{{ __('public.lab_portal.lbl_collected', [], $l) ?: 'Collected' }} {{ $order->collected_at?->diffForHumans() ?? '' }}</div>
                    </td>
                    <td data-label="{{ __('public.lab_portal.col_patient', [], $l) ?: 'Patient' }}">
                        {{ $order->patient?->full_name ?? '—' }}
                        @if($order->urgency === 'urgent') <span class="badge badge-danger badge-sm">{{ __('public.lab_portal.filter_urgency_urgent', [], $l) ?: 'Urgent' }}</span>@endif
                    </td>
                    <td class="row-actions" data-label="">
                        <form method="POST" action="{{ route('portals.lab.orders.process', $order->id) }}" class="inline-form">@csrf
                            <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="loader"></i> {{ __('public.lab_portal.btn_process', [], $l) ?: 'Process' }}</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="td-muted empty-cell">{{ __('public.lab_portal.no_collected_waiting', [], $l) ?: 'No collected samples waiting.' }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection

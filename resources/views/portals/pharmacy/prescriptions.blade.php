@extends('layouts.portal')
@section('title', __('public.pharmacy_portal.page_title', [], app()->getLocale()))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label', [], app()->getLocale()))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('public.pharmacy_portal.breadcrumb_section_rx', [], app()->getLocale()))
@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-head">
    <h2>{{ __('public.pharmacy_portal.page_heading_rx', [], $l) }}</h2>
    <p class="page-subtitle">{{ __('public.pharmacy_portal.page_subtitle_rx', [], $l) }}</p>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

<form method="GET" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="search" placeholder="{{ __('public.pharmacy_portal.ph_search_patient', [], $l) }}" value="{{ request('search') }}" aria-label="{{ __('public.aria_search_patient') }}">
    </label>
    <select name="status" class="filter-select" aria-label="{{ __('public.aria_status') }}" data-autosubmit>
        <option value="">{{ __('public.pharmacy_portal.filter_pending_all', [], $l) }}</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('public.pharmacy_portal.filter_status_active', [], $l) }}</option>
        <option value="partially_dispensed" {{ request('status') === 'partially_dispensed' ? 'selected' : '' }}>{{ __('public.pharmacy_portal.filter_status_partial', [], $l) }}</option>
        <option value="dispensed" {{ request('status') === 'dispensed' ? 'selected' : '' }}>{{ __('public.pharmacy_portal.filter_status_dispensed', [], $l) }}</option>
        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('public.pharmacy_portal.filter_status_cancelled', [], $l) }}</option>
        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>{{ __('public.pharmacy_portal.filter_status_expired', [], $l) }}</option>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="filter"></i> {{ __('public.pharmacy_portal.btn_filter', [], $l) }}</button>
    @if(request()->hasAny(['status','search']))
        <a href="{{ route('portals.pharmacy.prescriptions') }}" class="btn btn-ghost btn-sm">{{ __('public.pharmacy_portal.btn_clear', [], $l) }}</a>
    @endif
</form>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.pharmacy_portal.col_patient', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.col_items', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.col_prescribed', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.col_expires', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.col_status', [], $l) }}</th>
                    <th class="row-actions">{{ __('public.pharmacy_portal.col_action', [], $l) }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($prescriptions as $rx)
                <tr>
                    <td data-label="{{ __('public.pharmacy_portal.col_patient', [], $l) }}">
                        <span class="td-strong">{{ trim(($rx->patient?->first_name ?? '') . ' ' . ($rx->patient?->last_name ?? '')) ?: '—' }}</span>
                        <div class="td-muted">{{ $rx->patient?->health_id ?? '' }}</div>
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.col_items', [], $l) }}">
                        @foreach($rx->items->take(2) as $item)
                            <div>{{ $item->drug_name }} {{ $item->dosage }}</div>
                        @endforeach
                        @if($rx->items->count() > 2)
                            <div class="td-muted">+{{ $rx->items->count() - 2 }} {{ __('public.pharmacy_portal.lbl_items', [], $l) }}</div>
                        @endif
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.col_prescribed', [], $l) }}" class="td-muted">{{ $rx->prescribed_at?->translatedFormat('d M Y H:i') ?? $rx->created_at?->translatedFormat('d M Y') }}</td>
                    <td data-label="{{ __('public.pharmacy_portal.col_expires', [], $l) }}">
                        @if($rx->expires_at)
                            @if($rx->expires_at->isPast())<span class="badge badge-danger badge-sm">{{ $rx->expires_at->translatedFormat('d M Y') }}</span>
                            @else<span class="td-muted">{{ $rx->expires_at->translatedFormat('d M Y') }}</span>@endif
                        @else
                            <span class="td-muted">—</span>
                        @endif
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.col_status', [], $l) }}"><span class="badge badge-{{ $rx->statusColor() }}">@enum($rx->status)</span></td>
                    <td class="row-actions" data-label="{{ __('public.pharmacy_portal.col_action', [], $l) }}">
                        @if(in_array($rx->status, ['active', 'partially_dispensed']))
                        <form method="POST" action="{{ route('portals.pharmacy.dispense', $rx->id) }}" class="inline-form">@csrf
                            <button type="submit" class="btn btn-primary btn-sm" data-confirm="{{ __('public.pharmacy_portal.confirm_dispense_msg', [], $l) }}">
                                <i data-lucide="check"></i> {{ __('public.pharmacy_portal.btn_dispense', [], $l) }}
                            </button>
                        </form>
                        @else
                        <span class="td-muted">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="td-muted empty-cell">{{ __('public.pharmacy_portal.no_prescriptions', [], $l) }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $prescriptions->links() }}</div>
</div>

@endsection

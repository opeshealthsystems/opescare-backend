@extends('layouts.portal')
@section('title', __('public.pharmacy_portal.page_title', [], app()->getLocale()))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label', [], app()->getLocale()))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('public.pharmacy_portal.breadcrumb_section_rx', [], app()->getLocale()))
@php
    $l = app()->getLocale();
    $initials = function ($first, $last): string {
        $s = mb_strtoupper(mb_substr(trim((string) $first), 0, 1) . mb_substr(trim((string) $last), 0, 1));
        return $s !== '' ? $s : '—';
    };
    // Status filter pills: same `status` query parameter the select used to send.
    $statusPills = [
        ''                    => ['label' => __('public.pharmacy_portal.filter_pending_all', [], $l),      'dot' => 'neutral'],
        'active'              => ['label' => __('public.pharmacy_portal.filter_status_active', [], $l),    'dot' => 'info'],
        'partially_dispensed' => ['label' => __('public.pharmacy_portal.filter_status_partial', [], $l),   'dot' => 'warning'],
        'dispensed'           => ['label' => __('public.pharmacy_portal.filter_status_dispensed', [], $l), 'dot' => 'success'],
        'cancelled'           => ['label' => __('public.pharmacy_portal.filter_status_cancelled', [], $l), 'dot' => 'danger'],
        'expired'             => ['label' => __('public.pharmacy_portal.filter_status_expired', [], $l),   'dot' => 'danger'],
    ];
    $currentStatus = (string) request('status', '');
@endphp

@section('content')

<div class="page-head">
    <h2>{{ __('public.pharmacy_portal.page_heading_rx', [], $l) }}</h2>
    <p class="page-subtitle">{{ __('public.pharmacy_portal.page_subtitle_rx', [], $l) }}</p>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="btn btn-secondary btn-sm"><i data-lucide="scan-line"></i> {{ __('pharmacy_desk.btn_lookup', [], $l) }}</a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="clipboard-list"></i> {{ __('public.pharmacy_portal.page_heading_rx', [], $l) }} <span class="ph-count-inline">{{ __('pharmacy_desk.count_paren', ['count' => $prescriptions->total()], $l) }}</span></h3>
    </div>

    <div class="ph-tools">
        <nav class="ph-pills" aria-label="{{ __('public.aria_status') }}">
            @foreach($statusPills as $value => $pill)
                <a href="{{ route('portals.pharmacy.prescriptions', array_filter(['status' => $value, 'search' => request('search')], fn ($v) => $v !== null && $v !== '')) }}"
                   class="ph-pill {{ $currentStatus === $value ? 'active' : '' }}" @if($currentStatus === $value) aria-current="page" @endif>
                    <span class="ph-dot ph-dot--{{ $pill['dot'] }}"></span> {{ $pill['label'] }}
                </a>
            @endforeach
        </nav>
        <form method="GET" class="ph-toolbar">
            @if($currentStatus !== '')<input type="hidden" name="status" value="{{ $currentStatus }}">@endif
            <label class="ph-search">
                <i data-lucide="search"></i>
                <input type="text" name="search" placeholder="{{ __('public.pharmacy_portal.ph_search_patient', [], $l) }}" value="{{ request('search') }}" aria-label="{{ __('public.aria_search_patient') }}">
            </label>
            <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="filter"></i> {{ __('public.pharmacy_portal.btn_filter', [], $l) }}</button>
            @if(request()->hasAny(['status','search']))
                <a href="{{ route('portals.pharmacy.prescriptions') }}" class="btn btn-ghost btn-sm">{{ __('public.pharmacy_portal.btn_clear', [], $l) }}</a>
            @endif
        </form>
    </div>

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
                @php
                    $rxUrl = route('portals.pharmacy.prescriptions.show', $rx->id);
                    $rxName = trim(($rx->patient?->first_name ?? '') . ' ' . ($rx->patient?->last_name ?? ''));
                    $dispensable = in_array($rx->status, ['active', 'partially_dispensed']);
                @endphp
                <tr>
                    <td data-label="{{ __('public.pharmacy_portal.col_patient', [], $l) }}">
                        <div class="ph-cell">
                            <span class="ph-avatar" aria-hidden="true">{{ $initials($rx->patient?->first_name, $rx->patient?->last_name) }}</span>
                            <div>
                                <a href="{{ $rxUrl }}" class="ph-cell__title">{{ $rxName !== '' ? $rxName : '—' }}</a>
                                <div class="ph-cell__sub">{{ $rx->patient?->health_id ?? '' }} @if($rx->reference)&middot; <span class="mono">{{ $rx->reference }}</span>@endif &middot; {{ $rx->items->count() }} {{ __('public.pharmacy_portal.lbl_items', [], $l) }}</div>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.col_items', [], $l) }}" class="ph-lines">
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
                        @if($dispensable)
                        <form method="POST" action="{{ route('portals.pharmacy.dispense', $rx->id) }}" class="inline-form">@csrf
                            <button type="submit" class="btn btn-primary btn-sm" data-confirm="{{ __('public.pharmacy_portal.confirm_dispense_msg', [], $l) }}">
                                <i data-lucide="check"></i> {{ __('public.pharmacy_portal.btn_dispense', [], $l) }}
                            </button>
                        </form>
                        <a href="{{ $rxUrl }}" class="btn btn-ghost btn-icon ph-kebab" aria-label="{{ __('pharmacy_desk.btn_open_rx', [], $l) }}" title="{{ __('pharmacy_desk.btn_open_rx', [], $l) }}"><i data-lucide="more-horizontal"></i></a>
                        @else
                        <a href="{{ $rxUrl }}" class="btn btn-primary btn-sm"><i data-lucide="chevron-right"></i> {{ __('pharmacy_desk.btn_open_rx', [], $l) }}</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="clipboard-list"></i>
                        <p>{{ __('public.pharmacy_portal.no_prescriptions', [], $l) }}</p>
                        <a href="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="btn btn-primary btn-sm"><i data-lucide="scan-line"></i> {{ __('pharmacy_desk.btn_lookup', [], $l) }}</a>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-footer">{{ $prescriptions->links() }}</div>
</div>

@endsection

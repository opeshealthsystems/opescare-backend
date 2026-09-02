@extends('layouts.portal')

@section('title', __('public.staff_portal.clin_rx_title'))

@section('breadcrumb_home', __('public.staff_portal.clin_lab_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.staff_portal.clin_rx_breadcrumb'))

@section('content')

<div class="page-head">
    <h2>{{ __('public.staff_portal.clin_rx_title') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.staff.prescriptions.create') }}" class="btn btn-primary btn-sm">
        <i data-lucide="clipboard-plus"></i>
        {{ __('staff_clinical.rx_btn_new') }}
    </a>
    <a href="{{ route('portals.pharmacy.prescriptions') }}" class="btn btn-secondary btn-sm">
        <i data-lucide="pill"></i>
        {{ __('public.staff_portal.clin_rx_btn_queue') }}
    </a>
</div>
<p class="page-subtitle mb-4">{{ __('public.staff_portal.clin_rx_subtitle') }}</p>

@if(session('success'))
    <div class="alert alert-success mb-4">
        <i data-lucide="check-circle"></i><div>{{ session('success') }}</div>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger mb-4">
        <i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div>
    </div>
@endif

{{-- Summary chips --}}
<div class="stat-grid">
    <a href="{{ route('portals.staff.prescriptions') }}?status=active" class="stat-card stat-card--warning">
        <div class="stat-card__head"><i data-lucide="clock"></i></div>
        <div class="stat-card__value">{{ $summary['active'] }}</div>
        <div class="stat-card__label">{{ __('public.staff_portal.clin_rx_chip_active') }}</div>
    </a>
    <a href="{{ route('portals.staff.prescriptions') }}?status=partially_dispensed" class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="loader"></i></div>
        <div class="stat-card__value">{{ $summary['partially_dispensed'] }}</div>
        <div class="stat-card__label">{{ __('public.staff_portal.clin_rx_chip_partial') }}</div>
    </a>
    <div class="stat-card stat-card--success">
        <div class="stat-card__head"><i data-lucide="check-circle-2"></i></div>
        <div class="stat-card__value">{{ $summary['dispensed_today'] }}</div>
        <div class="stat-card__label">{{ __('public.staff_portal.clin_rx_chip_dispensed') }}</div>
    </div>
    <a href="{{ route('portals.staff.prescriptions') }}?status=expired" class="stat-card stat-card--danger">
        <div class="stat-card__head"><i data-lucide="alert-triangle"></i></div>
        <div class="stat-card__value">{{ $summary['expired'] }}</div>
        <div class="stat-card__label">{{ __('public.staff_portal.clin_rx_chip_expired') }}</div>
    </a>
</div>

{{-- Filters --}}
<form method="GET" class="filter-bar mt-6">
    <select name="status" class="filter-select" onchange="this.form.submit()">
        <option value="">{{ __('public.staff_portal.clin_rx_filter_all') }}</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('public.staff_portal.clin_rx_chip_active') }}</option>
        <option value="partially_dispensed" {{ request('status') === 'partially_dispensed' ? 'selected' : '' }}>{{ __('public.staff_portal.clin_rx_chip_partial') }}</option>
        <option value="dispensed" {{ request('status') === 'dispensed' ? 'selected' : '' }}>{{ __('public.staff_portal.clin_rx_chip_dispensed') }}</option>
        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>{{ __('public.staff_portal.clin_rx_chip_expired') }}</option>
        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('public.staff_portal.btn_cancel') }}</option>
        <option value="voided" {{ request('status') === 'voided' ? 'selected' : '' }}>@enum('voided')</option>
        <option value="entered_in_error" {{ request('status') === 'entered_in_error' ? 'selected' : '' }}>@enum('entered_in_error')</option>
    </select>
    <input type="text" name="search" class="filter-search" placeholder="{{ __('public.staff_portal.clin_rx_ph_search') }}" value="{{ request('search') }}">
    <button type="submit" class="btn btn-primary btn-sm">{{ __('public.staff_portal.clin_lab_btn_filter') }}</button>
    @if(request()->hasAny(['status','search']))
        <a href="{{ route('portals.staff.prescriptions') }}" class="btn btn-secondary btn-sm">{{ __('public.staff_portal.clin_lab_btn_clear') }}</a>
    @endif
</form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('public.staff_portal.clin_rx_col_patient') }}</th>
                        <th>{{ __('public.staff_portal.clin_rx_col_items') }}</th>
                        <th>{{ __('public.staff_portal.clin_rx_col_prescribed') }}</th>
                        <th>{{ __('public.staff_portal.clin_rx_col_expires') }}</th>
                        <th>{{ __('public.staff_portal.clin_rx_col_status') }}</th>
                        <th>{{ __('public.staff_portal.clin_rx_col_dispensed_at') }}</th>
                        <th>{{ __('staff_clinical.rx_col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prescriptions as $rx)
                    @php
                        $rxBadge = match($rx->statusColor()) {
                            'success' => 'success',
                            'info'    => 'info',
                            'warning' => 'warning',
                            'danger'  => 'danger',
                            default   => 'neutral',
                        };
                    @endphp
                    <tr>
                        <td data-label="{{ __('public.staff_portal.clin_rx_col_patient') }}">
                            <div class="td-strong">{{ $rx->patient?->full_name ?? '—' }}</div>
                            <div class="td-muted">{{ $rx->patient?->health_id ?? '' }}</div>
                        </td>
                        <td data-label="{{ __('public.staff_portal.clin_rx_col_items') }}">
                            @foreach($rx->items->take(2) as $item)
                                <div>{{ $item->drug_name ?? '—' }} {{ $item->dose ?? '' }}</div>
                            @endforeach
                            @if($rx->items->count() > 2)
                                <div class="td-muted">+{{ $rx->items->count()-2 }} {{ __('public.staff_portal.clin_rx_more_items') }}</div>
                            @endif
                        </td>
                        <td data-label="{{ __('public.staff_portal.clin_rx_col_prescribed') }}" class="td-muted">{{ $rx->prescribed_at?->format('d M Y') ?? $rx->created_at?->format('d M Y') }}</td>
                        <td data-label="{{ __('public.staff_portal.clin_rx_col_expires') }}">
                            @if($rx->expires_at)
                                @if($rx->expires_at->isPast())
                                    <span class="badge badge-danger">{{ $rx->expires_at->format('d M Y') }}</span>
                                @else
                                    {{ $rx->expires_at->format('d M Y') }}
                                @endif
                            @else
                                <span class="td-muted">—</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.staff_portal.clin_rx_col_status') }}">
                            <span class="badge badge-{{ $rxBadge }}">
                                @enum($rx->status)
                            </span>
                        </td>
                        <td data-label="{{ __('public.staff_portal.clin_rx_col_dispensed_at') }}" class="td-muted">{{ $rx->dispensed_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td data-label="{{ __('staff_clinical.rx_col_actions') }}">
                            {{-- No edit and no delete: a prescription is an immutable
                                 clinical event, corrected by void, never overwritten. --}}
                            @if($rx->isTerminal())
                                @if($rx->void_reason)
                                    <span class="td-muted">{{ __('staff_clinical.rx_voided_reason', ['reason' => $rx->void_reason]) }}</span>
                                @else
                                    <span class="td-muted">—</span>
                                @endif
                            @else
                                <form method="POST" action="{{ route('portals.staff.prescriptions.void', $rx->id) }}"
                                      class="rx-void-form">
                                    @csrf
                                    <input type="text" name="void_reason" class="form-control" required minlength="5" maxlength="500"
                                           placeholder="{{ __('staff_clinical.rx_void_reason_ph') }}">
                                    <label class="form-label" style="font-weight:400;margin-top:4px;">
                                        <input type="checkbox" name="entered_in_error" value="1" style="width:auto;">
                                        {{ __('staff_clinical.rx_void_entered_error') }}
                                    </label>
                                    <button type="submit" class="btn btn-ghost btn-sm">
                                        <i data-lucide="ban"></i>
                                        {{ __('staff_clinical.rx_void') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i data-lucide="clipboard-plus"></i></div>
                                <p>{{ __('public.staff_portal.clin_rx_empty') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel-body">{{ $prescriptions->links() }}</div>
</div>

@endsection

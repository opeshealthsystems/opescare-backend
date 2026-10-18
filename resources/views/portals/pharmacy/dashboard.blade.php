@extends('layouts.portal')

@section('title', __('public.pharmacy_portal.page_title', [], app()->getLocale()))

@section('sidebar_user_role', __('public.pharmacy_portal.role_label', [], app()->getLocale()))

@section('breadcrumb_home', __('public.pharmacy_portal.page_title', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('public.pharmacy_portal.nav_dashboard', [], app()->getLocale()))

@php
    $l = app()->getLocale();
    // Avatar initials for the queue rows: first letter of each name part, never the DOB or anything else.
    $initials = function ($first, $last): string {
        $a = mb_substr(trim((string) $first), 0, 1);
        $b = mb_substr(trim((string) $last), 0, 1);
        $s = mb_strtoupper($a . $b);
        return $s !== '' ? $s : '—';
    };
@endphp

@section('content')

<div class="page-head">
    <h2>{{ __('public.pharmacy_portal.page_heading', [], $l) }}</h2>
    <p class="page-subtitle">{{ __('public.pharmacy_portal.page_subtitle', [], $l) }}</p>
    <div class="page-head__spacer"></div>
    {{-- Everything that leads to the counter is `dispense` (config/staff_capabilities.php):
         medicine_stock opens this dashboard for the stock alerts and must not be
         offered a queue its role cannot open (the routes 403 it). --}}
    @capability('dispense')
    <a href="{{ route('portals.pharmacy.orders.index') }}" class="btn btn-secondary btn-sm" data-pending-orders="{{ $pendingOrders ?? 0 }}">
        <i data-lucide="shopping-bag"></i> {{ trans_choice('pharmacy_orders.dashboard_pending', $pendingOrders ?? 0, ['count' => $pendingOrders ?? 0]) }}
    </a>
    <a href="{{ route('portals.pharmacy.prescriptions') }}" class="btn btn-primary btn-sm">
        <i data-lucide="clipboard-list"></i> {{ __('public.pharmacy_portal.btn_view_queue', [], $l) }}
    </a>
    @endcapability
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

{{-- KPI tiles: tinted icon square + label + value + sub-line (visual standard) --}}
<div class="ph-kpis">
    @capability('dispense')
    <a href="{{ route('portals.pharmacy.prescriptions') }}" class="stat-card stat-card--warning">
        <div class="stat-card__icon"><i data-lucide="clipboard-list"></i></div>
        <div class="stat-card__label">{{ __('public.pharmacy_portal.stat_pending_rx', [], $l) }}</div>
        <div class="stat-card__value">{{ $stats['pending_rx'] ?? 0 }}</div>
        <div class="stat-card__sub">{{ __('pharmacy_desk.kpi_pending_sub', [], $l) }}</div>
    </a>
    <a href="{{ route('portals.pharmacy.dispensing', ['from' => today()->toDateString(), 'to' => today()->toDateString()]) }}" class="stat-card stat-card--success">
        <div class="stat-card__icon"><i data-lucide="check"></i></div>
        <div class="stat-card__label">{{ __('public.pharmacy_portal.stat_dispensed_today', [], $l) }}</div>
        <div class="stat-card__value">{{ $stats['dispensed_today'] ?? 0 }}</div>
        <div class="stat-card__sub">{{ __('pharmacy_desk.kpi_dispensed_sub', [], $l) }}</div>
    </a>
    @endcapability
    @capability('pharmacy_stock')
    <a href="{{ route('portals.pharmacy.stock') }}" class="stat-card stat-card--info">
        <div class="stat-card__icon"><i data-lucide="pill"></i></div>
        <div class="stat-card__label">{{ __('public.pharmacy_portal.stat_drug_lines', [], $l) }}</div>
        <div class="stat-card__value">{{ $stats['total_drugs'] ?? 0 }}</div>
        <div class="stat-card__sub">{{ __('pharmacy_desk.kpi_lines_sub', [], $l) }}</div>
    </a>
    <a href="{{ route('portals.pharmacy.stock', ['stock_status' => 'low_stock']) }}" class="stat-card stat-card--warning">
        <div class="stat-card__icon"><i data-lucide="package"></i></div>
        <div class="stat-card__label">{{ __('public.pharmacy_portal.stat_low_stock', [], $l) }}</div>
        <div class="stat-card__value">{{ $stats['low_stock'] ?? 0 }}</div>
        <div class="stat-card__sub">{{ __('pharmacy_desk.kpi_low_sub', [], $l) }}</div>
    </a>
    <a href="{{ route('portals.pharmacy.stock', ['stock_status' => 'out_of_stock']) }}" class="stat-card stat-card--danger">
        <div class="stat-card__icon"><i data-lucide="package-x"></i></div>
        <div class="stat-card__label">{{ __('public.pharmacy_portal.stat_out_of_stock', [], $l) }}</div>
        <div class="stat-card__value">{{ $stats['out_of_stock'] ?? 0 }}</div>
        <div class="stat-card__sub">{{ __('pharmacy_desk.kpi_out_sub', [], $l) }}</div>
    </a>
    <a href="{{ route('portals.pharmacy.stock.batches') }}" class="stat-card stat-card--danger">
        <div class="stat-card__icon"><i data-lucide="calendar-x"></i></div>
        <div class="stat-card__label">{{ __('public.pharmacy_portal.stat_expired', [], $l) }}</div>
        <div class="stat-card__value">{{ $stats['expired'] ?? 0 }}</div>
        <div class="stat-card__sub">{{ __('pharmacy_desk.kpi_expired_sub', [], $l) }}</div>
    </a>
    @endcapability
</div>

<div class="ph-split">

    {{-- Left: the pending-prescriptions queue (dispense roles only) --}}
    @capability('dispense')
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="clipboard-list"></i> {{ __('public.pharmacy_portal.panel_pending_rx', [], $l) }} <span class="ph-count-inline">{{ __('pharmacy_desk.count_paren', ['count' => $stats['pending_rx'] ?? $pendingRx->count()], $l) }}</span></h3>
            <a href="{{ route('portals.pharmacy.dispensing') }}" class="btn btn-ghost btn-sm"><i data-lucide="clipboard-check"></i> {{ __('pharmacy_desk.dash_register_link', [], $l) }}</a>
            <a href="{{ route('portals.pharmacy.prescriptions') }}" class="btn btn-secondary btn-sm">{{ __('public.portal.view_all', [], $l) }}</a>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.portal.col_patient', [], $l) }}</th>
                    <th>{{ __('public.portal.col_status', [], $l) }}</th>
                    <th class="row-actions"></th>
                </tr></thead>
                <tbody>
                @forelse($pendingRx as $rx)
                @php
                    $rxUrl = route('portals.pharmacy.prescriptions.show', $rx->id);
                    $rxName = trim(($rx->patient?->first_name ?? '') . ' ' . ($rx->patient?->last_name ?? ''));
                @endphp
                <tr>
                    <td data-label="{{ __('public.portal.col_patient', [], $l) }}">
                        <div class="ph-cell">
                            <span class="ph-avatar" aria-hidden="true">{{ $initials($rx->patient?->first_name, $rx->patient?->last_name) }}</span>
                            <div>
                                <a href="{{ $rxUrl }}" class="ph-cell__title">{{ $rxName !== '' ? $rxName : ($rx->reference ?? '—') }}</a>
                                <div class="ph-cell__sub">@if($rx->reference)<span class="mono">{{ $rx->reference }}</span> &middot; @endif{{ $rx->items->count() }} {{ __('public.pharmacy_portal.lbl_items', [], $l) }} &middot; {{ $rx->created_at?->diffForHumans() }}</div>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('public.portal.col_status', [], $l) }}"><span class="badge badge-{{ $rx->statusColor() }}">@enum($rx->status)</span></td>
                    <td class="row-actions" data-label="">
                        <a href="{{ $rxUrl }}" class="btn btn-primary btn-sm"><i data-lucide="chevron-right"></i> {{ __('pharmacy_desk.btn_open_rx', [], $l) }}</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="check-circle-2" class="empty-state-icon"></i>
                        <p>{{ __('pharmacy_desk.dash_empty_rx', [], $l) }}</p>
                        <a href="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="btn btn-secondary btn-sm"><i data-lucide="scan-line"></i> {{ __('pharmacy_desk.dash_empty_rx_action', [], $l) }}</a>
                    </div>
                </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endcapability

    {{-- Right: stock alerts, compact rows with Low / Out of stock pills --}}
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="alert-triangle"></i> {{ __('public.pharmacy_portal.panel_stock_alerts', [], $l) }}</h3>
            <a href="{{ route('portals.pharmacy.stock') }}" class="ph-view-all">{{ __('public.portal.view_all', [], $l) }}</a>
        </div>
        <div class="panel-body">
            @if($alerts->isEmpty())
                <div class="empty-state">
                    <i data-lucide="package-check"></i>
                    <p>{{ __('public.pharmacy_portal.no_stock_alerts', [], $l) }}</p>
                </div>
            @else
            <ul class="alert-list">
                @foreach($alerts as $drug)
                @php $st = $drug->stock_status instanceof \BackedEnum ? $drug->stock_status->value : (string) $drug->stock_status; @endphp
                <li>
                    <div>
                        @if(isset($ledgerItems) && $ledgerItems->has($drug->medicine_id))
                            <a href="{{ route('portals.pharmacy.stock.items.show', $ledgerItems->get($drug->medicine_id)) }}" class="td-strong">{{ $drug->medicine?->name ?? '—' }}</a>
                        @else
                            <span class="td-strong">{{ $drug->medicine?->name ?? '—' }}</span>
                        @endif
                        <div class="td-muted">{{ $drug->medicine?->generic_name }} &middot; {{ $drug->medicine?->form }} {{ $drug->medicine?->strength }}</div>
                    </div>
                    <span class="badge badge-sm badge-{{ $st === 'out_of_stock' ? 'danger' : 'warning' }}">@enum($st, 'stock_status')</span>
                </li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>

</div>

{{-- Quick actions + orders needing action + expired batches. Every number
     comes from what the controller already provides; nothing is invented. --}}
<div class="ph-grid-3">
    @capability('dispense')
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="zap"></i> {{ __('pharmacy_desk.quick_title', [], $l) }}</h3></div>
        <div class="panel-body">
            <div class="ph-quick">
                <a href="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="ph-quick__tile">
                    <i data-lucide="scan-line"></i>
                    <span>{{ __('pharmacy_desk.quick_lookup', [], $l) }}</span>
                    <span class="ph-quick__hint">{{ __('pharmacy_desk.quick_key_lookup', [], $l) }}</span>
                </a>
                <a href="{{ route('portals.pharmacy.prescriptions') }}" class="ph-quick__tile">
                    <i data-lucide="clipboard-list"></i>
                    <span>{{ __('pharmacy_desk.quick_queue', [], $l) }}</span>
                    <span class="ph-quick__hint">{{ __('pharmacy_desk.quick_key_queue', [], $l) }}</span>
                </a>
                <a href="{{ route('portals.pharmacy.dispensing') }}" class="ph-quick__tile">
                    <i data-lucide="clipboard-check"></i>
                    <span>{{ __('pharmacy_desk.quick_register', [], $l) }}</span>
                    <span class="ph-quick__hint">{{ __('pharmacy_desk.quick_key_register', [], $l) }}</span>
                </a>
                <a href="{{ route('portals.pharmacy.stock') }}" class="ph-quick__tile">
                    <i data-lucide="package"></i>
                    <span>{{ __('pharmacy_desk.quick_stock', [], $l) }}</span>
                    <span class="ph-quick__hint">{{ __('pharmacy_desk.quick_key_stock', [], $l) }}</span>
                </a>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="shopping-bag"></i> {{ __('pharmacy_desk.orders_panel', [], $l) }}</h3>
            <a href="{{ route('portals.pharmacy.orders.index') }}" class="ph-view-all">{{ __('public.portal.view_all', [], $l) }}</a>
        </div>
        <div class="panel-body">
            @if(($pendingOrders ?? 0) > 0)
                <div class="ph-summary">
                    <div class="ph-summary__icon"><i data-lucide="hand"></i></div>
                    <div>
                        <div class="ph-summary__value">{{ $pendingOrders }}</div>
                        <div class="ph-summary__label">{{ __('pharmacy_desk.orders_waiting', ['count' => $pendingOrders], $l) }}</div>
                    </div>
                </div>
                <a href="{{ route('portals.pharmacy.orders.index') }}" class="btn btn-primary btn-sm"><i data-lucide="arrow-right"></i> {{ __('pharmacy_desk.orders_open_desk', [], $l) }}</a>
            @else
                <div class="empty-state">
                    <i data-lucide="shopping-bag"></i>
                    <p>{{ __('pharmacy_desk.orders_none', [], $l) }}</p>
                </div>
            @endif
        </div>
    </div>
    @endcapability

    @capability('pharmacy_stock')
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="calendar-x"></i> {{ __('pharmacy_desk.expired_panel', [], $l) }}</h3>
            <a href="{{ route('portals.pharmacy.stock.batches') }}" class="ph-view-all">{{ __('public.portal.view_all', [], $l) }}</a>
        </div>
        <div class="panel-body">
            <div class="ph-summary">
                <div class="ph-summary__icon {{ ($stats['expired'] ?? 0) > 0 ? 'ph-summary__icon--danger' : 'ph-summary__icon--success' }}"><i data-lucide="{{ ($stats['expired'] ?? 0) > 0 ? 'alert-octagon' : 'check' }}"></i></div>
                <div>
                    <div class="ph-summary__value">{{ $stats['expired'] ?? 0 }}</div>
                    <div class="ph-summary__label">{{ __('pharmacy_desk.expired_count', ['count' => $stats['expired'] ?? 0], $l) }}</div>
                </div>
            </div>
            <a href="{{ route('portals.pharmacy.stock.batches') }}" class="btn btn-secondary btn-sm"><i data-lucide="layers"></i> {{ __('pharmacy_desk.expired_open', [], $l) }}</a>
        </div>
    </div>
    @endcapability
</div>

@endsection

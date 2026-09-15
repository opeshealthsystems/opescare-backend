@extends('layouts.portal')

@section('title', __('public.pharmacy_portal.page_title', [], app()->getLocale()) ?: 'Pharmacy Portal')

@section('sidebar_user_role', __('public.pharmacy_portal.role_label', [], app()->getLocale()) ?: 'Pharmacist')

@section('breadcrumb_home', __('public.pharmacy_portal.page_title', [], app()->getLocale()) ?: 'Pharmacy Portal')
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('public.pharmacy_portal.nav_dashboard', [], app()->getLocale()) ?: 'Dashboard')

@section('content')

<div class="page-head">
    <h2>{{ __('public.pharmacy_portal.page_heading', [], app()->getLocale()) ?: 'Pharmacy dashboard' }}</h2>
    <p class="page-subtitle">{{ __('public.pharmacy_portal.page_subtitle', [], app()->getLocale()) ?: "Today's prescription queue, stock alerts, and dispensing activity." }}</p>
    <div class="page-head__spacer"></div>
    {{-- Everything that leads to the counter is `dispense` (config/staff_capabilities.php):
         medicine_stock opens this dashboard for the stock alerts and must not be
         offered a queue its role cannot open (the routes 403 it). --}}
    @capability('dispense')
    <a href="{{ route('portals.pharmacy.orders.index') }}" class="btn btn-secondary btn-sm" data-pending-orders="{{ $pendingOrders ?? 0 }}">
        <i data-lucide="shopping-bag"></i> {{ trans_choice('pharmacy_orders.dashboard_pending', $pendingOrders ?? 0, ['count' => $pendingOrders ?? 0]) }}
    </a>
    <a href="{{ route('portals.pharmacy.prescriptions') }}" class="btn btn-primary btn-sm">
        <i data-lucide="clipboard-list"></i> {{ __('public.pharmacy_portal.btn_view_queue', [], app()->getLocale()) ?: 'View full queue' }}
    </a>
    @endcapability
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

{{-- Stat cards --}}
<div class="stat-grid mb-6">
    @capability('dispense')
    <a href="{{ route('portals.pharmacy.prescriptions') }}" class="stat-card stat-card--warning">
        <div class="stat-card__label">{{ __('public.pharmacy_portal.stat_pending_rx', [], app()->getLocale()) ?: 'Pending Rx' }}</div>
        <div class="stat-card__value">{{ $stats['pending_rx'] ?? 0 }}</div>
    </a>
    <a href="{{ route('portals.pharmacy.prescriptions', ['status' => 'dispensed']) }}" class="stat-card stat-card--success">
        <div class="stat-card__label">{{ __('public.pharmacy_portal.stat_dispensed_today', [], app()->getLocale()) ?: 'Dispensed today' }}</div>
        <div class="stat-card__value">{{ $stats['dispensed_today'] ?? 0 }}</div>
    </a>
    @endcapability
    @capability('pharmacy_stock')
    <a href="{{ route('portals.pharmacy.stock') }}" class="stat-card">
        <div class="stat-card__label">{{ __('public.pharmacy_portal.stat_drug_lines', [], app()->getLocale()) ?: 'Drug Lines' }}</div>
        <div class="stat-card__value">{{ $stats['total_drugs'] ?? 0 }}</div>
    </a>
    <a href="{{ route('portals.pharmacy.stock', ['stock_status' => 'low_stock']) }}" class="stat-card stat-card--warning">
        <div class="stat-card__label">{{ __('public.pharmacy_portal.stat_low_stock', [], app()->getLocale()) ?: 'Low Stock' }}</div>
        <div class="stat-card__value">{{ $stats['low_stock'] ?? 0 }}</div>
    </a>
    <a href="{{ route('portals.pharmacy.stock', ['stock_status' => 'out_of_stock']) }}" class="stat-card stat-card--danger">
        <div class="stat-card__label">{{ __('public.pharmacy_portal.stat_out_of_stock', [], app()->getLocale()) ?: 'Out of Stock' }}</div>
        <div class="stat-card__value">{{ $stats['out_of_stock'] ?? 0 }}</div>
    </a>
    <a href="{{ route('portals.pharmacy.stock.batches') }}" class="stat-card stat-card--danger">
        <div class="stat-card__label">{{ __('public.pharmacy_portal.stat_expired', [], app()->getLocale()) ?: 'Expired' }}</div>
        <div class="stat-card__value">{{ $stats['expired'] ?? 0 }}</div>
    </a>
    @endcapability
</div>

<div class="field-grid">

    {{-- Pending Prescriptions (dispense queue) — dispense roles only --}}
    @capability('dispense')
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="clipboard-list"></i> {{ __('public.pharmacy_portal.panel_pending_rx', [], app()->getLocale()) ?: 'Pending prescriptions' }}</h3>
            <a href="{{ route('portals.pharmacy.prescriptions') }}" class="btn btn-secondary btn-sm">{{ __('public.portal.view_all', [], app()->getLocale()) ?: 'View all' }}</a>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.portal.col_patient', [], app()->getLocale()) ?: 'Patient' }}</th>
                    <th>{{ __('public.portal.col_status', [], app()->getLocale()) ?: 'Status' }}</th>
                    <th class="row-actions"></th>
                </tr></thead>
                <tbody>
                @forelse($pendingRx as $rx)
                <tr>
                    <td data-label="{{ __('public.portal.col_patient', [], app()->getLocale()) ?: 'Patient' }}">
                        <span class="td-strong">{{ trim(($rx->patient?->first_name ?? '') . ' ' . ($rx->patient?->last_name ?? '')) ?: '—' }}</span>
                        <div class="td-muted">{{ $rx->items()->count() }} {{ __('public.pharmacy_portal.lbl_items', [], app()->getLocale()) ?: 'item(s)' }} &middot; {{ $rx->created_at?->diffForHumans() }}</div>
                    </td>
                    <td data-label="{{ __('public.portal.col_status', [], app()->getLocale()) ?: 'Status' }}"><span class="badge badge-{{ $rx->statusColor() }}">@enum($rx->status)</span></td>
                    <td class="row-actions" data-label="">
                        <form method="POST" action="{{ route('portals.pharmacy.dispense', $rx->id) }}" class="inline-form">@csrf
                            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('{{ __('public.pharmacy_portal.confirm_dispense', [], app()->getLocale()) ?: 'Mark as dispensed?' }}')">
                                {{ __('public.pharmacy_portal.btn_dispense', [], app()->getLocale()) ?: 'Dispense' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="check-circle-2" class="empty-state-icon"></i>
                        <p>{{ __('public.pharmacy_portal.no_pending_rx', [], app()->getLocale()) ?: 'All prescriptions are up to date.' }}</p>
                    </div>
                </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @endcapability

    {{-- Stock Alerts (low-stock) --}}
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="alert-triangle"></i> {{ __('public.pharmacy_portal.panel_stock_alerts', [], app()->getLocale()) ?: 'Stock alerts' }}</h3>
            <a href="{{ route('portals.pharmacy.stock') }}" class="btn btn-secondary btn-sm">{{ __('public.portal.view_all', [], app()->getLocale()) ?: 'View all' }}</a>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.pharmacy_portal.col_medicine', [], app()->getLocale()) ?: 'Medicine' }}</th>
                    <th>{{ __('public.portal.col_status', [], app()->getLocale()) ?: 'Status' }}</th>
                </tr></thead>
                <tbody>
                @forelse($alerts as $drug)
                @php $st = $drug->stock_status instanceof \BackedEnum ? $drug->stock_status->value : (string) $drug->stock_status; @endphp
                <tr>
                    <td data-label="{{ __('public.pharmacy_portal.col_medicine', [], app()->getLocale()) ?: 'Medicine' }}">
                        <span class="td-strong">{{ $drug->medicine?->name ?? '—' }}</span>
                        <div class="td-muted">{{ $drug->medicine?->generic_name }} &middot; {{ $drug->medicine?->form }} {{ $drug->medicine?->strength }}</div>
                    </td>
                    <td data-label="{{ __('public.portal.col_status', [], app()->getLocale()) ?: 'Status' }}">
                        <span class="badge badge-{{ $st === 'out_of_stock' ? 'danger' : 'warning' }}">@enum($st, 'stock_status')</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="2" class="td-muted empty-cell">{{ __('public.pharmacy_portal.no_stock_alerts', [], app()->getLocale()) ?: 'No stock alerts.' }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection

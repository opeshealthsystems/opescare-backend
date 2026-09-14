@php $l = app()->getLocale(); @endphp
{{-- medicine_stock holds pharmacy_stock only (config/staff_capabilities.php
     `$pharmacyStock`): it reports public medicine availability and keeps the
     controlled register, but never dispenses — the counter pages 403 it and
     the dashboard hides its dispense half. Both stock pages used to be
     reachable only by URL from here. --}}
<div class="sidebar-role-badge">
    <i data-lucide="package-2"></i>
    {{ __('portal.medicine_stock_role', [], $l) ?: 'Medicine Stock' }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.pharmacy.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>

@capability('pharmacy_stock')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_pharmacy', [], $l) ?: 'Pharmacy' }}</div>
    <a href="{{ route('portals.pharmacy.stock') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.stock') ? 'active' : '' }}">
        <i data-lucide="package"></i><span>{{ __('public.portal.nav_stock', [], $l) ?: 'Stock' }}</span>
    </a>
    <a href="{{ route('portals.pharmacy.controlled') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.controlled*') ? 'active' : '' }}">
        <i data-lucide="shield-alert"></i><span>{{ __('public.portal.nav_controlled', [], $l) ?: 'Controlled Substances' }}</span>
    </a>
</div>

{{-- The ledger behind the public listing: lots & expiry, the append-only
     movement log, minimum levels and deliveries (routes/pharmacy_ops.php,
     same capability as the routes). --}}
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('pharmacy_ops.nav_section', [], $l) }}</div>
    <a href="{{ route('portals.pharmacy.stock.batches') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.stock.batches*') ? 'active' : '' }}">
        <i data-lucide="layers"></i><span>{{ __('pharmacy_ops.nav_batches', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.pharmacy.stock.adjustments') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.stock.adjustments*') ? 'active' : '' }}">
        <i data-lucide="arrow-left-right"></i><span>{{ __('pharmacy_ops.nav_adjustments', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.pharmacy.stock.reorder') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.stock.reorder*') ? 'active' : '' }}">
        <i data-lucide="trending-down"></i><span>{{ __('pharmacy_ops.nav_reorder', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.pharmacy.stock.receipts') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.stock.receipts*') ? 'active' : '' }}">
        <i data-lucide="truck"></i><span>{{ __('pharmacy_ops.nav_receipts', [], $l) }}</span>
    </a>
</div>
@endcapability


{{-- Facility switching for clinicians posted at more than one facility.
     Self-gating: renders nothing unless this user holds more than one active
     posting (see partials/sidebars/_switch_facility.blade.php). --}}
@include('partials.sidebars._switch_facility')

@php $l = app()->getLocale(); @endphp
{{-- Rendered for pharmacist, pharmacy_technician and dispensing_officer. Every
     link self-gates with the same @capability as its route
     (config/staff_capabilities.php: dispense, pharmacy_stock, messaging). --}}
<div class="sidebar-role-badge">
    <i data-lucide="pill"></i>
    {{ __('portal.pharmacist_role', [], $l) }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.pharmacy.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) }}</span>
    </a>
</div>

@capability('pharmacy_stock', 'dispense', 'messaging')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_pharmacy', [], $l) }}</div>
    @capability('pharmacy_stock')
    <a href="{{ route('portals.pharmacy.stock') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.stock') ? 'active' : '' }}">
        <i data-lucide="package"></i><span>{{ __('public.portal.nav_stock', [], $l) }}</span>
    </a>
    @endcapability
    @capability('dispense')
    <a href="{{ route('portals.pharmacy.prescriptions') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.prescriptions') ? 'active' : '' }}">
        <i data-lucide="file-text"></i><span>{{ __('public.portal.nav_prescriptions', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.prescriptions.lookup') ? 'active' : '' }}">
        <i data-lucide="scan-line"></i><span>{{ __('prescriber.counter_link', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.pharmacy.dispensing') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.dispensing*') ? 'active' : '' }}">
        <i data-lucide="clipboard-check"></i><span>{{ __('pharmacy_desk.nav_dispensing', [], ) }}</span>
    </a>
    @endcapability
    @capability('pharmacy_stock')
    <a href="{{ route('portals.pharmacy.controlled') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.controlled*') ? 'active' : '' }}">
        <i data-lucide="shield-alert"></i><span>{{ __('public.portal.nav_controlled', [], $l) }}</span>
    </a>
    @endcapability
    @capability('dispense')
    <a href="{{ route('portals.pharmacy.orders.index') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.orders.*') ? 'active' : '' }}">
        <i data-lucide="shopping-bag"></i><span>{{ __('pharmacy_orders.title', [], $l) }}</span>
    </a>
    @endcapability
    @capability('pharmacy_stock')
    <a href="{{ route('portals.pharmacy.prices.index') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.prices.*') ? 'active' : '' }}">
        <i data-lucide="tags"></i><span>{{ __('pharmacy_prices.nav_prices', [], $l) }}</span>
    </a>
    @endcapability
    @capability('messaging')
    {{-- The staff inbox lives under /facility; pharmacy roles are in that
         portal bucket too (EnsurePortalAccess), so the link resolves. --}}
    <a href="{{ route('portals.staff.messages') }}" class="sidebar-link {{ request()->routeIs('portals.staff.messages*') ? 'active' : '' }}">
        <i data-lucide="messages-square"></i><span>{{ __('messaging.nav', [], $l) }}</span>
    </a>
    @endcapability
</div>
@endcapability

{{-- The ledger behind the public listing: lots & expiry, the append-only
     movement log, minimum levels and deliveries (routes/pharmacy_ops.php,
     same capability as the routes). --}}
@capability('pharmacy_stock')
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
    <a href="{{ route('portals.pharmacy.stock.suppliers') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.stock.suppliers*') ? 'active' : '' }}">
        <i data-lucide="building-2"></i><span>{{ __('pharmacy_stock_desk.nav_suppliers', [], ) }}</span>
    </a>
    <a href="{{ route('portals.pharmacy.reports') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.reports*') ? 'active' : '' }}">
        <i data-lucide="bar-chart-3"></i><span>{{ __('pharmacy_reports.nav_reports', [], ) }}</span>
    </a>
    <a href="{{ route('portals.pharmacy.stock.receipts') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.stock.receipts*') ? 'active' : '' }}">
        <i data-lucide="truck"></i><span>{{ __('pharmacy_ops.nav_receipts', [], $l) }}</span>
    </a>
</div>
@endcapability


{{-- Tasks + Help (routes/imports_tasks.php, imported from the platform).
     No capability gates these routes: every operational role has an inbox
     and may raise a ticket, so the links are not gated either. --}}
<div class="sidebar-nav-section">
    <a href="{{ route('portals.staff.tasks') }}" class="sidebar-link {{ request()->routeIs('portals.staff.tasks*') ? 'active' : '' }}">
        <i data-lucide="check-square"></i><span>{{ __('tasks.nav', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.staff.support') }}" class="sidebar-link {{ request()->routeIs('portals.staff.support*') ? 'active' : '' }}">
        <i data-lucide="life-buoy"></i><span>{{ __('support.nav', [], $l) }}</span>
    </a>
</div>

{{-- Facility switching for clinicians posted at more than one facility.
     Self-gating: renders nothing unless this user holds more than one active
     posting (see partials/sidebars/_switch_facility.blade.php). --}}
@include('partials.sidebars._switch_facility')

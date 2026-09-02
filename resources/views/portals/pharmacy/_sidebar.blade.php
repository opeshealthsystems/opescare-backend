@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="pill"></i>
    {{ __('public.pharmacy_portal.role_badge', [], $l) ?: 'Pharmacy' }}
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.pharmacy_portal.nav_section', [], $l) ?: 'Pharmacy' }}</div>
    <a href="{{ route('portals.pharmacy.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.pharmacy_portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
    <a href="{{ route('portals.pharmacy.prescriptions') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.prescriptions') ? 'active' : '' }}">
        <i data-lucide="clipboard-list"></i><span>{{ __('public.pharmacy_portal.nav_rx_queue', [], $l) ?: 'Prescription queue' }}</span>
    </a>
    {{-- Public availability: what patients see in the Medicine Finder. Not
         gated by inventory_ops — the finders ship in V1. --}}
    <a href="{{ route('portals.pharmacy.stock') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.stock') ? 'active' : '' }}">
        <i data-lucide="package"></i><span>{{ __('public.pharmacy_portal.nav_public_stock', [], $l) ?: 'Public availability' }}</span>
    </a>
    @feature('inventory_ops')
    <a href="{{ route('portals.pharmacy.inventory') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.inventory') ? 'active' : '' }}">
        <i data-lucide="boxes"></i><span>{{ __('public.pharmacy_portal.nav_inventory', [], $l) ?: 'Drug inventory' }}</span>
    </a>
    @endfeature
    <a href="{{ route('portals.pharmacy.controlled') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.controlled') ? 'active' : '' }}">
        <i data-lucide="lock"></i><span>{{ __('public.pharmacy_portal.nav_controlled', [], $l) ?: 'Controlled substances' }}</span>
    </a>
</div>
@feature('inventory_ops')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.pharmacy_portal.nav_stock_section', [], $l) ?: 'Stock management' }}</div>
    <a href="{{ route('portals.staff.inventory.pharmacy') }}" class="sidebar-link">
        <i data-lucide="archive"></i><span>{{ __('public.pharmacy_portal.nav_stock_manager', [], $l) ?: 'Full stock manager' }}</span>
    </a>
    <a href="{{ route('portals.staff.supply') }}" class="sidebar-link">
        <i data-lucide="truck"></i><span>{{ __('public.pharmacy_portal.nav_supply_chain', [], $l) ?: 'Supply chain' }}</span>
    </a>
</div>
@endfeature

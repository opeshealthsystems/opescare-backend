@php $l = app()->getLocale(); @endphp
<nav class="portal-sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i data-lucide="package"></i>
            <span>{{ __('public.supply_chain.nav_section', [], $l) ?: 'Supply Chain' }}</span>
        </div>
    </div>

    <div class="sidebar-section-label">{{ __('public.supply_chain.nav_overview_section', [], $l) ?: 'OVERVIEW' }}</div>
    <a href="{{ route('portals.staff.supply') }}"
       class="sidebar-link {{ request()->routeIs('portals.staff.supply') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.supply_chain.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>

    <div class="sidebar-section-label">{{ __('public.supply_chain.nav_catalog_section', [], $l) ?: 'CATALOG' }}</div>
    <a href="{{ route('portals.staff.supply.items') }}"
       class="sidebar-link {{ request()->routeIs('portals.staff.supply.items') ? 'active' : '' }}">
        <i data-lucide="list"></i><span>{{ __('public.supply_chain.nav_items', [], $l) ?: 'Items Catalog' }}</span>
    </a>
    <a href="{{ route('portals.staff.supply.suppliers') }}"
       class="sidebar-link {{ request()->routeIs('portals.staff.supply.suppliers') ? 'active' : '' }}">
        <i data-lucide="truck"></i><span>{{ __('public.supply_chain.nav_suppliers', [], $l) ?: 'Suppliers' }}</span>
    </a>

    <div class="sidebar-section-label">{{ __('public.supply_chain.nav_stock_section', [], $l) ?: 'STOCK' }}</div>
    <a href="{{ route('portals.staff.supply.stock') }}"
       class="sidebar-link {{ request()->routeIs('portals.staff.supply.stock') ? 'active' : '' }}">
        <i data-lucide="boxes"></i><span>{{ __('public.supply_chain.nav_stock', [], $l) ?: 'Stock Levels' }}</span>
    </a>
    <a href="{{ route('portals.staff.supply.movements') }}"
       class="sidebar-link {{ request()->routeIs('portals.staff.supply.movements') ? 'active' : '' }}">
        <i data-lucide="arrow-left-right"></i><span>{{ __('public.supply_chain.nav_movements', [], $l) ?: 'Movements' }}</span>
    </a>

    <div class="sidebar-section-label">{{ __('public.supply_chain.nav_procurement_section', [], $l) ?: 'PROCUREMENT' }}</div>
    <a href="{{ route('portals.staff.supply.purchase_orders') }}"
       class="sidebar-link {{ request()->routeIs('portals.staff.supply.purchase_orders') ? 'active' : '' }}">
        <i data-lucide="file-text"></i><span>{{ __('public.supply_chain.nav_purchase_orders', [], $l) ?: 'Purchase Orders' }}</span>
    </a>
    <a href="{{ route('portals.staff.supply.goods_receipts') }}"
       class="sidebar-link {{ request()->routeIs('portals.staff.supply.goods_receipts') ? 'active' : '' }}">
        <i data-lucide="package-check"></i><span>{{ __('public.supply_chain.nav_goods_receipts', [], $l) ?: 'Goods Receipts' }}</span>
    </a>

    <div class="sidebar-section-label">{{ __('public.supply_chain.nav_navigate_section', [], $l) ?: 'NAVIGATE' }}</div>
    <a href="{{ route('portals.staff') }}" class="sidebar-link">
        <i data-lucide="arrow-left"></i><span>{{ __('public.supply_chain.nav_staff_portal', [], $l) ?: 'Staff Portal' }}</span>
    </a>
</nav>

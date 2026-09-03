@php $l = app()->getLocale(); @endphp
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

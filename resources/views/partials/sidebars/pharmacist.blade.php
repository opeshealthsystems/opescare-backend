@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="pill"></i>
    {{ __('portal.pharmacist_role', [], $l) ?: 'Pharmacist' }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.pharmacy.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_pharmacy', [], $l) ?: 'Pharmacy' }}</div>
    <a href="{{ route('portals.pharmacy.stock') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.stock*') ? 'active' : '' }}">
        <i data-lucide="package"></i><span>{{ __('public.portal.nav_stock', [], $l) ?: 'Stock' }}</span>
    </a>
    <a href="{{ route('portals.pharmacy.prescriptions') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.prescriptions*') ? 'active' : '' }}">
        <i data-lucide="file-text"></i><span>{{ __('public.portal.nav_prescriptions', [], $l) ?: 'Prescriptions' }}</span>
    </a>
    <a href="{{ route('portals.pharmacy.controlled') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.controlled*') ? 'active' : '' }}">
        <i data-lucide="shield-alert"></i><span>{{ __('public.portal.nav_controlled', [], $l) ?: 'Controlled Substances' }}</span>
    </a>
</div>

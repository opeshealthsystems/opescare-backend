@php $l = app()->getLocale(); @endphp
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.insurance_portal.nav_section', [], $l) ?: 'Insurance' }}</div>
    <a href="{{ route('portals.insurance.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.insurance.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.insurance_portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
    <a href="{{ route('portals.insurance.providers') }}" class="sidebar-link {{ request()->routeIs('portals.insurance.providers') ? 'active' : '' }}">
        <i data-lucide="building-2"></i>
        <span>{{ __('public.insurance_portal.nav_providers', [], $l) ?: 'Providers & Plans' }}</span>
    </a>
    <a href="{{ route('portals.insurance.policies') }}" class="sidebar-link {{ request()->routeIs('portals.insurance.policies') ? 'active' : '' }}">
        <i data-lucide="shield-check"></i>
        <span>{{ __('public.insurance_portal.nav_policies', [], $l) ?: 'Patient Policies' }}</span>
    </a>
    <a href="{{ route('portals.insurance.preauths') }}" class="sidebar-link {{ request()->routeIs('portals.insurance.preauths') ? 'active' : '' }}">
        <i data-lucide="clipboard-list"></i>
        <span>{{ __('public.insurance_portal.nav_preauths', [], $l) ?: 'Preauthorization' }}</span>
    </a>
    <a href="{{ route('portals.insurance.claims') }}" class="sidebar-link {{ request()->routeIs('portals.insurance.claims') ? 'active' : '' }}">
        <i data-lucide="file-text"></i>
        <span>{{ __('public.insurance_portal.nav_claims', [], $l) ?: 'Claims' }}</span>
    </a>
</div>

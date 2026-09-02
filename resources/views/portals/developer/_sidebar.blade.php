@php $l = app()->getLocale(); @endphp
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.developer_portal.nav_section', [], $l) ?: 'Developer Portal' }}</div>
    <a href="{{ route('portals.developer.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.developer.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.developer_portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
    <a href="{{ route('portals.developer.apps') }}" class="sidebar-link {{ request()->routeIs('portals.developer.apps*') ? 'active' : '' }}">
        <i data-lucide="plug"></i><span>{{ __('public.developer_portal.nav_apps', [], $l) ?: 'My Apps' }}</span>
    </a>
    <a href="{{ route('portals.developer.production_requests') }}" class="sidebar-link {{ request()->routeIs('portals.developer.production_requests*') ? 'active' : '' }}">
        <i data-lucide="rocket"></i><span>{{ __('public.developer_portal.nav_prod_access', [], $l) ?: 'Production Access' }}</span>
    </a>
    <a href="{{ route('portals.developer.analytics') }}" class="sidebar-link {{ request()->routeIs('portals.developer.analytics') ? 'active' : '' }}">
        <i data-lucide="bar-chart-3"></i><span>{{ __('public.developer_portal.nav_analytics', [], $l) ?: 'API Analytics' }}</span>
    </a>
</div>

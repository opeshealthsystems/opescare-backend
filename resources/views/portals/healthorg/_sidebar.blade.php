@php $l = app()->getLocale(); @endphp
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.healthorg_portal.nav_org_section', [], $l) ?: 'Organization' }}</div>
    <a href="{{ route('portals.healthorg.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.healthorg.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.healthorg_portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
    <a href="{{ route('portals.healthorg.programs') }}" class="sidebar-link {{ request()->routeIs('portals.healthorg.programs') ? 'active' : '' }}">
        <i data-lucide="folder-open"></i><span>{{ __('public.healthorg_portal.nav_programs', [], $l) ?: 'Programs' }}</span>
    </a>
    <a href="{{ route('portals.healthorg.outreach') }}" class="sidebar-link {{ request()->routeIs('portals.healthorg.outreach') ? 'active' : '' }}">
        <i data-lucide="map-pin"></i><span>{{ __('public.healthorg_portal.nav_outreach', [], $l) ?: 'Outreach Sites' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.healthorg_portal.nav_public_health_section', [], $l) ?: 'Public Health' }}</div>
    <a href="{{ route('portals.healthorg.reports') }}" class="sidebar-link {{ request()->routeIs('portals.healthorg.reports') ? 'active' : '' }}">
        <i data-lucide="file-bar-chart-2"></i><span>{{ __('public.healthorg_portal.nav_reports', [], $l) ?: 'Reports' }}</span>
    </a>
    <a href="{{ route('portals.healthorg.signals') }}" class="sidebar-link {{ request()->routeIs('portals.healthorg.signals') ? 'active' : '' }}">
        <i data-lucide="activity"></i><span>{{ __('public.healthorg_portal.action_signals', [], $l) ?: 'Outbreak Signals' }}</span>
    </a>
    <a href="{{ route('public.care-map') }}" class="sidebar-link" target="_blank">
        <i data-lucide="map"></i><span>{{ __('public.portal.nav_care_map', [], $l) ?: 'Care Map' }}</span>
    </a>
</div>

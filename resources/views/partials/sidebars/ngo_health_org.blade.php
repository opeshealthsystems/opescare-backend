@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge" style="background:rgba(109,40,217,.3);border-color:rgba(109,40,217,.5);color:#C4B5FD;">
    <i data-lucide="heart-handshake" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    {{ __('portal.ngo_health_org_role', [], $l) ?: 'NGO Health Org' }}
</div>
<div style="margin-bottom:var(--p-space-3);"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_healthorg_portal', [], $l) ?: 'Health Org Portal' }}</div>
    <a href="{{ route('portals.healthorg.dashboard') }}" class="sidebar-link" style="background:rgba(245,158,11,.08);border-left:3px solid #f59e0b;">
        <i data-lucide="heart-handshake" style="color:#f59e0b;"></i>
        <span style="font-weight:700;">{{ __('public.portal.nav_healthorg_portal', [], $l) ?: 'Health Org Portal' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_administration', [], $l) ?: 'Administration' }}</div>
    <a href="{{ route('portals.healthorg.dashboard') }}" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
    <a href="{{ route('portals.healthorg.programs') }}" class="sidebar-link">
        <i data-lucide="folder-open"></i>
        <span>{{ __('public.portal.nav_programs', [], $l) ?: 'Programs' }}</span>
    </a>
    <a href="{{ route('portals.healthorg.reports') }}" class="sidebar-link">
        <i data-lucide="file-bar-chart-2"></i>
        <span>{{ __('public.portal.nav_public_health_reports', [], $l) ?: 'Public Health Reports' }}</span>
    </a>
    <a href="{{ route('portals.healthorg.signals') }}" class="sidebar-link">
        <i data-lucide="activity"></i>
        <span>{{ __('public.portal.nav_outbreak_signals', [], $l) ?: 'Outbreak Signals' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_resources', [], $l) ?: 'Resources' }}</div>
    <a href="{{ route('public.care-map') }}" class="sidebar-link" target="_blank">
        <i data-lucide="map"></i>
        <span>{{ __('public.portal.nav_care_map', [], $l) ?: 'Care Map' }}</span>
    </a>
    <a href="{{ route('public.help') }}" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span>{{ __('public.portal.nav_help', [], $l) ?: 'Help' }}</span>
    </a>
</div>

@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge" style="background:rgba(37,99,235,.2);border-color:rgba(37,99,235,.4);color:#93C5FD;">
    <i data-lucide="map-pin" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    {{ __('portal.outreach_mobile_role', [], $l) ?: 'Outreach / Mobile' }}
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
    <div class="sidebar-nav-label">{{ __('public.portal.nav_dashboard', [], $l) ?: 'Overview' }}</div>
    <a href="{{ route('portals.healthorg.dashboard') }}" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_clinical', [], $l) ?: 'Outreach' }}</div>
    <a href="{{ route('portals.healthorg.outreach') }}" class="sidebar-link">
        <i data-lucide="map-pin"></i>
        <span>{{ __('public.portal.nav_outreach_sites', [], $l) ?: 'Outreach Sites' }}</span>
    </a>
    <a href="{{ route('portals.healthorg.programs') }}" class="sidebar-link">
        <i data-lucide="folder-open"></i>
        <span>{{ __('public.portal.nav_programs', [], $l) ?: 'Programs' }}</span>
    </a>
    <a href="{{ route('portals.healthorg.reports') }}" class="sidebar-link">
        <i data-lucide="file-bar-chart-2"></i>
        <span>{{ __('public.portal.nav_health_reports', [], $l) ?: 'Health Reports' }}</span>
    </a>
    <a href="{{ route('public.care-map') }}" class="sidebar-link" target="_blank">
        <i data-lucide="map"></i>
        <span>{{ __('public.portal.nav_care_map', [], $l) ?: 'Care Map' }}</span>
    </a>
    <a href="{{ route('public.help') }}" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span>{{ __('public.portal.nav_help', [], $l) ?: 'Help' }}</span>
    </a>
</div>

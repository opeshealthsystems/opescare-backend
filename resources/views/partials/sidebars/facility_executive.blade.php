@php $l = app()->getLocale(); @endphp
{{-- Facility executive (CEO): same two accessible surfaces as facility_admin —
     the facility-scoped dashboard (/admin) and team management (/facility/team).
     The clinical operations desk is closed to this tier (EnsurePortalAccess). --}}
<div class="sidebar-role-badge">
    <i data-lucide="crown"></i>
    {{ __('portal.facility_executive_role', [], $l) ?: 'Facility Executive' }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.admin') }}" class="sidebar-link {{ request()->routeIs('portals.admin') && !request()->routeIs('portals.admin.*') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_facility', [], $l) ?: 'Facility' }}</div>
    <a href="{{ route('portals.facility.team') }}" class="sidebar-link {{ request()->routeIs('portals.facility.team*') ? 'active' : '' }}">
        <i data-lucide="users"></i><span>{{ __('public.portal.nav_team', [], $l) ?: 'Team' }}</span>
    </a>
</div>

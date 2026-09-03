@php $l = app()->getLocale(); @endphp
{{-- Facility admins reach exactly two surfaces: the facility-scoped dashboard
     (bare /admin — AdminPortalController scopes its stats to their facility;
     everything under /admin/* is platform god-mode and 403s) and team
     management at /facility/team. The clinical operations desk (/facility) is
     deliberately closed to them (EnsurePortalAccess), so it is NOT linked. --}}
<div class="sidebar-role-badge">
    <i data-lucide="building-2"></i>
    {{ __('portal.facility_admin_role', [], $l) ?: 'Facility Admin' }}
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

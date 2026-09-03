@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="network"></i>
    {{ __('portal.multi_facility_admin_role', [], $l) ?: 'Multi-Facility Admin' }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.admin') }}" class="sidebar-link {{ request()->routeIs('portals.admin') && !request()->routeIs('portals.admin.*') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_administration', [], $l) ?: 'Administration' }}</div>
    <a href="{{ route('portals.facility.team') }}" class="sidebar-link {{ request()->routeIs('portals.facility.team*') ? 'active' : '' }}">
        <i data-lucide="users"></i>
        <span>{{ __('team.page_title', [], $l) ?: 'My Team' }}</span>
    </a>
    <a href="{{ route('select-facility') }}" class="sidebar-link {{ request()->routeIs('select-facility') ? 'active' : '' }}">
        <i data-lucide="repeat"></i>
        <span>{{ __('public.portal.nav_switch_facility', [], $l) ?: 'Switch Facility' }}</span>
    </a>
</div>


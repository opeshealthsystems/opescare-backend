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
    @capability('facility_team')
    <a href="{{ route('portals.facility.admin.overview') }}" class="sidebar-link {{ request()->routeIs('portals.facility.admin.*') ? 'active' : '' }}">
        <i data-lucide="gauge"></i><span>{{ __('facility_admin.nav_overview', [], $l) }}</span>
    </a>
    @endcapability
    @capability('service_catalogue')
    <a href="{{ route('portals.facility.settings.index') }}" class="sidebar-link {{ request()->routeIs('portals.facility.settings.*') ? 'active' : '' }}">
        <i data-lucide="building-2"></i><span>{{ __('facility_settings.nav', [], $l) }}</span>
    </a>
    @endcapability
    <a href="{{ route('portals.facility.team') }}" class="sidebar-link {{ request()->routeIs('portals.facility.team*') ? 'active' : '' }}">
        <i data-lucide="users"></i><span>{{ __('public.portal.nav_team', [], $l) ?: 'Team' }}</span>
    </a>
    {{-- "What we offer": the facility's own service list, drawn from the
         institutional code dictionary. Self-gates on the same capability the
         route does, so a role without it sees no dead link. --}}
    @capability('service_catalogue')
    <a href="{{ route('portals.facility.catalogue') }}" class="sidebar-link {{ request()->routeIs('portals.facility.catalogue*') ? 'active' : '' }}">
        <i data-lucide="clipboard-list"></i><span>{{ __('catalogue.nav', [], $l) }}</span>
    </a>
    @endcapability
</div>


{{-- Facility switching for clinicians posted at more than one facility.
     Self-gating: renders nothing unless this user holds more than one active
     posting (see partials/sidebars/_switch_facility.blade.php). --}}
@include('partials.sidebars._switch_facility')

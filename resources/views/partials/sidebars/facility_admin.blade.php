@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="building-2"></i>
    {{ __('portal.facility_admin_role', [], $l) ?: 'Facility Admin' }}
</div>
<div class="sidebar-spacer"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_administration', [], $l) ?: 'My Facility' }}</div>
    <a href="{{ route('portals.admin') }}" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
    <a href="{{ route('portals.facility.team') }}" class="sidebar-link {{ request()->routeIs('portals.facility.team*') ? 'active' : '' }}">
        <i data-lucide="users"></i>
        <span>{{ __('team.page_title', [], $l) ?: 'My Team' }}</span>
    </a>
    <a href="{{ route('portals.listing.edit') }}" class="sidebar-link {{ request()->routeIs('portals.listing.*') ? 'active' : '' }}">
        <i data-lucide="map-pin"></i>
        <span>{{ __('caremap_claim.nav_my_listing', [], $l) }}</span>
    </a>
    @platformadmin
        @endplatformadmin
    @platformadmin
        @endplatformadmin
    <a href="{{ route('select-facility') }}" class="sidebar-link">
        <i data-lucide="repeat"></i>
        <span>{{ __('public.portal.nav_switch_facility', [], $l) ?: 'Switch Facility' }}</span>
    </a>
</div>
{{-- Every link in this section is platform-tier, so the section is guarded
     as a whole: leaving the heading outside the guard renders a bare
     'People' label with nothing beneath it for a facility admin.

     These particular pages remain platform-only under RequirePlatformAdmin,
     because they span every facility. The facility-scoped equivalent that was
     missing now exists for staff: "My Team" above (portals/facility/team) lets
     a facility admin invite and manage their own clinicians. Patient
     management still has no facility-scoped equivalent. --}}
@platformadmin
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_people', [], $l) ?: 'People' }}</div>
        </div>
@endplatformadmin
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_operations', [], $l) ?: 'Operations' }}</div>
    <a href="{{ route('portals.staff.appointments') }}" class="sidebar-link">
        <i data-lucide="calendar-check-2"></i>
        <span>{{ __('public.portal.nav_appointments', [], $l) ?: 'Appointments' }}</span>
    </a>
        </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_support', [], $l) ?: 'Support' }}</div>
    <a href="{{ route('public.help') }}" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span>{{ __('public.portal.nav_help', [], $l) ?: 'Help' }}</span>
    </a>
</div>

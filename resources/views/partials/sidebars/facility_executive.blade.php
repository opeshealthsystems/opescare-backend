@php $l = app()->getLocale(); @endphp
{{-- Facility executive (CEO): same two accessible surfaces as facility_admin —
     the facility-scoped dashboard (/admin) and team management (/facility/team).
     The clinical operations desk is closed to this tier (EnsurePortalAccess). --}}
<div class="sidebar-role-badge">
    <i data-lucide="crown"></i>
    {{ __('portal.facility_executive_role', [], $l) }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.admin') }}" class="sidebar-link {{ request()->routeIs('portals.admin') && !request()->routeIs('portals.admin.*') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_facility', [], $l) }}</div>
    @capability('facility_team')
    <a href="{{ route('portals.facility.admin.overview') }}" class="sidebar-link {{ request()->routeIs('portals.facility.admin.*') ? 'active' : '' }}">
        <i data-lucide="gauge"></i><span>{{ __('facility_admin.nav_overview', [], $l) }}</span>
    </a>
    {{-- Read-only invoices and the free-text department grouping: both live
         under the overview and share its capability (routes/facility_admin.php). --}}
    <a href="{{ route('portals.facility.admin.invoices') }}" class="sidebar-link sidebar-link--sub {{ request()->routeIs('portals.facility.admin.invoices*') ? 'active' : '' }}">
        <i data-lucide="receipt-text"></i><span>{{ __('facility_admin.nav_invoices', [], $l) }}</span>
    </a>
    @php
        // Same guard as facility_admin: wards only where patients are
        // admitted (or wards already exist); a diagnostic or imaging centre
        // admits nobody and has no departments to group, so both hide there.
        $__fid = app(\App\Services\Portal\PortalContextService::class)->facilityId();
        $__type = \App\Services\DiagnosticCentre\DiagnosticCentreDashboardService::facilityType($__fid);
        $__isCentre = \App\Services\DiagnosticCentre\DiagnosticCentreDashboardService::appliesToType($__type);
        $__showWards = ! $__isCentre && (in_array($__type, ['hospital', 'maternity', 'health_center'], true)
            || ($__fid && \Illuminate\Support\Facades\DB::table('wards')->where('facility_id', $__fid)->exists()));
    @endphp
    @unless($__isCentre)
    <a href="{{ route('portals.facility.admin.departments') }}" class="sidebar-link sidebar-link--sub {{ request()->routeIs('portals.facility.admin.departments') ? 'active' : '' }}">
        <i data-lucide="layout-grid"></i><span>{{ __('facility_admin.nav_departments', [], $l) }}</span>
    </a>
    @endunless
    <a href="{{ route('portals.facility.admin.reports') }}" class="sidebar-link sidebar-link--sub {{ request()->routeIs('portals.facility.admin.reports*') ? 'active' : '' }}">
        <i data-lucide="bar-chart-3"></i><span>{{ __('facility_admin.nav_reports', [], $l) }}</span>
    </a>
    @if($__showWards)
    <a href="{{ route('portals.facility.admin.wards') }}" class="sidebar-link sidebar-link--sub {{ request()->routeIs('portals.facility.admin.wards*') ? 'active' : '' }}">
        <i data-lucide="bed"></i><span>{{ __('facility_admin.nav_wards', [], $l) }}</span>
    </a>
    @endif
    @endcapability
    @capability('service_catalogue')
    <a href="{{ route('portals.facility.settings.index') }}" class="sidebar-link {{ request()->routeIs('portals.facility.settings.*') ? 'active' : '' }}">
        <i data-lucide="building-2"></i><span>{{ __('facility_settings.nav', [], $l) }}</span>
    </a>
    @endcapability
    <a href="{{ route('portals.facility.team') }}" class="sidebar-link {{ request()->routeIs('portals.facility.team*') ? 'active' : '' }}">
        <i data-lucide="users"></i><span>{{ __('public.portal.nav_team', [], $l) }}</span>
    </a>
    {{-- "What we offer": the facility's own service list, drawn from the
         institutional code dictionary. Self-gates on the same capability the
         route does, so a role without it sees no dead link. --}}
    @capability('service_catalogue')
    <a href="{{ route('portals.facility.catalogue') }}" class="sidebar-link {{ request()->routeIs('portals.facility.catalogue*') ? 'active' : '' }}">
        <i data-lucide="book-open"></i><span>{{ __('catalogue.nav', [], $l) }}</span>
    </a>
    @endcapability
    @capability('queue')
    <a href="{{ route('portals.queue.index') }}" class="sidebar-link {{ request()->routeIs('portals.queue.*') ? 'active' : '' }}">
        <i data-lucide="list-ordered"></i><span>{{ __('queue_desk.nav', [], $l) }}</span>
    </a>
    @endcapability
</div>


{{-- Tasks + Help (routes/imports_tasks.php). No capability gates these
     routes: every operational role has an inbox and may raise a ticket. --}}
<div class="sidebar-nav-section">
    <a href="{{ route('portals.staff.tasks') }}" class="sidebar-link {{ request()->routeIs('portals.staff.tasks*') ? 'active' : '' }}">
        <i data-lucide="check-square"></i><span>{{ __('tasks.nav', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.staff.support') }}" class="sidebar-link {{ request()->routeIs('portals.staff.support*') ? 'active' : '' }}">
        <i data-lucide="life-buoy"></i><span>{{ __('support.nav', [], $l) }}</span>
    </a>
</div>

{{-- Facility switching for clinicians posted at more than one facility.
     Self-gating: renders nothing unless this user holds more than one active
     posting (see partials/sidebars/_switch_facility.blade.php). --}}
@include('partials.sidebars._switch_facility')

@php $l = app()->getLocale(); @endphp
{{-- Facility admins reach exactly two surfaces: the facility-scoped dashboard
     (bare /admin — AdminPortalController scopes its stats to their facility;
     everything under /admin/* is platform god-mode and 403s) and team
     management at /facility/team. The clinical operations desk (/facility) is
     deliberately closed to them (EnsurePortalAccess), so it is NOT linked. --}}
<div class="sidebar-role-badge">
    <i data-lucide="building-2"></i>
    {{ __('portal.facility_admin_role', [], $l) }}
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
    <a href="{{ route('portals.facility.admin.invoices') }}" class="sidebar-link sidebar-link--sub {{ request()->routeIs('portals.facility.admin.invoices') ? 'active' : '' }}">
        <i data-lucide="receipt-text"></i><span>{{ __('facility_admin.nav_invoices', [], $l) }}</span>
    </a>
    @php
        // Wards and beds only mean something where patients are admitted. A
        // clinic, lab, dental or eye practice has none; the link was noise.
        // A facility that has recorded wards keeps the link whatever its type.
        // A diagnostic or imaging centre (I-7) admits nobody and has no
        // departments to group either: both links are hidden there.
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
    {{-- The centre landing (routes/diagnostic.php): lab + imaging on one page,
         only where this facility IS a diagnostic or imaging centre. --}}
    @if($__isCentre)
    @capability('lab_results', 'imaging')
    <a href="{{ route('portals.diagnostic.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.diagnostic.dashboard') ? 'active' : '' }}">
        <i data-lucide="scan-line"></i><span>{{ __('diagnostic_desk.nav', [], $l) }}</span>
    </a>
    @endcapability
    @endif
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
    <a href="{{ route('verify.health-id') }}" class="sidebar-link {{ request()->routeIs('verify.health-id*') ? 'active' : '' }}">
        <i data-lucide="scan-line"></i><span>{{ __('facility_admin_home.nav_verify_health_id', [], $l) }}</span>
    </a>
    @capability('service_catalogue')
    <a href="{{ route('portals.facility.catalogue') }}" class="sidebar-link {{ request()->routeIs('portals.facility.catalogue*') ? 'active' : '' }}">
        <i data-lucide="clipboard-list"></i><span>{{ __('catalogue.nav', [], $l) }}</span>
    </a>
    @endcapability
    {{-- The patient queue: today's measured wait times are the number a
         facility administrator answers for. Gated by the same `queue`
         capability as its routes (config/staff_capabilities.php). --}}
    @capability('queue')
    <a href="{{ route('portals.queue.index') }}" class="sidebar-link {{ request()->routeIs('portals.queue.*') ? 'active' : '' }}">
        <i data-lucide="list-ordered"></i><span>{{ __('queue_desk.nav', [], $l) }}</span>
    </a>
    @endcapability
</div>


{{-- Tasks + Help (routes/imports_tasks.php, imported from the platform).
     No capability gates these routes: every operational role has an inbox
     and may raise a ticket, so the links are not gated either. --}}
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

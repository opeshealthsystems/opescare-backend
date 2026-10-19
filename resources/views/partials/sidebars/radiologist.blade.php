@php $l = app()->getLocale(); @endphp
{{-- Rendered for radiologist. Every link self-gates with the same @capability as
     its route (config/staff_capabilities.php, routes/imaging_desk.php).
     Deliberately NO blood bank and NO sample collection: this is the imaging
     half of a diagnostic centre, not the bench. --}}
<div class="sidebar-role-badge">
    <i data-lucide="scan-eye"></i>
    {{ __('portal.radiologist_role', [], $l) }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.staff') }}" class="sidebar-link {{ request()->routeIs('portals.staff') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.staff.search') }}" class="sidebar-link {{ request()->routeIs('portals.staff.search') ? 'active' : '' }}">
        <i data-lucide="search"></i><span>{{ __('public.staff_portal.page_heading_search', [], $l) }}</span>
    </a>
</div>

@capability('imaging', 'messaging')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('imaging_desk.title', [], $l) }}</div>
    {{-- The centre landing (routes/diagnostic.php): lab + imaging on one page,
         only where the facility IS a centre (same guard as lab_manager). --}}
    @php $__diagFid = app(\App\Services\Portal\PortalContextService::class)->facilityId(); @endphp
    @if(\App\Services\DiagnosticCentre\DiagnosticCentreDashboardService::appliesTo($__diagFid))
    @capability('lab_results', 'imaging')
    <a href="{{ route('portals.diagnostic.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.diagnostic.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-grid"></i><span>{{ __('diagnostic_desk.nav', [], $l) }}</span>
    </a>
    @endcapability
    @endif
    @capability('imaging')
    {{-- One entry for the desk: the worklist is its default view. --}}
    <a href="{{ route('portals.imaging.index') }}" class="sidebar-link {{ request()->routeIs('portals.imaging.index', 'portals.imaging.show') ? 'active' : '' }}">
        <i data-lucide="scan-search"></i><span>{{ __('imaging_desk.nav', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.imaging.create') }}" class="sidebar-link {{ request()->routeIs('portals.imaging.create') ? 'active' : '' }}">
        <i data-lucide="plus-circle"></i><span>{{ __('imaging_desk.nav_new', [], $l) }}</span>
    </a>
    @endcapability
    @capability('imaging_report')
    <a href="{{ route('portals.imaging.reports.index') }}" class="sidebar-link {{ request()->routeIs('portals.imaging.reports.index') ? 'active' : '' }}">
        <i data-lucide="file-text"></i><span>{{ __('imaging_desk.nav_reports', [], $l) }}</span>
    </a>
    @endcapability
    @capability('messaging')
    <a href="{{ route('portals.staff.messages') }}" class="sidebar-link {{ request()->routeIs('portals.staff.messages*') ? 'active' : '' }}">
        <i data-lucide="messages-square"></i>
        <span>{{ __('messaging.nav', [], $l) }}</span>
    </a>
    @endcapability
</div>
@endcapability

{{-- Tasks + Help (routes/imports_tasks.php). No capability gates these routes. --}}
<div class="sidebar-nav-section">
    <a href="{{ route('portals.staff.tasks') }}" class="sidebar-link {{ request()->routeIs('portals.staff.tasks*') ? 'active' : '' }}">
        <i data-lucide="check-square"></i><span>{{ __('tasks.nav', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.staff.support') }}" class="sidebar-link {{ request()->routeIs('portals.staff.support*') ? 'active' : '' }}">
        <i data-lucide="life-buoy"></i><span>{{ __('support.nav', [], $l) }}</span>
    </a>
</div>

@include('partials.sidebars._switch_facility')

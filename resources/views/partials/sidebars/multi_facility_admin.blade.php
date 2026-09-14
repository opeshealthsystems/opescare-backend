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
    {{-- The route is gated by capability:facility_team. Unguarded, this link
         was right only while branch_admin happened to hold that capability;
         point another role at this partial and it becomes a 403 in the nav. --}}
    @capability('facility_team')
    <a href="{{ route('portals.facility.admin.overview') }}" class="sidebar-link {{ request()->routeIs('portals.facility.admin.*') ? 'active' : '' }}">
        <i data-lucide="gauge"></i><span>{{ __('facility_admin.nav_overview', [], $l) }}</span>
    </a>
    {{-- Read-only invoices and the free-text department grouping: both live
         under the overview and share its capability (routes/facility_admin.php). --}}
    <a href="{{ route('portals.facility.admin.invoices') }}" class="sidebar-link sidebar-link--sub {{ request()->routeIs('portals.facility.admin.invoices') ? 'active' : '' }}">
        <i data-lucide="receipt-text"></i><span>{{ __('facility_admin.nav_invoices', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.facility.admin.departments') }}" class="sidebar-link sidebar-link--sub {{ request()->routeIs('portals.facility.admin.departments') ? 'active' : '' }}">
        <i data-lucide="layout-grid"></i><span>{{ __('facility_admin.nav_departments', [], $l) }}</span>
    </a>
    @endcapability
    @capability('service_catalogue')
    <a href="{{ route('portals.facility.settings.index') }}" class="sidebar-link {{ request()->routeIs('portals.facility.settings.*') ? 'active' : '' }}">
        <i data-lucide="building-2"></i><span>{{ __('facility_settings.nav', [], $l) }}</span>
    </a>
    @endcapability
    @capability('facility_team')
    <a href="{{ route('portals.facility.team') }}" class="sidebar-link {{ request()->routeIs('portals.facility.team*') ? 'active' : '' }}">
        <i data-lucide="users"></i>
        <span>{{ __('team.page_title', [], $l) ?: 'My Team' }}</span>
    </a>
    @endcapability
    @capability('service_catalogue')
    <a href="{{ route('portals.facility.catalogue') }}" class="sidebar-link {{ request()->routeIs('portals.facility.catalogue*') ? 'active' : '' }}">
        <i data-lucide="clipboard-list"></i><span>{{ __('catalogue.nav', [], $l) }}</span>
    </a>
    @endcapability
    @capability('queue')
    <a href="{{ route('portals.queue.index') }}" class="sidebar-link {{ request()->routeIs('portals.queue.*') ? 'active' : '' }}">
        <i data-lucide="list-ordered"></i><span>{{ __('queue_desk.nav', [], $l) }}</span>
    </a>
    @endcapability
</div>

{{-- Self-gating: renders nothing unless this user holds more than one active posting. --}}
@include('partials.sidebars._switch_facility')


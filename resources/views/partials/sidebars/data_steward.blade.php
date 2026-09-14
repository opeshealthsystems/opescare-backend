@php $l = app()->getLocale(); @endphp
{{-- Data steward is a platform-tier role landing on the staff portal dashboard.
     Its analytics / data-quality surfaces are not present in the finder (the
     feature block was empty), so the sidebar is just the dashboard. --}}
<div class="sidebar-role-badge">
    <i data-lucide="database-zap"></i>
    {{ __('portal.data_steward_role', [], $l) ?: 'Data Steward' }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.staff') }}" class="sidebar-link {{ request()->routeIs('portals.staff') && !request()->routeIs('portals.staff.*') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
    {{-- Global search (facilities, documents, medicines, lab tests, partners,
         messages — never patients). The screen existed, complete, with no
         route: the audit called it unreachable. --}}
    <a href="{{ route('portals.staff.search') }}" class="sidebar-link {{ request()->routeIs('portals.staff.search') ? 'active' : '' }}">
        <i data-lucide="search"></i><span>{{ __('public.staff_portal.page_heading_search', [], $l) ?: 'Global Search' }}</span>
    </a>
</div>


{{-- Facility switching for clinicians posted at more than one facility.
     Self-gating: renders nothing unless this user holds more than one active
     posting (see partials/sidebars/_switch_facility.blade.php). --}}
@include('partials.sidebars._switch_facility')

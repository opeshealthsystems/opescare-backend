@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="git-fork"></i>
    {{ __('portal.dependent_manager_role', [], $l) }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.patient') }}" class="sidebar-link {{ request()->routeIs('portals.patient') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_my_health', [], $l) }}</div>
    <a href="{{ route('portals.guardian.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.guardian.dashboard') ? 'active' : '' }}">
        <i data-lucide="baby"></i>
        <span>{{ __('public.portal.nav_dependents', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.patient.appointments') }}" class="sidebar-link">
        <i data-lucide="calendar-check-2"></i>
        <span>{{ __('public.portal.nav_appointments', [], $l) }}</span>
    </a>
</div>

@include('partials.sidebars._patient_records')

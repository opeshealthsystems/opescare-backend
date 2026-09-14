@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="flask-conical"></i>
    {{ __('portal.demo_role', [], $l) ?: 'Demo Mode' }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.staff') }}" class="sidebar-link {{ request()->routeIs('portals.staff') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_clinical', [], $l) ?: 'Clinical' }}</div>
    <a href="{{ route('portals.staff.appointments') }}" class="sidebar-link">
        <i data-lucide="calendar-check-2"></i>
        <span>{{ __('public.portal.nav_appointments', [], $l) ?: 'Appointments' }}</span>
    </a>
    <a href="{{ route('portals.staff.appointments.waitlist') }}" class="sidebar-link {{ request()->routeIs('portals.staff.appointments.waitlist') ? 'active' : '' }}">
        <i data-lucide="list-ordered"></i><span>{{ __('waitlist.staff_title', [], $l) }}</span>
    </a>
</div>

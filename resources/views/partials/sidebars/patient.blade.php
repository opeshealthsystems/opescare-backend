@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="user"></i>
    {{ __('public.portal.patient_role', [], $l) }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.patient') }}" class="sidebar-link {{ request()->routeIs('portals.patient') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_care', [], $l) }}</div>
    <a href="{{ route('portals.patient.appointments') }}" class="sidebar-link {{ request()->routeIs('portals.patient.appointments*') ? 'active' : '' }}">
        <i data-lucide="calendar-check-2"></i><span>{{ __('public.portal.nav_appointments', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.patient.waitlist') }}" class="sidebar-link {{ request()->routeIs('portals.patient.waitlist*') ? 'active' : '' }}">
        <i data-lucide="hourglass"></i><span>{{ __('waitlist.nav', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.patient.prescriptions') }}" class="sidebar-link {{ request()->routeIs('portals.patient.prescriptions*') ? 'active' : '' }}">
        <i data-lucide="pill"></i><span>{{ __('public.portal.nav_prescriptions', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.patient.family') }}" class="sidebar-link {{ request()->routeIs('portals.patient.family*') ? 'active' : '' }}">
        <i data-lucide="users"></i><span>{{ __('public.portal.nav_family', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.patient.messages') }}" class="sidebar-link {{ request()->routeIs('portals.patient.messages*') ? 'active' : '' }}">
        <i data-lucide="messages-square"></i><span>{{ __('public.portal.nav_messages', [], $l) }}</span>
    </a>
</div>

@include('partials.sidebars._patient_records')

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_account', [], $l) }}</div>
    {{-- Both pages were built and reachable by URL only. Notification
         preferences live inside the notifications page. --}}
    <a href="{{ route('portals.patient.notifications') }}" class="sidebar-link {{ request()->routeIs('portals.patient.notifications*') ? 'active' : '' }}">
        <i data-lucide="bell"></i><span>{{ __('public.portal.nav_notifications', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.patient.refer') }}" class="sidebar-link {{ request()->routeIs('portals.patient.refer*') ? 'active' : '' }}">
        <i data-lucide="gift"></i><span>{{ __('referral.title', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.patient.settings') }}" class="sidebar-link {{ request()->routeIs('portals.patient.settings') ? 'active' : '' }}">
        <i data-lucide="settings"></i><span>{{ __('public.portal.nav_settings', [], $l) }}</span>
    </a>
</div>

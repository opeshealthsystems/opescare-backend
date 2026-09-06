@php $l = app()->getLocale(); @endphp
{{-- The 'resident' role. Every link self-gates with the same @capability as
     its route (config/staff_capabilities.php: residents are `$doctors`). --}}
<div class="sidebar-role-badge">
    <i data-lucide="stethoscope"></i>
    {{ __('portal.resident_doctor_role', [], $l) ?: 'Resident Doctor' }}
</div>
<div class="sidebar-spacer"></div>
<div class="sidebar-nav-section">
    <a href="{{ route('portals.staff') }}" class="sidebar-link {{ request()->routeIs('portals.staff') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>
@capability('front_desk', 'schedule', 'prescribe', 'blood_inventory', 'messaging')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_clinical', [], $l) ?: 'Clinical' }}</div>
    @capability('front_desk', 'schedule')
    <a href="{{ route('portals.staff.appointments') }}" class="sidebar-link {{ request()->routeIs('portals.staff.appointments*') ? 'active' : '' }}">
        <i data-lucide="calendar-check-2"></i>
        <span>{{ __('public.portal.nav_appointments', [], $l) ?: 'Appointments' }}</span>
    </a>
    @endcapability
    @capability('prescribe')
    <a href="{{ route('portals.prescriber.index') }}" class="sidebar-link {{ request()->routeIs('portals.prescriber.*') ? 'active' : '' }}">
        <i data-lucide="file-text"></i>
        <span>{{ __('prescriber.nav', [], $l) ?: 'Prescriptions' }}</span>
    </a>
    @endcapability
    @capability('blood_inventory')
    <a href="{{ route('portals.staff.inventory.blood') }}" class="sidebar-link {{ request()->routeIs('portals.staff.inventory.blood*') ? 'active' : '' }}">
        <i data-lucide="droplet"></i>
        <span>{{ __('public.portal.nav_blood_inventory', [], $l) ?: 'Blood Inventory' }}</span>
    </a>
    @endcapability
    @capability('messaging')
    <a href="{{ route('portals.staff.messages') }}" class="sidebar-link {{ request()->routeIs('portals.staff.messages*') ? 'active' : '' }}">
        <i data-lucide="messages-square"></i>
        <span>{{ __('messaging.nav', [], $l) ?: 'Messages' }}</span>
    </a>
    @endcapability
</div>
@endcapability


{{-- Facility switching for clinicians posted at more than one facility.
     Self-gating: renders nothing unless this user holds more than one active
     posting (see partials/sidebars/_switch_facility.blade.php). --}}
@include('partials.sidebars._switch_facility')

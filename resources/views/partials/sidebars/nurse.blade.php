@php $l = app()->getLocale(); @endphp
{{-- Every link is gated by the SAME capability as its route (routes/web.php,
     config/staff_capabilities.php) through @capability, so the sidebar can
     never offer a page the role 403s on. Nurses do not hold `prescribe`: the
     prescriber-desk link that used to sit here was a 403 waiting to happen. --}}
<div class="sidebar-role-badge">
    <i data-lucide="heart-pulse"></i>
    {{ __('portal.nurse_role', [], $l) ?: 'Nurse' }}
</div>
<div class="sidebar-spacer"></div>
<div class="sidebar-nav-section">
    <a href="{{ route('portals.staff') }}" class="sidebar-link {{ request()->routeIs('portals.staff') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>
@capability('front_desk', 'schedule', 'blood_inventory', 'messaging')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_clinical', [], $l) ?: 'Clinical' }}</div>
    @capability('front_desk', 'schedule')
    <a href="{{ route('portals.staff.appointments') }}" class="sidebar-link {{ request()->routeIs('portals.staff.appointments*') ? 'active' : '' }}">
        <i data-lucide="calendar-check-2"></i><span>{{ __('public.portal.nav_appointments', [], $l) ?: 'Appointments' }}</span>
    </a>
    @endcapability
    @capability('blood_inventory')
    <a href="{{ route('portals.staff.inventory.blood') }}" class="sidebar-link {{ request()->routeIs('portals.staff.inventory.blood*') ? 'active' : '' }}">
        <i data-lucide="droplet"></i><span>{{ __('public.portal.nav_blood_inventory', [], $l) ?: 'Blood Inventory' }}</span>
    </a>
    @endcapability
    @capability('messaging')
    <a href="{{ route('portals.staff.messages') }}" class="sidebar-link {{ request()->routeIs('portals.staff.messages*') ? 'active' : '' }}">
        <i data-lucide="messages-square"></i><span>{{ __('messaging.nav', [], $l) ?: 'Messages' }}</span>
    </a>
    @endcapability
</div>
@endcapability


{{-- Facility switching for clinicians posted at more than one facility.
     Self-gating: renders nothing unless this user holds more than one active
     posting (see partials/sidebars/_switch_facility.blade.php). --}}
@include('partials.sidebars._switch_facility')

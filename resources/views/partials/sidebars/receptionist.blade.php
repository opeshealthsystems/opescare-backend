@php $l = app()->getLocale(); @endphp
{{-- Front desk = the clinical operations desk (portals.staff → /facility).
     A receptionist runs appointments, blood inventory and messages for their
     own facility.

     SHARED PARTIAL: this is also the sidebar for front_desk, cashier,
     billing_officer, finance_manager and finance (RolesSeeder). The billing
     roles hold only `billing` in config/staff_capabilities.php — no
     appointments, no blood, no messages — so every link here self-gates with
     the SAME @capability as its route. What a cashier sees is the dashboard
     and nothing that would 403. (There is no billing page on the web yet; that
     is a product gap, not something to link.) The prescriber-desk link that
     used to be here is gone: no front-desk role holds `prescribe`. --}}
<div class="sidebar-role-badge">
    <i data-lucide="monitor"></i>
    {{ __('portal.receptionist_role', [], $l) ?: 'Receptionist' }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.staff') }}" class="sidebar-link {{ request()->routeIs('portals.staff') && !request()->routeIs('portals.staff.*') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>

@capability('front_desk', 'schedule', 'blood_inventory', 'messaging')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_operations', [], $l) ?: 'Operations' }}</div>
    @capability('front_desk', 'schedule')
    <a href="{{ route('portals.staff.appointments') }}" class="sidebar-link {{ request()->routeIs('portals.staff.appointments*') ? 'active' : '' }}">
        <i data-lucide="calendar-check-2"></i><span>{{ __('public.portal.nav_appointments', [], $l) ?: 'Appointments' }}</span>
    </a>
    @endcapability
    @capability('blood_inventory')
    <a href="{{ route('portals.staff.inventory.blood') }}" class="sidebar-link {{ request()->routeIs('portals.staff.inventory.blood*') ? 'active' : '' }}">
        <i data-lucide="droplet"></i><span>{{ __('public.portal.nav_blood_inventory', [], $l) ?: 'Blood Inventory' }}</span>
    </a>
    {{-- The queue of patients asking this blood bank for units. Sits beside
         the shelf it is asked against, behind the same capability. --}}
    <a href="{{ route('portals.staff.blood_requests.index') }}" class="sidebar-link {{ request()->routeIs('portals.staff.blood_requests.*') ? 'active' : '' }}">
        <i data-lucide="clipboard-list"></i><span>{{ __('blood_desk.nav', [], $l) ?: 'Blood Requests' }}</span>
    </a>
    @endcapability
    @capability('messaging')
    <a href="{{ route('portals.staff.messages') }}" class="sidebar-link {{ request()->routeIs('portals.staff.messages*') ? 'active' : '' }}">
        <i data-lucide="messages-square"></i><span>{{ __('public.portal.nav_messages', [], $l) ?: 'Messages' }}</span>
    </a>
    @endcapability
</div>
@endcapability


{{-- Facility switching for clinicians posted at more than one facility.
     Self-gating: renders nothing unless this user holds more than one active
     posting (see partials/sidebars/_switch_facility.blade.php). --}}
@include('partials.sidebars._switch_facility')

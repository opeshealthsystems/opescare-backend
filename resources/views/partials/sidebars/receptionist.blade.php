@php $l = app()->getLocale(); @endphp
{{-- Front desk = the clinical operations desk (portals.staff → /facility).
     A receptionist runs appointments, blood inventory and messages for their
     own facility.

     SHARED PARTIAL: this is also the sidebar for front_desk, cashier,
     billing_officer, finance_manager and finance (RolesSeeder). The billing
     roles hold only `billing` in config/staff_capabilities.php — no
     appointments, no blood, no messages — so every link here self-gates with
     the SAME @capability as its route. What a cashier sees is the dashboard
     and nothing that would 403 — plus the billing desk below, which is what
     the billing roles are here for. The prescriber-desk link that
     used to be here is gone: no front-desk role holds `prescribe`. --}}
<div class="sidebar-role-badge">
    <i data-lucide="monitor"></i>
    {{ __('portal.receptionist_role', [], $l) }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.staff') }}" class="sidebar-link {{ request()->routeIs('portals.staff') && !request()->routeIs('portals.staff.*') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) }}</span>
    </a>
    {{-- Global search (facilities, documents, medicines, lab tests, partners,
         messages — never patients). The screen existed, complete, with no
         route: the audit called it unreachable. --}}
    <a href="{{ route('portals.staff.search') }}" class="sidebar-link {{ request()->routeIs('portals.staff.search') ? 'active' : '' }}">
        <i data-lucide="search"></i><span>{{ __('public.staff_portal.page_heading_search', [], $l) }}</span>
    </a>
</div>

@capability('queue', 'front_desk', 'schedule', 'blood_inventory', 'messaging')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_operations', [], $l) }}</div>
    {{-- The patient queue: the waiting room, gated by the same `queue`
         capability as its routes (config/staff_capabilities.php). --}}
    @capability('queue')
    <a href="{{ route('portals.queue.index') }}" class="sidebar-link {{ request()->routeIs('portals.queue.*') ? 'active' : '' }}">
        <i data-lucide="list-ordered"></i><span>{{ __('queue_desk.nav', [], $l) }}</span>
    </a>
    @endcapability
    @capability('front_desk', 'schedule')
    <a href="{{ route('portals.staff.appointments') }}" class="sidebar-link {{ request()->routeIs('portals.staff.appointments*') ? 'active' : '' }}">
        <i data-lucide="calendar-check-2"></i><span>{{ __('public.portal.nav_appointments', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.staff.appointments.waitlist') }}" class="sidebar-link {{ request()->routeIs('portals.staff.appointments.waitlist') ? 'active' : '' }}">
        <i data-lucide="hourglass"></i><span>{{ __('waitlist.staff_title', [], $l) }}</span>
    </a>
    @endcapability
    @capability('blood_inventory')
    <a href="{{ route('portals.staff.inventory.blood') }}" class="sidebar-link {{ request()->routeIs('portals.staff.inventory.blood*') ? 'active' : '' }}">
        <i data-lucide="droplet"></i><span>{{ __('public.portal.nav_blood_inventory', [], $l) }}</span>
    </a>
    {{-- The queue of patients asking this blood bank for units. Sits beside
         the shelf it is asked against, behind the same capability. --}}
    <a href="{{ route('portals.staff.blood_requests.index') }}" class="sidebar-link {{ request()->routeIs('portals.staff.blood_requests.*') ? 'active' : '' }}">
        <i data-lucide="clipboard-list"></i><span>{{ __('blood_desk.nav', [], $l) }}</span>
    </a>
    @endcapability
    @capability('messaging')
    <a href="{{ route('portals.staff.messages') }}" class="sidebar-link {{ request()->routeIs('portals.staff.messages*') ? 'active' : '' }}">
        <i data-lucide="messages-square"></i><span>{{ __('messaging.nav', [], $l) }}</span>
    </a>
    @endcapability
</div>
@endcapability

{{-- The billing / cashier desk (routes/billing.php), gated by the same
     `billing` capability as its routes. Held by cashier, billing_officer,
     finance_manager, finance and the front desk. Refund sign-off is a
     separate capability (`refund_approve`) and its link self-gates too.
     Nothing on this desk gates care: an unpaid invoice changes no queue,
     no priority, no dispensing and no result. --}}
@capability('billing')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('billing_desk.title', [], $l) }}</div>
    <a href="{{ route('portals.billing.index') }}" class="sidebar-link {{ request()->routeIs('portals.billing.index', 'portals.billing.create', 'portals.billing.show', 'portals.billing.receipt') ? 'active' : '' }}">
        <i data-lucide="receipt"></i><span>{{ __('billing_desk.nav', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.billing.sessions') }}" class="sidebar-link {{ request()->routeIs('portals.billing.sessions*') ? 'active' : '' }}">
        <i data-lucide="wallet"></i><span>{{ __('billing_desk.nav_sessions', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.billing.refunds') }}" class="sidebar-link {{ request()->routeIs('portals.billing.refunds*') ? 'active' : '' }}">
        <i data-lucide="undo-2"></i><span>{{ __('billing_desk.nav_refunds', [], $l) }}</span>
    </a>
</div>
@endcapability


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

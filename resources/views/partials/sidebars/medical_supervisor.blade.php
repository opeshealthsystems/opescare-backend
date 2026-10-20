@php $l = app()->getLocale(); @endphp
{{-- A medical school supervisor prescribes, sees the schedule, the blood bank
     and messages (config/staff_capabilities.php `$supervisors`). Every link
     self-gates with the same @capability as its route. --}}
<div class="sidebar-role-badge">
    <i data-lucide="user-check"></i>
    {{ __('portal.medical_supervisor_role', [], $l) }}
</div>
<div class="sidebar-spacer"></div>
<div class="sidebar-nav-section">
    <a href="{{ route('portals.staff') }}" class="sidebar-link {{ request()->routeIs('portals.staff') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], $l) }}</span>
    </a>
    {{-- Global search (facilities, documents, medicines, lab tests, partners,
         messages — never patients). The screen existed, complete, with no
         route: the audit called it unreachable. --}}
    <a href="{{ route('portals.staff.search') }}" class="sidebar-link {{ request()->routeIs('portals.staff.search') ? 'active' : '' }}">
        <i data-lucide="search"></i><span>{{ __('public.staff_portal.page_heading_search', [], $l) }}</span>
    </a>
</div>
@capability('clinical_chart', 'queue', 'triage', 'front_desk', 'schedule', 'prescribe', 'imaging', 'blood_inventory', 'messaging')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_clinical', [], $l) }}</div>
    {{-- The patient chart: the read-only record behind `clinical_chart`,
         which every clinical role held and which gated no web screen until
         routes/chart.php. Record-level access is ClinicalRelationshipGate's. --}}
    @capability('clinical_chart')
    <a href="{{ route('portals.chart.index') }}" class="sidebar-link {{ request()->routeIs('portals.chart.*') ? 'active' : '' }}">
        <i data-lucide="folder-heart"></i><span>{{ __('chart.nav', [], $l) }}</span>
    </a>
    @endcapability
    @capability('clinical_chart')
    <a href="{{ route('portals.immunisations.desk') }}" class="sidebar-link {{ request()->routeIs('portals.immunisations.*') ? 'active' : '' }}">
        <i data-lucide="syringe"></i><span>{{ __('immunisation_desk.nav', [], $l) }}</span>
    </a>
    @endcapability
    @capability('clinical_chart')
    <a href="{{ route('portals.referrals.index') }}" class="sidebar-link {{ request()->routeIs('portals.referrals.*') ? 'active' : '' }}">
        <i data-lucide="arrow-right-left"></i><span>{{ __('referral_desk.nav', [], $l) }}</span>
    </a>
    @endcapability
    {{-- Triage. The first surface over the triage schema; gated by the same
         `triage` capability as its routes (config/staff_capabilities.php). --}}
    @capability('triage')
    <a href="{{ route('portals.triage.index') }}" class="sidebar-link {{ request()->routeIs('portals.triage.*') ? 'active' : '' }}">
        <i data-lucide="siren"></i><span>{{ __('triage.nav', [], $l) }}</span>
    </a>
    @endcapability
    {{-- The patient queue: the waiting room, gated by the same `queue`
         capability as its routes (config/staff_capabilities.php). --}}
    @capability('queue')
    <a href="{{ route('portals.queue.index') }}" class="sidebar-link {{ request()->routeIs('portals.queue.*') ? 'active' : '' }}">
        <i data-lucide="list-ordered"></i><span>{{ __('queue_desk.nav', [], $l) }}</span>
    </a>
    @endcapability
    @capability('front_desk', 'schedule')
    <a href="{{ route('portals.staff.appointments') }}" class="sidebar-link {{ request()->routeIs('portals.staff.appointments*') ? 'active' : '' }}">
        <i data-lucide="calendar-check-2"></i>
        <span>{{ __('public.portal.nav_appointments', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.staff.appointments.waitlist') }}" class="sidebar-link {{ request()->routeIs('portals.staff.appointments.waitlist') ? 'active' : '' }}">
        <i data-lucide="hourglass"></i><span>{{ __('waitlist.staff_title', [], $l) }}</span>
    </a>
    @endcapability
    @capability('prescribe')
    <a href="{{ route('portals.prescriber.index') }}" class="sidebar-link {{ request()->routeIs('portals.prescriber.*') ? 'active' : '' }}">
        <i data-lucide="file-text"></i>
        <span>{{ __('prescriber.nav', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.certificates.index') }}" class="sidebar-link {{ request()->routeIs('portals.certificates.*') ? 'active' : '' }}">
        <i data-lucide="file-badge"></i>
        <span>{{ __('doc_ex_cert.nav', [], $l) }}</span>
    </a>
    @endcapability
    {{-- Imaging referral and the desk (routes/imaging_desk.php, capability:imaging). --}}
    @capability('imaging')
    <a href="{{ route('portals.imaging.index') }}" class="sidebar-link {{ request()->routeIs('portals.imaging.*') ? 'active' : '' }}">
        <i data-lucide="scan"></i><span>{{ __('imaging_desk.title', [], $l) }}</span>
    </a>
    @endcapability
    @capability('blood_inventory')
    <a href="{{ route('portals.staff.inventory.blood') }}" class="sidebar-link {{ request()->routeIs('portals.staff.inventory.blood*') ? 'active' : '' }}">
        <i data-lucide="droplet"></i>
        <span>{{ __('public.portal.nav_blood_inventory', [], $l) }}</span>
    </a>
    {{-- The queue of patients asking this blood bank for units. Sits beside
         the shelf it is asked against, behind the same capability. --}}
    <a href="{{ route('portals.staff.blood_requests.index') }}" class="sidebar-link {{ request()->routeIs('portals.staff.blood_requests.*') ? 'active' : '' }}">
        <i data-lucide="clipboard-list"></i><span>{{ __('blood_desk.nav', [], $l) }}</span>
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


{{-- Tasks + Help (routes/imports_tasks.php). No capability gates these
     routes: every operational role has an inbox and may raise a ticket, so
     the links are not gated either (EnsurePortalAccess admits this role to
     facility/tasks and facility/support). --}}
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

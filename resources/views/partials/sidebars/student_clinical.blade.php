@php $l = app()->getLocale(); @endphp
{{-- Rendered for student_doctor, student_nurse and intern (`$trainees`):
     schedule and messaging, no prescribing. Links self-gate with the same
     @capability as their routes. --}}
<div class="sidebar-role-badge">
    <i data-lucide="graduation-cap"></i>
    {{ __('portal.student_clinical_role', [], $l) }}
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
@capability('clinical_chart', 'front_desk', 'schedule', 'messaging')
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
    @capability('front_desk', 'schedule')
    <a href="{{ route('portals.staff.appointments') }}" class="sidebar-link {{ request()->routeIs('portals.staff.appointments*') ? 'active' : '' }}">
        <i data-lucide="calendar-check-2"></i>
        <span>{{ __('public.portal.nav_appointments', [], $l) }}</span>
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


{{-- Facility switching for clinicians posted at more than one facility.
     Self-gating: renders nothing unless this user holds more than one active
     posting (see partials/sidebars/_switch_facility.blade.php). --}}
@include('partials.sidebars._switch_facility')

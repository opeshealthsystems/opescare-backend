@php $l = app()->getLocale(); @endphp
{{-- Compliance auditor is a platform-tier role landing on the admin dashboard
     (portals.admin). Historical governance surfaces (audit explorer, security
     ops) were pruned from the finder, and Help lives in the top bar, so the
     sidebar is just the dashboard. --}}
<div class="sidebar-role-badge">
    <i data-lucide="clipboard-check"></i>
    {{ __('portal.compliance_audit_role', [], $l) ?: 'Compliance Auditor' }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.admin') }}" class="sidebar-link {{ request()->routeIs('portals.admin') && !request()->routeIs('portals.admin.*') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>


{{-- Facility switching for clinicians posted at more than one facility.
     Self-gating: renders nothing unless this user holds more than one active
     posting (see partials/sidebars/_switch_facility.blade.php). --}}
@include('partials.sidebars._switch_facility')

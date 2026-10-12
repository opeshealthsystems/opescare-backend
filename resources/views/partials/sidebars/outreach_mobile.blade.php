@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="map-pin"></i>
    {{ __('portal.outreach_mobile_role', [], $l) }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.admin') }}" class="sidebar-link {{ request()->routeIs('portals.admin') && !request()->routeIs('portals.admin.*') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], $l) }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_clinical', [], $l) }}</div>
    <a href="{{ route('public.care-map') }}" class="sidebar-link" target="_blank">
        <i data-lucide="map"></i>
        <span>{{ __('public.portal.nav_care_map', [], $l) }}</span>
    </a>
</div>


{{-- Facility switching for clinicians posted at more than one facility.
     Self-gating: renders nothing unless this user holds more than one active
     posting (see partials/sidebars/_switch_facility.blade.php). --}}
@include('partials.sidebars._switch_facility')

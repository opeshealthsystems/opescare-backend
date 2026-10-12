@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="heart-handshake"></i>
    {{ __('portal.ngo_health_org_role', [], $l) }}
</div>
<div class="sidebar-spacer"></div>

{{-- Health-org and administration portals are not wired in the finder build,
     so only the public resources this role can actually reach are shown. --}}
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_resources', [], $l) }}</div>
    <a href="{{ route('public.care-map') }}" class="sidebar-link" target="_blank">
        <i data-lucide="map"></i><span>{{ __('public.portal.nav_care_map', [], $l) }}</span>
    </a>
</div>

@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="test-tube-2"></i>
    {{ __('portal.sample_collection_role', [], $l) ?: 'Sample Collection' }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.staff') }}" class="sidebar-link {{ request()->routeIs('portals.staff') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>

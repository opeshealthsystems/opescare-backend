@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="test-tube-2"></i>
    {{ __('portal.sample_collection_role', [], $l) ?: 'Sample Collection' }}
</div>
<div class="sidebar-spacer"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_lab_portal', [], $l) ?: 'Lab Portal' }}</div>
    </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_dashboard', [], $l) ?: 'Overview' }}</div>
    <a href="{{ route('portals.staff') }}" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_lab', [], $l) ?: 'Laboratory' }}</div>
        @feature('inventory_ops')
        @endfeature
    </div>

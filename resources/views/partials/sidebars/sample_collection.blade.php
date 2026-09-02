@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge" style="background:rgba(37,99,235,.2);border-color:rgba(37,99,235,.4);color:#93C5FD;">
    <i data-lucide="test-tube-2" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    {{ __('portal.sample_collection_role', [], $l) ?: 'Sample Collection' }}
</div>
<div style="margin-bottom:var(--p-space-3);"></div>
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

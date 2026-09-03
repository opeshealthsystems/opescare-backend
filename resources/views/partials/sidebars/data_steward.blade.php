@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="database-zap"></i>
    {{ __('portal.data_steward_role', [], $l) ?: 'Data Steward' }}
</div>
<div class="sidebar-spacer"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_dashboard', [], $l) ?: 'Overview' }}</div>
    <a href="{{ route('portals.staff') }}" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_administration', [], $l) ?: 'Data Quality' }}</div>
        @feature('analytics_dashboards')
    @endfeature
    </div>

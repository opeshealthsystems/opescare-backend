@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge" style="background:rgba(37,99,235,.2);border-color:rgba(37,99,235,.4);color:#93C5FD;">
    <i data-lucide="user-check" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    {{ __('portal.medical_supervisor_role', [], $l) ?: 'Medical Supervisor' }}
</div>
<div style="margin-bottom:var(--p-space-3);"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_dashboard', [], $l) ?: 'Overview' }}</div>
    <a href="{{ route('portals.staff') }}" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_clinical', [], $l) ?: 'Clinical' }}</div>
                    @feature('clinical_decision_support')
    @endfeature
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_operations', [], $l) ?: 'Operations' }}</div>
    @feature('analytics_dashboards')
    @endfeature
    </div>

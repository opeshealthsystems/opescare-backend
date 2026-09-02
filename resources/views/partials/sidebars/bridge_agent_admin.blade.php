@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge" style="background:rgba(109,40,217,.3);border-color:rgba(109,40,217,.5);color:#C4B5FD;">
    <i data-lucide="link-2" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    {{ __('portal.bridge_agent_admin_role', [], $l) ?: 'Bridge Agent Admin' }}
</div>
<div style="margin-bottom:var(--p-space-3);"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_administration', [], $l) ?: 'Administration' }}</div>
    <a href="{{ route('portals.admin') }}" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
    @platformadmin
    <a href="{{ route('portals.admin.kpi.index') }}" class="sidebar-link">
        <i data-lucide="trending-up"></i>
        <span>{{ __('public.portal.nav_kpi', [], $l) ?: 'KPI Dashboard' }}</span>
    </a>
    @endplatformadmin
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_bridge', [], $l) ?: 'Bridge Agents' }}</div>
    @platformadmin
    <a href="{{ route('portals.admin.bridge') }}" class="sidebar-link">
        <i data-lucide="link-2"></i>
        <span>{{ __('public.portal.nav_bridge', [], $l) ?: 'Bridge Agents' }}</span>
    </a>
    @endplatformadmin
    <a href="{{ route('public.help') }}" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span>{{ __('public.portal.nav_help', [], $l) ?: 'Help' }}</span>
    </a>
</div>

@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="code-2"></i>
    {{ __('portal.developer_role', [], $l) ?: 'Developer' }}
</div>
<div class="sidebar-spacer"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_dashboard', [], $l) ?: 'Overview' }}</div>
    <a href="{{ route('portals.admin') }}" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_developer_accounts', [], $l) ?: 'Developer' }}</div>
    @platformadmin
        @endplatformadmin
    @platformadmin
        @endplatformadmin
    @platformadmin
        @endplatformadmin
    @platformadmin
        @endplatformadmin
    <a href="{{ route('public.help') }}" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span>{{ __('public.portal.nav_help', [], $l) ?: 'Help' }}</span>
    </a>
</div>

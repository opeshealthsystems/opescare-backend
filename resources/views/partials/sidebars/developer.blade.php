@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge" style="background:rgba(245,158,11,.2);border-color:rgba(245,158,11,.4);color:#FCD34D;">
    <i data-lucide="code-2" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    {{ __('portal.developer_role', [], $l) ?: 'Developer' }}
</div>
<div style="margin-bottom:var(--p-space-3);"></div>
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
    <a href="{{ route('portals.admin.security') }}" class="sidebar-link">
        <i data-lucide="terminal"></i>
        <span>{{ __('public.portal.nav_apps', [], $l) ?: 'My Apps' }}</span>
    </a>
    @endplatformadmin
    @platformadmin
    <a href="{{ route('portals.admin.security') }}" class="sidebar-link">
        <i data-lucide="webhook"></i>
        <span>{{ __('public.portal.nav_webhooks', [], $l) ?: 'Webhooks' }}</span>
    </a>
    @endplatformadmin
    @platformadmin
    <a href="{{ route('portals.admin.security') }}" class="sidebar-link">
        <i data-lucide="file-code-2"></i>
        <span>{{ __('public.portal.nav_code_mappings', [], $l) ?: 'Code Mappings' }}</span>
    </a>
    @endplatformadmin
    @platformadmin
    <a href="{{ route('portals.admin.legal') }}" class="sidebar-link">
        <i data-lucide="scale"></i>
        <span>{{ __('public.portal.nav_legal', [], $l) ?: 'Legal Documents' }}</span>
    </a>
    @endplatformadmin
    <a href="{{ route('public.help') }}" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span>{{ __('public.portal.nav_help', [], $l) ?: 'Help' }}</span>
    </a>
</div>

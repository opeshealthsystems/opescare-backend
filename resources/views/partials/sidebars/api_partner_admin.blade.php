@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge" style="background:rgba(245,158,11,.2);border-color:rgba(245,158,11,.4);color:#FCD34D;">
    <i data-lucide="plug-zap" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    {{ __('portal.api_partner_admin_role', [], $l) ?: 'API Partner Admin' }}
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
    <div class="sidebar-nav-label">{{ __('public.portal.nav_connect', [], $l) ?: 'Connect Portal' }}</div>
    <a href="{{ route('portals.developer.apps') }}" class="sidebar-link">
        <i data-lucide="terminal"></i>
        <span>{{ __('public.portal.nav_apps', [], $l) ?: 'My Apps' }}</span>
    </a>
    <a href="{{ route('portals.developer.apps') }}" class="sidebar-link">
        <i data-lucide="webhook"></i>
        <span>{{ __('public.portal.nav_webhooks', [], $l) ?: 'Webhooks' }}</span>
    </a>
    <a href="{{ route('portals.developer.production_requests') }}" class="sidebar-link">
        <i data-lucide="send"></i>
        <span>{{ __('public.portal.nav_production_req', [], $l) ?: 'Production Requests' }}</span>
    </a>
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

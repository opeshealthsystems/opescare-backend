@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="plug-zap"></i>
    {{ __('portal.api_partner_admin_role', [], $l) ?: 'API Partner Admin' }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.admin') }}" class="sidebar-link {{ request()->routeIs('portals.admin') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_connect', [], $l) ?: 'Connect Portal' }}</div>
    <a href="{{ route('portals.developer.apps') }}" class="sidebar-link {{ request()->routeIs('portals.developer.apps*') ? 'active' : '' }}">
        <i data-lucide="terminal"></i><span>{{ __('public.portal.nav_apps', [], $l) ?: 'My Apps' }}</span>
    </a>
    <a href="{{ route('portals.developer.apps') }}" class="sidebar-link">
        <i data-lucide="webhook"></i><span>{{ __('public.portal.nav_webhooks', [], $l) ?: 'Webhooks' }}</span>
    </a>
    <a href="{{ route('portals.developer.production_requests') }}" class="sidebar-link {{ request()->routeIs('portals.developer.production_requests*') ? 'active' : '' }}">
        <i data-lucide="send"></i><span>{{ __('public.portal.nav_production_req', [], $l) ?: 'Production Requests' }}</span>
    </a>
</div>

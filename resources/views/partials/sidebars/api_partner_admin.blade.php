@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="plug-zap"></i>
    {{ __('portal.api_partner_admin_role', [], $l) }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    {{-- Developer-tier roles live under /developer. This pointed at /admin, which
         EnsurePortalAccess silently redirected back to /developer — a Dashboard
         link that never showed a dashboard. --}}
    <a href="{{ route('portals.developer.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.developer.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_connect', [], $l) }}</div>
    <a href="{{ route('portals.developer.apps') }}" class="sidebar-link {{ request()->routeIs('portals.developer.apps*') ? 'active' : '' }}">
        <i data-lucide="terminal"></i><span>{{ __('public.portal.nav_apps', [], $l) }}</span>
    </a>
    {{-- portals.developer.webhook_deliveries needs a {clientId}; linking it bare threw a
         UrlGenerationException on every page (docs/DEVELOPER_DASHBOARD_AUDIT.md D-1). --}}
    <a href="{{ route('portals.developer.webhooks') }}" class="sidebar-link {{ request()->routeIs('portals.developer.webhook*') ? 'active' : '' }}">
        <i data-lucide="webhook"></i><span>{{ __('public.portal.nav_webhooks', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.developer.consents') }}" class="sidebar-link {{ request()->routeIs('portals.developer.consents') ? 'active' : '' }}">
        <i data-lucide="shield-check"></i><span>{{ __('developer_ext.nav_consents', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.developer.production_requests') }}" class="sidebar-link {{ request()->routeIs('portals.developer.production_requests*') ? 'active' : '' }}">
        <i data-lucide="send"></i><span>{{ __('public.portal.nav_production_req', [], $l) }}</span>
    </a>
</div>

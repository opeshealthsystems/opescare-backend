@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="code-2"></i>
    {{ __('portal.developer_role', [], $l) }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.developer.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.developer.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_developer', [], $l) }}</div>
    <a href="{{ route('portals.developer.apps') }}" class="sidebar-link {{ request()->routeIs('portals.developer.apps*') ? 'active' : '' }}">
        <i data-lucide="app-window"></i><span>{{ __('public.portal.nav_apps', [], $l) }}</span>
    </a>
    {{-- portals.developer.webhook_deliveries needs a {clientId}; linking it bare threw a
         UrlGenerationException on every developer page (docs/DEVELOPER_DASHBOARD_AUDIT.md D-1). --}}
    <a href="{{ route('portals.developer.webhooks') }}" class="sidebar-link {{ request()->routeIs('portals.developer.webhook*') ? 'active' : '' }}">
        <i data-lucide="webhook"></i><span>{{ __('developer_ext.nav_webhooks', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.developer.activity') }}" class="sidebar-link {{ request()->routeIs('portals.developer.activity') ? 'active' : '' }}">
        <i data-lucide="activity"></i><span>{{ __('developer_ext.nav_activity', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.developer.consents') }}" class="sidebar-link {{ request()->routeIs('portals.developer.consents') ? 'active' : '' }}">
        <i data-lucide="shield-check"></i><span>{{ __('developer_ext.nav_consents', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.developer.quickstart') }}" class="sidebar-link {{ request()->routeIs('portals.developer.quickstart') ? 'active' : '' }}">
        <i data-lucide="terminal"></i><span>{{ __('developer_ext.nav_quickstart', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.developer.analytics') }}" class="sidebar-link {{ request()->routeIs('portals.developer.analytics*') ? 'active' : '' }}">
        <i data-lucide="bar-chart-3"></i><span>{{ __('public.portal.nav_analytics', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.developer.production_requests') }}" class="sidebar-link {{ request()->routeIs('portals.developer.production_requests*') ? 'active' : '' }}">
        <i data-lucide="rocket"></i><span>{{ __('public.portal.nav_production_requests', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.developer.onboard') }}" class="sidebar-link {{ request()->routeIs('portals.developer.onboard*') ? 'active' : '' }}">
        <i data-lucide="graduation-cap"></i><span>{{ __('public.portal.nav_onboarding', [], $l) }}</span>
    </a>
</div>

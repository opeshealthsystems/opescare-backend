@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="webhook"></i>
    {{ __('portal.webhook_manager_role', [], $l) ?: 'Webhook Manager' }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    {{-- Developer-tier roles live under /developer. This pointed at /admin, which
         EnsurePortalAccess silently redirected back to /developer — a Dashboard
         link that never showed a dashboard. --}}
    <a href="{{ route('portals.developer.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.developer.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_developer_accounts', [], $l) ?: 'Developer' }}</div>
    <a href="{{ route('portals.developer.webhook_deliveries') }}" class="sidebar-link {{ request()->routeIs('portals.developer.webhook_deliveries*') ? 'active' : '' }}">
        <i data-lucide="webhook"></i><span>{{ __('public.portal.nav_webhooks', [], $l) ?: 'Webhooks' }}</span>
    </a>
    <a href="{{ route('portals.developer.apps') }}" class="sidebar-link {{ request()->routeIs('portals.developer.apps*') ? 'active' : '' }}">
        <i data-lucide="terminal"></i><span>{{ __('public.portal.nav_apps', [], $l) ?: 'My Apps' }}</span>
    </a>
</div>

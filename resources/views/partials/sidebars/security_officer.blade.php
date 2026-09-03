@php $l = app()->getLocale(); @endphp
{{-- Security officer is a platform-tier role landing on the admin dashboard.
     Its historical security-ops surfaces were pruned from the finder, and Help
     lives in the top bar, so the sidebar is just the dashboard. --}}
<div class="sidebar-role-badge">
    <i data-lucide="lock"></i>
    {{ __('portal.security_officer_role', [], $l) ?: 'Security Officer' }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.admin') }}" class="sidebar-link {{ request()->routeIs('portals.admin') && !request()->routeIs('portals.admin.*') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>

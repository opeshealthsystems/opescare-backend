@php $l = app()->getLocale(); @endphp
{{-- Platform-tier role. It lands on the admin dashboard and holds the whole
     platform console by tier, but until now its sidebar linked ONLY the
     dashboard: the desks this role exists for were built, reachable by URL,
     and never linked — a reviewer who did not know the address had no job
     to do. The links below are the role's actual work. --}}
<div class="sidebar-role-badge">
    <i data-lucide="alert-triangle"></i>
    {{ __('portal.emergency_access_reviewer_role', [], $l) ?: 'Emergency Access Reviewer' }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.admin') }}" class="sidebar-link {{ request()->routeIs('portals.admin') && !request()->routeIs('portals.admin.*') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.admin.security.emergency_access') }}" class="sidebar-link {{ request()->routeIs('portals.admin.security.emergency_access*') ? 'active' : '' }}">
        <i data-lucide="alert-triangle"></i><span>{{ __('security_ops.nav_emergency_access', [], $l) ?: 'Emergency access reviews' }}</span>
    </a>
    <a href="{{ route('portals.admin.security.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.security.index*') ? 'active' : '' }}">
        <i data-lucide="shield-alert"></i><span>{{ __('security_ops.nav', [], $l) ?: 'Security oversight' }}</span>
    </a>
</div>

@include('partials.sidebars._switch_facility')

@php $l = app()->getLocale(); @endphp
{{-- Platform-tier role. It lands on the admin dashboard and holds the whole
     platform console by tier, but until now its sidebar linked ONLY the
     dashboard: the desks this role exists for were built, reachable by URL,
     and never linked — a reviewer who did not know the address had no job
     to do. The links below are the role's actual work. --}}
<div class="sidebar-role-badge">
    <i data-lucide="eye-off"></i>
    {{ __('portal.privacy_officer_role', [], $l) }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.admin') }}" class="sidebar-link {{ request()->routeIs('portals.admin') && !request()->routeIs('portals.admin.*') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.admin.governance.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.governance.index*') ? 'active' : '' }}">
        <i data-lucide="scale"></i><span>{{ __('governance_desk.nav', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.admin.legal.complaints') }}" class="sidebar-link {{ request()->routeIs('portals.admin.legal.complaints*') ? 'active' : '' }}">
        <i data-lucide="message-square-warning"></i><span>{{ __('public.adm_legal_btn_complaints', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.admin.legal.closures') }}" class="sidebar-link {{ request()->routeIs('portals.admin.legal.closures*') ? 'active' : '' }}">
        <i data-lucide="archive"></i><span>{{ __('public.adm_legal_btn_closures', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.admin.legal.minor_transitions') }}" class="sidebar-link {{ request()->routeIs('portals.admin.legal.minor_transitions*') ? 'active' : '' }}">
        <i data-lucide="user-round-cog"></i><span>{{ __('public.adm_legal_btn_minor_transitions', [], $l) }}</span>
    </a>
</div>

@include('partials.sidebars._switch_facility')

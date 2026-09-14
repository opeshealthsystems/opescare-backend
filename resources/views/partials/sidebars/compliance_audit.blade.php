@php $l = app()->getLocale(); @endphp
{{-- Platform-tier role. It lands on the admin dashboard and holds the whole
     platform console by tier, but until now its sidebar linked ONLY the
     dashboard: the desks this role exists for were built, reachable by URL,
     and never linked — a reviewer who did not know the address had no job
     to do. The links below are the role's actual work. --}}
<div class="sidebar-role-badge">
    <i data-lucide="clipboard-check"></i>
    {{ __('portal.compliance_audit_role', [], $l) ?: 'Compliance Auditor' }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.admin') }}" class="sidebar-link {{ request()->routeIs('portals.admin') && !request()->routeIs('portals.admin.*') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.admin.cc.audit') }}" class="sidebar-link {{ request()->routeIs('portals.admin.cc.audit*') ? 'active' : '' }}">
        <i data-lucide="scroll-text"></i><span>{{ __('public.adm_cc_sidebar_admin_action_log', [], $l) ?: 'Audit Log' }}</span>
    </a>
    <a href="{{ route('portals.admin.security.audit_explorer') }}" class="sidebar-link {{ request()->routeIs('portals.admin.security.audit_explorer*') ? 'active' : '' }}">
        <i data-lucide="search-code"></i><span>{{ __('security_ops.nav_audit_explorer', [], $l) ?: 'Audit explorer' }}</span>
    </a>
    <a href="{{ route('portals.admin.governance.log') }}" class="sidebar-link {{ request()->routeIs('portals.admin.governance.log*') ? 'active' : '' }}">
        <i data-lucide="file-check"></i><span>{{ __('governance_desk.nav_log', [], $l) ?: 'Decision log' }}</span>
    </a>
</div>

@include('partials.sidebars._switch_facility')

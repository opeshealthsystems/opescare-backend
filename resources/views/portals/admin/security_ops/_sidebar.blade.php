@section('sidebar_role_badge')
<div class="sidebar-role-badge sidebar-role-badge--danger">
    <i data-lucide="shield-alert"></i>
    {{ __('public.adm_secops_sidebar_badge') }}
</div>
@endsection
@section('sidebar_user_role', __('public.adm_secops_sidebar_user_role'))

@section('sidebar_nav')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_secops_sidebar_section_overview') }}</div>
    <a href="{{ route('portals.admin') }}" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.adm_secops_sidebar_governance_portal') }}</span>
    </a>
    <a href="{{ route('portals.admin.security') }}" class="sidebar-link {{ request()->routeIs('portals.admin.security') && !request()->routeIs('portals.admin.security.*') ? 'active' : '' }}">
        <i data-lucide="shield-alert"></i><span>{{ __('public.adm_secops_sidebar_soc_dashboard') }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_secops_sidebar_section_security') }}</div>
    <a href="{{ route('portals.admin.security.incidents') }}" class="sidebar-link {{ request()->routeIs('portals.admin.security.incidents*') ? 'active' : '' }}">
        <i data-lucide="file-warning"></i><span>{{ __('public.adm_secops_sidebar_incidents') }}</span>
    </a>
    <a href="{{ route('portals.admin.security.emergency_access') }}" class="sidebar-link {{ request()->routeIs('portals.admin.security.emergency_access') ? 'active' : '' }}">
        <i data-lucide="siren"></i><span>{{ __('public.adm_secops_sidebar_emergency_access') }}</span>
    </a>
    <a href="{{ route('portals.admin.security.audit_explorer') }}" class="sidebar-link {{ request()->routeIs('portals.admin.security.audit_explorer') ? 'active' : '' }}">
        <i data-lucide="search-code"></i><span>{{ __('public.adm_secops_sidebar_audit_explorer') }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_secops_sidebar_section_admin') }}</div>
    <a href="{{ route('portals.admin.cc') }}" class="sidebar-link">
        <i data-lucide="settings-2"></i><span>{{ __('public.adm_secops_sidebar_control_center') }}</span>
    </a>
</div>
@endsection

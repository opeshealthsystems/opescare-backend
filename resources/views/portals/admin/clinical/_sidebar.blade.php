@section('sidebar_role_badge')
<div class="sidebar-role-badge sidebar-role-badge--primary">
    <i data-lucide="hospital"></i>
    {{ __('public.adm_clin_sidebar_role_badge') }}
</div>
@endsection
@section('sidebar_user_role', __('public.adm_clin_sidebar_user_role'))

@section('sidebar_nav')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_clin_sidebar_section_overview') }}</div>
    <a href="{{ route('portals.admin') }}" class="sidebar-link {{ request()->routeIs('portals.admin') && !request()->routeIs('portals.admin.clinical*') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.adm_clin_sidebar_link_dashboard') }}</span>
    </a>
    <a href="{{ route('portals.admin.go-live') }}" class="sidebar-link {{ request()->routeIs('portals.admin.go-live') ? 'active' : '' }}">
        <i data-lucide="rocket"></i><span>{{ __('public.adm_clin_sidebar_link_golive') }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_clin_sidebar_section_clinical') }}</div>
    <a href="{{ route('portals.admin.clinical.prescriptions') }}" class="sidebar-link {{ request()->routeIs('portals.admin.clinical.prescriptions') ? 'active' : '' }}">
        <i data-lucide="clipboard-list"></i><span>{{ __('public.adm_clin_sidebar_link_prescriptions') }}</span>
    </a>
    <a href="{{ route('portals.admin.clinical.lab_orders') }}" class="sidebar-link {{ request()->routeIs('portals.admin.clinical.lab_orders') ? 'active' : '' }}">
        <i data-lucide="microscope"></i><span>{{ __('public.adm_clin_sidebar_link_lab_orders') }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_clin_sidebar_section_staff') }}</div>
    <a href="{{ route('portals.staff.hr.directory') }}" class="sidebar-link">
        <i data-lucide="users"></i><span>{{ __('public.adm_clin_sidebar_link_staff_dir') }}</span>
    </a>
    <a href="{{ route('portals.staff.hr.shifts') }}" class="sidebar-link">
        <i data-lucide="calendar-clock"></i><span>{{ __('public.adm_clin_sidebar_link_shifts') }}</span>
    </a>
    @feature('analytics_dashboards')
    @endfeature
    <a href="{{ route('portals.staff.wards') }}" class="sidebar-link">
        <i data-lucide="bed"></i><span>{{ __('public.adm_clin_sidebar_link_wards') }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_clin_sidebar_section_finance') }}</div>
    @feature('billing')
    <a href="{{ route('portals.staff.billing') }}" class="sidebar-link">
        <i data-lucide="receipt"></i><span>{{ __('public.adm_clin_sidebar_link_billing') }}</span>
    </a>
    @endfeature
    @feature('analytics_dashboards')
    @endfeature
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_clin_sidebar_section_security') }}</div>
    <a href="{{ route('portals.admin.security') }}" class="sidebar-link {{ request()->routeIs('portals.admin.security*') ? 'active' : '' }}">
        <i data-lucide="shield-alert"></i><span>{{ __('public.adm_clin_sidebar_link_security') }}</span>
    </a>
    <a href="{{ route('portals.admin.kpi.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.kpi*') ? 'active' : '' }}">
        <i data-lucide="gauge"></i><span>{{ __('public.admin_governance.nav_kpi_dashboard', [], app()->getLocale()) ?: 'KPI Dashboard' }}</span>
    </a>
</div>
@endsection

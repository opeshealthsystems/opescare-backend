@section('sidebar_role_badge')
<div class="sidebar-role-badge sidebar-role-badge--primary">
    <i data-lucide="shield-check"></i>
    {{ __('public.adm_cc_sidebar_super_admin') }}
</div>
@endsection
@section('sidebar_user_role', __('public.adm_cc_sidebar_super_admin'))

@section('sidebar_nav')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_cc_sidebar_section_overview') }}</div>
    <a href="{{ route('portals.admin') }}" class="sidebar-link {{ request()->routeIs('portals.admin') && !request()->routeIs('portals.admin.cc*') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.adm_cc_sidebar_governance') }}</span>
    </a>
    <a href="{{ route('portals.admin.cc') }}" class="sidebar-link {{ request()->routeIs('portals.admin.cc') ? 'active' : '' }}">
        <i data-lucide="settings-2"></i><span>{{ __('public.adm_cc_sidebar_control_center') }}</span>
    </a>
    <a href="{{ route('portals.admin.cc.health') }}" class="sidebar-link {{ request()->routeIs('portals.admin.cc.health') ? 'active' : '' }}">
        <i data-lucide="activity"></i><span>{{ __('public.adm_cc_sidebar_system_health') }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_cc_sidebar_section_platform') }}</div>
    <a href="{{ route('portals.admin.cc.settings') }}" class="sidebar-link {{ request()->routeIs('portals.admin.cc.settings') ? 'active' : '' }}">
        <i data-lucide="sliders-horizontal"></i><span>{{ __('public.adm_cc_sidebar_platform_settings') }}</span>
    </a>
    <a href="{{ route('portals.admin.cc.feature_flags') }}" class="sidebar-link {{ request()->routeIs('portals.admin.cc.feature_flags') ? 'active' : '' }}">
        <i data-lucide="toggle-right"></i><span>{{ __('public.adm_cc_sidebar_feature_flags') }}</span>
    </a>
    <a href="{{ route('portals.admin.cc.modules') }}" class="sidebar-link {{ request()->routeIs('portals.admin.cc.modules') ? 'active' : '' }}">
        <i data-lucide="puzzle"></i><span>{{ __('public.adm_cc_sidebar_module_toggles') }}</span>
    </a>
    <a href="{{ route('portals.admin.cc.maintenance') }}" class="sidebar-link {{ request()->routeIs('portals.admin.cc.maintenance') ? 'active' : '' }}">
        <i data-lucide="wrench"></i><span>{{ __('public.adm_cc_sidebar_maintenance') }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_cc_sidebar_section_audit') }}</div>
    <a href="{{ route('portals.admin.cc.audit') }}" class="sidebar-link {{ request()->routeIs('portals.admin.cc.audit') ? 'active' : '' }}">
        <i data-lucide="scroll-text"></i><span>{{ __('public.adm_cc_sidebar_admin_action_log') }}</span>
    </a>
        </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_cc_sidebar_section_billing') }}</div>
            </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_cc_sidebar_section_integrations') }}</div>
        </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_cc_sidebar_section_god_mode') }}</div>
    <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
        <i data-lucide="users"></i><span>{{ __('public.adm_cc_sidebar_all_users') }}</span>
        @php $pendingUsers = \App\Models\User::where('status','pending')->count(); @endphp
        @if($pendingUsers > 0)<span class="sidebar-badge sidebar-badge--warning">{{ $pendingUsers }}</span>@endif
    </a>
    <a href="{{ route('admin.facilities.index') }}" class="sidebar-link {{ request()->routeIs('admin.facilities*') ? 'active' : '' }}">
        <i data-lucide="building-2"></i><span>{{ __('public.adm_cc_sidebar_all_facilities') }}</span>
    </a>
    <a href="{{ route('admin.care-map.review') }}" class="sidebar-link {{ request()->routeIs('admin.care-map.review*') ? 'active' : '' }}">
        <i data-lucide="clipboard-check"></i><span>{{ __('caremap_claim.nav_directory_review') }}</span>
        @php
            $directoryPending = \App\Models\FacilityClaim::query()->open()->count()
                + \App\Models\FacilityImportReview::where('status', \App\Enums\FacilityImportReviewStatus::Pending->value)->count();
        @endphp
        @if($directoryPending > 0)<span class="sidebar-badge sidebar-badge--warning">{{ $directoryPending }}</span>@endif
    </a>
                <a href="{{ route('admin.roles.index') }}" class="sidebar-link {{ request()->routeIs('admin.roles*') ? 'active' : '' }}">
        <i data-lucide="shield"></i><span>{{ __('public.adm_cc_sidebar_roles_rbac') }}</span>
    </a>
    <a href="{{ route('portals.admin.appointments.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.appointments*') ? 'active' : '' }}">
        <i data-lucide="calendar"></i><span>{{ __('public.adm_cc_sidebar_appointments') }}</span>
    </a>
        <a href="{{ route('portals.admin.leads') }}" class="sidebar-link {{ request()->routeIs('portals.admin.leads*') ? 'active' : '' }}">
        <i data-lucide="user-plus"></i><span>{{ __('leads.admin.page_title') }}</span>
    </a>
    @feature('clinical_decision_support')
    @endfeature
    </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_cc_sidebar_section_analytics') }}</div>
        </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_cc_sidebar_section_compliance') }}</div>
                    <a href="{{ route('portals.admin.certifications.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.certifications*') ? 'active' : '' }}">
        <i data-lucide="badge-check"></i><span>{{ __('public.adm_cc_sidebar_certifications') }}</span>
    </a>
    </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_cc_sidebar_section_onboarding') }}</div>
        <a href="{{ route('portals.admin.developer.accounts') }}" class="sidebar-link {{ request()->routeIs('portals.admin.developer.accounts*') ? 'active' : '' }}">
        <i data-lucide="code"></i><span>{{ __('public.adm_cc_sidebar_developer_accounts') }}</span>
    </a>
    <a href="{{ route('portals.admin.developer.production_requests') }}" class="sidebar-link {{ request()->routeIs('portals.admin.developer.production_requests*') ? 'active' : '' }}">
        <i data-lucide="rocket"></i><span>{{ __('public.adm_cc_sidebar_production_requests') }}</span>
        @php $pendingProd = \Illuminate\Support\Facades\DB::table('production_access_requests')->where('status','pending')->count(); @endphp
        @if($pendingProd > 0)<span class="sidebar-badge sidebar-badge--warning">{{ $pendingProd }}</span>@endif
    </a>
</div>
@endsection

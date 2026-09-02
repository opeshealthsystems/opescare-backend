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
    <a href="{{ route('portals.admin.go-live') }}" class="sidebar-link">
        <i data-lucide="rocket"></i><span>{{ __('public.adm_cc_sidebar_facility_golive') }}</span>
    </a>
    <a href="{{ route('portals.admin.security') }}" class="sidebar-link {{ request()->routeIs('portals.admin.security*') ? 'active' : '' }}">
        <i data-lucide="shield-alert"></i><span>{{ __('public.adm_cc_sidebar_security_ops') }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_cc_sidebar_section_billing') }}</div>
    <a href="{{ route('portals.admin.subscription') }}" class="sidebar-link {{ request()->routeIs('portals.admin.subscription') || request()->routeIs('portals.admin.subscription.detail') ? 'active' : '' }}">
        <i data-lucide="credit-card"></i><span>{{ __('public.adm_cc_sidebar_subscriptions') }}</span>
        @php $pastDue = \App\Models\OrganizationSubscription::where('status','past_due')->count(); @endphp
        @if($pastDue > 0)
            <span class="sidebar-badge sidebar-badge--warning">{{ $pastDue }}</span>
        @endif
    </a>
    <a href="{{ route('portals.admin.subscription.plans') }}" class="sidebar-link {{ request()->routeIs('portals.admin.subscription.plans*') ? 'active' : '' }}">
        <i data-lucide="layers"></i><span>{{ __('public.adm_cc_sidebar_plans') }}</span>
    </a>
    <a href="{{ route('portals.admin.subscription.invoices') }}" class="sidebar-link {{ request()->routeIs('portals.admin.subscription.invoices*') ? 'active' : '' }}">
        <i data-lucide="file-text"></i><span>{{ __('public.adm_cc_sidebar_invoices') }}</span>
        @php $overdueInv = \App\Models\SubscriptionInvoice::where('status','sent')->where('due_date','<',now())->count(); @endphp
        @if($overdueInv > 0)
            <span class="sidebar-badge sidebar-badge--danger">{{ $overdueInv }}</span>
        @endif
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_cc_sidebar_section_integrations') }}</div>
    <a href="{{ route('portals.admin.connect') }}" class="sidebar-link {{ request()->routeIs('portals.admin.connect*') ? 'active' : '' }}">
        <i data-lucide="plug-zap"></i><span>{{ __('public.adm_cc_sidebar_connect_suite') }}</span>
        @php $pendingConnect = \App\Models\IntegrationClient::where('status','pending')->count(); @endphp
        @if($pendingConnect > 0)
            <span class="sidebar-badge sidebar-badge--warning">{{ $pendingConnect }}</span>
        @endif
    </a>
    <a href="{{ route('portals.admin.bridge') }}" class="sidebar-link {{ request()->routeIs('portals.admin.bridge*') ? 'active' : '' }}">
        <i data-lucide="cable"></i><span>{{ __('public.adm_cc_sidebar_bridge_agents') }}</span>
    </a>
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
    <a href="{{ route('admin.patients.index') }}" class="sidebar-link {{ request()->routeIs('admin.patients*') ? 'active' : '' }}">
        <i data-lucide="heart-pulse"></i><span>{{ __('public.adm_cc_sidebar_all_patients') }}</span>
    </a>
    <a href="{{ route('admin.staff.index') }}" class="sidebar-link {{ request()->routeIs('admin.staff*') ? 'active' : '' }}">
        <i data-lucide="user-check"></i><span>{{ __('public.adm_cc_sidebar_all_staff') }}</span>
    </a>
    <a href="{{ route('admin.organizations.index') }}" class="sidebar-link {{ request()->routeIs('admin.organizations*') ? 'active' : '' }}">
        <i data-lucide="network"></i><span>{{ __('public.adm_cc_sidebar_organizations') }}</span>
    </a>
    <a href="{{ route('admin.roles.index') }}" class="sidebar-link {{ request()->routeIs('admin.roles*') ? 'active' : '' }}">
        <i data-lucide="shield"></i><span>{{ __('public.adm_cc_sidebar_roles_rbac') }}</span>
    </a>
    <a href="{{ route('portals.admin.appointments.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.appointments*') ? 'active' : '' }}">
        <i data-lucide="calendar"></i><span>{{ __('public.adm_cc_sidebar_appointments') }}</span>
    </a>
    <a href="{{ route('portals.admin.support.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.support*') ? 'active' : '' }}">
        <i data-lucide="headphones"></i><span>{{ __('public.adm_cc_sidebar_support_tickets') }}</span>
    </a>
    <a href="{{ route('portals.admin.leads') }}" class="sidebar-link {{ request()->routeIs('portals.admin.leads*') ? 'active' : '' }}">
        <i data-lucide="user-plus"></i><span>{{ __('leads.admin.page_title') }}</span>
    </a>
    @feature('clinical_decision_support')
    @endfeature
    <a href="{{ route('portals.admin.reports.minsante-monthly') }}" class="sidebar-link {{ request()->routeIs('portals.admin.reports*') ? 'active' : '' }}">
        <i data-lucide="file-bar-chart"></i><span>{{ __('public.adm_cc_sidebar_reports') }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_cc_sidebar_section_analytics') }}</div>
    <a href="{{ route('portals.admin.kpi.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.kpi*') ? 'active' : '' }}">
        <i data-lucide="bar-chart-3"></i><span>{{ __('public.admin_governance.nav_kpi_dashboard', [], app()->getLocale()) ?: 'KPI Dashboard' }}</span>
    </a>
    <a href="{{ route('portals.admin.kpi.trend') }}" class="sidebar-link {{ request()->routeIs('portals.admin.kpi.trend') ? 'active' : '' }}">
        <i data-lucide="trending-up"></i><span>{{ __('public.admin_governance.nav_kpi_trends', [], app()->getLocale()) ?: 'KPI Trends' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_cc_sidebar_section_compliance') }}</div>
    <a href="{{ route('portals.admin.legal') }}" class="sidebar-link {{ request()->routeIs('portals.admin.legal') ? 'active' : '' }}">
        <i data-lucide="scale"></i><span>{{ __('public.adm_cc_sidebar_legal_docs') }}</span>
    </a>
    <a href="{{ route('portals.admin.legal.closures') }}" class="sidebar-link {{ request()->routeIs('portals.admin.legal.closures*') ? 'active' : '' }}">
        <i data-lucide="file-x-2"></i><span>{{ __('public.adm_cc_sidebar_patient_rights') }}</span>
    </a>
    <a href="{{ route('portals.admin.legal.complaints') }}" class="sidebar-link {{ request()->routeIs('portals.admin.legal.complaints*') ? 'active' : '' }}">
        <i data-lucide="message-circle-warning"></i><span>{{ __('public.adm_cc_sidebar_privacy_complaints') }}</span>
    </a>
    <a href="{{ route('portals.admin.legal.minor_transitions') }}" class="sidebar-link {{ request()->routeIs('portals.admin.legal.minor_transitions') ? 'active' : '' }}">
        <i data-lucide="user-cog"></i><span>{{ __('public.adm_cc_sidebar_minor_transitions') }}</span>
    </a>
    <a href="{{ route('portals.admin.certifications.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.certifications*') ? 'active' : '' }}">
        <i data-lucide="badge-check"></i><span>{{ __('public.adm_cc_sidebar_certifications') }}</span>
    </a>
    <a href="{{ route('portals.admin.code_mappings.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.code_mappings*') ? 'active' : '' }}">
        <i data-lucide="code-2"></i><span>{{ __('public.adm_cc_sidebar_code_mappings') }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.adm_cc_sidebar_section_onboarding') }}</div>
    <a href="{{ route('portals.admin.onboarding') }}" class="sidebar-link {{ request()->routeIs('portals.admin.onboarding*') ? 'active' : '' }}">
        <i data-lucide="clipboard-list"></i><span>{{ __('public.adm_cc_sidebar_facility_onboarding') }}</span>
    </a>
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

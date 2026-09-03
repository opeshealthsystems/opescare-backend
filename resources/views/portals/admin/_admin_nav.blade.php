{{--
    Shared admin navigation — single source of truth for the full admin surface.
    Included by super_admin / platform_owner partials AND control_center/_sidebar
    so the nav is identical on every admin page (dashboard, people, directory,
    partners, operations, control center). Add a link here, not in three files.
--}}
@php $l = app()->getLocale(); @endphp

{{-- Overview --}}
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_platform', [], $l) ?: 'Platform' }}</div>
    <a href="{{ route('portals.admin') }}" class="sidebar-link {{ request()->routeIs('portals.admin') && ! request()->routeIs('portals.admin.*') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>

{{-- Directory --}}
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_directory', [], $l) ?: 'Directory' }}</div>
    <a href="{{ route('portals.admin.facilities.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.facilities.*') || request()->routeIs('admin.facilities.*') ? 'active' : '' }}">
        <i data-lucide="hospital"></i><span>{{ __('public.portal.nav_facilities', [], $l) ?: 'Facilities' }}</span>
    </a>
    <a href="{{ route('admin.care-map.review') }}" class="sidebar-link {{ request()->routeIs('admin.care-map.review*') ? 'active' : '' }}">
        <i data-lucide="clipboard-check"></i><span>{{ __('caremap_claim.nav_directory_review', [], $l) ?: 'Directory Review' }}</span>
        @php $directoryPending = rescue(fn () => \App\Models\FacilityClaim::query()->open()->count()
            + \App\Models\FacilityImportReview::where('status', \App\Enums\FacilityImportReviewStatus::Pending->value)->count(), 0, false); @endphp
        @if($directoryPending > 0)<span class="sidebar-badge sidebar-badge--warning">{{ $directoryPending }}</span>@endif
    </a>
</div>

{{-- People --}}
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_people', [], $l) ?: 'People' }}</div>
    <a href="{{ route('portals.admin.users.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.users.*') || request()->routeIs('admin.users.*') ? 'active' : '' }}">
        <i data-lucide="users"></i><span>{{ __('public.portal.nav_users', [], $l) ?: 'Users' }}</span>
        @php $pendingUsers = rescue(fn () => \App\Models\User::where('status', 'pending')->count(), 0, false); @endphp
        @if($pendingUsers > 0)<span class="sidebar-badge sidebar-badge--warning">{{ $pendingUsers }}</span>@endif
    </a>
    <a href="{{ route('portals.admin.roles.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.roles.*') || request()->routeIs('admin.roles.*') ? 'active' : '' }}">
        <i data-lucide="shield-check"></i><span>{{ __('public.portal.nav_roles', [], $l) ?: 'Roles' }}</span>
    </a>
</div>

{{-- Partners --}}
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_partners', [], $l) ?: 'Partners' }}</div>
    <a href="{{ route('portals.admin.developer.accounts') }}" class="sidebar-link {{ request()->routeIs('portals.admin.developer.accounts*') ? 'active' : '' }}">
        <i data-lucide="code-2"></i><span>{{ __('public.adm_cc_sidebar_developer_accounts', [], $l) ?: 'Developer Accounts' }}</span>
    </a>
    <a href="{{ route('portals.admin.developer.production_requests') }}" class="sidebar-link {{ request()->routeIs('portals.admin.developer.production_requests*') ? 'active' : '' }}">
        <i data-lucide="rocket"></i><span>{{ __('public.adm_cc_sidebar_production_requests', [], $l) ?: 'Production Requests' }}</span>
        @php $pendingProd = rescue(fn () => \Illuminate\Support\Facades\DB::table('production_access_requests')->where('status', 'pending')->count(), 0, false); @endphp
        @if($pendingProd > 0)<span class="sidebar-badge sidebar-badge--warning">{{ $pendingProd }}</span>@endif
    </a>
    <a href="{{ route('portals.admin.certifications.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.certifications.*') ? 'active' : '' }}">
        <i data-lucide="badge-check"></i><span>{{ __('public.adm_cc_sidebar_certifications', [], $l) ?: 'Certifications' }}</span>
    </a>
</div>

{{-- Operations --}}
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_operations', [], $l) ?: 'Operations' }}</div>
    <a href="{{ route('portals.admin.appointments.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.appointments.*') ? 'active' : '' }}">
        <i data-lucide="calendar"></i><span>{{ __('public.adm_cc_sidebar_appointments', [], $l) ?: 'Appointments' }}</span>
    </a>
    <a href="{{ route('portals.admin.leads') }}" class="sidebar-link {{ request()->routeIs('portals.admin.leads*') ? 'active' : '' }}">
        <i data-lucide="user-plus"></i><span>{{ __('leads.admin.page_title', [], $l) ?: 'Leads' }}</span>
    </a>
</div>

{{-- Control Center --}}
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_control_center', [], $l) ?: 'Control Center' }}</div>
    <a href="{{ route('portals.admin.cc') }}" class="sidebar-link {{ request()->routeIs('portals.admin.cc') && ! request()->routeIs('portals.admin.cc.*') ? 'active' : '' }}">
        <i data-lucide="layout-grid"></i><span>{{ __('public.adm_cc_sidebar_section_overview', [], $l) ?: 'Overview' }}</span>
    </a>
    <a href="{{ route('portals.admin.cc.settings') }}" class="sidebar-link {{ request()->routeIs('portals.admin.cc.settings*') ? 'active' : '' }}">
        <i data-lucide="sliders-horizontal"></i><span>{{ __('public.adm_cc_sidebar_platform_settings', [], $l) ?: 'Platform Settings' }}</span>
    </a>
    <a href="{{ route('portals.admin.cc.feature_flags') }}" class="sidebar-link {{ request()->routeIs('portals.admin.cc.feature_flags*') ? 'active' : '' }}">
        <i data-lucide="toggle-right"></i><span>{{ __('public.adm_cc_sidebar_feature_flags', [], $l) ?: 'Feature Flags' }}</span>
    </a>
    <a href="{{ route('portals.admin.cc.modules') }}" class="sidebar-link {{ request()->routeIs('portals.admin.cc.modules*') ? 'active' : '' }}">
        <i data-lucide="puzzle"></i><span>{{ __('public.adm_cc_sidebar_module_toggles', [], $l) ?: 'Module Toggles' }}</span>
    </a>
    <a href="{{ route('portals.admin.cc.maintenance') }}" class="sidebar-link {{ request()->routeIs('portals.admin.cc.maintenance*') ? 'active' : '' }}">
        <i data-lucide="wrench"></i><span>{{ __('public.adm_cc_sidebar_maintenance', [], $l) ?: 'Maintenance' }}</span>
    </a>
    <a href="{{ route('portals.admin.cc.health') }}" class="sidebar-link {{ request()->routeIs('portals.admin.cc.health*') ? 'active' : '' }}">
        <i data-lucide="activity"></i><span>{{ __('public.adm_cc_sidebar_system_health', [], $l) ?: 'System Health' }}</span>
    </a>
    <a href="{{ route('portals.admin.cc.audit') }}" class="sidebar-link {{ request()->routeIs('portals.admin.cc.audit*') ? 'active' : '' }}">
        <i data-lucide="scroll-text"></i><span>{{ __('public.adm_cc_sidebar_admin_action_log', [], $l) ?: 'Audit Log' }}</span>
    </a>
</div>

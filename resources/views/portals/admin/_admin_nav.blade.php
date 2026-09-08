{{--
    Shared admin navigation — single source of truth for the full admin surface.
    Included by super_admin / platform_owner partials AND control_center/_sidebar
    so the nav is identical on every admin page (dashboard, people, directory,
    partners, operations, control center). Add a link here, not in three files.
--}}
@php $l = app()->getLocale(); @endphp

{{-- Dashboard (standalone) --}}
<div class="sidebar-nav-section">
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
    <a href="{{ route('portals.admin.verifications.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.verifications.*') ? 'active' : '' }}">
        <i data-lucide="badge-check"></i><span>{{ __('professional.nav_verifications', [], $l) ?: 'Verifications' }}</span>
        @php $pendingCreds = rescue(fn () => \App\Models\ProviderCredential::whereNull('verified_at')->where('status', '!=', 'suspended')->count(), 0, false); @endphp
        @if($pendingCreds > 0)<span class="sidebar-badge sidebar-badge--warning">{{ $pendingCreds }}</span>@endif
    </a>
    <a href="{{ route('portals.admin.mpi.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.mpi.*') ? 'active' : '' }}">
        <i data-lucide="git-merge"></i><span>{{ __('mpi.nav', [], $l) ?: 'Patient identity' }}</span>
        @php $pendingMpi = rescue(fn () => \App\Models\MpiCandidate::where('status', 'pending_review')->count(), 0, false); @endphp
        @if($pendingMpi > 0)<span class="sidebar-badge sidebar-badge--warning">{{ $pendingMpi }}</span>@endif
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
    <a href="{{ route('portals.admin.data_import.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.data_import.*') ? 'active' : '' }}">
        <i data-lucide="upload-cloud"></i><span>{{ __('staff_data.title_import', [], $l) ?: 'Data Import' }}</span>
    </a>
</div>

{{-- Compliance --}}
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_compliance', [], $l) ?: 'Compliance' }}</div>
    <a href="{{ route('portals.admin.legal.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.legal.index') || request()->routeIs('portals.admin.legal.show') || request()->routeIs('portals.admin.legal.store') || request()->routeIs('portals.admin.legal.versions.*') ? 'active' : '' }}">
        <i data-lucide="scale"></i><span>{{ __('public.adm_legal_page_title', [], $l) ?: 'Legal Documents' }}</span>
    </a>
    <a href="{{ route('portals.admin.legal.closures') }}" class="sidebar-link {{ request()->routeIs('portals.admin.legal.closures*') ? 'active' : '' }}">
        <i data-lucide="user-x"></i><span>{{ __('public.adm_legal_btn_closures', [], $l) ?: 'Closure Requests' }}</span>
        {{-- Only requests still awaiting a human. The patient-facing deletion
             flow writes pending/cancelled/completed/purged and never 'pending'
             review, so this counts exactly what this desk has to act on. --}}
        @php $pendingClosures = rescue(fn () => \App\Models\AccountClosureRequest::where('status', 'pending')->count(), 0, false); @endphp
        @if($pendingClosures > 0)<span class="sidebar-badge sidebar-badge--warning">{{ $pendingClosures }}</span>@endif
    </a>
    <a href="{{ route('portals.admin.legal.complaints') }}" class="sidebar-link {{ request()->routeIs('portals.admin.legal.complaints*') ? 'active' : '' }}">
        <i data-lucide="message-square-warning"></i><span>{{ __('public.adm_legal_btn_complaints', [], $l) ?: 'Privacy Complaints' }}</span>
        @php $openComplaints = rescue(fn () => \App\Models\PrivacyComplaint::whereIn('status', ['open', 'investigating'])->count(), 0, false); @endphp
        @if($openComplaints > 0)<span class="sidebar-badge sidebar-badge--warning">{{ $openComplaints }}</span>@endif
    </a>
    <a href="{{ route('portals.admin.legal.minor_transitions') }}" class="sidebar-link {{ request()->routeIs('portals.admin.legal.minor_transitions*') ? 'active' : '' }}">
        <i data-lucide="user-round-cog"></i><span>{{ __('public.adm_legal_btn_minor_transitions', [], $l) ?: 'Minor Transitions' }}</span>
    </a>
    <a href="{{ route('portals.admin.security.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.security.*') ? 'active' : '' }}">
        <i data-lucide="shield-alert"></i><span>{{ __('security_ops.nav', [], $l) ?: 'Security oversight' }}</span>
        {{-- Emergency overrides nobody has signed off on. Demo isolation is
             lifted for the same reason the desk lifts it: a real break-glass
             must not be invisible because the host runs in demo mode. --}}
        @php $unreviewedBreakGlass = rescue(fn () => \App\Models\EmergencyAccessEvent::withoutGlobalScope('isolate_demo')
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('emergency_review_cases')
                ->whereColumn('emergency_review_cases.emergency_access_event_id', 'emergency_access_events.id')
                ->where('emergency_review_cases.status', '!=', 'pending'))->count(), 0, false); @endphp
        @if($unreviewedBreakGlass > 0)<span class="sidebar-badge sidebar-badge--warning">{{ $unreviewedBreakGlass }}</span>@endif
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
    <a href="{{ route('portals.admin.plans.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.plans.*') ? 'active' : '' }}">
        <i data-lucide="banknote"></i><span>{{ __('plan_pricing.nav', [], $l) ?: 'Plans & pricing' }}</span>
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

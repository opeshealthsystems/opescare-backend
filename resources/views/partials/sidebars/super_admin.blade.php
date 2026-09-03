@php $l = app()->getLocale(); @endphp

{{-- Platform --}}
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_platform', [], $l) ?: 'Platform' }}</div>
    <a href="{{ route('portals.admin') }}" class="sidebar-link {{ request()->routeIs('portals.admin') && !request()->routeIs('portals.admin.*') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>

{{-- Organizations --}}
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_organizations', [], $l) ?: 'Organizations' }}</div>
    <a href="{{ route('portals.admin.facilities.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.facilities.*') ? 'active' : '' }}">
        <i data-lucide="hospital"></i><span>{{ __('public.portal.nav_facilities', [], $l) ?: 'Facilities' }}</span>
    </a>
    @platformadmin
    <a href="{{ route('admin.care-map.review') }}" class="sidebar-link {{ request()->routeIs('admin.care-map.review*') ? 'active' : '' }}">
        <i data-lucide="clipboard-check"></i><span>{{ __('caremap_claim.nav_directory_review', [], $l) }}</span>
    </a>
    @endplatformadmin
</div>

{{-- People --}}
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_people', [], $l) ?: 'People' }}</div>
    <a href="{{ route('portals.admin.users.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.users.*') ? 'active' : '' }}">
        <i data-lucide="users"></i><span>{{ __('public.portal.nav_users', [], $l) ?: 'Users' }}</span>
    </a>
    <a href="{{ route('portals.admin.roles.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.roles.*') ? 'active' : '' }}">
        <i data-lucide="shield-check"></i><span>{{ __('public.portal.nav_roles', [], $l) ?: 'Roles' }}</span>
    </a>
</div>

{{-- Clinical --}}
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_clinical', [], $l) ?: 'Clinical' }}</div>
    @feature('clinical_decision_support')
    @endfeature
    <a href="{{ route('portals.admin.certifications.index') }}" class="sidebar-link {{ request()->routeIs('portals.admin.certifications.*') ? 'active' : '' }}">
        <i data-lucide="award"></i><span>{{ __('public.portal.nav_certifications', [], $l) ?: 'Certifications' }}</span>
    </a>
</div>

{{-- Integration --}}
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_integration', [], $l) ?: 'Integration' }}</div>
    <a href="{{ route('portals.admin.developer.accounts') }}" class="sidebar-link {{ request()->routeIs('portals.admin.developer.*') ? 'active' : '' }}">
        <i data-lucide="code-2"></i><span>{{ __('public.portal.nav_developers', [], $l) ?: 'Developers' }}</span>
    </a>
</div>

{{-- Compliance --}}
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_compliance', [], $l) ?: 'Compliance' }}</div>
</div>

{{-- System --}}
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_system', [], $l) ?: 'System' }}</div>
    <a href="{{ route('portals.admin.cc') }}" class="sidebar-link {{ request()->routeIs('portals.admin.cc*') ? 'active' : '' }}">
        <i data-lucide="server"></i><span>{{ __('public.portal.nav_control_center', [], $l) ?: 'Control Center' }}</span>
    </a>
</div>

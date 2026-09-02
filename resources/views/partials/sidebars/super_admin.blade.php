@push('head')
<style>
.nav-section-label{font-size:.65rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--p-text-muted,rgba(255,255,255,.45));padding:.75rem 1rem .25rem;margin-top:.25rem}
.nav-section-label:first-child{margin-top:0}
</style>
@endpush
@php $l = app()->getLocale(); @endphp

<nav class="sidebar-nav">

  {{-- Platform --}}
  <div class="nav-section-label">{{ __('public.portal.nav_platform', [], $l) ?: 'Platform' }}</div>
  <a href="{{ route('portals.admin') }}" class="nav-item {{ request()->routeIs('portals.admin') && !request()->routeIs('portals.admin.*') ? 'active' : '' }}">
    <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
  </a>
  <a href="{{ route('portals.admin.tasks') }}" class="nav-item {{ request()->routeIs('portals.admin.tasks*') ? 'active' : '' }}">
    <i data-lucide="clipboard-list"></i><span>{{ __('tasks.admin_title', [], $l) ?: 'Tasks' }}</span>
  </a>
  <a href="{{ route('portals.admin.broadcasts') }}" class="nav-item {{ request()->routeIs('portals.admin.broadcasts*') ? 'active' : '' }}">
    <i data-lucide="megaphone"></i><span>{{ __('broadcasts.nav', [], $l) ?: 'Broadcasts' }}</span>
  </a>
  <a href="{{ route('portals.admin.kpi.index') }}" class="nav-item {{ request()->routeIs('portals.admin.kpi.*') ? 'active' : '' }}">
    <i data-lucide="bar-chart-2"></i><span>{{ __('public.portal.nav_kpi_analytics', [], $l) ?: 'KPI & Analytics' }}</span>
  </a>
  <a href="{{ route('portals.admin.onboarding') }}" class="nav-item {{ request()->routeIs('portals.admin.onboarding*') ? 'active' : '' }}">
    <i data-lucide="rocket"></i><span>{{ __('public.portal.nav_onboarding', [], $l) ?: 'Onboarding' }}</span>
  </a>
  <a href="{{ route('portals.admin.go-live') }}" class="nav-item {{ request()->routeIs('portals.admin.go-live*') ? 'active' : '' }}">
    <i data-lucide="check-circle-2"></i><span>{{ __('public.portal.nav_go_live', [], $l) ?: 'Go-Live Readiness' }}</span>
  </a>

  {{-- Organizations --}}
  <div class="nav-section-label">{{ __('public.portal.nav_organizations', [], $l) ?: 'Organizations' }}</div>
  <a href="{{ route('portals.admin.organizations.index') }}" class="nav-item {{ request()->routeIs('portals.admin.organizations.*') ? 'active' : '' }}">
    <i data-lucide="building-2"></i><span>{{ __('public.portal.nav_organizations', [], $l) ?: 'Organizations' }}</span>
  </a>
  <a href="{{ route('portals.admin.facilities.index') }}" class="nav-item {{ request()->routeIs('portals.admin.facilities.*') ? 'active' : '' }}">
    <i data-lucide="hospital"></i><span>{{ __('public.portal.nav_facilities', [], $l) ?: 'Facilities' }}</span>
  </a>
  @platformadmin
  <a href="{{ route('admin.care-map.review') }}" class="nav-item {{ request()->routeIs('admin.care-map.review*') ? 'active' : '' }}">
    <i data-lucide="clipboard-check"></i><span>{{ __('caremap_claim.nav_directory_review', [], $l) }}</span>
  </a>
  @endplatformadmin
  <a href="{{ route('portals.admin.subscription') }}" class="nav-item {{ request()->routeIs('portals.admin.subscription*') ? 'active' : '' }}">
    <i data-lucide="credit-card"></i><span>{{ __('public.portal.nav_subscriptions', [], $l) ?: 'Subscriptions' }}</span>
  </a>

  {{-- People --}}
  <div class="nav-section-label">{{ __('public.portal.nav_people', [], $l) ?: 'People' }}</div>
  <a href="{{ route('portals.admin.users.index') }}" class="nav-item {{ request()->routeIs('portals.admin.users.*') ? 'active' : '' }}">
    <i data-lucide="users"></i><span>{{ __('public.portal.nav_users', [], $l) ?: 'Users' }}</span>
  </a>
  <a href="{{ route('portals.admin.roles.index') }}" class="nav-item {{ request()->routeIs('portals.admin.roles.*') ? 'active' : '' }}">
    <i data-lucide="shield-check"></i><span>{{ __('public.portal.nav_roles', [], $l) ?: 'Roles' }}</span>
  </a>
  <a href="{{ route('portals.admin.staff.index') }}" class="nav-item {{ request()->routeIs('portals.admin.staff.*') ? 'active' : '' }}">
    <i data-lucide="stethoscope"></i><span>{{ __('public.portal.nav_staff', [], $l) ?: 'Staff' }}</span>
  </a>
  <a href="{{ route('portals.admin.patients.index') }}" class="nav-item {{ request()->routeIs('portals.admin.patients.*') ? 'active' : '' }}">
    <i data-lucide="heart-pulse"></i><span>{{ __('public.portal.nav_patients', [], $l) ?: 'Patients' }}</span>
  </a>

  {{-- Clinical --}}
  <div class="nav-section-label">{{ __('public.portal.nav_clinical', [], $l) ?: 'Clinical' }}</div>
  @feature('clinical_decision_support')
  @endfeature
  <a href="{{ route('portals.admin.code_mappings.index') }}" class="nav-item {{ request()->routeIs('portals.admin.code_mappings.*') ? 'active' : '' }}">
    <i data-lucide="tags"></i><span>{{ __('public.portal.nav_code_mappings', [], $l) ?: 'Code Mappings' }}</span>
  </a>
  <a href="{{ route('portals.admin.certifications.index') }}" class="nav-item {{ request()->routeIs('portals.admin.certifications.*') ? 'active' : '' }}">
    <i data-lucide="award"></i><span>{{ __('public.portal.nav_certifications', [], $l) ?: 'Certifications' }}</span>
  </a>

  {{-- Integration --}}
  <div class="nav-section-label">{{ __('public.portal.nav_integration', [], $l) ?: 'Integration' }}</div>
  <a href="{{ route('portals.admin.connect') }}" class="nav-item {{ request()->routeIs('portals.admin.connect*') ? 'active' : '' }}">
    <i data-lucide="plug"></i><span>{{ __('public.portal.nav_connect_api', [], $l) ?: 'Connect / API' }}</span>
  </a>
  <a href="{{ route('portals.admin.bridge') }}" class="nav-item {{ request()->routeIs('portals.admin.bridge*') ? 'active' : '' }}">
    <i data-lucide="git-branch"></i><span>{{ __('public.portal.nav_bridge', [], $l) ?: 'Bridge' }}</span>
  </a>
  <a href="{{ route('portals.admin.developer.accounts') }}" class="nav-item {{ request()->routeIs('portals.admin.developer.*') ? 'active' : '' }}">
    <i data-lucide="code-2"></i><span>{{ __('public.portal.nav_developers', [], $l) ?: 'Developers' }}</span>
  </a>

  {{-- Compliance --}}
  <div class="nav-section-label">{{ __('public.portal.nav_compliance', [], $l) ?: 'Compliance' }}</div>
  <a href="{{ route('portals.admin.security') }}" class="nav-item {{ request()->routeIs('portals.admin.security*') ? 'active' : '' }}">
    <i data-lucide="shield"></i><span>{{ __('public.portal.nav_security_ops', [], $l) ?: 'Security Ops' }}</span>
  </a>
  <a href="{{ route('portals.admin.legal') }}" class="nav-item {{ request()->routeIs('portals.admin.legal*') ? 'active' : '' }}">
    <i data-lucide="scale"></i><span>{{ __('public.portal.nav_legal', [], $l) ?: 'Legal' }}</span>
  </a>
  <a href="{{ route('portals.admin.reports.minsante-monthly') }}" class="nav-item {{ request()->routeIs('portals.admin.reports.*') ? 'active' : '' }}">
    <i data-lucide="file-bar-chart"></i><span>{{ __('public.portal.nav_reports', [], $l) ?: 'Reports' }}</span>
  </a>

  {{-- System --}}
  <div class="nav-section-label">{{ __('public.portal.nav_system', [], $l) ?: 'System' }}</div>
  <a href="{{ route('portals.admin.cc') }}" class="nav-item {{ request()->routeIs('portals.admin.cc*') ? 'active' : '' }}">
    <i data-lucide="server"></i><span>{{ __('public.portal.nav_control_center', [], $l) ?: 'Control Center' }}</span>
  </a>
  <a href="{{ route('portals.admin.support.index') }}" class="nav-item {{ request()->routeIs('portals.admin.support.*') ? 'active' : '' }}">
    <i data-lucide="life-buoy"></i><span>{{ __('public.portal.nav_support', [], $l) ?: 'Support' }}</span>
  </a>

</nav>

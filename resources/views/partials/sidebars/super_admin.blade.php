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
          
  {{-- Organizations --}}
  <div class="nav-section-label">{{ __('public.portal.nav_organizations', [], $l) ?: 'Organizations' }}</div>
    <a href="{{ route('portals.admin.facilities.index') }}" class="nav-item {{ request()->routeIs('portals.admin.facilities.*') ? 'active' : '' }}">
    <i data-lucide="hospital"></i><span>{{ __('public.portal.nav_facilities', [], $l) ?: 'Facilities' }}</span>
  </a>
  @platformadmin
  <a href="{{ route('admin.care-map.review') }}" class="nav-item {{ request()->routeIs('admin.care-map.review*') ? 'active' : '' }}">
    <i data-lucide="clipboard-check"></i><span>{{ __('caremap_claim.nav_directory_review', [], $l) }}</span>
  </a>
  @endplatformadmin
  
  {{-- People --}}
  <div class="nav-section-label">{{ __('public.portal.nav_people', [], $l) ?: 'People' }}</div>
  <a href="{{ route('portals.admin.users.index') }}" class="nav-item {{ request()->routeIs('portals.admin.users.*') ? 'active' : '' }}">
    <i data-lucide="users"></i><span>{{ __('public.portal.nav_users', [], $l) ?: 'Users' }}</span>
  </a>
  <a href="{{ route('portals.admin.roles.index') }}" class="nav-item {{ request()->routeIs('portals.admin.roles.*') ? 'active' : '' }}">
    <i data-lucide="shield-check"></i><span>{{ __('public.portal.nav_roles', [], $l) ?: 'Roles' }}</span>
  </a>
    
  {{-- Clinical --}}
  <div class="nav-section-label">{{ __('public.portal.nav_clinical', [], $l) ?: 'Clinical' }}</div>
  @feature('clinical_decision_support')
  @endfeature
    <a href="{{ route('portals.admin.certifications.index') }}" class="nav-item {{ request()->routeIs('portals.admin.certifications.*') ? 'active' : '' }}">
    <i data-lucide="award"></i><span>{{ __('public.portal.nav_certifications', [], $l) ?: 'Certifications' }}</span>
  </a>

  {{-- Integration --}}
  <div class="nav-section-label">{{ __('public.portal.nav_integration', [], $l) ?: 'Integration' }}</div>
      <a href="{{ route('portals.admin.developer.accounts') }}" class="nav-item {{ request()->routeIs('portals.admin.developer.*') ? 'active' : '' }}">
    <i data-lucide="code-2"></i><span>{{ __('public.portal.nav_developers', [], $l) ?: 'Developers' }}</span>
  </a>

  {{-- Compliance --}}
  <div class="nav-section-label">{{ __('public.portal.nav_compliance', [], $l) ?: 'Compliance' }}</div>
      
  {{-- System --}}
  <div class="nav-section-label">{{ __('public.portal.nav_system', [], $l) ?: 'System' }}</div>
  <a href="{{ route('portals.admin.cc') }}" class="nav-item {{ request()->routeIs('portals.admin.cc*') ? 'active' : '' }}">
    <i data-lucide="server"></i><span>{{ __('public.portal.nav_control_center', [], $l) ?: 'Control Center' }}</span>
  </a>
  
</nav>

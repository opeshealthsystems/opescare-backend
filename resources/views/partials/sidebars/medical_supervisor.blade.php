@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="user-check"></i>
    {{ __('portal.medical_supervisor_role', [], $l) ?: 'Medical Supervisor' }}
</div>
<div class="sidebar-spacer"></div>
<div class="sidebar-nav-section">
    <a href="{{ route('portals.staff') }}" class="sidebar-link {{ request()->routeIs('portals.staff') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_clinical', [], $l) ?: 'Clinical' }}</div>
    <a href="{{ route('portals.prescriber.index') }}" class="sidebar-link {{ request()->routeIs('portals.prescriber.*') ? 'active' : '' }}">
        <i data-lucide="file-text"></i>
        <span>{{ __('prescriber.nav', [], $l) ?: 'Prescriptions' }}</span>
    </a>
</div>

@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="git-fork"></i>
    {{ __('portal.dependent_manager_role', [], $l) ?: 'Dependent Manager' }}
</div>
<div class="sidebar-spacer"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_my_health', [], $l) ?: 'My Health' }}</div>
    <a href="{{ route('portals.patient') }}" class="sidebar-link">
        <i data-lucide="id-card"></i>
        <span>{{ __('public.medical_id.health_id', [], $l) ?: 'Health ID' }}</span>
    </a>
    <a href="{{ route('portals.patient.family') }}" class="sidebar-link">
        <i data-lucide="git-branch"></i>
        <span>{{ __('public.portal.nav_dependents', [], $l) ?: 'Manage Dependents' }}</span>
    </a>
    <a href="{{ route('portals.patient.appointments') }}" class="sidebar-link">
        <i data-lucide="calendar-check-2"></i>
        <span>{{ __('public.portal.nav_appointments', [], $l) ?: 'Appointments' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_privacy', [], $l) ?: 'Privacy & Access' }}</div>
        </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_resources', [], $l) ?: 'Resources' }}</div>
    <a href="{{ route('public.help') }}" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span>{{ __('public.portal.nav_help', [], $l) ?: 'Help' }}</span>
    </a>
</div>

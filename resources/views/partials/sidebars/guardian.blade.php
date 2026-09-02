@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge" style="background:rgba(15,118,110,.3);border-color:rgba(15,118,110,.5);color:#5EEAD4;">
    <i data-lucide="users" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    {{ __('portal.guardian_role', [], $l) ?: 'Guardian / Parent' }}
</div>
<div style="margin-bottom:var(--p-space-3);"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_my_health', [], $l) ?: 'My Health' }}</div>
    <a href="{{ route('portals.patient') }}" class="sidebar-link">
        <i data-lucide="id-card"></i>
        <span>{{ __('public.medical_id.health_id', [], $l) ?: 'Health ID' }}</span>
    </a>
    <a href="{{ route('portals.patient.appointments') }}" class="sidebar-link">
        <i data-lucide="calendar-check-2"></i>
        <span>{{ __('public.portal.nav_appointments', [], $l) ?: 'Appointments' }}</span>
    </a>
    <a href="{{ route('portals.patient') }}" class="sidebar-link">
        <i data-lucide="git-branch"></i>
        <span>{{ __('public.portal.nav_dependents', [], $l) ?: 'Dependents' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_privacy', [], $l) ?: 'Privacy & Access' }}</div>
    <a href="{{ route('portals.patient.logs') }}" class="sidebar-link">
        <i data-lucide="history"></i>
        <span>{{ __('public.portal.nav_access_logs', [], $l) ?: 'Access Logs' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_resources', [], $l) ?: 'Resources' }}</div>
    <a href="{{ route('public.care-map') }}" class="sidebar-link">
        <i data-lucide="map-pin"></i>
        <span>{{ __('public.portal.nav_care_map', [], $l) ?: 'Care Map' }}</span>
    </a>
    <a href="{{ route('public.help') }}" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span>{{ __('public.portal.nav_help', [], $l) ?: 'Help' }}</span>
    </a>
</div>

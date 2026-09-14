{{-- Records + dependents: shared by every patient-family sidebar (patient, guardian, caregiver, dependent manager).
     Every route here honours the guardian "viewing" switch (routes/web_family.php). --}}
@php $l = app()->getLocale(); @endphp
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('family.nav_records', [], $l) }}</div>
    <a href="{{ route('portals.patient.immunizations') }}" class="sidebar-link {{ request()->routeIs('portals.patient.immunizations') ? 'active' : '' }}">
        <i data-lucide="syringe"></i><span>{{ __('family.nav_immunizations', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.patient.timeline') }}" class="sidebar-link {{ request()->routeIs('portals.patient.timeline') ? 'active' : '' }}">
        <i data-lucide="activity"></i><span>{{ __('family.nav_timeline', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.patient.documents') }}" class="sidebar-link {{ request()->routeIs('portals.patient.documents*') ? 'active' : '' }}">
        <i data-lucide="file-text"></i><span>{{ __('family.nav_documents', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.patient.allergies') }}" class="sidebar-link {{ request()->routeIs('portals.patient.allergies') ? 'active' : '' }}">
        <i data-lucide="alert-triangle"></i><span>{{ __('family.nav_allergies', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.patient.clinical') }}" class="sidebar-link {{ request()->routeIs('portals.patient.clinical') ? 'active' : '' }}">
        <i data-lucide="stethoscope"></i><span>{{ __('family.nav_clinical', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.patient.care-plans') }}" class="sidebar-link {{ request()->routeIs('portals.patient.care-plans') ? 'active' : '' }}">
        <i data-lucide="clipboard-list"></i><span>{{ __('family.nav_care_plans', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.patient.referrals') }}" class="sidebar-link {{ request()->routeIs('portals.patient.referrals') ? 'active' : '' }}">
        <i data-lucide="arrow-right-left"></i><span>{{ __('public.pat_nav_referrals', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.patient.access-logs') }}" class="sidebar-link {{ request()->routeIs('portals.patient.access-logs') ? 'active' : '' }}">
        <i data-lucide="history"></i><span>{{ __('family.nav_access_logs', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.patient.health-id-card') }}" class="sidebar-link">
        <i data-lucide="printer"></i><span>{{ __('family.nav_print_card', [], $l) }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('family.nav_dependents', [], $l) }}</div>
    <a href="{{ route('portals.guardian.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.guardian.dashboard') ? 'active' : '' }}">
        <i data-lucide="baby"></i><span>{{ __('family.nav_dependents', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.patient.family') }}" class="sidebar-link {{ request()->routeIs('portals.patient.family') || request()->routeIs('portals.patient.family.add') || request()->routeIs('portals.patient.family.invite') ? 'active' : '' }}">
        <i data-lucide="users"></i><span>{{ __('family.nav_family', [], $l) }}</span>
    </a>
    @if(auth()->user()?->patient_id)
    <a href="{{ route('portals.patient.family.invitations') }}" class="sidebar-link {{ request()->routeIs('portals.patient.family.invitations*') ? 'active' : '' }}">
        <i data-lucide="mail"></i><span>{{ __('family.nav_invitations', [], $l) }}</span>
    </a>
    @endif
</div>

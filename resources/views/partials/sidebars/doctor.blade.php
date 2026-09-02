@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge" style="background:rgba(37,99,235,.2);border-color:rgba(37,99,235,.4);color:#93C5FD;">
    <i data-lucide="stethoscope" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    {{ __('portal.doctor_role', [], $l) ?: 'Doctor' }}
</div>
<div style="margin-bottom:var(--p-space-3);"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_dashboard', [], $l) ?: 'Overview' }}</div>
    <a href="{{ route('portals.staff') }}" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
    <a href="{{ route('portals.staff.messages') }}" class="sidebar-link {{ request()->routeIs('portals.staff.messages*') ? 'active' : '' }}">
        <i data-lucide="messages-square"></i>
        <span>{{ __('messaging.nav', [], $l) ?: 'Messages' }}</span>
    </a>
    <a href="{{ route('portals.staff.tasks') }}" class="sidebar-link {{ request()->routeIs('portals.staff.tasks*') ? 'active' : '' }}">
        <i data-lucide="check-square"></i>
        <span>{{ __('tasks.nav', [], $l) ?: 'Tasks' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_clinical', [], $l) ?: 'Clinical' }}</div>
    <a href="{{ route('portals.staff.visits') }}" class="sidebar-link">
        <i data-lucide="clipboard-list"></i>
        <span>{{ __('public.portal.nav_visits', [], $l) ?: 'Visits' }}</span>
    </a>
    <a href="{{ route('portals.staff.appointments') }}" class="sidebar-link">
        <i data-lucide="calendar-check-2"></i>
        <span>{{ __('public.portal.nav_appointments', [], $l) ?: 'Appointments' }}</span>
    </a>
    <a href="{{ route('portals.staff.queue') }}" class="sidebar-link">
        <i data-lucide="list-ordered"></i>
        <span>{{ __('public.portal.nav_queue', [], $l) ?: 'Patient Queue' }}</span>
    </a>
    <a href="{{ route('portals.staff.referrals') }}" class="sidebar-link">
        <i data-lucide="send"></i>
        <span>{{ __('public.portal.nav_referrals', [], $l) ?: 'Referrals' }}</span>
    </a>
    <a href="{{ route('portals.staff.immunizations') }}" class="sidebar-link">
        <i data-lucide="syringe"></i>
        <span>{{ __('public.portal.nav_immunizations', [], $l) ?: 'Immunizations' }}</span>
    </a>
    <a href="{{ route('portals.staff.prescriptions') }}" class="sidebar-link">
        <i data-lucide="clipboard-list"></i>
        <span>{{ __('public.portal.nav_prescriptions', [], $l) ?: 'Prescriptions' }}</span>
    </a>
    <a href="{{ route('portals.staff.prescriptions.create') }}" class="sidebar-link {{ request()->routeIs('portals.staff.prescriptions.create') ? 'active' : '' }}">
        <i data-lucide="clipboard-plus"></i>
        <span>{{ __('public.portal.nav_prescribe', [], $l) ?: 'New Prescription' }}</span>
    </a>
    <a href="{{ route('portals.staff.lab_orders') }}" class="sidebar-link">
        <i data-lucide="microscope"></i>
        <span>{{ __('public.portal.nav_lab_orders', [], $l) ?: 'Lab Orders' }}</span>
    </a>
    @feature('clinical_decision_support')
    @endfeature
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_operations', [], $l) ?: 'Operations' }}</div>
    @feature('billing')
    <a href="{{ route('portals.staff.billing') }}" class="sidebar-link">
        <i data-lucide="receipt"></i>
        <span>{{ __('public.portal.nav_billing', [], $l) ?: 'Billing' }}</span>
    </a>
    @endfeature
    @feature('analytics_dashboards')
    @endfeature
    <a href="{{ route('portals.staff.support') }}" class="sidebar-link">
        <i data-lucide="headset"></i>
        <span>{{ __('public.portal.nav_support', [], $l) ?: 'Support' }}</span>
    </a>
</div>

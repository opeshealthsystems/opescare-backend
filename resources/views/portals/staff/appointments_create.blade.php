@extends('layouts.portal')

@section('title', __('public.staff_portal.btn_book_appointment', [], app()->getLocale()) ?: 'Book Appointment')

@section('sidebar_role_badge')
<div class="sidebar-role-badge">{{ __('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff' }}</div>
@endsection
@section('sidebar_user_role', __('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff')

@section('sidebar_nav')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.stf_nav_overview') }}</div>
    <a href="{{ route('portals.staff') }}" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], app()->getLocale()) ?: 'Dashboard' }}</span>
    </a>
    @feature('analytics_dashboards')
    @endfeature
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.stf_nav_clinical') }}</div>
    <a href="{{ route('portals.staff.appointments') }}" class="sidebar-link active">
        <i data-lucide="calendar-check-2"></i>
        <span>{{ __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments' }}</span>
    </a>
    <a href="{{ route('portals.staff.queue') }}" class="sidebar-link">
        <i data-lucide="list-ordered"></i>
        <span>{{ __('public.portal.nav_queue', [], app()->getLocale()) ?: 'Patient Queue' }}</span>
    </a>
    <a href="{{ route('portals.staff.visits') }}" class="sidebar-link">
        <i data-lucide="stethoscope"></i>
        <span>{{ __('public.portal.nav_visits', [], app()->getLocale()) ?: 'Visits' }}</span>
    </a>
    @feature('clinical_decision_support')
    @endfeature
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.stf_nav_hr_staff') }}</div>
    <a href="{{ route('portals.staff.hr.directory') }}" class="sidebar-link">
        <i data-lucide="users"></i>
        <span>{{ __('public.portal.nav_staff_directory', [], app()->getLocale()) ?: 'Directory' }}</span>
    </a>
    <a href="{{ route('portals.staff.hr.shifts') }}" class="sidebar-link">
        <i data-lucide="clock"></i>
        <span>{{ __('public.portal.nav_staff_shifts', [], app()->getLocale()) ?: 'Shifts' }}</span>
    </a>
    <a href="{{ route('portals.staff.hr.roster') }}" class="sidebar-link">
        <i data-lucide="calendar-range"></i>
        <span>{{ __('public.portal.nav_staff_roster', [], app()->getLocale()) ?: 'Duty Roster' }}</span>
    </a>
    <a href="{{ route('portals.staff.hr.leave') }}" class="sidebar-link">
        <i data-lucide="plane-takeoff"></i>
        <span>{{ __('public.portal.nav_staff_leave', [], app()->getLocale()) ?: 'Leave' }}</span>
    </a>
</div>
@feature('inventory_ops')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.stf_nav_inventory') }}</div>
    <a href="{{ route('portals.staff.inventory.pharmacy') }}" class="sidebar-link">
        <i data-lucide="pill"></i>
        <span>{{ __('public.portal.nav_inventory_pharmacy', [], app()->getLocale()) ?: 'Pharmacy' }}</span>
    </a>
    <a href="{{ route('portals.staff.inventory.blood') }}" class="sidebar-link">
        <i data-lucide="droplets"></i>
        <span>{{ __('public.portal.nav_inventory_blood', [], app()->getLocale()) ?: 'Blood Bank' }}</span>
    </a>
</div>
@endfeature
@feature('inventory_ops')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.stf_nav_supply_chain') }}</div>
    <a href="{{ route('portals.staff.supply') }}" class="sidebar-link {{ request()->routeIs('portals.staff.supply*') ? 'active' : '' }}">
        <i data-lucide="package"></i>
        <span>{{ __('public.stf_nav_supply_chain') }}</span>
    </a>
</div>
@endfeature
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.stf_nav_operations') }}</div>
    @feature('billing')
    <a href="{{ route('portals.staff.billing') }}" class="sidebar-link">
        <i data-lucide="receipt"></i>
        <span>{{ __('public.portal.nav_billing', [], app()->getLocale()) ?: 'Billing' }}</span>
    </a>
    @endfeature
    <a href="{{ route('portals.staff.support') }}" class="sidebar-link">
        <i data-lucide="headset"></i>
        <span>{{ __('public.portal.nav_support', [], app()->getLocale()) ?: 'Support' }}</span>
    </a>
    @feature('insurance')
    <a href="{{ route('portals.insurance.policies') }}" class="sidebar-link">
        <i data-lucide="shield-check"></i>
        <span>{{ __('public.portal.nav_insurance', [], app()->getLocale()) ?: 'Insurance' }}</span>
    </a>
    @endfeature
    <a href="{{ route('portals.staff.data_import.index') }}" class="sidebar-link">
        <i data-lucide="upload-cloud"></i>
        <span>{{ __('public.portal.nav_data_import', [], app()->getLocale()) ?: 'Data Import' }}</span>
    </a>
    <a href="{{ route('portals.staff.search') }}" class="sidebar-link {{ request()->routeIs('portals.staff.search') ? 'active' : '' }}">
        <i data-lucide="search"></i>
        <span>{{ __('public.portal.nav_search', [], app()->getLocale()) ?: 'Global Search' }}</span>
    </a>
    <a href="{{ route('portals.staff.files.index') }}" class="sidebar-link {{ request()->routeIs('portals.staff.files*') ? 'active' : '' }}">
        <i data-lucide="paperclip"></i>
        <span>{{ __('public.portal.nav_files', [], app()->getLocale()) ?: 'Files & Attachments' }}</span>
    </a>
    <a href="{{ route('portals.staff.wards') }}" class="sidebar-link {{ request()->routeIs('portals.staff.wards*') ? 'active' : '' }}">
        <i data-lucide="bed"></i>
        <span>{{ __('public.portal.nav_wards', [], app()->getLocale()) ?: 'Wards & Beds' }}</span>
    </a>
</div>
@endsection

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.staff_portal.btn_book_appointment', [], app()->getLocale()) ?: 'Book Appointment')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.staff_portal.btn_book_appointment', [], app()->getLocale()) ?: 'Book Appointment' }}</h1>
        <p class="page-subtitle">{{ __('public.staff_portal.appointments_subtitle', [], app()->getLocale()) ?: 'Schedule a new patient appointment.' }}</p>
    </div>
    <a href="{{ route('portals.staff.appointments') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i>
        {{ __('public.stf_back') }}
    </a>
</div>

@if(session('error'))
    <div class="auth-alert auth-alert-danger mb-4">
        <i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div>
    </div>
@endif

<div class="panel">
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.staff.appointments.store') }}">
            @csrf

            <div class="form-group mb-4">
                <label class="form-label">{{ __('public.stf_appt_patient_id') }}</label>
                @if(count($patients) > 0)
                    <select name="patient_id" class="form-control" required>
                        <option value="">{{ __('public.stf_select_patient') }}</option>
                        @foreach($patients as $p)
                            <option value="{{ $p->id }}" {{ old('patient_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->health_id ?? $p->id }} ({{ $p->first_name ?? '' }} {{ $p->last_name ?? '' }})
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="patient_id" class="form-control" required
                        placeholder="{{ __('public.stf_appt_enter_patient_id') }}" value="{{ old('patient_id') }}">
                @endif
                @error('patient_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            {{-- The facility is the one this session is working in, shown rather
                 than chosen. It used to be a free choice posted in the body, so a
                 clerk could book into any facility in the country by picking a
                 different option; the controller now takes it from the session and
                 ignores the field entirely, so offering a picker would only mislead. --}}
            <div class="form-group mb-4">
                <label class="form-label">{{ __('public.stf_appt_facility') }}</label>
                <input type="text" class="form-control" disabled
                       value="{{ $activeFacilityName ?? __('public.stf_appt_facility_session') }}">
            </div>

            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_appt_type') }}</label>
                    <select name="appointment_type" class="form-control" required>
                        <option value="general" {{ old('appointment_type') === 'general' ? 'selected' : '' }}>
                            {{ __('public.staff_portal.type_general', [], app()->getLocale()) ?: 'General' }}
                        </option>
                        <option value="follow_up" {{ old('appointment_type') === 'follow_up' ? 'selected' : '' }}>
                            {{ __('public.staff_portal.type_followup', [], app()->getLocale()) ?: 'Follow-up' }}
                        </option>
                        <option value="specialist" {{ old('appointment_type') === 'specialist' ? 'selected' : '' }}>
                            {{ __('public.staff_portal.type_specialist', [], app()->getLocale()) ?: 'Specialist' }}
                        </option>
                        <option value="lab" {{ old('appointment_type') === 'lab' ? 'selected' : '' }}>
                            {{ __('public.staff_portal.type_lab', [], app()->getLocale()) ?: 'Lab / Diagnostics' }}
                        </option>
                        <option value="pharmacy" {{ old('appointment_type') === 'pharmacy' ? 'selected' : '' }}>
                            {{ __('public.staff_portal.type_pharmacy', [], app()->getLocale()) ?: 'Pharmacy' }}
                        </option>
                        <option value="emergency" {{ old('appointment_type') === 'emergency' ? 'selected' : '' }}>
                            {{ __('public.staff_portal.type_emergency', [], app()->getLocale()) ?: 'Emergency' }}
                        </option>
                    </select>
                    @error('appointment_type')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_appt_datetime') }}</label>
                    <input type="datetime-local" name="scheduled_at" class="form-control" required
                        value="{{ old('scheduled_at', now()->addDay()->format('Y-m-d\TH:i')) }}">
                    @error('scheduled_at')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="form-group mb-6">
                <label class="form-label">{{ __('public.stf_appt_reason') }}</label>
                <textarea name="reason" class="form-control" rows="3" maxlength="500"
                    placeholder="{{ __('public.stf_appt_reason_placeholder') }}">{{ old('reason') }}</textarea>
            </div>

            <div class="row-actions-inline">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="calendar-plus"></i>
                    {{ __('public.staff_portal.btn_book_appointment', [], app()->getLocale()) ?: 'Book Appointment' }}
                </button>
                <a href="{{ route('portals.staff.appointments') }}" class="btn btn-ghost">{{ __('public.stf_cancel') }}</a>
            </div>
        </form>
    </div>
</div>

@endsection

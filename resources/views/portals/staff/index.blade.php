@extends('layouts.portal')

@section('title', __('public.staff_portal.dashboard_title', [], app()->getLocale()) ?: 'Staff Clinical Portal')

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))

@section('content')

<div class="page-head">
    <h2>{{ __('public.staff_portal.dashboard_title', [], app()->getLocale()) ?: 'Staff Clinical Portal' }}</h2>
</div>
<p class="page-subtitle mb-6">{{ __('public.staff_portal.dashboard_subtitle', [], app()->getLocale()) ?: 'Manage appointments, queues, and patient care from one place.' }}</p>

{{-- KPI Cards --}}
<div class="stat-grid">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="calendar-check-2"></i></div>
        <div class="stat-card__label">{{ __('public.staff_portal.kpi_todays_appointments', [], app()->getLocale()) ?: "Today's Appointments" }}</div>
        <div class="stat-card__value">{{ $kpis['todays_appointments'] ?? 0 }}</div>
        <div class="stat-card__hint">{{ __('public.staff_portal.kpi_scheduled_today', [], app()->getLocale()) ?: 'Scheduled today' }}</div>
    </div>
    <div class="stat-card stat-card--teal">
        <div class="stat-card__head"><i data-lucide="list-ordered"></i></div>
        <div class="stat-card__label">{{ __('public.staff_portal.kpi_patient_queue', [], app()->getLocale()) ?: 'Patient Queue' }}</div>
        <div class="stat-card__value">{{ $kpis['in_queue'] ?? 0 }}</div>
        <div class="stat-card__hint">{{ __('public.staff_portal.kpi_currently_waiting', [], app()->getLocale()) ?: 'Currently waiting' }}</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__head"><i data-lucide="send"></i></div>
        <div class="stat-card__label">{{ __('public.staff_portal.kpi_pending_referrals', [], app()->getLocale()) ?: 'Pending Referrals' }}</div>
        <div class="stat-card__value">{{ $kpis['pending_referrals'] ?? 0 }}</div>
        <div class="stat-card__hint">{{ __('public.staff_portal.kpi_awaiting_action', [], app()->getLocale()) ?: 'Awaiting action' }}</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="receipt"></i></div>
        <div class="stat-card__label">{{ __('public.staff_portal.kpi_open_invoices', [], app()->getLocale()) ?: 'Open Invoices' }}</div>
        <div class="stat-card__value">{{ $kpis['open_invoices'] ?? 0 }}</div>
        <div class="stat-card__hint">{{ __('public.staff_portal.kpi_unpaid_balance', [], app()->getLocale()) ?: 'Unpaid balance' }}</div>
    </div>
</div>

<div class="grid-main-side mt-6">

    {{-- Patient Verification Form --}}
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">
                <i data-lucide="shield-check"></i>
                {{ __('public.staff_portal.patient_verification', [], app()->getLocale()) ?: 'Patient Verification' }}
            </h2>
        </div>
        <div class="panel-body">
            <p class="form-hint mb-4">
                {{ __('public.staff_portal.verification_desc', [], app()->getLocale()) ?: 'Enter a patient Health ID to verify their identity before providing care.' }}
            </p>
            <form method="GET" action="{{ route('portals.staff') }}" autocomplete="off">
                <div class="form-group">
                    <label class="form-label form-label-required" for="health_id">
                        {{ __('public.medical_id.health_id', [], app()->getLocale()) ?: 'Health ID' }}
                    </label>
                    <div class="form-search">
                        <span class="search-icon">
                            <i data-lucide="search"></i>
                        </span>
                        <input
                            type="text"
                            id="health_id"
                            name="health_id"
                            class="form-control mono"
                            placeholder="e.g. CM-HID-7KQ9-MP42-X8D1"
                            value="{{ request('health_id') }}"
                        >
                    </div>
                    <span class="form-hint">{{ __('public.staff_portal.health_id_hint', [], app()->getLocale()) ?: 'Enter the full Health ID as printed on the patient card.' }}</span>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label form-label-required" for="purpose">
                            {{ __('public.staff_portal.purpose', [], app()->getLocale()) ?: 'Access Purpose' }}
                        </label>
                        <select id="purpose" name="purpose" class="form-control">
                            <option value="treatment" @selected(request('purpose') === 'treatment')>{{ __('public.staff_portal.purpose_treatment', [], app()->getLocale()) ?: 'Treatment' }}</option>
                            <option value="pharmacy_dispense" @selected(request('purpose') === 'pharmacy_dispense')>{{ __('public.staff_portal.purpose_pharmacy', [], app()->getLocale()) ?: 'Pharmacy Dispense' }}</option>
                            <option value="lab_order" @selected(request('purpose') === 'lab_order')>{{ __('public.staff_portal.purpose_lab', [], app()->getLocale()) ?: 'Lab Order' }}</option>
                            <option value="insurance_claim" @selected(request('purpose') === 'insurance_claim')>{{ __('public.staff_portal.purpose_insurance', [], app()->getLocale()) ?: 'Insurance Claim' }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="facility_id">
                            {{ __('public.staff_portal.facility_id', [], app()->getLocale()) ?: 'Facility ID' }}
                        </label>
                        <input
                            type="text"
                            id="facility_id"
                            name="facility_id"
                            class="form-control"
                            placeholder="{{ __('public.staff_portal.facility_id_placeholder', [], app()->getLocale()) ?: 'FAC-XXXXX' }}"
                            value="{{ request('facility_id') }}"
                        >
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="search"></i>
                        {{ __('public.medical_id.verify_health_id', [], app()->getLocale()) ?: 'Verify Patient' }}
                    </button>
                </div>
            </form>

            @isset($verification)
                @if($verification['patient'])
                    @php
                        $vp = $verification['patient'];
                        $vStatus = $vp->verification_status instanceof \BackedEnum ? $vp->verification_status->value : $vp->verification_status;
                    @endphp
                    <div class="alert alert-success mt-4" role="status">
                        <i data-lucide="user-check"></i>
                        <div>
                            <strong>{{ $vp->first_name }} {{ $vp->last_name }}</strong>
                            <span class="badge badge-info">@enum($vStatus ?? 'active', 'verification')</span>
                            <div class="td-muted mt-1">
                                <span class="mono">{{ $vp->health_id }}</span>
                                @if($vp->date_of_birth) · {{ \Carbon\Carbon::parse($vp->date_of_birth)->format('d M Y') }}@endif
                                @if($vp->sex) · @enum($vp->sex)@endif
                            </div>
                            <div class="form-hint mt-1">{{ __('public.staff_portal.verification_logged', [], app()->getLocale()) ?: 'Identity confirmed — this access has been logged and audited.' }}</div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-danger mt-4" role="alert">
                        <i data-lucide="user-x"></i>
                        <div>{{ __('public.staff_portal.verification_not_found', [], app()->getLocale()) ?: 'No patient found for that Health ID. Check the ID and try again.' }} <span class="mono">{{ $verification['health_id'] }}</span></div>
                    </div>
                @endif
            @endisset
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">
                <i data-lucide="zap"></i>
                {{ __('public.staff_portal.quick_links', [], app()->getLocale()) ?: 'Quick Links' }}
            </h2>
        </div>
        <div class="panel-body">
            @php $roleSlug = auth()->user()->role?->name ?? ''; @endphp
            <div class="grid-2">
                {{-- All roles --}}
                <a href="{{ route('portals.staff.appointments') }}" class="btn btn-secondary">
                    <i data-lucide="calendar-check-2"></i>
                    {{ __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments' }}
                </a>
                <a href="{{ route('portals.staff.queue') }}" class="btn btn-secondary">
                    <i data-lucide="list-ordered"></i>
                    {{ __('public.portal.nav_queue', [], app()->getLocale()) ?: 'Patient Queue' }}
                </a>
                <a href="{{ route('portals.staff.support') }}" class="btn btn-secondary">
                    <i data-lucide="headset"></i>
                    {{ __('public.portal.nav_support', [], app()->getLocale()) ?: 'Support' }}
                </a>
                <a href="{{ route('portals.staff.immunizations') }}" class="btn btn-secondary">
                    <i data-lucide="syringe"></i>
                    {{ __('public.portal.nav_immunizations', [], app()->getLocale()) ?: 'Immunizations' }}
                </a>
                <a href="{{ route('portals.staff.visits') }}" class="btn btn-secondary">
                    <i data-lucide="stethoscope"></i>
                    {{ __('public.portal.nav_visits', [], app()->getLocale()) ?: 'Visits' }}
                </a>
                @feature('clinical_decision_support')
                @endfeature
                <a href="{{ route('portals.staff.files.index') }}" class="btn btn-secondary">
                    <i data-lucide="folder"></i>
                    {{ __('public.portal.nav_files', [], app()->getLocale()) ?: 'Files' }}
                </a>
                <a href="{{ route('portals.staff.search') }}" class="btn btn-secondary">
                    <i data-lucide="search"></i>
                    {{ __('public.portal.nav_search', [], app()->getLocale()) ?: 'Global Search' }}
                </a>
                <a href="{{ route('portals.staff.wards') }}" class="btn btn-secondary">
                    <i data-lucide="bed"></i>
                    {{ __('public.portal.nav_wards', [], app()->getLocale()) ?: 'Wards' }}
                </a>

                {{-- Billing: receptionist, cashier, billing/finance staff, admins, doctors --}}
                @if(in_array($roleSlug, ['receptionist', 'front_desk', 'cashier', 'billing_officer', 'finance_manager', 'hospital_admin', 'clinic_admin', 'doctor']))
                @feature('billing')
                <a href="{{ route('portals.staff.billing') }}" class="btn btn-secondary">
                    <i data-lucide="receipt"></i>
                    {{ __('public.portal.nav_billing', [], app()->getLocale()) ?: 'Billing' }}
                </a>
                @endfeature
                @endif

                {{-- Referrals: doctor, hospital_admin, clinic_admin --}}
                @if(in_array($roleSlug, ['doctor', 'hospital_admin', 'clinic_admin']))
                <a href="{{ route('portals.staff.referrals') }}" class="btn btn-secondary">
                    <i data-lucide="send"></i>
                    {{ __('public.portal.nav_referrals', [], app()->getLocale()) ?: 'Referrals' }}
                </a>
                @endif

                {{-- Analytics: hospital_admin, clinic_admin, doctor --}}
                @if(in_array($roleSlug, ['hospital_admin', 'clinic_admin', 'doctor']))
                @feature('analytics_dashboards')
                @endfeature
                @endif

                {{-- Telemedicine: doctor only --}}
                @if($roleSlug === 'doctor')
                @endif

                {{-- HR Directory: hospital_admin, clinic_admin --}}
                @if(in_array($roleSlug, ['hospital_admin', 'clinic_admin']))
                <a href="{{ route('portals.staff.hr.directory') }}" class="btn btn-secondary">
                    <i data-lucide="users"></i>
                    {{ __('public.portal.nav_staff_directory', [], app()->getLocale()) ?: 'HR Directory' }}
                </a>
                @endif

                {{-- Pharmacy Stock: pharmacist, hospital_admin, clinic_admin --}}
                @if(in_array($roleSlug, ['pharmacist', 'hospital_admin', 'clinic_admin']))
                @feature('inventory_ops')
                <a href="{{ route('portals.staff.inventory.pharmacy') }}" class="btn btn-secondary">
                    <i data-lucide="pill"></i>
                    {{ __('public.portal.nav_inventory_pharmacy', [], app()->getLocale()) ?: 'Pharmacy Stock' }}
                </a>
                @endfeature
                @endif

                {{-- Supply Chain: hospital_admin, clinic_admin, pharmacist --}}
                @if(in_array($roleSlug, ['hospital_admin', 'clinic_admin', 'pharmacist']))
                @feature('inventory_ops')
                <a href="{{ route('portals.staff.supply') }}" class="btn btn-secondary">
                    <i data-lucide="package"></i>
                    {{ __('public.staff_portal.nav_supply_chain', [], app()->getLocale()) ?: 'Supply Chain' }}
                </a>
                @endfeature
                @endif

                {{-- Data Import: hospital_admin, clinic_admin --}}
                @if(in_array($roleSlug, ['hospital_admin', 'clinic_admin']))
                <a href="{{ route('portals.staff.data_import.index') }}" class="btn btn-secondary">
                    <i data-lucide="upload"></i>
                    {{ __('public.portal.nav_data_import', [], app()->getLocale()) ?: 'Data Import' }}
                </a>
                @endif

                {{-- Prescriptions: doctor, nurse, pharmacist --}}
                @if(in_array($roleSlug, ['doctor', 'nurse', 'pharmacist']))
                <a href="{{ route('portals.staff.prescriptions') }}" class="btn btn-secondary">
                    <i data-lucide="clipboard-plus"></i>
                    {{ __('public.portal.nav_prescriptions', [], app()->getLocale()) ?: 'Prescriptions' }}
                </a>
                @endif

                {{-- Lab Orders: doctor, nurse, lab technicians --}}
                @if(in_array($roleSlug, ['doctor', 'nurse', 'labtech', 'lab_scientist', 'lab_manager', 'lab_validator', 'sample_collection']))
                <a href="{{ route('portals.staff.lab_orders') }}" class="btn btn-secondary">
                    <i data-lucide="flask-conical"></i>
                    {{ __('public.staff_portal.nav_lab_orders', [], app()->getLocale()) ?: 'Lab Orders' }}
                </a>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- Clinical Safety Disclaimer --}}
<div class="alert alert-warning mt-6">
    <i data-lucide="triangle-alert"></i>
    <div>
        <strong>{{ __('public.staff_portal.disclaimer_title', [], app()->getLocale()) ?: 'Clinical Safety Disclaimer' }}</strong>
        {{ __('public.staff_portal.disclaimer_text', [], app()->getLocale()) ?: 'This portal is for authorised clinical staff only. All access is logged and audited. Do not share login credentials. Patient data must be handled in accordance with applicable data protection regulations and facility privacy policies. In an emergency, use the break-glass access procedure and document the clinical justification.' }}
    </div>
</div>

@endsection

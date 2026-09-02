@extends('layouts.portal')

@section('title', __('public.staff_portal.page_heading_consult', [], app()->getLocale()) ?: 'Consultation')

@section('sidebar_role_badge')
<div class="sidebar-role-badge">{{ __('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff' }}</div>
@endsection
@section('sidebar_user_role', __('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff')

@section('sidebar_nav')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.nav_section_overview', [], app()->getLocale()) ?: 'Overview' }}</div>
    <a href="{{ route('portals.staff') }}" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], app()->getLocale()) ?: 'Dashboard' }}</span>
    </a>
    @feature('analytics_dashboards')
    @endfeature
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.nav_section_clinical', [], app()->getLocale()) ?: 'Clinical' }}</div>
    <a href="{{ route('portals.staff.appointments') }}" class="sidebar-link">
        <i data-lucide="calendar-check-2"></i>
        <span>{{ __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments' }}</span>
    </a>
    <a href="{{ route('portals.staff.queue') }}" class="sidebar-link">
        <i data-lucide="list-ordered"></i>
        <span>{{ __('public.portal.nav_queue', [], app()->getLocale()) ?: 'Patient Queue' }}</span>
    </a>
    <a href="{{ route('portals.staff.visits') }}" class="sidebar-link active">
        <i data-lucide="stethoscope"></i>
        <span>{{ __('public.portal.nav_visits', [], app()->getLocale()) ?: 'Visits' }}</span>
    </a>
    @feature('clinical_decision_support')
    @endfeature
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.nav_section_hr', [], app()->getLocale()) ?: 'HR & Staff' }}</div>
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
    <div class="sidebar-nav-label">{{ __('public.staff_portal.nav_section_inventory', [], app()->getLocale()) ?: 'Inventory' }}</div>
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
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.nav_section_operations', [], app()->getLocale()) ?: 'Operations' }}</div>
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
</div>
@endsection

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.staff_portal.breadcrumb_section_consult', [], app()->getLocale()) ?: 'Consultation')

@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-head">
    <h2>{{ __('public.staff_portal.page_heading_consult', [], $l) ?: 'Clinical Consultation' }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.staff.visits') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i>
        {{ __('public.staff_portal.btn_back_to_visits', [], $l) ?: 'Back to Visits' }}
    </a>
</div>
<p class="page-subtitle mb-4">
    {{ __('public.staff_portal.lbl_patient', [], $l) ?: 'Patient' }}: <strong class="mono">{{ $visit->patient?->health_id ?? $visit->patient_id }}</strong>
    &nbsp;·&nbsp; {{ __('public.staff_portal.col_status', [], $l) ?: 'Status' }}: <span class="badge badge-primary">@enum($visit->status)</span>
</p>

@if(session('error'))
    <div class="alert alert-danger mb-4">
        <i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div>
    </div>
@endif

<div class="grid-main-side">

    {{-- Consultation Form --}}
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">
                <i data-lucide="file-pen"></i>
                {{ __('public.staff_portal.panel_clinical_note', [], $l) ?: 'Clinical Note' }}
            </h2>
        </div>
        <div class="panel-body">
            <form method="POST" action="{{ route('portals.staff.visits.consult.store', $visit->id) }}">
                @csrf
                <div class="form-group mb-4">
                    <label class="form-label form-label-required">{{ __('public.staff_portal.field_history_hpi', [], $l) ?: 'History of Present Illness' }} *</label>
                    <textarea name="history_of_present_illness" class="form-control" rows="5"
                        required minlength="10" maxlength="5000"
                        placeholder="{{ __('public.staff_portal.ph_history_hpi', [], $l) ?: 'Describe the presenting complaint, onset, duration, character, associated symptoms…' }}">{{ old('history_of_present_illness') }}</textarea>
                </div>
                <div class="form-group mb-4">
                    <label class="form-label">{{ __('public.staff_portal.field_examination', [], $l) ?: 'Examination Findings' }}</label>
                    <textarea name="examination_findings" class="form-control" rows="4"
                        maxlength="5000"
                        placeholder="{{ __('public.staff_portal.ph_examination', [], $l) ?: 'Physical examination findings, system review…' }}">{{ old('examination_findings') }}</textarea>
                </div>
                <div class="form-group mb-4">
                    <label class="form-label">{{ __('public.staff_portal.field_treatment_plan', [], $l) ?: 'Treatment Plan / Assessment' }}</label>
                    <textarea name="treatment_plan" class="form-control" rows="4"
                        maxlength="5000"
                        placeholder="{{ __('public.staff_portal.ph_treatment_plan', [], $l) ?: 'Diagnosis, management plan, prescriptions, referrals, follow-up instructions…' }}">{{ old('treatment_plan') }}</textarea>
                </div>
                <div class="form-group mb-6">
                    <label class="form-label">{{ __('public.staff_portal.field_note_status', [], $l) ?: 'Note Status' }}</label>
                    <select name="status" class="form-control">
                        <option value="draft">{{ __('public.staff_portal.opt_save_draft', [], $l) ?: 'Save as Draft' }}</option>
                        <option value="signed">{{ __('public.staff_portal.opt_sign_finalize', [], $l) ?: 'Sign & Finalize' }}</option>
                    </select>
                    <span class="form-hint">{{ __('public.staff_portal.hint_signed_notes', [], $l) ?: 'Signed notes cannot be edited — only amended.' }}</span>
                </div>
                <div class="row-actions">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="file-pen"></i>
                        {{ __('public.staff_portal.btn_save_note', [], $l) ?: 'Save Note' }}
                    </button>
                    <a href="{{ route('portals.staff.visits') }}" class="btn btn-ghost">{{ __('public.staff_portal.filter_clear', [], $l) ?: 'Cancel' }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Sidebar: Previous notes + triage summary --}}
    <div>
        {{-- Triage Summary --}}
        @if($visit->triageRecords->isNotEmpty())
        <div class="panel mb-6">
            <div class="panel-header">
                <h2 class="panel-title">
                    <i data-lucide="activity"></i>
                    {{ __('public.staff_portal.panel_triage_summary', [], $l) ?: 'Triage Summary' }}
                </h2>
            </div>
            <div class="panel-body panel-body--flush">
                @php $triage = $visit->triageRecords->sortByDesc('created_at')->first(); @endphp
                <table class="kv-table">
                    <tr><td class="kv-strong">{{ __('public.staff_portal.lbl_complaint', [], $l) ?: 'Complaint' }}</td><td>{{ $triage->presenting_complaint ?? '--' }}</td></tr>
                    <tr><td class="kv-strong">{{ __('public.staff_portal.col_acuity', [], $l) ?: 'Acuity' }}</td><td>@enum($triage->acuity_score ?? '--')</td></tr>
                    <tr><td class="kv-strong">{{ __('public.staff_portal.lbl_pain', [], $l) ?: 'Pain' }}</td><td>{{ $triage->pain_score !== null ? $triage->pain_score . '/10' : '--' }}</td></tr>
                    @if($triage->vitalSigns->isNotEmpty())
                        @php $v = $triage->vitalSigns->first(); @endphp
                        <tr><td class="kv-strong">T</td><td>{{ $v->temperature ?? '--' }}°C</td></tr>
                        <tr><td class="kv-strong">BP</td><td>{{ $v->blood_pressure_systolic ?? '--' }}/{{ $v->blood_pressure_diastolic ?? '--' }} mmHg</td></tr>
                        <tr><td class="kv-strong">{{ __('public.staff_portal.field_pulse', [], $l) ?: 'Pulse' }}</td><td>{{ $v->pulse ?? '--' }} bpm</td></tr>
                        <tr><td class="kv-strong">SpO₂</td><td>{{ $v->oxygen_saturation ?? '--' }}%</td></tr>
                        @if($v->weight)<tr><td class="kv-strong">{{ __('public.staff_portal.lbl_weight', [], $l) ?: 'Weight' }}</td><td>{{ $v->weight }} kg</td></tr>@endif
                    @endif
                </table>
            </div>
        </div>
        @endif

        {{-- Previous Notes --}}
        @if($visit->clinicalNotes->isNotEmpty())
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">
                    <i data-lucide="notebook-text"></i>
                    {{ __('public.staff_portal.panel_prev_notes', [], $l) ?: 'Previous Notes' }} ({{ $visit->clinicalNotes->count() }})
                </h2>
            </div>
            <div class="panel-body panel-body--flush">
                <div class="table-wrapper">
                    <table class="data-table">
                        <tbody>
                        @foreach($visit->clinicalNotes->sortByDesc('created_at') as $note)
                        <tr>
                            <td data-label="{{ __('public.staff_portal.col_date', [], $l) ?: 'Date' }}">{{ \Carbon\Carbon::parse($note->created_at)->format('M d, Y H:i') }}</td>
                            <td data-label="{{ __('public.staff_portal.col_status', [], $l) ?: 'Status' }}">
                                <span class="badge {{ $note->status === 'signed' ? 'badge-success' : 'badge-neutral' }}">@enum($note->status)</span>
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_summary', [], $l) ?: 'Summary' }}" class="td-muted">{{ Str::limit($note->history_of_present_illness ?? '', 120) }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

</div>

@endsection

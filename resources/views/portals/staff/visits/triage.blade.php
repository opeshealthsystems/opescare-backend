@extends('layouts.portal')

@section('title', __('public.staff_portal.page_heading_triage', [], app()->getLocale()) ?: 'Triage')

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
@feature('inventory_ops')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.nav_section_supply', [], app()->getLocale()) ?: 'Supply Chain' }}</div>
    <a href="{{ route('portals.staff.supply') }}" class="sidebar-link {{ request()->routeIs('portals.staff.supply*') ? 'active' : '' }}">
        <i data-lucide="package"></i>
        <span>{{ __('public.staff_portal.nav_supply_chain', [], app()->getLocale()) ?: 'Supply Chain' }}</span>
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
    <a href="{{ route('portals.staff.data_import.index') }}" class="sidebar-link">
        <i data-lucide="upload"></i>
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
@section('breadcrumb_section', __('public.staff_portal.breadcrumb_section_triage', [], app()->getLocale()) ?: 'Triage')

@php $l = app()->getLocale(); @endphp

@section('content')

@php
    use App\Modules\Triage\Services\TriageService;
    $lastTriage = $visit->triageRecords->sortByDesc('created_at')->first();
    $isCritical = $lastTriage && in_array($lastTriage->acuity_score, ['critical', 'resuscitation']);
    $isEmergency = $visit->status === 'emergency';

    $vitalAlerts = [];
    if ($lastTriage && $lastTriage->vitalSigns->isNotEmpty()) {
        $v = $lastTriage->vitalSigns->first();
        $vitalAlerts = TriageService::assessVitals([
            'temperature'             => $v->temperature,
            'blood_pressure_systolic' => $v->blood_pressure_systolic,
            'pulse'                   => $v->pulse,
            'respiratory_rate'        => $v->respiratory_rate,
            'oxygen_saturation'       => $v->oxygen_saturation,
        ]);
    }
@endphp

<div class="page-head">
    <h2>
        @if($isEmergency)<i data-lucide="siren"></i> @endif
        {{ __('public.staff_portal.page_heading_triage', [], $l) ?: 'Triage Assessment' }}
    </h2>
    <div class="page-head__spacer"></div>
    @if(!$isEmergency)
        <button type="button" class="btn btn-danger btn-sm" onclick="openEscalateModal()">
            <i data-lucide="siren"></i> {{ __('public.staff_portal.btn_declare_emergency', [], $l) ?: 'Declare Emergency' }}
        </button>
    @endif
    <a href="{{ route('portals.staff.visits') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i> {{ __('public.staff_portal.btn_back', [], $l) ?: 'Back' }}
    </a>
</div>

<p class="page-subtitle mb-4">
    {{ __('public.staff_portal.lbl_patient', [], $l) ?: 'Patient' }}: <strong class="mono">{{ $visit->patient?->health_id ?? $visit->patient_id }}</strong>
    &nbsp;·&nbsp; {{ __('public.staff_portal.lbl_visit_id', [], $l) ?: 'Visit ID' }}: <span class="mono">{{ substr($visit->id, 0, 8) }}…</span>
    &nbsp;·&nbsp;
    @php
        $statusBadge = match($visit->status) {
            'emergency' => 'badge-danger',
            'in_triage' => 'badge-warning',
            'completed' => 'badge-success',
            default     => 'badge-neutral',
        };
    @endphp
    <span class="badge {{ $statusBadge }}">@enum($visit->status)</span>
</p>

{{-- Emergency Banner --}}
@if($isEmergency)
<div class="alert alert-danger mb-4">
    <i data-lucide="siren"></i>
    <div>
        <strong>{{ __('public.staff_portal.alert_emergency_banner', [], $l) ?: 'EMERGENCY — Resuscitation Level' }}</strong>
        <div>{{ __('public.staff_portal.alert_emergency_desc', [], $l) ?: 'This visit has been declared an emergency. Acuity: Resuscitation (Level 1).' }}</div>
    </div>
</div>
@elseif($isCritical)
<div class="alert alert-danger mb-4">
    <i data-lucide="alert-triangle"></i>
    <div>
        <strong>{{ __('public.staff_portal.alert_critical_acuity', [], $l) ?: 'Critical acuity detected.' }}</strong>
        <span>{{ __('public.staff_portal.col_acuity', [], $l) ?: 'Acuity' }}: @enum($lastTriage->acuity_score)</span>
    </div>
    <button type="button" class="btn btn-danger btn-sm" onclick="openEscalateModal()">{{ __('public.staff_portal.btn_escalate_emergency', [], $l) ?: 'Escalate to Emergency' }}</button>
</div>
@endif

{{-- Vital Sign Clinical Alerts --}}
@if(count($vitalAlerts) > 0)
<div class="mb-4">
    @foreach($vitalAlerts as $alert)
    <div class="alert {{ $alert['status'] === 'critical' ? 'alert-danger' : 'alert-warning' }} mb-3">
        <i data-lucide="{{ $alert['status'] === 'critical' ? 'x-circle' : 'alert-circle' }}"></i>
        <div><strong>{{ $alert['vital'] }}:</strong> {{ $alert['value'] }} — {{ $alert['note'] }}</div>
    </div>
    @endforeach
</div>
@endif

@if(session('success'))
    <div class="alert alert-success mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-4"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

{{-- Previous triage records --}}
@if($visit->triageRecords->isNotEmpty())
<div class="panel mb-6">
    <div class="panel-header">
        <h2 class="panel-title">
            <i data-lucide="clock"></i>
            {{ __('public.staff_portal.panel_prev_triage', [], $l) ?: 'Previous Triage Records' }}
        </h2>
    </div>
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('public.staff_portal.col_time', [], $l) ?: 'Time' }}</th>
                        <th>{{ __('public.staff_portal.col_complaint', [], $l) ?: 'Complaint' }}</th>
                        <th>{{ __('public.staff_portal.col_acuity', [], $l) ?: 'Acuity' }}</th>
                        <th>{{ __('public.staff_portal.col_pain', [], $l) ?: 'Pain' }}</th>
                        <th>{{ __('public.staff_portal.col_vitals', [], $l) ?: 'Vitals' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($visit->triageRecords->sortByDesc('created_at') as $triage)
                    @php
                        $acuityBadge = match($triage->acuity_score) {
                            'resuscitation','critical' => 'badge-danger',
                            'urgent'      => 'badge-warning',
                            'semi_urgent' => 'badge-primary',
                            default       => 'badge-neutral',
                        };
                        $v = $triage->vitalSigns->first();
                        $triageAlerts = $v ? TriageService::assessVitals([
                            'oxygen_saturation'       => $v->oxygen_saturation,
                            'pulse'                   => $v->pulse,
                            'blood_pressure_systolic' => $v->blood_pressure_systolic,
                            'temperature'             => $v->temperature,
                            'respiratory_rate'        => $v->respiratory_rate,
                        ]) : [];
                        $hasCriticalVital = collect($triageAlerts)->where('status','critical')->count() > 0;
                    @endphp
                    <tr>
                        <td data-label="{{ __('public.staff_portal.col_time', [], $l) ?: 'Time' }}">
                            {{ \Carbon\Carbon::parse($triage->created_at)->format('M d, H:i') }}
                            @if($hasCriticalVital)<span class="badge badge-critical">{{ __('public.staff_portal.alert_critical_acuity', [], $l) ?: 'Critical' }}</span>@endif
                        </td>
                        <td data-label="{{ __('public.staff_portal.col_complaint', [], $l) ?: 'Complaint' }}">{{ Str::limit($triage->presenting_complaint ?? '--', 50) }}</td>
                        <td data-label="{{ __('public.staff_portal.col_acuity', [], $l) ?: 'Acuity' }}"><span class="badge {{ $acuityBadge }}">@enum($triage->acuity_score ?? '--')</span></td>
                        <td data-label="{{ __('public.staff_portal.col_pain', [], $l) ?: 'Pain' }}">{{ $triage->pain_score !== null ? $triage->pain_score . '/10' : '--' }}</td>
                        <td data-label="{{ __('public.staff_portal.col_vitals', [], $l) ?: 'Vitals' }}" class="mono">
                            @if($v)
                                @php
                                    $spo2Crit = isset($v->oxygen_saturation) && $v->oxygen_saturation < 90;
                                    $pulseCrit = isset($v->pulse) && ($v->pulse < 50 || $v->pulse > 150);
                                @endphp
                                T:{{ $v->temperature ?? '--' }}°C
                                BP:{{ $v->blood_pressure_systolic ?? '--' }}/{{ $v->blood_pressure_diastolic ?? '--' }}
                                @if($pulseCrit)<span class="badge badge-critical">P:{{ $v->pulse }}</span>@else P:{{ $v->pulse ?? '--' }} @endif
                                @if($spo2Crit)<span class="badge badge-critical">SpO₂:{{ $v->oxygen_saturation }}%</span>@else SpO₂:{{ $v->oxygen_saturation ?? '--' }}% @endif
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- New Triage Form --}}
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">
            <i data-lucide="activity"></i>
            {{ __('public.staff_portal.panel_record_triage', [], $l) ?: 'Record Triage' }}
        </h2>
    </div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.staff.visits.triage.store', $visit->id) }}">
            @csrf

            <div class="form-group mb-4">
                <label class="form-label form-label-required">{{ __('public.staff_portal.field_presenting_complaint', [], $l) ?: 'Presenting Complaint' }} *</label>
                <textarea name="presenting_complaint" class="form-control" rows="3" required
                    maxlength="1000" placeholder="{{ __('public.staff_portal.ph_presenting_complaint', [], $l) ?: 'Chief complaint / reason for visit…' }}">{{ old('presenting_complaint') }}</textarea>
            </div>

            <div class="field-grid mb-4">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.staff_portal.field_acuity_score', [], $l) ?: 'Acuity Score' }} *</label>
                    <select name="acuity_score" id="acuity_score" class="form-control" required>
                        <option value="resuscitation">{{ __('public.staff_portal.opt_resuscitation', [], $l) ?: 'Resuscitation (Level 1)' }}</option>
                        <option value="critical">{{ __('public.staff_portal.opt_critical_acuity', [], $l) ?: 'Critical (Level 2)' }}</option>
                        <option value="urgent">{{ __('public.staff_portal.opt_urgent_acuity', [], $l) ?: 'Urgent (Level 3)' }}</option>
                        <option value="semi_urgent" selected>{{ __('public.staff_portal.opt_semi_urgent', [], $l) ?: 'Semi-Urgent (Level 4)' }}</option>
                        <option value="non_urgent">{{ __('public.staff_portal.opt_non_urgent', [], $l) ?: 'Non-Urgent (Level 5)' }}</option>
                    </select>
                    <div id="acuity-hint" class="form-hint" style="display:none;"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_portal.field_pain_score', [], $l) ?: 'Pain Score (0–10)' }}</label>
                    <input type="number" name="pain_score" class="form-control" min="0" max="10" value="{{ old('pain_score') }}" placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_portal.field_pregnancy_status', [], $l) ?: 'Pregnancy Status' }}</label>
                    <select name="pregnancy_status" class="form-control">
                        <option value="">{{ __('staff_clinical.opt_na', [], $l) ?: 'N/A' }}</option>
                        <option value="not_applicable">{{ __('public.staff_portal.opt_not_applicable', [], $l) ?: 'Not Applicable' }}</option>
                        <option value="not_pregnant">{{ __('public.staff_portal.opt_not_pregnant', [], $l) ?: 'Not Pregnant' }}</option>
                        <option value="pregnant">{{ __('public.staff_portal.opt_pregnant', [], $l) ?: 'Pregnant' }}</option>
                        <option value="unknown">{{ __('public.staff_portal.opt_unknown', [], $l) ?: 'Unknown' }}</option>
                    </select>
                </div>
            </div>

            <h3 class="panel-title mt-6 mb-3">
                <i data-lucide="heart-pulse"></i>
                {{ __('public.staff_portal.panel_vital_signs', [], $l) ?: 'Vital Signs' }}
                <span id="vitals-alert-badge" style="display:none;"></span>
            </h3>
            <div class="field-grid mb-6">
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_portal.field_temperature', [], $l) ?: 'Temperature (°C)' }}</label>
                    <input type="number" id="v_temp" name="temperature" class="form-control" step="0.1" min="20" max="45" placeholder="36.5">
                    <div class="vital-hint form-hint"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_portal.field_bp_systolic', [], $l) ?: 'BP Systolic' }}</label>
                    <input type="number" id="v_sys" name="blood_pressure_systolic" class="form-control" min="40" max="300" placeholder="120">
                    <div class="vital-hint form-hint"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_portal.field_bp_diastolic', [], $l) ?: 'BP Diastolic' }}</label>
                    <input type="number" name="blood_pressure_diastolic" class="form-control" min="20" max="200" placeholder="80">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_portal.field_pulse', [], $l) ?: 'Pulse (bpm)' }}</label>
                    <input type="number" id="v_pulse" name="pulse" class="form-control" min="20" max="300" placeholder="72">
                    <div class="vital-hint form-hint"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_portal.field_resp_rate', [], $l) ?: 'Resp. Rate (/min)' }}</label>
                    <input type="number" id="v_rr" name="respiratory_rate" class="form-control" min="4" max="60" placeholder="16">
                    <div class="vital-hint form-hint"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_portal.field_spo2', [], $l) ?: 'SpO₂ (%)' }}</label>
                    <input type="number" id="v_spo2" name="oxygen_saturation" class="form-control" step="0.1" min="50" max="100" placeholder="98">
                    <div class="vital-hint form-hint"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_portal.field_weight', [], $l) ?: 'Weight (kg)' }}</label>
                    <input type="number" name="weight" class="form-control" step="0.1" min="0.5" max="500" placeholder="70">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_portal.field_height', [], $l) ?: 'Height (cm)' }}</label>
                    <input type="number" name="height" class="form-control" step="0.1" min="20" max="250" placeholder="170">
                </div>
            </div>

            <div class="row-actions">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="activity"></i>
                    {{ __('public.staff_portal.btn_save_triage', [], $l) ?: 'Save Triage' }}
                </button>
                <a href="{{ route('portals.staff.visits') }}" class="btn btn-ghost">{{ __('public.staff_portal.filter_clear', [], $l) ?: 'Cancel' }}</a>
            </div>
        </form>
    </div>
</div>

{{-- Emergency Escalation Modal --}}
<div id="escalate-modal" class="modal-backdrop" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="escalate-modal-title">
        <h3 class="modal__title" id="escalate-modal-title"><i data-lucide="siren"></i> {{ __('public.staff_portal.modal_emergency_title', [], $l) ?: 'Declare Emergency' }}</h3>
        <form method="POST" action="{{ route('portals.staff.visits.triage.escalate', $visit->id) }}">
            @csrf
            <div class="modal__body">
                <p>{{ __('public.staff_portal.modal_emergency_body', [], $l) ?: 'This will set acuity to Resuscitation (Level 1) and mark the visit as an emergency. This action is logged and cannot be undone without re-assessment.' }}</p>
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_portal.field_emergency_reason', [], $l) ?: 'Reason for Emergency Escalation' }} *</label>
                    <textarea name="reason" class="form-control" rows="3" required maxlength="500"
                        placeholder="{{ __('public.staff_portal.ph_emergency_reason', [], $l) ?: 'e.g. Sudden cardiac arrest, severe respiratory distress…' }}"></textarea>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeEscalateModal()">{{ __('public.staff_portal.filter_clear', [], $l) ?: 'Cancel' }}</button>
                <button type="submit" class="btn btn-danger btn-sm">
                    <i data-lucide="siren"></i> {{ __('public.staff_portal.btn_confirm_emergency', [], $l) ?: 'Confirm Emergency' }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Emergency modal
function openEscalateModal()  { document.getElementById('escalate-modal').removeAttribute('hidden'); }
function closeEscalateModal() { document.getElementById('escalate-modal').setAttribute('hidden',''); }
document.getElementById('escalate-modal').addEventListener('click', function(e) { if (e.target === this) closeEscalateModal(); });

// Live vital sign range feedback + auto-acuity suggestion
const ranges = {
    v_spo2:  { critical: [0, 90],  warning: [90, 95],  label: @json(__('staff_clinical.vital_spo2', [], app()->getLocale()) ?: 'SpO₂'),       criticalNote: @json(__('staff_clinical.note_spo2_crit', [], app()->getLocale()) ?: 'Severe hypoxia — suggest Resuscitation'), warningNote: @json(__('staff_clinical.note_spo2_warn', [], app()->getLocale()) ?: 'Low O₂ — suggest Critical') },
    v_pulse: { critical: [[0,50],[150,400]], warning: [[50,60],[100,150]], label: @json(__('staff_clinical.vital_pulse', [], app()->getLocale()) ?: 'Pulse'), criticalNote: @json(__('staff_clinical.note_pulse_crit', [], app()->getLocale()) ?: 'Extreme HR — suggest Critical'), warningNote: @json(__('staff_clinical.note_pulse_warn', [], app()->getLocale()) ?: 'Abnormal HR') },
    v_sys:   { critical: [0, 90],  warning: [90, 100], label: @json(__('staff_clinical.vital_bp_sys', [], app()->getLocale()) ?: 'BP Systolic'), criticalNote: @json(__('staff_clinical.note_bp_crit', [], app()->getLocale()) ?: 'Hypotension — suggest Critical'), warningNote: @json(__('staff_clinical.note_bp_warn', [], app()->getLocale()) ?: 'Low blood pressure') },
    v_temp:  { critical: [[0,35],[40,50]], warning: [[35,36],[38.5,40]], label: @json(__('staff_clinical.vital_temp', [], app()->getLocale()) ?: 'Temp'), criticalNote: @json(__('staff_clinical.note_temp_crit', [], app()->getLocale()) ?: 'Extreme temperature'), warningNote: @json(__('staff_clinical.note_temp_warn', [], app()->getLocale()) ?: 'Abnormal temperature') },
    v_rr:    { critical: [0, 8],   warning: [8, 12],   label: @json(__('staff_clinical.vital_rr', [], app()->getLocale()) ?: 'Resp. Rate'),  criticalNote: @json(__('staff_clinical.note_rr_crit', [], app()->getLocale()) ?: 'Respiratory failure risk'), warningNote: @json(__('staff_clinical.note_rr_warn', [], app()->getLocale()) ?: 'Abnormal breathing rate') },
};

function checkRange(id) {
    const el = document.getElementById(id);
    if (!el) return null;
    const val = parseFloat(el.value);
    if (isNaN(val)) { clearHint(el); return null; }
    const r = ranges[id];
    if (!r) return null;

    let status = 'ok';
    let note = '';

    const isCrit = Array.isArray(r.critical[0])
        ? r.critical.some(([a,b]) => val >= a && val < b)
        : (val < r.critical[0] || val >= r.critical[1]);
    const isWarn = !isCrit && (Array.isArray(r.warning[0])
        ? r.warning.some(([a,b]) => val >= a && val < b)
        : (val < r.warning[0] || val >= r.warning[1]));

    if (isCrit) { status = 'critical'; note = r.criticalNote; }
    else if (isWarn) { status = 'warning'; note = r.warningNote; }

    const hint = el.parentNode.querySelector('.vital-hint');
    if (hint) {
        hint.textContent = note;
        hint.style.color = status === 'critical' ? 'var(--p-danger)' : status === 'warning' ? 'var(--p-warning)' : '';
    }
    el.style.borderColor = status === 'critical' ? 'var(--p-danger)' : status === 'warning' ? 'var(--p-warning)' : '';
    return status;
}

function clearHint(el) {
    const hint = el.parentNode.querySelector('.vital-hint');
    if (hint) { hint.textContent = ''; }
    el.style.borderColor = '';
}

function updateAcuitySuggestion() {
    let worstStatus = 'ok';
    let worstNote = '';
    Object.keys(ranges).forEach(id => {
        const s = checkRange(id);
        if (s === 'critical') { worstStatus = 'critical'; worstNote = ranges[id].criticalNote; }
        else if (s === 'warning' && worstStatus !== 'critical') { worstStatus = 'warning'; worstNote = ranges[id].warningNote; }
    });

    const hint = document.getElementById('acuity-hint');
    const sel  = document.getElementById('acuity_score');
    if (worstStatus === 'critical') {
        hint.textContent = worstNote;
        hint.style.color = 'var(--p-danger)';
        hint.style.display = 'block';
        if (sel.value === 'semi_urgent' || sel.value === 'non_urgent') sel.value = 'critical';
    } else if (worstStatus === 'warning') {
        hint.textContent = '△ ' + worstNote;
        hint.style.color = 'var(--p-warning)';
        hint.style.display = 'block';
        if (sel.value === 'non_urgent') sel.value = 'urgent';
    } else {
        hint.textContent = '';
        hint.style.display = 'none';
    }
}

Object.keys(ranges).forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', updateAcuitySuggestion);
});
</script>
@endsection

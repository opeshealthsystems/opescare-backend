@extends('layouts.portal')
@section('title', __('public.staff_wards.admissions_title', [], app()->getLocale()) ?: 'Admissions')
@section('breadcrumb_home', __('public.portal.nav_dashboard', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.staff_wards.admissions_breadcrumb', [], app()->getLocale()) ?: 'Admissions')
@php $l = app()->getLocale(); @endphp

@section('content')
<div class="page-head">
    <h2>{{ __('public.staff_wards.admissions_heading', [], $l) ?: 'Admissions' }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.staff.wards') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="layout-grid"></i> {{ __('public.staff_wards.btn_admissions_bed_map', [], $l) ?: 'Bed Map' }}
    </a>
    <button type="button" class="btn btn-primary btn-sm" onclick="openAdmitModal()">
        <i data-lucide="plus"></i> {{ __('public.staff_wards.btn_new_admission', [], $l) ?: 'Admit Patient' }}
    </button>
</div>
<p class="page-subtitle mb-6">{{ __('public.staff_wards.admissions_subtitle', [], $l) ?: 'Manage patient admissions, discharges, and bed transfers.' }}</p>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

{{-- Status filter --}}
<div class="tabs mb-6">
    @foreach(['' => __('public.stf_wards_adm_tab_all'), 'active' => __('public.stf_wards_adm_tab_active'), 'discharged' => __('public.stf_wards_adm_tab_discharged'), 'transferred' => __('public.stf_wards_adm_tab_transferred')] as $val => $label)
        <a href="{{ route('portals.staff.wards.admissions', $val ? ['status'=>$val] : []) }}"
           class="tab {{ request('status', '') === $val ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($admissions->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="bed"></i></div>
                <h3>{{ __('public.staff_wards.no_admissions', [], $l) ?: 'No admissions found.' }}</h3>
                <p>{{ __('public.staff_wards.no_admissions_desc', [], $l) ?: 'Admit a patient to a bed to start tracking inpatient stays.' }}</p>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.staff_wards.col_patient', [], $l) ?: 'Patient' }}</th>
                    <th>{{ __('public.staff_wards.col_bed', [], $l) ?: 'Bed' }}</th>
                    <th>{{ __('public.staff_wards.col_ward', [], $l) ?: 'Ward' }}</th>
                    <th>{{ __('public.staff_wards.col_status', [], $l) ?: 'Status' }}</th>
                    <th>{{ __('public.staff_wards.col_admitted', [], $l) ?: 'Admitted' }}</th>
                    <th>{{ __('public.staff_wards.col_los', [], $l) ?: 'LOS' }}</th>
                    <th>{{ __('public.staff_wards.col_reason', [], $l) ?: 'Reason' }}</th>
                    <th>{{ __('public.staff_wards.col_actions', [], $l) ?: 'Actions' }}</th>
                </tr></thead>
                <tbody>
                    @foreach($admissions as $adm)
                    @php
                        $stBadge = match($adm->status) {
                            'active'      => 'badge-success',
                            'discharged'  => 'badge-neutral',
                            'transferred' => 'badge-primary',
                            default       => 'badge-neutral',
                        };
                    @endphp
                    <tr>
                        <td data-label="{{ __('public.staff_wards.col_patient', [], $l) ?: 'Patient' }}">
                            <span class="td-strong">{{ $adm->patient?->health_id ?? substr($adm->patient_id,0,10).'…' }}</span>
                            @if($adm->patient)
                                <div class="td-muted">
                                    {{ $adm->patient->first_name }} {{ $adm->patient->last_name }}
                                </div>
                            @endif
                        </td>
                        <td data-label="{{ __('public.staff_wards.col_bed', [], $l) ?: 'Bed' }}">
                            <span class="mono">{{ $adm->bed?->bed_number ?? '—' }}</span>
                        </td>
                        <td data-label="{{ __('public.staff_wards.col_ward', [], $l) ?: 'Ward' }}">{{ $adm->bed?->ward?->name ?? '—' }}</td>
                        <td data-label="{{ __('public.staff_wards.col_status', [], $l) ?: 'Status' }}"><span class="badge {{ $stBadge }}">@enum($adm->status)</span></td>
                        <td data-label="{{ __('public.staff_wards.col_admitted', [], $l) ?: 'Admitted' }}" class="td-muted">
                            {{ \Carbon\Carbon::parse($adm->admitted_at)->format('M d, Y H:i') }}
                        </td>
                        <td data-label="{{ __('public.staff_wards.col_los', [], $l) ?: 'LOS' }}" class="td-muted">{{ $adm->lengthOfStay() }}d</td>
                        <td data-label="{{ __('public.staff_wards.col_reason', [], $l) ?: 'Reason' }}" class="td-muted">{{ Str::limit($adm->admission_reason ?? '—', 35) }}</td>
                        <td data-label="{{ __('public.staff_wards.col_actions', [], $l) ?: 'Actions' }}">
                            @if($adm->status === 'active')
                                <div class="row-actions-inline">
                                <button type="button" class="btn btn-ghost btn-xs"
                                    onclick="openDischargeModal('{{ $adm->id }}')">
                                    <i data-lucide="log-out"></i> {{ __('public.staff_wards.btn_discharge', [], $l) ?: 'Discharge' }}
                                </button>
                                <button type="button" class="btn btn-ghost btn-xs"
                                    onclick="openTransferModal('{{ $adm->id }}')">
                                    <i data-lucide="arrow-right-left"></i> {{ __('public.staff_wards.btn_transfer', [], $l) ?: 'Transfer' }}
                                </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="panel-footer">
            {{ $admissions->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Admit Modal --}}
<div id="admit-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal modal--md" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="bed"></i> {{ __('public.staff_wards.modal_admit_title', [], $l) ?: 'Admit Patient' }}</h3>
        <form method="POST" action="{{ route('portals.staff.wards.admit') }}">
            @csrf
            <div class="modal__body">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.staff_wards.field_patient_id', [], $l) ?: 'Patient ID / Health ID' }}</label>
                    <input type="text" name="patient_id" class="form-control" required placeholder="{{ __('public.stf_wards_adm_ph_patient_id') }}">
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.staff_wards.field_select_bed', [], $l) ?: 'Select Bed' }}</label>
                    <select name="bed_id" class="form-control" required>
                        <option value="">{{ __('public.stf_wards_adm_select_bed_ph') }}</option>
                        @php
                            $availBeds = \App\Models\Bed::with('ward')
                                ->where('status','available')
                                ->whereHas('ward', fn($q) => $q->where('is_active',true))
                                ->orderBy('ward_id')
                                ->get();
                            $byWard = $availBeds->groupBy(fn($b) => $b->ward?->name ?? (__('staff_clinical.ward_unknown', [], $l) ?: 'Unknown'));
                        @endphp
                        @foreach($byWard as $wardName => $beds)
                            <optgroup label="{{ $wardName }}">
                                @foreach($beds as $bed)
                                    <option value="{{ $bed->id }}">{{ $bed->bed_number }} ({{ $bed->bed_type }})</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_wards.field_admission_reason', [], $l) ?: 'Admission Reason' }}</label>
                    <textarea name="admission_reason" class="form-control" rows="2" maxlength="500" placeholder="{{ __('public.stf_wards_adm_ph_reason') }}"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_wards.field_visit_id', [], $l) ?: 'Visit ID' }} <span class="td-muted">{{ __('staff_clinical.lbl_optional', [], $l) ?: '(optional)' }}</span></label>
                    <input type="text" name="visit_id" class="form-control" placeholder="{{ __('public.stf_wards_adm_ph_visit_id') }}">
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeAdmitModal()">{{ __('public.staff_wards.btn_cancel', [], $l) ?: 'Cancel' }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('public.staff_wards.btn_admit_patient', [], $l) ?: 'Admit Patient' }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Discharge Modal --}}
<div id="discharge-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="log-out"></i> {{ __('public.staff_wards.modal_discharge_title', [], $l) ?: 'Discharge Patient' }}</h3>
        <form id="discharge-form" method="POST" action="">
            @csrf
            <div class="modal__body">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.staff_wards.field_discharge_dest', [], $l) ?: 'Discharge Destination' }}</label>
                    <select name="discharge_destination" class="form-control" required>
                        <option value="home">{{ __('public.stf_wards_adm_opt_home') }}</option>
                        <option value="referral">{{ __('public.stf_wards_adm_opt_referral') }}</option>
                        <option value="transferred">{{ __('public.stf_wards_adm_opt_transferred') }}</option>
                        <option value="ama">{{ __('public.stf_wards_adm_opt_ama') }}</option>
                        <option value="deceased">{{ __('public.stf_wards_adm_opt_deceased') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_wards.field_discharge_notes', [], $l) ?: 'Discharge Notes' }}</label>
                    <textarea name="discharge_reason" class="form-control" rows="2" maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeDischargeModal()">{{ __('public.staff_wards.btn_cancel', [], $l) ?: 'Cancel' }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('public.staff_wards.btn_confirm_discharge', [], $l) ?: 'Confirm Discharge' }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Transfer Modal --}}
<div id="transfer-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="arrow-right-left"></i> {{ __('public.staff_wards.modal_transfer_title', [], $l) ?: 'Transfer to Another Bed' }}</h3>
        <form id="transfer-form" method="POST" action="">
            @csrf
            <div class="modal__body">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.staff_wards.field_target_bed', [], $l) ?: 'Target Bed' }}</label>
                    <select name="to_bed_id" class="form-control" required>
                        <option value="">{{ __('public.stf_wards_adm_select_bed_ph') }}</option>
                        @foreach($byWard ?? [] as $wardName => $beds)
                            <optgroup label="{{ $wardName }}">
                                @foreach($beds as $bed)
                                    <option value="{{ $bed->id }}">{{ $bed->bed_number }} ({{ $bed->bed_type }})</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_wards.field_transfer_reason', [], $l) ?: 'Transfer Reason' }}</label>
                    <input type="text" name="reason" class="form-control" maxlength="300" placeholder="{{ __('public.stf_wards_adm_ph_transfer_reason') }}">
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeTransferModal()">{{ __('public.staff_wards.btn_cancel', [], $l) ?: 'Cancel' }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('public.staff_wards.btn_confirm_transfer', [], $l) ?: 'Confirm Transfer' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
function openAdmitModal()    { document.getElementById('admit-modal').removeAttribute('hidden'); }
function closeAdmitModal()   { document.getElementById('admit-modal').setAttribute('hidden',''); }
function openDischargeModal(id) {
    document.getElementById('discharge-form').action = '/portals/staff/wards/admissions/' + id + '/discharge';
    document.getElementById('discharge-modal').removeAttribute('hidden');
}
function closeDischargeModal() { document.getElementById('discharge-modal').setAttribute('hidden',''); }
function openTransferModal(id) {
    document.getElementById('transfer-form').action = '/portals/staff/wards/admissions/' + id + '/transfer';
    document.getElementById('transfer-modal').removeAttribute('hidden');
}
function closeTransferModal() { document.getElementById('transfer-modal').setAttribute('hidden',''); }

['admit-modal','discharge-modal','transfer-modal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) { if(e.target===this) this.setAttribute('hidden',''); });
});
</script>
@endsection

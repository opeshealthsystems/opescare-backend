@extends('layouts.portal')
@section('title', __('public.adm_patients_show_title_prefix') . ' ' . $patient->health_id)
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_patients_idx_breadcrumb'))
@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.patients.index') }}">{{ __('public.adm_patients_idx_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ $patient->health_id }}</span>
</div>

<div class="entity-head">
    <div class="entity-head__icon"><i data-lucide="user"></i></div>
    <h2 class="entity-head__title">{{ $patient->first_name ?? '' }} {{ $patient->last_name ?? '' }}</h2>
    @if(($patient->identity_status??'')==='active')<span class="badge badge-success">@enum('active')</span>
    @elseif(($patient->identity_status??'')==='suspended')<span class="badge badge-danger">@enum('suspended')</span>
    @else<span class="badge badge-warning">@enum($patient->identity_status ?? 'provisional')</span>@endif
    <div class="entity-head__spacer"></div>
    @if(($patient->identity_status??'')==='active')
    <button type="button" class="btn btn-warning" onclick="opOpenModal('suspend-modal')"><i data-lucide="pause-circle"></i> {{ __('public.adm_patients_show_btn_suspend') }}</button>
    @else
    <button type="button" class="btn btn-success" onclick="opOpenModal('activate-modal')"><i data-lucide="check-circle"></i> {{ __('public.adm_patients_show_btn_activate') }}</button>
    @endif
    @if($patient->entered_in_error ?? false)
    <button type="button" class="btn btn-danger" onclick="opOpenModal('delete-modal')"><i data-lucide="trash-2"></i> {{ __('public.adm_patients_show_btn_delete') }}</button>
    @endif
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="tabs">
    <span class="tab active">{{ __('public.adm_patients_show_tab_overview') }}</span>
</div>

<div class="field-grid mb-6">
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_patients_show_lbl_health_id') }}</div>
        <div class="stat-card__value td-mono">{{ $patient->health_id }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_patients_show_lbl_full_name') }}</div>
        <div class="stat-card__value">{{ $patient->first_name ?? '' }} {{ $patient->last_name ?? '' }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_patients_show_lbl_dob') }}</div>
        <div class="stat-card__value">{{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->format('d M Y') : '—' }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_patients_show_lbl_sex') }}</div>
        <div class="stat-card__value">{{ ucfirst($patient->sex ?? '—') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_patients_show_lbl_status') }}</div>
        <div class="stat-card__value">@enum($patient->identity_status ?? 'provisional')</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_patients_show_lbl_registered') }}</div>
        <div class="stat-card__value">{{ $patient->created_at?->format('d M Y, H:i') }}</div>
    </div>
</div>

<div class="panel mb-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="phone"></i> {{ __('public.adm_patients_show_panel_contact') }}</h3></div>
    <div class="panel-body">
        <div class="field-grid">
            <div class="stat-card">
                <div class="stat-card__label">{{ __('public.adm_patients_show_lbl_phone') }}</div>
                <div class="stat-card__value">{{ $patient->phone ?? $patient->phone_number ?? '—' }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__label">{{ __('public.adm_patients_show_lbl_email') }}</div>
                <div class="stat-card__value">{{ $patient->email ?? '—' }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__label">{{ __('public.adm_patients_show_lbl_address') }}</div>
                <div class="stat-card__value">{{ $patient->address ?? '—' }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__label">{{ __('public.adm_patients_show_lbl_facility') }}</div>
                <div class="stat-card__value">{{ $patient->facility?->name ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="edit"></i> {{ __('public.adm_patients_show_panel_quick_edit') }}</h3></div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.admin.patients.update', $patient) }}">
            @csrf @method('PUT')
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">{{ __('public.adm_patients_show_lbl_phone') }}</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $patient->phone ?? $patient->phone_number ?? '') }}">
                    @error('phone')<div class="form-hint">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.adm_patients_show_lbl_email') }}</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $patient->email ?? '') }}">
                    @error('email')<div class="form-hint">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-group mt-6">
                <label class="form-label">{{ __('public.adm_patients_show_lbl_address') }}</label>
                <textarea name="address" class="form-control" rows="2">{{ old('address', $patient->address ?? '') }}</textarea>
            </div>
            <div class="form-group mt-6">
                <label class="form-label">{{ __('public.adm_patients_show_lbl_facility') }}</label>
                <select name="facility_id" class="form-control">
                    <option value="">{{ __('public.adm_patients_show_ph_select_facility') }}</option>
                    @foreach($facilities as $facility)
                    <option value="{{ $facility->id }}" @selected(old('facility_id',$patient->facility_id)==$facility->id)>{{ $facility->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> {{ __('public.adm_patients_show_btn_save') }}</button>
            </div>
        </form>
    </div>
</div>

<div id="suspend-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="suspend-modal-title">
        <h3 class="modal__title" id="suspend-modal-title"><i data-lucide="pause-circle"></i> {{ __('public.adm_patients_show_modal_suspend_title') }}</h3>
        <form method="POST" action="{{ route('portals.admin.patients.suspend', $patient) }}">@csrf @method('PATCH')
            <div class="modal__body"><p>{{ __('public.adm_patients_show_modal_suspend_body_before') }} <strong>{{ $patient->first_name ?? '' }} {{ $patient->last_name ?? '' }}</strong>? {{ __('public.adm_patients_show_modal_suspend_body_after') }}</p></div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('suspend-modal')">{{ __('public.adm_patients_show_btn_cancel') }}</button>
                <button type="submit" class="btn btn-warning">{{ __('public.adm_patients_show_btn_suspend') }}</button>
            </div>
        </form>
    </div>
</div>

<div id="activate-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="activate-modal-title">
        <h3 class="modal__title" id="activate-modal-title"><i data-lucide="check-circle"></i> {{ __('public.adm_patients_show_modal_activate_title') }}</h3>
        <form method="POST" action="{{ route('portals.admin.patients.activate', $patient) }}">@csrf @method('PATCH')
            <div class="modal__body"><p>{{ __('public.adm_patients_show_modal_activate_body_before') }} <strong>{{ $patient->first_name ?? '' }} {{ $patient->last_name ?? '' }}</strong> {{ __('public.adm_patients_show_modal_activate_body_after') }}</p></div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('activate-modal')">{{ __('public.adm_patients_show_btn_cancel') }}</button>
                <button type="submit" class="btn btn-success">{{ __('public.adm_patients_show_btn_activate') }}</button>
            </div>
        </form>
    </div>
</div>

@if($patient->entered_in_error ?? false)
<div id="delete-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
        <h3 class="modal__title" id="delete-modal-title"><i data-lucide="alert-triangle"></i> {{ __('public.adm_patients_show_modal_delete_title') }}</h3>
        <form method="POST" action="{{ route('portals.admin.patients.destroy', $patient) }}">@csrf @method('DELETE')
            <div class="modal__body">
                <div class="alert alert-danger mb-6"><i data-lucide="alert-triangle"></i><div><strong>{{ __('public.adm_patients_show_irreversible') }}</strong> {{ __('public.adm_patients_show_modal_delete_body_before') }} <strong>{{ $patient->first_name ?? '' }} {{ $patient->last_name ?? '' }}</strong> ({{ $patient->health_id }}) {{ __('public.adm_patients_show_modal_delete_body_after') }}</div></div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('delete-modal')">{{ __('public.adm_patients_show_btn_cancel') }}</button>
                <button type="submit" class="btn btn-danger">{{ __('public.adm_patients_show_btn_delete_perm') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection
@section('scripts')
<script>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection

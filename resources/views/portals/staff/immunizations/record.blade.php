@extends('layouts.portal')

@section('title', __('public.stf_immun_rec_title') . ' — OpesCare Staff Portal')

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.stf_immun_rec_title'))

@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.staff.immunizations') }}">{{ __('public.stf_immun_title') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('staff_clinical.breadcrumb_record', [], app()->getLocale()) ?: 'Record' }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.stf_immun_rec_title') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.staff.immunizations', ['patient_id' => request('patient_id')]) }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i> {{ __('public.stf_immun_rec_back') }}
    </a>
</div>
<p class="page-subtitle mb-4">{{ __('public.stf_immun_rec_subtitle') }}</p>

@if($errors->any())
<div class="alert alert-danger mb-6" role="alert">
    <i data-lucide="triangle-alert"></i>
    <div>
        <strong>{{ __('public.stf_immun_rec_errors') }}</strong>
        <ul class="alert-list">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<div class="alert alert-warning mb-6">
    <i data-lucide="alert-triangle"></i>
    <div>{{ __('public.stf_immun_rec_dup_warning') }}</div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="syringe"></i> {{ __('public.stf_immun_rec_panel_title') }}</h2>
    </div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.staff.immunizations.store') }}" novalidate>
            @csrf

            <!-- Patient & Facility -->
            <h3 class="panel-title mb-4">{{ __('public.stf_immun_rec_patient_section') }}</h3>

            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label form-label-required" for="patient_id">{{ __('public.stf_immun_rec_lbl_patient_id') }}</label>
                    <input id="patient_id" name="patient_id" class="form-control mono"
                           value="{{ old('patient_id', request('patient_id')) }}" required aria-required="true"
                           placeholder="{{ __('public.stf_immun_rec_ph_patient_id') }}">
                    @error('patient_id')<div class="form-hint">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required" for="facility_id">{{ __('public.stf_immun_rec_lbl_facility_id') }}</label>
                    <input id="facility_id" name="facility_id" class="form-control" value="{{ old('facility_id') }}" required aria-required="true">
                    @error('facility_id')<div class="form-hint">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Vaccine Details -->
            <h3 class="panel-title mt-6 mb-4">{{ __('public.stf_immun_rec_vaccine_section') }}</h3>

            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label form-label-required" for="vaccine_code">{{ __('public.stf_immun_rec_lbl_vaccine_code') }}</label>
                    <input id="vaccine_code" name="vaccine_code" class="form-control" value="{{ old('vaccine_code') }}" placeholder="{{ __('staff_clinical.ph_vaccine_code', [], app()->getLocale()) ?: 'e.g. BCG, OPV, DPT' }}" required aria-required="true">
                    <div class="form-hint">{{ __('staff_clinical.hint_vaccine_code', [], app()->getLocale()) ?: 'WHO-EPI code or local code' }}</div>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required" for="vaccine_name">{{ __('public.stf_immun_rec_lbl_vaccine_name') }}</label>
                    <input id="vaccine_name" name="vaccine_name" class="form-control" value="{{ old('vaccine_name') }}" placeholder="{{ __('staff_clinical.ph_vaccine_name', [], app()->getLocale()) ?: 'e.g. Bacillus Calmette-Guérin' }}" required aria-required="true">
                </div>
            </div>

            <div class="form-row-3 mb-4">
                <div class="form-group">
                    <label class="form-label" for="dose_number">{{ __('public.stf_immun_rec_lbl_dose_number') }}</label>
                    <input type="number" id="dose_number" name="dose_number" class="form-control" value="{{ old('dose_number') }}" min="1" placeholder="1">
                </div>
                <div class="form-group">
                    <label class="form-label" for="lot_number">{{ __('public.stf_immun_rec_lbl_lot_number') }}</label>
                    <input id="lot_number" name="lot_number" class="form-control" value="{{ old('lot_number') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="expiry_date">{{ __('public.stf_immun_rec_lbl_expiry') }}</label>
                    <input type="date" id="expiry_date" name="expiry_date" class="form-control" value="{{ old('expiry_date') }}">
                </div>
            </div>

            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label" for="manufacturer">{{ __('public.stf_immun_rec_lbl_manufacturer') }}</label>
                    <input id="manufacturer" name="manufacturer" class="form-control" value="{{ old('manufacturer') }}">
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required" for="administered_at">{{ __('public.stf_immun_rec_lbl_admin_at') }}</label>
                    <input type="datetime-local" id="administered_at" name="administered_at" class="form-control"
                           value="{{ old('administered_at', now()->format('Y-m-d\TH:i')) }}" required aria-required="true">
                </div>
            </div>

            <!-- Administration Details -->
            <h3 class="panel-title mt-6 mb-4">{{ __('public.stf_immun_rec_admin_section') }}</h3>

            <div class="form-row-3 mb-4">
                <div class="form-group">
                    <label class="form-label" for="route">{{ __('public.stf_immun_rec_lbl_route') }}</label>
                    <select id="route" name="route" class="form-control">
                        <option value="">{{ __('staff_clinical.opt_select', [], app()->getLocale()) ?: '— Select —' }}</option>
                        <option value="IM"          @selected(old('route')==='IM')>{{ __('staff_clinical.route_im', [], app()->getLocale()) ?: 'IM (Intramuscular)' }}</option>
                        <option value="SC"          @selected(old('route')==='SC')>{{ __('staff_clinical.route_sc', [], app()->getLocale()) ?: 'SC (Subcutaneous)' }}</option>
                        <option value="oral"        @selected(old('route')==='oral')>{{ __('staff_clinical.route_oral', [], app()->getLocale()) ?: 'Oral' }}</option>
                        <option value="intradermal" @selected(old('route')==='intradermal')>{{ __('staff_clinical.route_intradermal', [], app()->getLocale()) ?: 'Intradermal' }}</option>
                        <option value="IN"          @selected(old('route')==='IN')>{{ __('staff_clinical.route_intranasal', [], app()->getLocale()) ?: 'Intranasal' }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="site">{{ __('public.stf_immun_rec_lbl_site') }}</label>
                    <input id="site" name="site" class="form-control" value="{{ old('site') }}" placeholder="{{ __('staff_clinical.ph_site', [], app()->getLocale()) ?: 'e.g. Left deltoid' }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="dose_quantity">{{ __('public.stf_immun_rec_lbl_dose_qty') }}</label>
                    <input type="number" step="0.01" id="dose_quantity" name="dose_quantity" class="form-control" value="{{ old('dose_quantity') }}" placeholder="0.5">
                </div>
            </div>

            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label form-label-required" for="status">{{ __('public.stf_immun_rec_lbl_status') }}</label>
                    <select id="status" name="status" class="form-control" required aria-required="true">
                        <option value="completed" @selected(old('status','completed')==='completed')>{{ __('staff_clinical.opt_completed', [], app()->getLocale()) ?: 'Completed' }}</option>
                        <option value="not_done"  @selected(old('status')==='not_done')>{{ __('staff_clinical.opt_not_done', [], app()->getLocale()) ?: 'Not Done' }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="not_done_reason">{{ __('public.stf_immun_rec_lbl_not_done') }}</label>
                    <input id="not_done_reason" name="not_done_reason" class="form-control" value="{{ old('not_done_reason') }}" placeholder="{{ __('public.aria_ph_if_not_done') }}">
                </div>
            </div>

            <div class="toggle-row mb-6">
                <div class="toggle-row__body">
                    <div class="toggle-row__title">{{ __('public.stf_immun_rec_historical_title') }}</div>
                    <div class="toggle-row__desc">{{ __('public.stf_immun_rec_historical_desc') }}</div>
                </div>
                <label class="switch">
                    <input type="checkbox" name="is_historical" value="1" @checked(old('is_historical'))>
                    <span class="switch__track"></span>
                </label>
            </div>

            <div class="row-actions">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="syringe"></i>
                    {{ __('public.stf_immun_rec_btn_submit') }}
                </button>
                <a href="{{ route('portals.staff.immunizations', ['patient_id' => request('patient_id')]) }}" class="btn btn-secondary">
                    {{ __('public.stf_immun_rec_btn_cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>

@endsection

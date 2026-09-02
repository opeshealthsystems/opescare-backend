@extends('layouts.portal')

@section('title', __('public.stf_ref_create_title'))

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.stf_ref_create_breadcrumb'))

@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.staff.referrals') }}">{{ __('public.stf_ref_create_breadcrumb_referrals') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.stf_ref_create_breadcrumb_new') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.stf_ref_create_page_heading') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.staff.referrals') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i> {{ __('public.stf_ref_create_btn_back') }}
    </a>
</div>
<p class="page-subtitle mb-4">{{ __('public.stf_ref_create_page_subtitle') }}</p>

@if($errors->any())
<div class="alert alert-danger mb-6" role="alert">
    <i data-lucide="triangle-alert"></i>
    <div>
        <strong>{{ __('public.stf_ref_create_errors_heading') }}</strong>
        <ul class="alert-list">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="send"></i> {{ __('public.stf_ref_create_panel_title') }}</h2>
    </div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.staff.referrals.store') }}" novalidate>
            @csrf

            <div class="alert alert-info mb-6">
                <i data-lucide="info"></i>
                <div>{{ __('public.stf_ref_create_info_draft') }}</div>
            </div>

            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label form-label-required" for="patient_id">{{ __('public.stf_ref_create_lbl_patient_id') }}</label>
                    <input id="patient_id" name="patient_id" class="form-control mono" value="{{ old('patient_id') }}" required
                           placeholder="{{ __('public.stf_ref_create_ph_patient_id') }}" aria-required="true">
                    @error('patient_id')<div class="form-hint">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required" for="urgency">{{ __('public.stf_ref_create_lbl_urgency') }}</label>
                    <select id="urgency" name="urgency" class="form-control" required aria-required="true">
                        <option value="routine"   @selected(old('urgency','routine')==='routine')>{{ __('public.stf_ref_priority_routine') }}</option>
                        <option value="urgent"    @selected(old('urgency')==='urgent')>{{ __('public.stf_ref_priority_urgent') }}</option>
                        <option value="emergency" @selected(old('urgency')==='emergency')>{{ __('public.stf_ref_priority_emergency') }}</option>
                    </select>
                </div>
            </div>

            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label form-label-required" for="referring_facility_id">{{ __('public.stf_ref_create_lbl_ref_facility') }}</label>
                    <input id="referring_facility_id" name="referring_facility_id" class="form-control"
                           value="{{ old('referring_facility_id') }}" required aria-required="true">
                    @error('referring_facility_id')<div class="form-hint">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="referring_provider_id">{{ __('public.stf_ref_create_lbl_ref_provider') }}</label>
                    <input id="referring_provider_id" name="referring_provider_id" class="form-control"
                           value="{{ old('referring_provider_id') }}" placeholder="{{ __('public.stf_ref_create_ph_ref_provider') }}">
                </div>
            </div>

            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label" for="receiving_facility_id">{{ __('public.stf_ref_create_lbl_recv_facility') }}</label>
                    <input id="receiving_facility_id" name="receiving_facility_id" class="form-control"
                           value="{{ old('receiving_facility_id') }}" placeholder="{{ __('public.stf_ref_create_ph_recv_facility') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="receiving_specialty">{{ __('public.stf_ref_create_lbl_specialty') }}</label>
                    <input id="receiving_specialty" name="receiving_specialty" class="form-control"
                           value="{{ old('receiving_specialty') }}" placeholder="{{ __('public.stf_ref_create_ph_specialty') }}">
                </div>
            </div>

            <div class="form-group mb-4">
                <label class="form-label form-label-required" for="reason">{{ __('public.stf_ref_create_lbl_reason') }}</label>
                <textarea id="reason" name="reason" rows="3" class="form-control" required aria-required="true">{{ old('reason') }}</textarea>
                @error('reason')<div class="form-hint">{{ $message }}</div>@enderror
            </div>

            <div class="form-group mb-4">
                <label class="form-label" for="clinical_summary">{{ __('public.stf_ref_create_lbl_clinical') }}</label>
                <textarea id="clinical_summary" name="clinical_summary" rows="5" class="form-control">{{ old('clinical_summary') }}</textarea>
                <div class="form-hint">{{ __('public.stf_ref_create_hint_clinical') }}</div>
            </div>

            <div class="form-group mb-6">
                <label class="form-label" for="expires_at">{{ __('public.stf_ref_create_lbl_expires') }}</label>
                <input type="datetime-local" id="expires_at" name="expires_at" class="form-control" value="{{ old('expires_at') }}">
                <div class="form-hint">{{ __('public.stf_ref_create_hint_expires') }}</div>
            </div>

            <div class="row-actions">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="send"></i>
                    {{ __('public.stf_ref_create_btn_submit') }}
                </button>
                <a href="{{ route('portals.staff.referrals') }}" class="btn btn-secondary">
                    <i data-lucide="x"></i> {{ __('public.stf_ref_create_btn_cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>

@endsection

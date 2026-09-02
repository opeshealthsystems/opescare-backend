@extends('layouts.portal')
@section('title', __('public.adm_codemap_form_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_codemap_form_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_codemap_form_breadcrumb_parent'))

@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.admin.code_mappings.index') }}">{{ __('public.adm_codemap_form_breadcrumb_parent') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_codemap_form_breadcrumb_add') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_codemap_form_heading') }}</h2>
    <div class="page-head__spacer"></div>
</div>
<p class="td-muted mb-6">{{ __('public.adm_codemap_form_desc') }}</p>

<div class="panel">
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.admin.code_mappings.store') }}">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_codemap_form_lbl_local_code') }}</label>
                    <input type="text" name="local_code" value="{{ old('local_code') }}" required placeholder="{{ __('public.adm_codemap_form_ph_local_code') }}" class="form-control">
                    @error('local_code') <div class="form-hint">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_codemap_form_lbl_resource_type') }}</label>
                    <select name="resource_type" required class="form-control">
                        <option value="">{{ __('public.adm_codemap_form_opt_select_type') }}</option>
                        @foreach($resourceTypes as $rt)
                        <option value="{{ $rt }}" {{ old('resource_type') === $rt ? 'selected' : '' }}>{{ $rt }}</option>
                        @endforeach
                    </select>
                    @error('resource_type') <div class="form-hint">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('public.adm_codemap_form_lbl_local_name') }}</label>
                <input type="text" name="local_name" value="{{ old('local_name') }}" placeholder="{{ __('public.adm_codemap_form_ph_local_name') }}" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('public.adm_codemap_form_lbl_local_unit') }} <span class="td-muted">{{ __('public.adm_codemap_form_lbl_local_unit_hint') }}</span></label>
                <input type="text" name="local_unit" value="{{ old('local_unit') }}" placeholder="{{ __('public.adm_codemap_form_ph_local_unit') }}" class="form-control">
            </div>

            <div class="panel-header mt-6 mb-6"><h3 class="panel-title">{{ __('public.adm_codemap_form_section_standard') }}</h3></div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_codemap_form_lbl_std_system') }}</label>
                    <select name="standard_system" required class="form-control">
                        <option value="">{{ __('public.adm_codemap_form_opt_select_system') }}</option>
                        @foreach($systems as $sys)
                        <option value="{{ $sys }}" {{ old('standard_system') === $sys ? 'selected' : '' }}>{{ strtoupper($sys) }}</option>
                        @endforeach
                    </select>
                    @error('standard_system') <div class="form-hint">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_codemap_form_lbl_std_code') }}</label>
                    <input type="text" name="standard_code" value="{{ old('standard_code') }}" required placeholder="{{ __('public.adm_codemap_form_ph_std_code') }}" class="form-control mono">
                    @error('standard_code') <div class="form-hint">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('public.adm_codemap_form_lbl_std_display') }}</label>
                <input type="text" name="standard_display" value="{{ old('standard_display') }}" placeholder="{{ __('public.adm_codemap_form_ph_std_display') }}" class="form-control">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_codemap_form_lbl_confidence') }}</label>
                    <select name="mapping_confidence" required class="form-control">
                        @foreach($confidences as $c)
                        <option value="{{ $c }}" {{ old('mapping_confidence', 'manual') === $c ? 'selected' : '' }}>{{ ucfirst($c) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.adm_codemap_form_lbl_std_version') }}</label>
                    <input type="text" name="standard_version" value="{{ old('standard_version') }}" placeholder="{{ __('public.adm_codemap_form_ph_std_version') }}" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('public.adm_codemap_form_lbl_notes') }}</label>
                <textarea name="notes" rows="2" placeholder="{{ __('public.adm_codemap_form_ph_notes') }}" class="form-control">{{ old('notes') }}</textarea>
            </div>

            <div class="alert alert-warning mb-6">
                <i data-lucide="info"></i>
                <div>{{ __('public.adm_codemap_form_alert_pending') }} <strong>{{ __('public.adm_codemap_form_alert_pending_status') }}</strong> {{ __('public.adm_codemap_form_alert_pending_rest') }}</div>
            </div>

            <div class="row-actions">
                <button type="submit" class="btn btn-primary">{{ __('public.adm_codemap_form_btn_add') }}</button>
                <a href="{{ route('portals.admin.code_mappings.index') }}" class="btn btn-secondary">{{ __('public.adm_codemap_form_btn_cancel') }}</a>
            </div>
        </form>
    </div>
</div>

@endsection

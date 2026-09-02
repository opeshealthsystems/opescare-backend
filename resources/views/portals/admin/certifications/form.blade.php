@extends('layouts.portal')
@section('title', __('public.adm_cert_form_title'))
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection

@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.admin.certifications.index') }}">{{ __('public.adm_cert_form_breadcrumb_parent') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_cert_form_breadcrumb_new') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_cert_form_heading') }}</h2>
</div>

<div class="panel form-panel">
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.admin.certifications.store') }}">
            @csrf

            <div class="form-group mb-4">
                <label class="form-label form-label-required">{{ __('public.adm_cert_form_lbl_integration_name') }}</label>
                <input type="text" name="integration_name" value="{{ old('integration_name') }}" required
                       class="form-control" placeholder="{{ __('public.adm_cert_form_ph_integration_name') }}">
                @error('integration_name') <div class="form-hint">{{ $message }}</div> @enderror
            </div>

            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_cert_form_lbl_integration_type') }}</label>
                    <select name="integration_type" class="form-control" required>
                        <option value="">{{ __('public.adm_cert_form_opt_select_type') }}</option>
                        @foreach($types as $t)
                        <option value="{{ $t }}" {{ old('integration_type') === $t ? 'selected' : '' }}>{{ strtoupper($t) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.adm_cert_form_lbl_version') }}</label>
                    <input type="text" name="version" value="{{ old('version') }}" class="form-control" placeholder="{{ __('public.adm_cert_form_ph_version') }}">
                </div>
            </div>

            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label">{{ __('public.adm_cert_form_lbl_vendor_name') }}</label>
                    <input type="text" name="vendor_name" value="{{ old('vendor_name') }}" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.adm_cert_form_lbl_vendor_contact') }}</label>
                    <input type="text" name="vendor_contact" value="{{ old('vendor_contact') }}" class="form-control" placeholder="{{ __('public.adm_cert_form_ph_vendor_contact') }}">
                </div>
            </div>

            <div class="form-group mb-6">
                <label class="form-label">{{ __('public.adm_cert_form_lbl_scope') }}</label>
                <textarea name="scope_description" rows="3" class="form-control"
                          placeholder="{{ __('public.adm_cert_form_ph_scope') }}">{{ old('scope_description') }}</textarea>
            </div>

            <div class="row-actions-inline">
                <button type="submit" class="btn btn-primary">{{ __('public.adm_cert_form_btn_start') }}</button>
                <a href="{{ route('portals.admin.certifications.index') }}" class="btn btn-secondary">{{ __('public.adm_cert_form_btn_cancel') }}</a>
            </div>
        </form>
    </div>
</div>

@endsection

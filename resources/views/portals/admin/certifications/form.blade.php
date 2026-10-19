@extends('layouts.portal')
@section('title', __('public.adm_cert_form_title'))
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page">

<nav class="oda-gc-crumbs" aria-label="{{ __('od.breadcrumb') }}">
    <a href="{{ route('portals.admin.certifications.index') }}">{{ __('public.adm_cert_form_breadcrumb_parent') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_cert_form_breadcrumb_new') }}</span>
</nav>

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_cert_form_heading') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_cert_form_subtitle') }}</p>
    </div>
</div>

<section class="od-card oda-gc-narrow">
    <div class="od-card__body">
        <form method="POST" action="{{ route('portals.admin.certifications.store') }}" class="oda-form">
            @csrf

            <label class="oda-label oda-form__full">
                <span class="oda-label__text">{{ __('public.adm_cert_form_lbl_integration_name') }} *</span>
                <input type="text" name="integration_name" value="{{ old('integration_name') }}" required
                       class="od-input" placeholder="{{ __('public.adm_cert_form_ph_integration_name') }}">
                @error('integration_name') <span class="oda-error">{{ $message }}</span> @enderror
            </label>

            <label class="oda-label">
                <span class="oda-label__text">{{ __('public.adm_cert_form_lbl_integration_type') }} *</span>
                <select name="integration_type" class="od-select" required>
                    <option value="">{{ __('public.adm_cert_form_opt_select_type') }}</option>
                    @foreach($types as $t)
                    <option value="{{ $t }}" {{ old('integration_type') === $t ? 'selected' : '' }}>{{ strtoupper($t) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="oda-label">
                <span class="oda-label__text">{{ __('public.adm_cert_form_lbl_version') }}</span>
                <input type="text" name="version" value="{{ old('version') }}" class="od-input" placeholder="{{ __('public.adm_cert_form_ph_version') }}">
            </label>

            <label class="oda-label">
                <span class="oda-label__text">{{ __('public.adm_cert_form_lbl_vendor_name') }}</span>
                <input type="text" name="vendor_name" value="{{ old('vendor_name') }}" class="od-input">
            </label>
            <label class="oda-label">
                <span class="oda-label__text">{{ __('public.adm_cert_form_lbl_vendor_contact') }}</span>
                <input type="text" name="vendor_contact" value="{{ old('vendor_contact') }}" class="od-input" placeholder="{{ __('public.adm_cert_form_ph_vendor_contact') }}">
            </label>

            <label class="oda-label oda-form__full">
                <span class="oda-label__text">{{ __('public.adm_cert_form_lbl_scope') }}</span>
                <textarea name="scope_description" rows="3" class="oda-textarea"
                          placeholder="{{ __('public.adm_cert_form_ph_scope') }}">{{ old('scope_description') }}</textarea>
            </label>

            <div class="oda-form__actions">
                <a href="{{ route('portals.admin.certifications.index') }}" class="od-btn od-btn--ghost">{{ __('public.adm_cert_form_btn_cancel') }}</a>
                <button type="submit" class="od-btn od-btn--primary">{{ __('public.adm_cert_form_btn_start') }}</button>
            </div>
        </form>
    </div>
</section>

</div>
@endsection

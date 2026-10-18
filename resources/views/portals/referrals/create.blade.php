@extends('layouts.portal')

@section('title', __('referral_desk.create_title'))
@section('breadcrumb_home', __('referral_desk.title'))
@section('breadcrumb_home_url', route('portals.referrals.index'))
@section('breadcrumb_section', __('referral_desk.create_title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('referral_desk.create_title') }}</h1>
        <p class="od-page__sub">{{ __('referral_desk.create_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.referrals.index') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('referral_desk.back') }}</a>
</div>

@if($errors->any())
<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div>
@endif

<form method="POST" action="{{ route('portals.referrals.store') }}" class="odc-form">
    @csrf

    <section class="od-card">
        <header class="od-card__head"><h2 class="od-card__title">{{ __('hospital_desk.d_ref_sec_patient') }}</h2></header>
        <div class="od-card__body odc-grid">
            <div class="odc-field">
                <label class="odc-label odc-label--req" for="ref-health-id">{{ __('referral_desk.health_id') }}</label>
                <input type="text" id="ref-health-id" name="health_id" class="od-input" value="{{ old('health_id', request('health_id')) }}" required maxlength="64" autocomplete="off">
                <p class="odc-hint">{{ __('referral_desk.health_id_hint') }}</p>
            </div>
            <div class="odc-field">
                <label class="odc-label odc-label--req" for="ref-urgency">{{ __('referral_desk.urgency_label') }}</label>
                <select id="ref-urgency" name="urgency" class="od-select" required>
                    @foreach($urgencies as $u)
                        <option value="{{ $u }}" @selected(old('urgency', 'routine') === $u)>{{ __('referral_desk.urgency.' . $u) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    <section class="od-card">
        <header class="od-card__head"><h2 class="od-card__title">{{ __('hospital_desk.d_ref_sec_destination') }}</h2></header>
        <div class="od-card__body odc-grid">
            <div class="odc-field odc-field--full">
                <label class="odc-label" for="ref-facility">{{ __('referral_desk.receiving_facility_name') }}</label>
                <input type="text" id="ref-facility" name="receiving_facility_name" class="od-input" list="ref-facility-names" value="{{ old('receiving_facility_name') }}" maxlength="255" autocomplete="off">
                <datalist id="ref-facility-names">
                    @foreach($facilityNames as $name)<option value="{{ $name }}">@endforeach
                </datalist>
                <p class="odc-hint">{{ __('referral_desk.receiving_facility_hint') }}</p>
            </div>
            <div class="odc-field">
                <label class="odc-label" for="ref-specialty">{{ __('referral_desk.receiving_specialty') }}</label>
                <input type="text" id="ref-specialty" name="receiving_specialty" class="od-input" value="{{ old('receiving_specialty') }}" maxlength="120">
            </div>
            <div class="odc-field">
                <label class="odc-label" for="ref-provider">{{ __('referral_desk.receiving_provider_name') }}</label>
                <input type="text" id="ref-provider" name="receiving_provider_name" class="od-input" value="{{ old('receiving_provider_name') }}" maxlength="200">
            </div>
        </div>
    </section>

    <section class="od-card">
        <header class="od-card__head"><h2 class="od-card__title">{{ __('hospital_desk.d_ref_sec_clinical') }}</h2></header>
        <div class="od-card__body odc-grid">
            <div class="odc-field odc-field--full">
                <label class="odc-label odc-label--req" for="ref-reason">{{ __('referral_desk.reason') }}</label>
                <textarea id="ref-reason" name="reason" class="odc-textarea" rows="3" required maxlength="2000">{{ old('reason') }}</textarea>
            </div>
            <div class="odc-field odc-field--full">
                <label class="odc-label" for="ref-summary">{{ __('referral_desk.clinical_summary') }}</label>
                <textarea id="ref-summary" name="clinical_summary" class="odc-textarea" rows="5" maxlength="8000">{{ old('clinical_summary') }}</textarea>
            </div>
            <fieldset class="odc-field odc-field--full">
                <legend class="odc-label">{{ __('referral_desk.included_record_types') }}</legend>
                <div class="odc-checks">
                @foreach($recordTypes as $type)
                    <label class="odc-check">
                        <input type="checkbox" name="included_record_types[]" value="{{ $type }}" @checked(in_array($type, old('included_record_types', []), true))>
                        {{ is_array(__('referral_desk.record_types')) ? (__('referral_desk.record_types')[$type] ?? $type) : $type }}
                    </label>
                @endforeach
                </div>
                <p class="odc-hint">{{ __('referral_desk.included_record_types_hint') }}</p>
            </fieldset>
        </div>
    </section>

    <div class="odc-actions">
        <a href="{{ route('portals.referrals.index') }}" class="od-btn od-btn--secondary">{{ __('referral_desk.back') }}</a>
        <button type="submit" class="od-btn od-btn--primary"><i data-lucide="save"></i> {{ __('referral_desk.save_draft') }}</button>
    </div>
</form>

</div>
@endsection

@extends('layouts.portal')

@section('title', __('referral_desk.create_title'))
@section('breadcrumb_home', __('referral_desk.title'))
@section('breadcrumb_home_url', route('portals.referrals.index'))
@section('breadcrumb_section', __('referral_desk.create_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('referral_desk.create_title') }}</h1>
        <p class="page-subtitle">{{ __('referral_desk.create_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.referrals.index') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> {{ __('referral_desk.back') }}
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger mb-4" role="alert">
    <i data-lucide="alert-circle"></i>
    <div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
</div>
@endif

<div class="panel form-panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="arrow-right-left"></i> {{ __('referral_desk.create_title') }}</h2>
    </div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.referrals.store') }}">
            @csrf
            <div class="form-group mb-4">
                <label class="form-label form-label-required" for="ref-health-id">{{ __('referral_desk.health_id') }}</label>
                <input type="text" id="ref-health-id" name="health_id" class="form-control" value="{{ old('health_id', request('health_id')) }}"
                       required maxlength="64" autocomplete="off">
                <div class="form-hint">{{ __('referral_desk.health_id_hint') }}</div>
            </div>

            <div class="form-group mb-4">
                <label class="form-label" for="ref-facility">{{ __('referral_desk.receiving_facility_name') }}</label>
                <input type="text" id="ref-facility" name="receiving_facility_name" class="form-control" list="ref-facility-names"
                       value="{{ old('receiving_facility_name') }}" maxlength="255" autocomplete="off">
                <datalist id="ref-facility-names">
                    @foreach($facilityNames as $name)<option value="{{ $name }}">@endforeach
                </datalist>
                <div class="form-hint">{{ __('referral_desk.receiving_facility_hint') }}</div>
            </div>

            <div class="form-grid mb-4">
                <div class="form-group">
                    <label class="form-label" for="ref-specialty">{{ __('referral_desk.receiving_specialty') }}</label>
                    <input type="text" id="ref-specialty" name="receiving_specialty" class="form-control" value="{{ old('receiving_specialty') }}" maxlength="120">
                </div>
                <div class="form-group">
                    <label class="form-label" for="ref-provider">{{ __('referral_desk.receiving_provider_name') }}</label>
                    <input type="text" id="ref-provider" name="receiving_provider_name" class="form-control" value="{{ old('receiving_provider_name') }}" maxlength="200">
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required" for="ref-urgency">{{ __('referral_desk.urgency_label') }}</label>
                    <select id="ref-urgency" name="urgency" class="form-control" required>
                        @foreach($urgencies as $u)
                            <option value="{{ $u }}" @selected(old('urgency', 'routine') === $u)>{{ __('referral_desk.urgency.' . $u) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group mb-4">
                <label class="form-label form-label-required" for="ref-reason">{{ __('referral_desk.reason') }}</label>
                <textarea id="ref-reason" name="reason" class="form-control" rows="3" required maxlength="2000">{{ old('reason') }}</textarea>
            </div>

            <div class="form-group mb-4">
                <label class="form-label" for="ref-summary">{{ __('referral_desk.clinical_summary') }}</label>
                <textarea id="ref-summary" name="clinical_summary" class="form-control" rows="5" maxlength="8000">{{ old('clinical_summary') }}</textarea>
            </div>

            <fieldset class="form-group mb-4">
                <legend class="form-label">{{ __('referral_desk.included_record_types') }}</legend>
                @foreach($recordTypes as $type)
                    <label class="checkbox-label">
                        <input type="checkbox" name="included_record_types[]" value="{{ $type }}" @checked(in_array($type, old('included_record_types', []), true))>
                        {{ __('referral_desk.record_types.' . $type) }}
                    </label>
                @endforeach
                <div class="form-hint">{{ __('referral_desk.included_record_types_hint') }}</div>
            </fieldset>

            <div class="row-actions-inline">
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> {{ __('referral_desk.save_draft') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

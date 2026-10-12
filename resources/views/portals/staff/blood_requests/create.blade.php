{{--
    New blood request — /facility/blood-requests/new

    The clinician's way to file a request the patient app would otherwise
    have to. Same write path as the app (BloodRequestService::request), same
    listing the queue reads, so the request lands on this desk the moment it
    is saved. The facility is the session's — never a field on this form.

    Nothing on this screen holds or moves a unit.
--}}
@extends('layouts.portal')

@section('title', __('blood_desk.create_title'))

@section('sidebar_user_role', __('public.staff_portal.role_clinical_staff', [], app()->getLocale()))

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('blood_desk.breadcrumb'))

@section('content')

<div class="page-head">
    <h2>{{ __('blood_desk.create_title') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.staff.blood_requests.index') }}" class="btn btn-ghost btn-sm fs-tap">
        <i data-lucide="arrow-left"></i>
        {{ __('blood_desk.back_to_queue') }}
    </a>
</div>
<p class="page-subtitle mb-6">{{ __('blood_desk.create_subtitle') }}</p>

@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

@if(! $hasFacility)
    <div class="alert alert-warning mb-6">
        <i data-lucide="triangle-alert"></i>
        <div>{{ __('blood_desk.no_facility_notice') }}</div>
    </div>
@elseif(! $hasListing)
    <div class="alert alert-warning mb-6">
        <i data-lucide="triangle-alert"></i>
        <div>{{ __('blood_desk.no_listing_notice') }}</div>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger mb-6">
        <i data-lucide="triangle-alert"></i>
        <div>
            @foreach($errors->all() as $message)
                <div>{{ $message }}</div>
            @endforeach
        </div>
    </div>
@endif

<div class="alert alert-info mb-6">
    <i data-lucide="info"></i>
    <div>{{ __('blood_desk.create_no_hold_notice') }}</div>
</div>

<div class="panel">
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.staff.blood_requests.store') }}" id="blood-request-create-form">
            @csrf

            {{-- Exact Health ID only. No name lookup exists on this desk. --}}
            <div class="form-group">
                <label class="form-label" for="health_id">{{ __('blood_desk.lbl_health_id') }}</label>
                <input type="text" id="health_id" name="health_id" class="form-control mono" required maxlength="64"
                       autocomplete="off" spellcheck="false"
                       value="{{ old('health_id') }}"
                       placeholder="{{ __('blood_desk.create_health_id_placeholder') }}">
                <p class="td-muted">{{ __('blood_desk.create_health_id_help') }}</p>
            </div>

            @if($listings->count() > 1)
                <div class="form-group">
                    <label class="form-label" for="listing_id">{{ __('blood_desk.create_lbl_listing') }}</label>
                    <select name="listing_id" id="listing_id" class="form-control">
                        @foreach($listings as $listing)
                            <option value="{{ $listing->id }}" {{ old('listing_id') === (string) $listing->id ? 'selected' : '' }}>
                                {{ $listing->facility_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="form-group">
                <label class="form-label">{{ __('blood_desk.lbl_group') }}</label>
                <div class="fs-bgrid" role="radiogroup" aria-label="{{ __('blood_desk.lbl_group') }}">
                    @foreach(\App\Enums\BloodGroup::cases() as $g)
                        <label class="fs-bchip">
                            <input type="radio" name="blood_group" value="{{ $g->value }}"
                                   {{ old('blood_group', 'O+') === $g->value ? 'checked' : '' }} required>
                            <span>{{ $g->value }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="component_type">{{ __('blood_desk.lbl_component') }}</label>
                <select name="component_type" id="component_type" class="form-control" required>
                    @foreach(\App\Enums\BloodComponentType::cases() as $c)
                        <option value="{{ $c->value }}" {{ old('component_type', 'whole_blood') === $c->value ? 'selected' : '' }}>
                            {{ __('blood_desk.component_' . $c->value) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="quantity">{{ __('blood_desk.lbl_quantity') }}</label>
                <input type="number" id="quantity" name="quantity" class="form-control" required
                       min="1" max="{{ $maxUnits }}" inputmode="numeric" value="{{ old('quantity', 1) }}">
                <p class="td-muted">{{ __('blood_desk.create_units_help', ['max' => $maxUnits]) }}</p>
            </div>

            <div class="form-group">
                <label class="form-label" for="urgency">{{ __('blood_desk.lbl_urgency') }}</label>
                <select name="urgency" id="urgency" class="form-control" required>
                    @foreach(\App\Enums\BloodRequestUrgency::cases() as $u)
                        <option value="{{ $u->value }}" {{ old('urgency', 'routine') === $u->value ? 'selected' : '' }}>
                            {{ __('blood_desk.urgency_' . $u->value) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="indication">{{ __('blood_desk.create_lbl_indication') }}</label>
                <textarea name="indication" id="indication" rows="3" maxlength="500" class="form-control" required
                          placeholder="{{ __('blood_desk.create_indication_placeholder') }}">{{ old('indication') }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="needed_by">{{ __('blood_desk.lbl_needed_by') }}</label>
                <input type="datetime-local" id="needed_by" name="needed_by" class="form-control"
                       value="{{ old('needed_by') }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="contact_phone">{{ __('blood_desk.lbl_phone') }}</label>
                <input type="tel" id="contact_phone" name="contact_phone" class="form-control" maxlength="32"
                       value="{{ old('contact_phone') }}" placeholder="+237 6XX XXX XXX">
            </div>

            <div class="row-actions-inline">
                <button type="submit" class="btn btn-primary btn-sm fs-tap" {{ $hasListing ? '' : 'disabled' }}>
                    <i data-lucide="droplet"></i>
                    {{ __('blood_desk.create_submit') }}
                </button>
                <a href="{{ route('portals.staff.blood_requests.index') }}" class="btn btn-ghost btn-sm">{{ __('blood_desk.clear_btn') }}</a>
            </div>
        </form>
    </div>
</div>

@endsection

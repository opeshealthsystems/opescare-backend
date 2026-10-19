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


@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page odc-page">

<header class="od-page__head odc-head">
    <div>
        <h1 class="od-page__title">{{ __('blood_desk.create_title') }}</h1>
        <p class="od-page__sub">{{ __('blood_desk.create_subtitle') }}</p>
    </div>
    <div class="odc-head__actions">
        <a href="{{ route('portals.staff.blood_requests.index') }}" class="od-btn od-btn--secondary">
            <i data-lucide="arrow-left"></i>
            {{ __('blood_desk.back_to_queue') }}
        </a>
    </div>
</header>

@if(session('error'))
    <div class="odc-note odc-note--err" role="alert"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

@if(! $hasFacility)
    <div class="odc-note odc-note--warn"><i data-lucide="triangle-alert"></i><div>{{ __('blood_desk.no_facility_notice') }}</div></div>
@elseif(! $hasListing)
    <div class="odc-note odc-note--warn"><i data-lucide="triangle-alert"></i><div>{{ __('blood_desk.no_listing_notice') }}</div></div>
@endif

@if($errors->any())
    <div class="odc-note odc-note--err" role="alert">
        <i data-lucide="triangle-alert"></i>
        <div>
            @foreach($errors->all() as $message)
                <div>{{ $message }}</div>
            @endforeach
        </div>
    </div>
@endif

<div class="odc-note odc-note--info">
    <i data-lucide="info"></i>
    <div>{{ __('blood_desk.create_no_hold_notice') }}</div>
</div>

<form method="POST" action="{{ route('portals.staff.blood_requests.store') }}" id="blood-request-create-form" class="odc-form">
    @csrf

    <section class="od-card">
        <header class="od-card__head"><h2 class="od-card__title">{{ __('blood_desk.col_patient') }}</h2></header>
        <div class="od-card__body odc-grid">
            {{-- Exact Health ID only. No name lookup exists on this desk. --}}
            <label class="odc-label" for="health_id">{{ __('blood_desk.lbl_health_id') }}
                <input type="text" id="health_id" name="health_id" class="od-input odc-mono" required maxlength="64"
                       autocomplete="off" spellcheck="false"
                       value="{{ old('health_id') }}"
                       placeholder="{{ __('blood_desk.create_health_id_placeholder') }}">
                <span class="odc-hint">{{ __('blood_desk.create_health_id_help') }}</span>
            </label>

            <label class="odc-label" for="contact_phone">{{ __('blood_desk.lbl_phone') }}
                <input type="tel" id="contact_phone" name="contact_phone" class="od-input" maxlength="32"
                       value="{{ old('contact_phone') }}" placeholder="+237 6XX XXX XXX">
            </label>

            @if($listings->count() > 1)
                <label class="odc-label odc-span" for="listing_id">{{ __('blood_desk.create_lbl_listing') }}
                    <select name="listing_id" id="listing_id" class="od-select">
                        @foreach($listings as $listing)
                            <option value="{{ $listing->id }}" {{ old('listing_id') === (string) $listing->id ? 'selected' : '' }}>
                                {{ $listing->facility_name }}
                            </option>
                        @endforeach
                    </select>
                </label>
            @endif
        </div>
    </section>

    <section class="od-card">
        <header class="od-card__head"><h2 class="od-card__title">{{ __('blood_desk.col_request') }}</h2></header>
        <div class="od-card__body odc-grid">
            <div class="odc-label odc-span">
                <span>{{ __('blood_desk.lbl_group') }}</span>
                <div class="odc-bgrid" role="radiogroup" aria-label="{{ __('blood_desk.lbl_group') }}">
                    @foreach(\App\Enums\BloodGroup::cases() as $g)
                        <label class="odc-bchip">
                            <input type="radio" name="blood_group" value="{{ $g->value }}"
                                   {{ old('blood_group', 'O+') === $g->value ? 'checked' : '' }} required>
                            <span>{{ $g->value }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <label class="odc-label" for="component_type">{{ __('blood_desk.lbl_component') }}
                <select name="component_type" id="component_type" class="od-select" required>
                    @foreach(\App\Enums\BloodComponentType::cases() as $c)
                        <option value="{{ $c->value }}" {{ old('component_type', 'whole_blood') === $c->value ? 'selected' : '' }}>
                            {{ __('blood_desk.component_' . $c->value) }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="odc-label" for="quantity">{{ __('blood_desk.lbl_quantity') }}
                <input type="number" id="quantity" name="quantity" class="od-input" required
                       min="1" max="{{ $maxUnits }}" inputmode="numeric" value="{{ old('quantity', 1) }}">
                <span class="odc-hint">{{ __('blood_desk.create_units_help', ['max' => $maxUnits]) }}</span>
            </label>

            <label class="odc-label" for="urgency">{{ __('blood_desk.lbl_urgency') }}
                <select name="urgency" id="urgency" class="od-select" required>
                    @foreach(\App\Enums\BloodRequestUrgency::cases() as $u)
                        <option value="{{ $u->value }}" {{ old('urgency', 'routine') === $u->value ? 'selected' : '' }}>
                            {{ __('blood_desk.urgency_' . $u->value) }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="odc-label" for="needed_by">{{ __('blood_desk.lbl_needed_by') }}
                <input type="datetime-local" id="needed_by" name="needed_by" class="od-input"
                       value="{{ old('needed_by') }}">
            </label>

            <label class="odc-label odc-span" for="indication">{{ __('blood_desk.create_lbl_indication') }}
                <textarea name="indication" id="indication" rows="3" maxlength="500" class="odc-textarea" required
                          placeholder="{{ __('blood_desk.create_indication_placeholder') }}">{{ old('indication') }}</textarea>
            </label>
        </div>
    </section>

    <div class="odc-actions">
        <a href="{{ route('portals.staff.blood_requests.index') }}" class="od-btn od-btn--secondary">{{ __('blood_desk.clear_btn') }}</a>
        <button type="submit" class="od-btn od-btn--primary" {{ $hasListing ? '' : 'disabled' }}>
            <i data-lucide="droplet"></i>
            {{ __('blood_desk.create_submit') }}
        </button>
    </div>
</form>

</div>
@endsection

@extends('layouts.portal')

@section('title', __('family.dash_add_child') . ' — OpesCare')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('family.dash_add_child'))

@section('head')
    @include('portals.patient._finder_head')
@endsection

@php
    $l             = app()->getLocale();
    $relationships = $relationships ?? \App\Http\Controllers\MedicalId\FamilyController::RELATIONSHIPS;
    $majorityAge   = $majorityAge ?? 18;
    $maxDob        = now()->toDateString(); // a newborn is registered on the day of birth
    $minDob        = now()->subYears($majorityAge)->addDay()->toDateString();
@endphp

@section('content')
<div class="pf pf--form">

    <div class="pf-head">
        <div>
            <h1>{{ __('family.dash_add_child') }}</h1>
            <p class="pf-sub">{{ __('public.portal.add_dependent_info', [], $l) ?: 'This creates a new patient record for your dependent. No login account is created — you manage their records.' }}</p>
        </div>
        <a href="{{ route('portals.patient.family') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
            {{ __('public.portal.family_breadcrumb', [], $l) ?: 'My Family' }}
        </a>
    </div>

    <div class="pf-alert" data-section="child-only-note">
        @include('partials.finder.icon', ['name' => 'info', 'size' => 18])
        <p class="f-small">{{ __('family.age_child_note', ['age' => $majorityAge]) }}
            <a href="{{ route('portals.patient.family.invite') }}">{{ __('family.dash_invite_adult') }}</a></p>
    </div>

    @if($errors->any())
    <div class="pf-alert pf-alert--danger" role="alert">
        @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
        <div>@foreach($errors->all() as $e)<p class="f-small" style="margin:0;">{{ $e }}</p>@endforeach</div>
    </div>
    @endif

    <form method="POST" action="{{ route('portals.patient.family.store') }}" class="f-card">
        @csrf
        <div class="f-field">
            <label class="f-label" for="first_name">{{ __('public.portal.field_first_name', [], $l) ?: 'First Name' }}</label>
            <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required class="f-input">
        </div>
        <div class="f-field">
            <label class="f-label" for="last_name">{{ __('public.portal.field_last_name', [], $l) ?: 'Last Name' }}</label>
            <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required class="f-input">
        </div>
        <div class="f-field">
            <label class="f-label" for="date_of_birth">{{ __('public.portal.field_dob', [], $l) ?: 'Date of Birth' }}</label>
            <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}" required class="f-input" min="{{ $minDob }}" max="{{ $maxDob }}">
        </div>
        <div class="f-field">
            <label class="f-label" for="sex">{{ __('public.portal.field_sex', [], $l) ?: 'Sex' }}</label>
            <select id="sex" name="sex" required class="f-input">
                <option value="">{{ __('public.portal.opt_select', [], $l) ?: '— select —' }}</option>
                <option value="male" {{ old('sex') === 'male' ? 'selected' : '' }}>{{ __('public.portal.opt_male', [], $l) ?: 'Male' }}</option>
                <option value="female" {{ old('sex') === 'female' ? 'selected' : '' }}>{{ __('public.portal.opt_female', [], $l) ?: 'Female' }}</option>
            </select>
        </div>
        <div class="f-field">
            <label class="f-label" for="relationship">{{ __('public.portal.field_relationship', [], $l) ?: 'Your Relationship' }}</label>
            <select id="relationship" name="relationship" required class="f-input">
                <option value="">{{ __('public.portal.opt_select', [], $l) ?: '— select —' }}</option>
                @foreach($relationships as $r)
                <option value="{{ $r }}" {{ old('relationship', 'parent') === $r ? 'selected' : '' }}>{{ __('family.relationship.' . $r) }}</option>
                @endforeach
            </select>
            <p class="f-hint">{{ __('family.relationship_hint') }}</p>
        </div>
        <div class="f-field">
            <label class="f-label" for="access_level">{{ __('public.portal.field_access_level', [], $l) ?: 'Access Level' }}</label>
            <select id="access_level" name="access_level" required class="f-input">
                <option value="full" {{ old('access_level', 'full') === 'full' ? 'selected' : '' }}>{{ __('family.access.full') }}</option>
                <option value="read_only" {{ old('access_level') === 'read_only' ? 'selected' : '' }}>{{ __('family.access.read_only') }}</option>
            </select>
            <p class="f-hint">{{ __('family.access_full_explained') }}</p>
            <p class="f-hint">{{ __('family.access_read_only_explained') }}</p>
        </div>
        <div class="pf-actions" style="margin-top:8px;">
            <button type="submit" class="f-btn f-btn--primary" style="min-height:48px;">{{ __('public.portal.btn_register_dependent', [], $l) ?: 'Register Dependent' }}</button>
            <a href="{{ route('portals.patient.family') }}" class="f-btn">{{ __('public.portal.btn_cancel', [], $l) ?: 'Cancel' }}</a>
        </div>
    </form>
</div>
@endsection

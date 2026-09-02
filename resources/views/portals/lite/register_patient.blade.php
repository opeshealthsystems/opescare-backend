@extends('layouts.lite')
@section('title', __('public.lite_portal.register_title', [], app()->getLocale()) ?: 'Register Patient')
@php $l = app()->getLocale(); @endphp

@section('content')

<h1 class="lite-page-title">{{ __('public.lite_portal.register_title', [], $l) ?: 'Register patient' }}</h1>
<p class="lite-page-sub">{{ __('public.lite_portal.register_subtitle', [], $l) ?: 'Basic patient registration — essential fields only' }}</p>

@if($errors->any())
    <div class="lite-alert lite-alert--danger lite-alert--column">
        @foreach($errors->all() as $err)
            <div>• {{ $err }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('portals.lite.register_patient.store') }}">
    @csrf

    <div class="lite-card">
        <div class="lite-card__head">{{ __('public.lite_portal.register_card_info', [], $l) ?: 'Patient information' }}</div>
        <div class="lite-card__body">
            <div class="lite-grid-2">
                <div class="lite-form-group">
                    <label class="lite-label" for="first_name">{{ __('public.lite_portal.register_lbl_first_name', [], $l) ?: 'First name *' }}</label>
                    <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" class="lite-input" required autofocus>
                </div>
                <div class="lite-form-group">
                    <label class="lite-label" for="last_name">{{ __('public.lite_portal.register_lbl_last_name', [], $l) ?: 'Last name *' }}</label>
                    <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" class="lite-input" required>
                </div>
            </div>
            <div class="lite-grid-2">
                <div class="lite-form-group">
                    <label class="lite-label" for="date_of_birth">{{ __('public.lite_portal.register_lbl_dob', [], $l) ?: 'Date of birth' }}</label>
                    <input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth') }}" class="lite-input">
                </div>
                <div class="lite-form-group">
                    <label class="lite-label" for="gender">{{ __('public.lite_portal.register_lbl_gender', [], $l) ?: 'Gender' }}</label>
                    <select id="gender" name="gender" class="lite-input">
                        <option value="">{{ __('public.lite_portal.register_opt_select', [], $l) ?: '— Select —' }}</option>
                        <option value="male"    {{ old('gender') === 'male'    ? 'selected' : '' }}>{{ __('public.lite_portal.register_opt_male', [], $l) ?: 'Male' }}</option>
                        <option value="female"  {{ old('gender') === 'female'  ? 'selected' : '' }}>{{ __('public.lite_portal.register_opt_female', [], $l) ?: 'Female' }}</option>
                    </select>
                </div>
            </div>
            <div class="lite-form-group">
                <label class="lite-label" for="phone">{{ __('public.lite_portal.register_lbl_phone', [], $l) ?: 'Phone number' }}</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" class="lite-input" placeholder="{{ __('public.lite_portal.register_ph_phone', [], $l) ?: '+237 6 99 00 00 00' }}">
            </div>
        </div>
    </div>

    <div class="lite-row lite-mt">
        <button type="submit" class="lite-btn lite-btn--success lite-btn--full">
            <i data-lucide="user-plus"></i> {{ __('public.lite_portal.register_btn_submit', [], $l) ?: 'Register patient' }}
        </button>
    </div>
    <div class="lite-empty lite-mt">
        <a href="{{ route('portals.lite.lookup') }}" class="lite-muted-link">{{ __('public.lite_portal.register_btn_back', [], $l) ?: '← Back to lookup' }}</a>
    </div>
</form>

@endsection

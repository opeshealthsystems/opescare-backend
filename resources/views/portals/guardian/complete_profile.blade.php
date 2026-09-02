@extends('layouts.auth')

@section('title', __('onboarding.guardian.complete_title'))

{{--
    The half of caregiver sign-up that moved to after login.

    Nothing here grants access. It describes the guardian and the dependant so
    a facility can verify the relationship, which is a consent decision and
    stays with a human.
--}}

@section('content')
    <div class="auth-card">
        <h1 class="auth-headline">{{ __('onboarding.guardian.complete_headline') }}</h1>
        <p class="auth-subheadline">{{ __('onboarding.guardian.complete_subheadline') }}</p>

        @if ($errors->any())
            <div class="auth-alert auth-alert--error" role="alert">
                <i data-lucide="alert-triangle" style="width:1.05rem;height:1.05rem;flex-shrink:0;"></i>
                <ul style="margin:0;padding-left:1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('portals.guardian.complete-profile.store') }}" novalidate>
            @csrf

            <h2 class="auth-section-title">{{ __('onboarding.guardian.sec_guardian') }}</h2>

            <div class="auth-row">
                <div class="auth-field">
                    <label for="first_name" class="auth-label">{{ __('onboarding.complete.first_name') }}</label>
                    <input type="text" id="first_name" name="first_name" required autofocus
                           autocomplete="given-name" class="auth-input @error('first_name') auth-input--error @enderror"
                           value="{{ old('first_name') }}">
                    @error('first_name')<span class="auth-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="auth-field">
                    <label for="last_name" class="auth-label">{{ __('onboarding.complete.last_name') }}</label>
                    <input type="text" id="last_name" name="last_name" required
                           autocomplete="family-name" class="auth-input @error('last_name') auth-input--error @enderror"
                           value="{{ old('last_name') }}">
                    @error('last_name')<span class="auth-field-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="auth-row">
                <div class="auth-field">
                    <label for="phone" class="auth-label">{{ __('onboarding.complete.phone') }}</label>
                    <input type="tel" id="phone" name="phone" required autocomplete="tel"
                           class="auth-input @error('phone') auth-input--error @enderror" value="{{ old('phone') }}">
                    @error('phone')<span class="auth-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="auth-field">
                    <label for="dob" class="auth-label">{{ __('onboarding.complete.dob') }}</label>
                    <input type="date" id="dob" name="dob" max="{{ now()->toDateString() }}"
                           class="auth-input @error('dob') auth-input--error @enderror" value="{{ old('dob') }}">
                    @error('dob')<span class="auth-field-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <h2 class="auth-section-title">{{ __('onboarding.guardian.sec_dependent') }}</h2>

            <div class="auth-row">
                <div class="auth-field">
                    <label for="dep_name" class="auth-label">{{ __('onboarding.guardian.dep_name_lbl') }}</label>
                    <input type="text" id="dep_name" name="dep_name" required
                           class="auth-input @error('dep_name') auth-input--error @enderror" value="{{ old('dep_name') }}">
                    @error('dep_name')<span class="auth-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="auth-field">
                    <label for="dep_relationship" class="auth-label">{{ __('onboarding.guardian.relationship_lbl') }}</label>
                    <input type="text" id="dep_relationship" name="dep_relationship" required
                           class="auth-input @error('dep_relationship') auth-input--error @enderror" value="{{ old('dep_relationship') }}">
                    @error('dep_relationship')<span class="auth-field-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="auth-row">
                <div class="auth-field">
                    <label for="dep_dob" class="auth-label">{{ __('onboarding.guardian.dep_dob_lbl') }}</label>
                    <input type="date" id="dep_dob" name="dep_dob" max="{{ now()->toDateString() }}"
                           class="auth-input" value="{{ old('dep_dob') }}">
                </div>
                <div class="auth-field">
                    <label for="dep_sex" class="auth-label">{{ __('onboarding.guardian.dep_sex_lbl') }}</label>
                    <select id="dep_sex" name="dep_sex" class="auth-input">
                        <option value="">{{ __('onboarding.complete.sex_choose') }}</option>
                        <option value="male"   {{ old('dep_sex') === 'male' ? 'selected' : '' }}>{{ __('onboarding.patient.sex_male') }}</option>
                        <option value="female" {{ old('dep_sex') === 'female' ? 'selected' : '' }}>{{ __('onboarding.patient.sex_female') }}</option>
                    </select>
                </div>
            </div>

            <div class="auth-field">
                <label for="dep_health_id" class="auth-label">{{ __('onboarding.guardian.dep_health_id') }}</label>
                <input type="text" id="dep_health_id" name="dep_health_id" class="auth-input"
                       placeholder="CM-HID-XXXX-XXXX-XXXX" value="{{ old('dep_health_id') }}">
                <span class="auth-field-hint">{{ __('onboarding.guardian.dep_health_id_hint') }}</span>
            </div>

            <div class="auth-field">
                <label for="access_reason" class="auth-label">{{ __('onboarding.guardian.reason_lbl') }}</label>
                <textarea id="access_reason" name="access_reason" rows="3" class="auth-input">{{ old('access_reason') }}</textarea>
            </div>

            <button type="submit" class="auth-btn auth-btn-primary auth-btn--mt">
                {{ __('onboarding.guardian.complete_submit') }}
            </button>
        </form>

        <p class="auth-fineprint">{{ __('onboarding.guardian.verification_note') }}</p>
    </div>
@endsection

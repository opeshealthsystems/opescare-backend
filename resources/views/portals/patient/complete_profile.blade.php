@extends('layouts.auth')

@section('title', __('onboarding.complete.title'))

{{--
    The second half of sign-up, now that the account exists.

    Shown once. RequireCompletePatientProfile sends every other patient page
    here until it is submitted, because almost everything in the portal assumes
    a Patient behind it. The Health ID is issued when this is saved.
--}}

@section('content')
    <div class="auth-card">
        <h1 class="auth-headline">{{ __('onboarding.complete.headline') }}</h1>
        <p class="auth-subheadline">{{ __('onboarding.complete.subheadline') }}</p>

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

        <form method="POST" action="{{ route('portals.patient.complete-profile.store') }}" novalidate>
            @csrf

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
                    <label for="dob" class="auth-label">{{ __('onboarding.complete.dob') }}</label>
                    <input type="date" id="dob" name="dob" required max="{{ now()->toDateString() }}"
                           class="auth-input @error('dob') auth-input--error @enderror" value="{{ old('dob') }}">
                    @error('dob')<span class="auth-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="auth-field">
                    <label for="sex" class="auth-label">{{ __('onboarding.complete.sex') }}</label>
                    <select id="sex" name="sex" required class="auth-input @error('sex') auth-input--error @enderror">
                        <option value="">{{ __('onboarding.complete.sex_choose') }}</option>
                        <option value="male"   {{ old('sex') === 'male' ? 'selected' : '' }}>{{ __('onboarding.patient.sex_male') }}</option>
                        <option value="female" {{ old('sex') === 'female' ? 'selected' : '' }}>{{ __('onboarding.patient.sex_female') }}</option>
                    </select>
                    @error('sex')<span class="auth-field-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="auth-field">
                <label for="phone" class="auth-label">{{ __('onboarding.complete.phone') }}</label>
                <input type="tel" id="phone" name="phone" required autocomplete="tel"
                       class="auth-input @error('phone') auth-input--error @enderror" value="{{ old('phone') }}">
                @error('phone')<span class="auth-field-error">{{ $message }}</span>@enderror
            </div>

            <div class="auth-field">
                <label for="city" class="auth-label">{{ __('onboarding.complete.city') }}</label>
                <input type="text" id="city" name="city" autocomplete="address-level2"
                       class="auth-input" value="{{ old('city') }}">
            </div>

            {{-- Optional. Useful in an emergency, but never a reason to block
                 someone from finishing sign-up. --}}
            <details class="auth-details">
                <summary class="auth-details__summary">{{ __('onboarding.complete.emergency_toggle') }}</summary>
                <div class="auth-row">
                    <div class="auth-field">
                        <label for="emergency_name" class="auth-label">{{ __('onboarding.complete.emergency_name') }}</label>
                        <input type="text" id="emergency_name" name="emergency_name" class="auth-input" value="{{ old('emergency_name') }}">
                    </div>
                    <div class="auth-field">
                        <label for="emergency_phone" class="auth-label">{{ __('onboarding.complete.emergency_phone') }}</label>
                        <input type="tel" id="emergency_phone" name="emergency_phone" class="auth-input" value="{{ old('emergency_phone') }}">
                    </div>
                </div>
                <div class="auth-field">
                    <label for="emergency_relationship" class="auth-label">{{ __('onboarding.complete.emergency_relationship') }}</label>
                    <input type="text" id="emergency_relationship" name="emergency_relationship" class="auth-input" value="{{ old('emergency_relationship') }}">
                </div>
            </details>

            <button type="submit" class="auth-btn auth-btn-primary auth-btn--mt">
                {{ __('onboarding.complete.submit') }}
            </button>
        </form>

        <p class="auth-fineprint">{{ __('onboarding.complete.health_id_note') }}</p>
    </div>
@endsection

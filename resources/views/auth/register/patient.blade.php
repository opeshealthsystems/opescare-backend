@extends('layouts.auth')

@section('title', __('onboarding.patient.title'))

{{--
    Two fields. That is the whole form.

    This page used to ask for nine required things — names, date of birth, sex,
    phone and a complete emergency contact — before anyone could have an
    account. Identity is now collected after login, at
    /portals/patient/complete-profile, where the Health ID is also minted.
--}}

@section('content')
    <a href="{{ route('register') }}" class="auth-back-link">
        <i data-lucide="arrow-left" style="width:1rem;height:1rem;"></i>
        {{ __('onboarding.patient.back') }}
    </a>

    <div class="auth-card">
        <h1 class="auth-headline">{{ __('onboarding.patient.headline') }}</h1>
        <p class="auth-subheadline">{{ __('onboarding.patient.subheadline') }}</p>

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

        <form method="POST" action="{{ route('register.patient.submit') }}" novalidate>
            @csrf

            {{-- Preserved across the shortened form so referrals still credit. --}}
            <input type="hidden" name="ref" value="{{ request('ref') }}">

            <div class="auth-field">
                <label for="email" class="auth-label">{{ __('onboarding.patient.email') }}</label>
                <input type="email" id="email" name="email" required autofocus
                       autocomplete="email" inputmode="email"
                       class="auth-input @error('email') auth-input--error @enderror"
                       value="{{ old('email') }}">
                @error('email')<span class="auth-field-error">{{ $message }}</span>@enderror
            </div>

            <div class="auth-field">
                <label for="password" class="auth-label">{{ __('onboarding.patient.password') }}</label>
                <input type="password" id="password" name="password" required
                       autocomplete="new-password" minlength="8"
                       class="auth-input @error('password') auth-input--error @enderror">
                <span class="auth-field-hint">{{ __('onboarding.patient.password_hint') }}</span>
                @error('password')<span class="auth-field-error">{{ $message }}</span>@enderror
            </div>

            <div class="auth-field">
                <label for="password_confirmation" class="auth-label">{{ __('onboarding.patient.password_confirm') }}</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       autocomplete="new-password" minlength="8" class="auth-input">
            </div>

            <button type="submit" class="auth-btn auth-btn-primary auth-btn--mt">
                {{ __('onboarding.patient.submit') }}
            </button>
        </form>

        {{-- The form this replaced carried "I agree" checkboxes that were never
             validated server-side, so they recorded nothing. A notice with real
             links is honest about the same thing and keeps the form at two
             fields. No health data is collected here — identity is gathered at
             profile completion, behind its own consent notice. --}}
        <p class="auth-fineprint">
            {!! __('onboarding.patient.terms_notice', [
                'terms'   => '<a href="' . route('public.terms') . '">' . e(__('onboarding.patient.terms_link')) . '</a>',
                'privacy' => '<a href="' . route('public.privacy') . '">' . e(__('onboarding.patient.privacy_link')) . '</a>',
            ]) !!}
        </p>

        <p class="auth-fineprint">
            {{ __('onboarding.patient.next_step_note') }}
        </p>

        <p class="auth-alt-action">
            {{ __('onboarding.patient.have_account') }}
            <a href="{{ route('login') }}">{{ __('onboarding.patient.sign_in') }}</a>
        </p>
    </div>
@endsection

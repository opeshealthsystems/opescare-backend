@extends('layouts.auth')

@section('title', __('onboarding.patient.title'))

{{--
    Two fields. That is the whole form — the reduced flow is deliberate.

    This page used to ask for nine required things — names, date of birth, sex,
    phone and a complete emergency contact — before anyone could have an
    account. Identity is now collected after login, at
    /portals/patient/complete-profile, where the Health ID is also minted.
--}}

@section('content')
    <div class="f-auth-col">
        <a href="{{ route('register') }}" class="f-auth-back">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 16])
            {{ __('onboarding.patient.back') }}
        </a>

        <div class="f-auth-card">
            <h1 class="f-auth-title">{{ __('onboarding.patient.headline') }}</h1>
            <p class="f-auth-sub">{{ __('onboarding.patient.subheadline') }}</p>

            {{-- The emblem the Health ID being created will carry. --}}
            @include('auth.partials.secure_seal')

            {{-- Why an account helps — one line, per the redesign brief --}}
            <p class="f-auth-benefit">
                @include('partials.finder.icon', ['name' => 'calendar', 'size' => 16])
                {{ __('finder_auth.signup.benefit') }}
            </p>

            @if ($errors->any())
                <div class="f-auth-alert f-auth-alert--danger" role="alert">
                    @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                    <ul>
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

                <div class="f-field">
                    <label for="email" class="f-label">{{ __('onboarding.patient.email') }}</label>
                    <input type="email" id="email" name="email" required autofocus
                           autocomplete="email" inputmode="email"
                           class="f-input @error('email') f-input--invalid @enderror"
                           value="{{ old('email') }}">
                    @error('email')
                        <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                    @enderror
                </div>

                <div class="f-field">
                    <label for="password" class="f-label">{{ __('onboarding.patient.password') }}</label>
                    <div class="f-auth-pass">
                        <input type="password" id="password" name="password" required
                               autocomplete="new-password" minlength="8"
                               class="f-input @error('password') f-input--invalid @enderror">
                        @include('auth.partials.password_toggle', ['for' => 'password'])
                    </div>
                    <span class="f-hint">{{ __('onboarding.patient.password_hint') }}</span>
                    @error('password')
                        <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                    @enderror
                </div>

                <div class="f-field">
                    <label for="password_confirmation" class="f-label">{{ __('onboarding.patient.password_confirm') }}</label>
                    <div class="f-auth-pass">
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                               autocomplete="new-password" minlength="8" class="f-input">
                        @include('auth.partials.password_toggle', ['for' => 'password_confirmation'])
                    </div>
                </div>

                <button type="submit" class="f-btn f-btn--primary f-btn--block">
                    {{ __('onboarding.patient.submit') }}
                </button>
            </form>

            {{-- The form this replaced carried "I agree" checkboxes that were never
                 validated server-side, so they recorded nothing. A notice with real
                 links is honest about the same thing and keeps the form at two
                 fields. No health data is collected here — identity is gathered at
                 profile completion, behind its own consent notice. --}}
            <p class="f-auth-fine">
                {!! __('onboarding.patient.terms_notice', [
                    'terms'   => '<a href="' . route('public.terms') . '">' . e(__('onboarding.patient.terms_link')) . '</a>',
                    'privacy' => '<a href="' . route('public.privacy') . '">' . e(__('onboarding.patient.privacy_link')) . '</a>',
                ]) !!}
            </p>

            <p class="f-auth-fine">
                {{ __('onboarding.patient.next_step_note') }}
            </p>

            <div class="f-auth-alt f-auth-alt--divide">
                <p>{{ __('onboarding.patient.have_account') }}
                    <a href="{{ route('login') }}">{{ __('onboarding.patient.sign_in') }}</a>
                </p>
            </div>
        </div>
    </div>
@endsection

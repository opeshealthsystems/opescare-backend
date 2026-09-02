@extends('layouts.auth')

@section('title', __('onboarding.guardian.title'))

{{--
    Two fields, like every other account.

    This page used to ask for sixteen things — the guardian's full identity and
    the dependant's, plus a reason for access — before anyone could sign up.
    All of it is collected after login, at /portals/guardian/complete-profile,
    where it goes to a reviewer. Guardian access still requires institutional
    verification; that has not changed, only when we ask.
--}}

@section('content')
    <a href="{{ route('register') }}" class="auth-back-link">
        <i data-lucide="arrow-left" style="width:1rem;height:1rem;"></i>
        {{ __('onboarding.guardian.back') }}
    </a>

    <div class="auth-card">
        <h1 class="auth-headline">{{ __('onboarding.guardian.headline') }}</h1>
        <p class="auth-subheadline">{{ __('onboarding.guardian.subheadline') }}</p>

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

        <form method="POST" action="{{ route('register.guardian.submit') }}" novalidate>
            @csrf

            <div class="auth-field">
                <label for="email" class="auth-label">{{ __('onboarding.guardian.email') }}</label>
                <input type="email" id="email" name="email" required autofocus
                       autocomplete="email" inputmode="email"
                       class="auth-input @error('email') auth-input--error @enderror"
                       value="{{ old('email') }}">
                @error('email')<span class="auth-field-error">{{ $message }}</span>@enderror
            </div>

            <div class="auth-field">
                <label for="password" class="auth-label">{{ __('onboarding.guardian.password') }}</label>
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
                {{ __('onboarding.guardian.submit') }}
            </button>
        </form>

        <p class="auth-fineprint">
            {!! __('onboarding.patient.terms_notice', [
                'terms'   => '<a href="' . route('public.terms') . '">' . e(__('onboarding.patient.terms_link')) . '</a>',
                'privacy' => '<a href="' . route('public.privacy') . '">' . e(__('onboarding.patient.privacy_link')) . '</a>',
            ]) !!}
        </p>

        <p class="auth-fineprint">
            {{ __('onboarding.guardian.next_step_note') }}
        </p>

        <p class="auth-alt-action">
            {{ __('onboarding.patient.have_account') }}
            <a href="{{ route('login') }}">{{ __('onboarding.patient.sign_in') }}</a>
        </p>
    </div>
@endsection

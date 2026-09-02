{{--
    Two states, and only two.

    A live link renders the form. A dead one — expired, already spent, forged,
    or pointed at an address with no account — renders the card below and NO
    form at all, served with 410 Gone. The page used to render a working form
    for any string in the URL, which invited people to type a new password into
    something that could not save it.
--}}
@extends('layouts.auth')

@section('title', $invalid ?? false ? __('passwords.invalid_link.title') : __('onboarding.forgot.reset_title'))

@section('content')
    <div class="auth-card">

        @if($invalid ?? false)

            <div class="auth-card__head">
                <h1 class="auth-card__title">{{ __('passwords.invalid_link.title') }}</h1>
            </div>

            <div class="auth-alert auth-alert-danger">
                <i data-lucide="link-2-off"></i>
                <div>{{ __('passwords.invalid_link.body', ['minutes' => $ttl ?? 60]) }}</div>
            </div>

            <p class="auth-card__sub">{{ __('passwords.invalid_link.next') }}</p>

            <div class="auth-back-action">
                <a href="{{ route('password.request') }}" class="auth-btn auth-btn-primary">
                    <i data-lucide="rotate-ccw"></i>
                    <span>{{ __('passwords.invalid_link.cta') }}</span>
                </a>
            </div>

            <div class="auth-footer-links auth-footer-links--mt">
                <a href="{{ route('login') }}" class="auth-back-link">
                    <i data-lucide="arrow-left"></i>
                    {{ __('onboarding.selector.signin') }}
                </a>
            </div>

        @else

            <div class="auth-card__head">
                <h1 class="auth-card__title">{{ __('onboarding.forgot.reset_title') }}</h1>
                <p class="auth-card__sub">{{ __('passwords.form.intro', ['email' => $email]) }}</p>
            </div>

            @if(session('error'))
                <div class="auth-alert auth-alert-danger">
                    <i data-lucide="alert-circle"></i>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            @if($errors->any())
                <div class="auth-alert auth-alert-danger">
                    <i data-lucide="alert-circle"></i>
                    <ul class="auth-alert-list">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('password.update', $token) }}" method="POST" class="auth-form">
                @csrf

                {{-- password_reset_tokens is keyed by email, so the address has
                     to travel with the token. Changing it here does not open
                     another account: the token was hashed against this one. --}}
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="auth-form-group">
                    <label for="password" class="auth-label">{{ __('onboarding.forgot.new_pass') }} *</label>
                    <div class="auth-pass-wrapper auth-input-icon-wrap">
                        <i data-lucide="lock" class="auth-input-icon"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="auth-input auth-input--icon{{ $errors->has('password') ? ' auth-input-error' : '' }}"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            placeholder="••••••••"
                        >
                        <button type="button" class="auth-pass-toggle" data-toggle-password="password">
                            <i data-lucide="eye" id="password-toggle-icon"></i>
                        </button>
                    </div>
                    @error('password')<div class="auth-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="auth-form-group">
                    <label for="password_confirmation" class="auth-label">{{ __('onboarding.forgot.confirm_new') }} *</label>
                    <div class="auth-pass-wrapper auth-input-icon-wrap">
                        <i data-lucide="lock" class="auth-input-icon"></i>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="auth-input auth-input--icon{{ $errors->has('password_confirmation') ? ' auth-input-error' : '' }}"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            placeholder="••••••••"
                        >
                        <button type="button" class="auth-pass-toggle" data-toggle-password="password_confirmation">
                            <i data-lucide="eye" id="password_confirmation-toggle-icon"></i>
                        </button>
                    </div>
                    @error('password_confirmation')<div class="auth-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="auth-privacy-note">
                    <strong>{{ __('passwords.form.policy_title') }}</strong>
                    <ul class="auth-policy-list">
                        <li>{{ __('passwords.form.policy_length') }}</li>
                        <li>{{ __('passwords.form.policy_unique') }}</li>
                        <li>{{ __('passwords.form.policy_private') }}</li>
                    </ul>
                </div>

                <button type="submit" class="auth-btn auth-btn-primary auth-btn--mt">
                    <i data-lucide="check-circle"></i>
                    <span>{{ __('onboarding.forgot.reset_cta') }}</span>
                </button>
            </form>

            <div class="auth-footer-links auth-footer-links--mt">
                <a href="{{ route('login') }}" class="auth-back-link">
                    <i data-lucide="arrow-left"></i>
                    {{ __('onboarding.selector.signin') }}
                </a>
            </div>

        @endif

    </div>
@endsection

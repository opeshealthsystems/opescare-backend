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
    <div class="f-auth-col">
        <div class="f-auth-card">

            @if($invalid ?? false)

                <div class="f-auth-badge f-auth-badge--danger">
                    @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 22])
                </div>

                <h1 class="f-auth-title">{{ __('passwords.invalid_link.title') }}</h1>
                <p class="f-auth-sub" style="margin-bottom:16px">{{ __('passwords.invalid_link.body', ['minutes' => $ttl ?? 60]) }}</p>
                <p class="f-auth-sub">{{ __('passwords.invalid_link.next') }}</p>

                <a href="{{ route('password.request') }}" class="f-btn f-btn--primary f-btn--block">
                    @include('partials.finder.icon', ['name' => 'refresh-cw', 'size' => 18])
                    {{ __('passwords.invalid_link.cta') }}
                </a>

                <div class="f-auth-alt f-auth-alt--divide" style="margin-top:16px">
                    <p><a href="{{ route('login') }}">{{ __('onboarding.selector.signin') }}</a></p>
                </div>

            @else

                <h1 class="f-auth-title">{{ __('onboarding.forgot.reset_title') }}</h1>
                <p class="f-auth-sub">{{ __('passwords.form.intro', ['email' => $email]) }}</p>

                @if(session('error'))
                    <div class="f-auth-alert f-auth-alert--danger" role="alert">
                        @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="f-auth-alert f-auth-alert--danger" role="alert">
                        @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                        <ul>
                            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('password.update', $token) }}" method="POST" novalidate>
                    @csrf

                    {{-- password_reset_tokens is keyed by email, so the address has
                         to travel with the token. Changing it here does not open
                         another account: the token was hashed against this one. --}}
                    <input type="hidden" name="email" value="{{ $email }}">

                    <div class="f-field">
                        <label for="password" class="f-label">{{ __('onboarding.forgot.new_pass') }}</label>
                        <div class="f-auth-pass">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="f-input{{ $errors->has('password') ? ' f-input--invalid' : '' }}"
                                required
                                minlength="8"
                                autocomplete="new-password"
                            >
                            @include('auth.partials.password_toggle', ['for' => 'password'])
                        </div>
                        @error('password')
                            <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                        @enderror
                    </div>

                    <div class="f-field">
                        <label for="password_confirmation" class="f-label">{{ __('onboarding.forgot.confirm_new') }}</label>
                        <div class="f-auth-pass">
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                class="f-input{{ $errors->has('password_confirmation') ? ' f-input--invalid' : '' }}"
                                required
                                minlength="8"
                                autocomplete="new-password"
                            >
                            @include('auth.partials.password_toggle', ['for' => 'password_confirmation'])
                        </div>
                        @error('password_confirmation')
                            <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                        @enderror
                    </div>

                    <div class="f-auth-fine" style="margin:0 0 16px">
                        <strong>{{ __('passwords.form.policy_title') }}</strong>
                        <ul style="margin:6px 0 0;padding-left:18px">
                            <li>{{ __('passwords.form.policy_length') }}</li>
                            <li>{{ __('passwords.form.policy_unique') }}</li>
                            <li>{{ __('passwords.form.policy_private') }}</li>
                        </ul>
                    </div>

                    <button type="submit" class="f-btn f-btn--primary f-btn--block">
                        @include('partials.finder.icon', ['name' => 'check', 'size' => 18])
                        {{ __('onboarding.forgot.reset_cta') }}
                    </button>
                </form>

                <div class="f-auth-alt f-auth-alt--divide">
                    <p><a href="{{ route('login') }}">{{ __('onboarding.selector.signin') }}</a></p>
                </div>

            @endif

        </div>
    </div>
@endsection

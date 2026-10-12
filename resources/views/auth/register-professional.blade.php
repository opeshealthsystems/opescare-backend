@extends('layouts.auth')

@section('title', __('professional.reg_title', [], app()->getLocale()))

{{--
    Public self-service registration for a health professional. Served by
    ProfessionalRegistrationController::create (route register.professional).
    Field names map 1:1 to that controller's store() validation:
      name, email, phone, profession, password, password_confirmation,
      issuing_body, credential_number (the last two an optional first
      qualification). Accounts are created UNVERIFIED — see reg_note.
--}}

@section('content')
    <div class="f-auth-col">
        <a href="{{ route('register') }}" class="f-auth-back">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 16])
            {{ __('onboarding.common.back', [], app()->getLocale()) }}
        </a>

        <div class="f-auth-card">
            <h1 class="f-auth-title">{{ __('professional.reg_title', [], app()->getLocale()) }}</h1>
            <p class="f-auth-sub">{{ __('professional.reg_subtitle', [], app()->getLocale()) }}</p>

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

            <form method="POST" action="{{ route('register.professional.submit') }}" novalidate>
                @csrf

                <div class="f-field">
                    <label for="name" class="f-label">{{ __('professional.reg_full_name', [], app()->getLocale()) }}</label>
                    <input type="text" id="name" name="name" required autofocus
                           autocomplete="name"
                           class="f-input @error('name') f-input--invalid @enderror"
                           value="{{ old('name') }}">
                    @error('name')
                        <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                    @enderror
                </div>

                <div class="f-field">
                    <label for="email" class="f-label">{{ __('professional.reg_email', [], app()->getLocale()) }}</label>
                    <input type="email" id="email" name="email" required
                           autocomplete="email" inputmode="email"
                           class="f-input @error('email') f-input--invalid @enderror"
                           value="{{ old('email') }}">
                    @error('email')
                        <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                    @enderror
                </div>

                <div class="f-field">
                    <label for="phone" class="f-label">{{ __('professional.reg_phone', [], app()->getLocale()) }}</label>
                    <input type="tel" id="phone" name="phone" required
                           autocomplete="tel" inputmode="tel"
                           class="f-input @error('phone') f-input--invalid @enderror"
                           value="{{ old('phone') }}">
                    @error('phone')
                        <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                    @enderror
                </div>

                <div class="f-field">
                    <label for="profession" class="f-label">{{ __('professional.reg_profession', [], app()->getLocale()) }}</label>
                    <select id="profession" name="profession" required
                            class="f-input @error('profession') f-input--invalid @enderror">
                        <option value="" disabled {{ old('profession') ? '' : 'selected' }}>{{ __('onboarding.common.select_option', [], app()->getLocale()) }}</option>
                        @foreach ($professions as $key)
                            <option value="{{ $key }}" {{ old('profession') === $key ? 'selected' : '' }}>{{ __('professional.prof_' . $key, [], app()->getLocale()) ?: ucfirst($key) }}</option>
                        @endforeach
                    </select>
                    @error('profession')
                        <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                    @enderror
                </div>

                <div class="f-field">
                    <label for="password" class="f-label">{{ __('professional.reg_password', [], app()->getLocale()) }}</label>
                    <div class="f-auth-pass">
                        <input type="password" id="password" name="password" required
                               autocomplete="new-password" minlength="8"
                               class="f-input @error('password') f-input--invalid @enderror">
                        @include('auth.partials.password_toggle', ['for' => 'password'])
                    </div>
                    @error('password')
                        <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                    @enderror
                </div>

                <div class="f-field">
                    <label for="password_confirmation" class="f-label">{{ __('professional.reg_password_confirm', [], app()->getLocale()) }}</label>
                    <div class="f-auth-pass">
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                               autocomplete="new-password" minlength="8" class="f-input">
                        @include('auth.partials.password_toggle', ['for' => 'password_confirmation'])
                    </div>
                </div>

                {{-- Optional first qualification — most professionals have one to hand. --}}
                <p class="f-auth-section">{{ __('professional.reg_qual_section', [], app()->getLocale()) }}</p>
                <p class="f-auth-fine" style="margin-top:0">{{ __('professional.reg_qual_intro', [], app()->getLocale()) }}</p>

                <div class="f-field">
                    <label for="issuing_body" class="f-label">{{ __('professional.issuing_body', [], app()->getLocale()) }}</label>
                    <input type="text" id="issuing_body" name="issuing_body"
                           autocomplete="off"
                           class="f-input @error('issuing_body') f-input--invalid @enderror"
                           value="{{ old('issuing_body') }}">
                    @error('issuing_body')
                        <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                    @enderror
                </div>

                <div class="f-field">
                    <label for="credential_number" class="f-label">{{ __('professional.credential_number', [], app()->getLocale()) }}</label>
                    <input type="text" id="credential_number" name="credential_number"
                           autocomplete="off"
                           class="f-input @error('credential_number') f-input--invalid @enderror"
                           value="{{ old('credential_number') }}">
                    @error('credential_number')
                        <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="f-btn f-btn--primary f-btn--block">
                    {{ __('professional.reg_create', [], app()->getLocale()) }}
                </button>
            </form>

            <div class="f-auth-note">
                @include('partials.finder.icon', ['name' => 'info', 'size' => 16])
                <p>{{ __('professional.reg_note', [], app()->getLocale()) }}</p>
            </div>

            <div class="f-auth-alt f-auth-alt--divide">
                <p>{{ __('professional.reg_have_account', [], app()->getLocale()) }}
                    <a href="{{ route('login') }}">{{ __('professional.reg_sign_in', [], app()->getLocale()) }}</a>
                </p>
            </div>
        </div>
    </div>
@endsection

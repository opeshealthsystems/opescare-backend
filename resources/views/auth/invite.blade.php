@extends('layouts.auth')

@section('title', __('onboarding.invite.title'))

@section('content')
    <div class="f-auth-col">
        <div class="f-auth-card">
            @if(isset($error))
                <div class="f-auth-card--center" style="padding:8px 0">
                    <div class="f-auth-badge f-auth-badge--danger f-auth-badge--center">
                        @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 22])
                    </div>

                    @if($error === 'expired')
                        <h1 class="f-auth-title">{{ __('onboarding.invite.errors.expired') }}</h1>
                    @elseif($error === 'used')
                        <h1 class="f-auth-title">{{ __('onboarding.invite.errors.used') }}</h1>
                    @elseif($error === 'revoked')
                        <h1 class="f-auth-title">{{ __('onboarding.invite.errors.revoked') }}</h1>
                    @endif

                    <p class="f-auth-sub">{{ __('team.invite_error_help') }}</p>

                    <a href="{{ route('login') }}" class="f-btn f-btn--block">
                        @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 16])
                        {{ __('onboarding.selector.signin') }}
                    </a>
                </div>
            @else
                <div class="f-auth-badge">
                    @include('partials.finder.icon', ['name' => 'building-2', 'size' => 22])
                </div>
                <h1 class="f-auth-title">{{ __('onboarding.invite.title') }}</h1>
                <p class="f-auth-sub">{{ __('onboarding.invite.subtitle') }}</p>

                <div class="f-auth-meta">
                    <p class="f-auth-meta__title">{{ __('onboarding.invite.sec_details') }}</p>
                    <div class="f-auth-meta__row">
                        <span class="f-auth-meta__label">{{ __('team.invite_email_lbl') }}</span>
                        <span class="f-auth-meta__value">{{ $email }}</span>
                    </div>
                    <div class="f-auth-meta__row">
                        <span class="f-auth-meta__label">{{ __('onboarding.invite.facility_lbl') }}</span>
                        <span class="f-auth-meta__value">{{ $facility_name }}</span>
                    </div>
                    <div class="f-auth-meta__row">
                        <span class="f-auth-meta__label">{{ __('onboarding.invite.role_lbl') }}</span>
                        <span class="f-auth-meta__value f-auth-meta__value--brand">{{ $role_name }}</span>
                    </div>
                    <div class="f-auth-meta__row">
                        <span class="f-auth-meta__label">{{ __('onboarding.invite.invited_by_lbl') }}</span>
                        <span class="f-auth-meta__value">{{ $invited_by }}</span>
                    </div>
                    <div class="f-auth-meta__row">
                        <span class="f-auth-meta__label">{{ __('onboarding.invite.expiry_lbl') }}</span>
                        <span class="f-auth-meta__value f-auth-meta__value--warn">{{ $expiry }}</span>
                    </div>
                </div>

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

                <form action="{{ route('invite.accept.submit', $token) }}" method="POST" novalidate>
                    @csrf

                    <p class="f-auth-section" style="margin-top:0">{{ __('onboarding.invite.sec_profile') }}</p>

                    <div class="f-field">
                        <label for="name" class="f-label">{{ __('onboarding.common.full_name') }}</label>
                        <input type="text" id="name" name="name" autocomplete="name"
                               class="f-input{{ $errors->has('name') ? ' f-input--invalid' : '' }}"
                               required value="{{ old('name') }}">
                        @error('name')
                            <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                        @enderror
                    </div>

                    <div class="f-field">
                        <label for="phone" class="f-label">{{ __('onboarding.common.phone') }}</label>
                        <input type="tel" id="phone" name="phone" autocomplete="tel" inputmode="tel"
                               class="f-input{{ $errors->has('phone') ? ' f-input--invalid' : '' }}"
                               required value="{{ old('phone') }}">
                        @error('phone')
                            <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                        @enderror
                    </div>

                    <div class="f-field">
                        <label for="password" class="f-label">{{ __('onboarding.common.password') }}</label>
                        <div class="f-auth-pass">
                            <input type="password" id="password" name="password" minlength="8"
                                   autocomplete="new-password"
                                   class="f-input{{ $errors->has('password') ? ' f-input--invalid' : '' }}" required>
                            @include('auth.partials.password_toggle', ['for' => 'password'])
                        </div>
                        <span class="f-hint">{{ __('team.password_hint') }}</span>
                        @error('password')
                            <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                        @enderror
                    </div>

                    <div class="f-field">
                        <label for="confirm_password" class="f-label">{{ __('onboarding.common.confirm_password') }}</label>
                        <div class="f-auth-pass">
                            <input type="password" id="confirm_password" name="confirm_password"
                                   autocomplete="new-password"
                                   class="f-input{{ $errors->has('confirm_password') ? ' f-input--invalid' : '' }}" required>
                            @include('auth.partials.password_toggle', ['for' => 'confirm_password'])
                        </div>
                        @error('confirm_password')
                            <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                        @enderror
                    </div>

                    <div class="f-auth-check">
                        <input type="checkbox" id="accept_terms" name="accept_terms" required>
                        <label for="accept_terms">{{ __('onboarding.invite.terms_label') }}</label>
                    </div>
                    @error('accept_terms')
                        <span class="f-error f-auth-error" style="margin:-8px 0 12px">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                    @enderror

                    <button type="submit" class="f-btn f-btn--primary f-btn--block">
                        @include('partials.finder.icon', ['name' => 'check', 'size' => 18])
                        {{ __('onboarding.invite.cta_btn') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection

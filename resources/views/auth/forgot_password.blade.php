@extends('layouts.auth')

@section('title', __('onboarding.forgot.title'))

@section('content')
    <div class="f-auth-col">
        <div class="f-auth-card">
            <h1 class="f-auth-title">{{ __('onboarding.forgot.title') }}</h1>
            {{-- The old copy promised "email address or phone number". Recovery
                 goes out by email only, so the description is the email one. --}}
            <p class="f-auth-sub">{{ __('passwords.request.desc') }}</p>

            @if(session('success'))
                {{-- The generic "if that address matches an account…" flash is a
                     deliberate anti-enumeration device — rendered verbatim. --}}
                <div class="f-auth-alert f-auth-alert--success" role="status">
                    @include('partials.finder.icon', ['name' => 'check', 'size' => 18])
                    <div>{{ session('success') }}</div>
                </div>

                <a href="{{ route('login') }}" class="f-btn f-btn--block">
                    @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 16])
                    {{ __('onboarding.selector.signin') }}
                </a>
            @else
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

                <form action="{{ route('password.email') }}" method="POST" novalidate>
                    @csrf

                    <div class="f-field">
                        <label for="email" class="f-label">{{ __('onboarding.common.email') }}</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="f-input{{ $errors->has('email') ? ' f-input--invalid' : '' }}"
                            required
                            autofocus
                            autocomplete="email"
                            inputmode="email"
                            maxlength="180"
                            value="{{ old('email') }}"
                        >
                        @error('email')
                            <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                        @enderror
                    </div>

                    <p class="f-auth-fine" style="margin:0 0 16px">{{ __('onboarding.forgot.privacy_note') }}</p>

                    <button type="submit" class="f-btn f-btn--primary f-btn--block">
                        {{ __('onboarding.forgot.cta') }}
                        @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 18])
                    </button>
                </form>

                <div class="f-auth-alt f-auth-alt--divide">
                    <p><a href="{{ route('login') }}">{{ __('onboarding.selector.signin') }}</a></p>
                </div>
            @endif
        </div>
    </div>
@endsection

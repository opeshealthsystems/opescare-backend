@extends('layouts.auth')

@section('title', __('finder_auth.mfa.title'))

@section('content')
    <div class="f-auth-col">
        <div class="f-auth-card">
            <div class="f-auth-badge">
                @include('partials.finder.icon', ['name' => 'shield-check', 'size' => 22])
            </div>

            <h1 class="f-auth-title">{{ __('finder_auth.mfa.title') }}</h1>
            <p class="f-auth-sub">
                @if($setupRequired ?? false)
                    {{ __('finder_auth.mfa.setup_required') }}
                @else
                    {{ __('finder_auth.mfa.enter_code') }}
                @endif
            </p>

            @if(session('error'))
                <div class="f-auth-alert f-auth-alert--danger" role="alert">
                    @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            @if(! ($setupRequired ?? false))
            <form action="{{ route('mfa.challenge.submit') }}" method="POST" novalidate>
                @csrf

                <div class="f-field">
                    <label for="code" class="f-label">{{ __('finder_auth.mfa.code_label') }}</label>
                    <input
                        type="text"
                        id="code"
                        name="code"
                        class="f-input{{ $errors->has('code') ? ' f-input--invalid' : '' }}"
                        style="text-align:center;letter-spacing:.3em;font-size:1.375rem;font-weight:700"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        placeholder="000000"
                        required
                        autofocus
                    >
                    @error('code')
                        <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="f-btn f-btn--primary f-btn--block">
                    @include('partials.finder.icon', ['name' => 'check', 'size' => 18])
                    {{ __('finder_auth.mfa.verify') }}
                </button>
            </form>
            @endif

            <div class="f-auth-alt f-auth-alt--divide">
                <p>{{ __('finder_auth.mfa.lost_access') }}
                    <a href="{{ route('public.contact') }}">{{ __('finder_auth.mfa.contact_support') }}</a>
                </p>
            </div>
        </div>
    </div>
@endsection

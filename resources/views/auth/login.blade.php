@extends('layouts.auth')

@section('title', __('login_page.head_title'))
@section('auth_bare', '1')
@section('body_class', 'f-login')

@push('head')
    {{-- The background is above the fold: preload it, never lazy-load it. --}}
    <link rel="preload" as="image" href="{{ asset('brand/login/background.webp') }}" type="image/webp">
    <link rel="stylesheet" href="{{ asset('css/login-page.css') }}?v={{ @filemtime(public_path('css/login-page.css')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('css/auth-clean.css') }}?v={{ @filemtime(public_path('css/auth-clean.css')) ?: '1' }}">
@endpush

{{--
    Sign-in page, redesigned 2026-10-18 to the owner's references
    (docs/design/login-2026-10-18). Two columns on desktop (brand panel left,
    white card right); brand header above the card on phones.

    Behaviour contract (unchanged): POST login.submit, CSRF, throttle, flash
    keys, old() input, password toggle via [data-f-toggle] (layout script with
    a CSP nonce). The demo panel is a separate partial (auth/partials/demo-panel),
    never rendered in production. The identifier field is
    name="login" (email OR Health ID); an error raised on either "login" or the
    legacy "email" key is shown under it.
--}}
@section('content')
@php
    $loginError = $errors->first('login') ?: $errors->first('email');
@endphp
<div class="lg-page">
    <div class="lg-topbar">
        <nav class="lg-lang" aria-label="{{ __('login_page.language') }}">
            @include('partials.finder.icon', ['name' => 'globe', 'size' => 18])
            <a href="{{ route('lang.switch', 'en') }}" @class(['is-active' => app()->getLocale() === 'en']) lang="en" hreflang="en">English</a>
            <span aria-hidden="true">|</span>
            <a href="{{ route('lang.switch', 'fr') }}" @class(['is-active' => app()->getLocale() === 'fr']) lang="fr" hreflang="fr">Français</a>
        </nav>
    </div>

    <div class="lg-grid">
        {{-- Brand panel --}}
        <section class="lg-brand" aria-labelledby="lg-brand-name">
            <div class="lg-caps">
                <p>{{ __('login_page.caps_values') }}</p>
                <span class="lg-rule" aria-hidden="true"></span>
                <p>{{ __('login_page.caps_promise') }}</p>
            </div>

            <a href="{{ route('public.landing') }}" class="lg-mark" aria-label="{{ __('login_page.home') }}">
                <img src="{{ asset('brand/opescare-logo-512.png') }}" width="512" height="512" alt="" fetchpriority="high">
            </a>
            <p class="lg-wordmark" id="lg-brand-name">OPES<span>CARE</span></p>
            <p class="lg-tagline">{{ __('login_page.brand_tagline') }}</p>
            <span class="lg-rule lg-rule--center" aria-hidden="true"></span>
            <p class="lg-lead">{{ __('login_page.brand_lead') }}</p>

            <ul class="lg-features" aria-label="{{ __('login_page.features_label') }}">
                <li>@include('partials.finder.icon', ['name' => 'fingerprint', 'size' => 34])<span>{{ __('login_page.feature_identity') }}</span></li>
                <li>@include('partials.finder.icon', ['name' => 'network', 'size' => 34])<span>{{ __('login_page.feature_connected') }}</span></li>
                <li>@include('partials.finder.icon', ['name' => 'shield-check', 'size' => 34])<span>{{ __('login_page.feature_trusted') }}</span></li>
                <li>@include('partials.finder.icon', ['name' => 'chart-column', 'size' => 34])<span>{{ __('login_page.feature_communities') }}</span></li>
            </ul>
            <ul class="lg-values" aria-label="{{ __('login_page.values_label') }}">
                <li>@include('partials.finder.icon', ['name' => 'lock', 'size' => 20])<span>{{ __('login_page.value_secure') }}</span></li>
                <li>@include('partials.finder.icon', ['name' => 'database', 'size' => 20])<span>{{ __('login_page.value_interop') }}</span></li>
                <li>@include('partials.finder.icon', ['name' => 'users', 'size' => 20])<span>{{ __('login_page.value_people') }}</span></li>
                <li>@include('partials.finder.icon', ['name' => 'earth', 'size' => 20])<span>{{ __('login_page.value_africa') }}</span></li>
            </ul>
        </section>

        {{-- Sign-in card --}}
        <section class="lg-card" aria-labelledby="lg-title">
            <h1 class="lg-title" id="lg-title">{{ __('login_page.welcome') }}</h1>
            <p class="lg-sub">{{ __('login_page.subtitle') }}</p>

            @if(session('success'))
                <div class="f-auth-alert f-auth-alert--success" role="status">
                    @include('partials.finder.icon', ['name' => 'check', 'size' => 18])
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if(session('error'))
                <div class="f-auth-alert f-auth-alert--danger" role="alert">
                    @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            <form action="{{ route('login.submit') }}" method="POST" novalidate class="lg-form">
                @csrf

                <div class="lg-field">
                    <label for="login" class="lg-label">{{ __('login_page.identifier') }}</label>
                    <div class="lg-control">
                        @include('partials.finder.icon', ['name' => 'mail', 'size' => 20, 'class' => 'lg-control__icon'])
                        <input
                            type="text"
                            id="login"
                            name="login"
                            class="lg-input{{ $loginError ? ' is-invalid' : '' }}"
                            required
                            autofocus
                            autocomplete="username"
                            autocapitalize="none"
                            spellcheck="false"
                            aria-describedby="login-hint{{ $loginError ? ' login-error' : '' }}"
                            @if($loginError) aria-invalid="true" @endif
                            value="{{ old('login', old('email')) }}"
                        >
                    </div>
                    <p class="lg-hint" id="login-hint">{{ __('login_page.identifier_hint') }}</p>
                    @if($loginError)
                        <p class="lg-error" id="login-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $loginError }}</p>
                    @endif
                </div>

                <div class="lg-field">
                    <label for="password" class="lg-label">{{ __('login_page.password') }}</label>
                    <div class="lg-control f-auth-pass">
                        @include('partials.finder.icon', ['name' => 'lock', 'size' => 20, 'class' => 'lg-control__icon'])
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="lg-input{{ $errors->has('password') ? ' is-invalid' : '' }}"
                            required
                            autocomplete="current-password"
                            @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        >
                        @include('auth.partials.password_toggle', ['for' => 'password'])
                    </div>
                    @error('password')
                        <p class="lg-error" id="password-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</p>
                    @enderror
                </div>

                <div class="lg-row">
                    <label class="lg-check" for="remember">
                        <input type="checkbox" id="remember" name="remember" @checked(old('remember'))>
                        <span>{{ __('login_page.remember') }}</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="lg-link">{{ __('login_page.forgot') }}</a>
                </div>

                <button type="submit" class="lg-submit">
                    @include('partials.finder.icon', ['name' => 'log-in', 'size' => 20])
                    <span>{{ __('login_page.submit') }}</span>
                </button>
            </form>

            <div class="lg-or" role="separator"><span>{{ __('login_page.or') }}</span></div>

            {{-- "Sign in with OPESCARD" (button + scanner) is a separate partial. --}}
            @includeIf('auth.partials.opescard-scan')

            <p class="lg-alt">{{ __('login_page.no_account') }}
                <a href="{{ route('register') }}" class="lg-link">{{ __('login_page.create_account') }}</a>
            </p>

            {{-- The Android build, offered only when this host actually holds it. --}}
            @php $apkPath = public_path('downloads/opescare-finder.apk'); @endphp
            @if(is_file($apkPath))
            <div class="f-auth-app lg-app">
                <a class="f-auth-app__btn" href="{{ url('/downloads/opescare-finder.apk') }}"
                   download="OpesCare.apk" rel="nofollow">
                    @include('partials.finder.icon', ['name' => 'download', 'size' => 18])
                    <span>{{ __('onboarding.login.download_app') }}</span>
                </a>
                <p class="f-auth-app__meta">
                    {{ __('onboarding.login.download_app_meta', ['size' => number_format(filesize($apkPath) / 1048576, 0)]) }}
                </p>
            </div>
            @endif

            <ul class="lg-badges" aria-label="{{ __('login_page.badges_label') }}">
                <li><span class="lg-badge__ring">@include('partials.finder.icon', ['name' => 'id-card', 'size' => 22])</span><span>{{ __('login_page.badge_health_id') }}</span></li>
                <li><span class="lg-badge__ring">@include('partials.finder.icon', ['name' => 'lock-keyhole', 'size' => 22])</span><span>{{ __('login_page.badge_encrypted') }}</span></li>
                <li><span class="lg-badge__ring">@include('partials.finder.icon', ['name' => 'file-check', 'size' => 22])</span><span>{{ __('login_page.badge_consent') }}</span></li>
                <li><span class="lg-badge__ring">@include('partials.finder.icon', ['name' => 'languages', 'size' => 22])</span><span>{{ __('login_page.badge_bilingual') }}</span></li>
            </ul>
        </section>
    </div>

    {{-- Demo one-click sign-in lives in its own partial and is never part of the
         default page: only a non-production host with OPESCARE_DEMO_MODE=true
         renders it. Production cannot, whatever the config says. --}}
    @if(config('demo.enabled') && ! app()->isProduction())
        @include('auth.partials.demo-panel')
    @endif

    <footer class="lg-footer">
        <p>{{ __('login_page.copyright', ['year' => 2026]) }}</p>
        <nav aria-label="{{ __('login_page.footer_label') }}">
            <a href="{{ route('public.privacy') }}">{{ __('login_page.privacy') }}</a>
            <span aria-hidden="true">|</span>
            <a href="{{ route('public.terms') }}">{{ __('login_page.terms') }}</a>
            <span aria-hidden="true">|</span>
            <a href="{{ Route::has('public.support') ? route('public.support') : route('public.contact') }}">{{ __('login_page.support') }}</a>
        </nav>
    </footer>
</div>
@endsection

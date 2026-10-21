{{-- Shared header of the home page and the explainer pages (landing-home.css). --}}
@php
    $hLocale   = $hLocale ?? app()->getLocale();
    $hLogo     = $hLogo ?? asset('brand/landing/opescare-logo-176.webp');
    $hHealthId = $hHealthId ?? route('public.solutions.patients');
    $hIcon     = $hIcon ?? fn (string $name, int $size = 20) => view('partials.finder.icon', ['name' => $name, 'size' => $size])->render();
@endphp
<header class="hm-header">
    <div class="hm-wrap hm-header__row">
        <a class="hm-logo" href="{{ route('public.landing') }}" aria-label="{{ __('home.nav.home') }}">
            <img src="{{ $hLogo }}" alt="" width="32" height="30">OpesCare
        </a>
        <nav class="hm-nav" aria-label="{{ __('home.nav.label') }}">
            <a href="{{ route('public.care-map') }}">{{ __('home.nav.find_care') }}</a>
            <a href="{{ $hHealthId }}">{{ __('home.nav.health_id') }}</a>
            <a href="{{ route('public.solutions.hospitals') }}">{{ __('home.nav.facilities') }}</a>
            <a href="{{ route('public.solutions.index') }}">{{ __('home.nav.solutions') }}</a>
            <a href="{{ route('blog.index') }}">{{ __('home.nav.blog') }}</a>
        </nav>
        <div class="hm-header__r">
            <div class="hm-lang" role="group" aria-label="{{ __('home.nav.language') }}">
                <a href="{{ route('lang.switch', 'en') }}" lang="en" hreflang="en" @if($hLocale === 'en') aria-current="true" @endif>EN</a>
                <span aria-hidden="true">·</span>
                <a href="{{ route('lang.switch', 'fr') }}" lang="fr" hreflang="fr" @if($hLocale === 'fr') aria-current="true" @endif>FR</a>
            </div>
            <a class="hm-btn hm-btn--sm hm-hide-md" href="{{ route('login') }}">{{ __('home.nav.sign_in') }}</a>
            <a class="hm-btn hm-btn--sm hm-btn--primary hm-hide-sm" href="{{ route('register.patient') }}">{{ __('home.nav.get_id') }}</a>
            <details class="hm-menu">
                <summary aria-label="{{ __('home.nav.menu') }}">{!! $hIcon('menu', 22) !!}</summary>
                <nav class="hm-menu__panel" aria-label="{{ __('home.nav.label') }}">
                    <a href="{{ route('public.care-map') }}">{{ __('home.nav.find_care') }}</a>
                    <a href="{{ $hHealthId }}">{{ __('home.nav.health_id') }}</a>
                    <a href="{{ route('public.solutions.hospitals') }}">{{ __('home.nav.facilities') }}</a>
                    <a href="{{ route('public.solutions.index') }}">{{ __('home.nav.solutions') }}</a>
                    <a href="{{ route('public.interoperability') }}">{{ __('home.nav.interop') }}</a>
                    <a href="{{ route('public.master-patient-index') }}">{{ __('home.nav.mpi') }}</a>
                    <a href="{{ route('blog.index') }}">{{ __('home.nav.blog') }}</a>
                    <a href="{{ route('public.download') }}">{{ __('home.nav.app') }}</a>
                    <a href="{{ route('login') }}">{{ __('home.nav.sign_in') }}</a>
                    <a class="hm-btn hm-btn--primary" href="{{ route('register.patient') }}">{{ __('home.nav.get_id') }}</a>
                </nav>
            </details>
        </div>
    </div>
</header>

{{--
    OpesCare — shared public shell (mobile-first).

    Every redesigned public page extends THIS layout:

        @extends('layouts.finder')
        @section('title', __('...'))            → <title>{title} — OpesCare</title>
        @section('description', __('...'))      → meta description
        @section('content') ... @endsection
        @push('head')    extra <head> tags (per-page CSS, preloads)
        @push('schema')  JSON-LD, rendered just before </head>
        @push('scripts') per-page JS, rendered just before </body>

    Styling: public/assets/finder.css only (tokens + .f-* components) — no
    framework, no webfonts, no emoji; icons via partials/finder/icon.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    {{-- A page with its own SEO head (e.g. facility profile) yields 'seo_head'
         and owns <title>, description, canonical and hreflang itself. --}}
    @hasSection('seo_head')
    @yield('seo_head')
    @else
    @hasSection('title')<title>{{ \App\Support\FinderSeo::branded(\App\Support\FinderSeo::plain(trim($__env->yieldContent('title')))) }}</title>@else<title>{{ __('finder_shell.meta.default_title') }}</title>@endif
    <meta name="description" content="@yield('description', __('finder_shell.meta.default_description'))">
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Favicons + theme-color #0F4C81 --}}
    @include('partials.favicons')
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <link rel="stylesheet" href="{{ asset('assets/finder.css') }}?v={{ @filemtime(public_path('assets/finder.css')) ?: '1' }}">

    {{-- Canonical, hreflang, OG/Twitter cards, geo.region + site JSON-LD (SEO layer) --}}
    @sectionMissing('seo_head')
    @include('partials.finder.schema.site')
    @endif
    @includeIf('partials.seo.entity-schema')

    {{-- Landing-page language for the public + auth shells (Inter, #EEF3F9, one accent, flat cards). Loads last. --}}
    <link rel="stylesheet" href="{{ asset('css/od-public.css') }}?v={{ @filemtime(public_path('css/od-public.css')) ?: '1' }}">
    @stack('head')
    @stack('schema')
</head>
<body>

    <a class="f-skip" href="#f-main">{{ __('finder_shell.a11y.skip_to_content') }}</a>

    {{-- Mobile nav state (pure CSS; a sibling of header + panel on purpose) --}}
    <input type="checkbox" id="f-nav-toggle" class="f-nav-toggle" aria-hidden="true" tabindex="-1">

    <header class="f-header">
        <div class="f-container f-header__row">
            <a href="{{ route('public.landing') }}" class="f-brand" aria-label="{{ __('finder_shell.a11y.home') }}">
                <x-brand-logo :size="22" class="f-brand__mark" />
                <span>OpesCare</span>
            </a>

            <nav class="f-nav" aria-label="{{ __('finder_shell.a11y.main_navigation') }}">
                <a href="{{ route('public.network.medicine-finder') }}">{{ __('finder_shell.nav.find_medicine') }}</a>
                <a href="{{ route('public.network.lab-test-finder') }}">{{ __('lab_manager.finder.title') }}</a>
                <a href="{{ route('public.network.imaging-finder') }}">{{ __('imaging_finder.title') }}</a>
                <a href="{{ route('public.network.blood-finder') }}">{{ __('finder_shell.nav.find_blood') }}</a>
                <a href="{{ route('portals.patient.appointments.book') }}">{{ __('finder_shell.nav.book_appointment') }}</a>
                <a href="{{ route('public.care-map') }}">{{ __('finder_shell.nav.directory') }}</a>
                <a href="{{ route('public.solutions.pharmacies') }}">{{ __('finder_shell.nav.for_pharmacies') }}</a>
                <a href="{{ route('blog.index') }}" @if(request()->routeIs('blog.*')) aria-current="page" @endif>{{ __('blog.nav') }}</a>
            </nav>

            <div class="f-header__actions">
                <nav class="f-lang" aria-label="{{ __('finder_shell.a11y.language') }}">
                    <a href="{{ route('lang.switch', 'en') }}" @class(['is-active' => app()->getLocale() === 'en']) @if(app()->getLocale() === 'en') aria-current="true" @endif lang="en" hreflang="en">EN</a>
                    <a href="{{ route('lang.switch', 'fr') }}" @class(['is-active' => app()->getLocale() === 'fr']) @if(app()->getLocale() === 'fr') aria-current="true" @endif lang="fr" hreflang="fr">FR</a>
                </nav>

                <a href="{{ route('login') }}" class="f-btn f-btn--ghost f-nav__login">{{ __('finder_shell.nav.login') }}</a>

                <label for="f-nav-toggle" class="f-burger" role="button" tabindex="0"
                       aria-controls="f-nav-panel" aria-expanded="false"
                       aria-label="{{ __('finder_shell.a11y.open_menu') }}">
                    <span class="f-burger__open">@include('partials.finder.icon', ['name' => 'menu', 'size' => 24])</span>
                    <span class="f-burger__close">@include('partials.finder.icon', ['name' => 'x', 'size' => 24])</span>
                </label>
            </div>
        </div>
    </header>

    {{-- Full-screen mobile navigation (≤960px) --}}
    <nav class="f-nav-panel" id="f-nav-panel" aria-label="{{ __('finder_shell.a11y.main_navigation') }}">
        <a href="{{ route('public.network.medicine-finder') }}">
            <span class="f-nav-panel__label">@include('partials.finder.icon', ['name' => 'pill', 'size' => 22]){{ __('finder_shell.nav.find_medicine') }}</span>
            <span class="f-nav-panel__go">@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 20])</span>
        </a>
        <a href="{{ route('public.network.lab-test-finder') }}">
            <span class="f-nav-panel__label">@include('partials.finder.icon', ['name' => 'search', 'size' => 22]){{ __('lab_manager.finder.title') }}</span>
            <span class="f-nav-panel__go">@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 20])</span>
        </a>
        <a href="{{ route('public.network.imaging-finder') }}">
            <span class="f-nav-panel__label">@include('partials.finder.icon', ['name' => 'eye', 'size' => 22]){{ __('imaging_finder.title') }}</span>
            <span class="f-nav-panel__go">@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 20])</span>
        </a>
        <a href="{{ route('public.network.blood-finder') }}">
            <span class="f-nav-panel__label">@include('partials.finder.icon', ['name' => 'droplet', 'size' => 22]){{ __('finder_shell.nav.find_blood') }}</span>
            <span class="f-nav-panel__go">@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 20])</span>
        </a>
        <a href="{{ route('portals.patient.appointments.book') }}">
            <span class="f-nav-panel__label">@include('partials.finder.icon', ['name' => 'calendar', 'size' => 22]){{ __('finder_shell.nav.book_appointment') }}</span>
            <span class="f-nav-panel__go">@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 20])</span>
        </a>
        <a href="{{ route('public.care-map') }}">
            <span class="f-nav-panel__label">@include('partials.finder.icon', ['name' => 'map-pin', 'size' => 22]){{ __('finder_shell.nav.directory') }}</span>
            <span class="f-nav-panel__go">@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 20])</span>
        </a>
        <a href="{{ route('public.solutions.pharmacies') }}">
            <span class="f-nav-panel__label">@include('partials.finder.icon', ['name' => 'building-2', 'size' => 22]){{ __('finder_shell.nav.for_pharmacies') }}</span>
            <span class="f-nav-panel__go">@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 20])</span>
        </a>
        <a href="{{ route('blog.index') }}">
            <span class="f-nav-panel__label">@include('partials.finder.icon', ['name' => 'newspaper', 'size' => 22]){{ __('blog.nav') }}</span>
            <span class="f-nav-panel__go">@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 20])</span>
        </a>
        <div class="f-nav-panel__cta">
            <a href="{{ route('login') }}" class="f-btn f-btn--primary f-btn--block">
                @include('partials.finder.icon', ['name' => 'log-in', 'size' => 18]){{ __('finder_shell.nav.login') }}
            </a>
        </div>
    </nav>

    <main id="f-main" class="f-main">
        @yield('content')
    </main>

    <footer class="f-footer" aria-label="{{ __('finder_shell.a11y.footer_navigation') }}">
        <div class="f-container">
            <div class="f-footer__grid">
                <div>
                    <a href="{{ route('public.landing') }}" class="f-brand" aria-label="{{ __('finder_shell.a11y.home') }}">
                        <x-brand-logo :size="20" class="f-brand__mark" />
                        <span>OpesCare</span>
                    </a>
                    <p class="f-footer__tagline">{{ __('finder_shell.brand.tagline') }}</p>
                </div>

                <nav aria-label="{{ __('finder_shell.footer.col_services') }}">
                    <h2 class="f-footer__title">{{ __('finder_shell.footer.col_services') }}</h2>
                    <ul>
                        <li><a class="f-footer__link" href="{{ route('public.network.medicine-finder') }}">{{ __('finder_shell.nav.find_medicine') }}</a></li>
                        <li><a class="f-footer__link" href="{{ route('public.network.lab-test-finder') }}">{{ __('lab_manager.finder.title') }}</a></li>
                        <li><a class="f-footer__link" href="{{ route('public.network.imaging-finder') }}">{{ __('imaging_finder.title') }}</a></li>
                        <li><a class="f-footer__link" href="{{ route('public.network.blood-finder') }}">{{ __('finder_shell.nav.find_blood') }}</a></li>
                        <li><a class="f-footer__link" href="{{ route('portals.patient.appointments.book') }}">{{ __('finder_shell.nav.book_appointment') }}</a></li>
                        <li><a class="f-footer__link" href="{{ route('public.care-map') }}">{{ __('finder_shell.nav.directory') }}</a></li>
                        <li><a class="f-footer__link" href="{{ route('public.care-map.emergency') }}">{{ __('finder_shell.footer.emergency') }}</a></li>
                        <li><a class="f-footer__link" href="{{ route('public.download') }}">{{ __('finder_shell.footer.app') }}</a></li>
                    </ul>
                </nav>

                <nav aria-label="{{ __('finder_shell.footer.col_facilities') }}">
                    <h2 class="f-footer__title">{{ __('finder_shell.footer.col_facilities') }}</h2>
                    <ul>
                        <li><a class="f-footer__link" href="{{ route('public.solutions.pharmacies') }}">{{ __('finder_shell.nav.for_pharmacies') }}</a></li>
                        <li><a class="f-footer__link" href="{{ route('public.solutions.hospitals') }}">{{ __('finder_shell.footer.for_hospitals') }}</a></li>
                        <li><a class="f-footer__link" href="{{ route('public.interoperability') }}">{{ __('finder_shell.footer.interop') }}</a></li>
                        <li><a class="f-footer__link" href="{{ route('public.master-patient-index') }}">{{ __('finder_shell.footer.mpi') }}</a></li>
                        <li><a class="f-footer__link" href="{{ route('login') }}">{{ __('finder_shell.nav.login') }}</a></li>
                    </ul>
                </nav>

                <nav aria-label="{{ __('finder_shell.footer.col_legal') }}">
                    <h2 class="f-footer__title">{{ __('finder_shell.footer.col_legal') }}</h2>
                    <ul>
                        <li><a class="f-footer__link" href="{{ route('public.terms') }}">{{ __('finder_shell.footer.terms') }}</a></li>
                        <li><a class="f-footer__link" href="{{ route('public.privacy') }}">{{ __('finder_shell.footer.privacy') }}</a></li>
                        <li><a class="f-footer__link" href="{{ route('public.help') }}">{{ __('finder_shell.footer.help') }}</a></li>
                        <li><a class="f-footer__link" href="{{ route('public.faq') }}">{{ __('landing.footer.link_faq') }}</a></li>
                        <li><a class="f-footer__link" href="{{ route('blog.index') }}">{{ __('blog.nav') }}</a></li>
                        @includeIf('partials.seo.footer-facts-link')
                        <li><a class="f-footer__link" href="{{ route('public.contact') }}">{{ __('finder_shell.footer.contact') }}</a></li>
                    </ul>
                </nav>
            </div>

            <div class="f-footer__meta">
                <p>{{ __('finder_shell.footer.attribution') }}</p>
                <p>{{ __('finder_shell.footer.disclaimer') }}</p>
                <div class="f-footer__meta-row">
                    <p>{{ __('finder_shell.footer.copyright', ['year' => date('Y')]) }}</p>
                    <p>
                        <a class="f-footer__link" href="{{ route('lang.switch', 'en') }}" lang="en" hreflang="en">English</a>
                        &nbsp;·&nbsp;
                        <a class="f-footer__link" href="{{ route('lang.switch', 'fr') }}" lang="fr" hreflang="fr">Français</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    {{-- Progressive enhancement only: keyboard + aria for the CSS-only menu.
         The menu itself works with JS disabled. --}}
    <script @cspNonce>
    (function () {
        var t = document.getElementById('f-nav-toggle');
        var b = document.querySelector('.f-burger');
        if (!t || !b) return;
        var openLabel = @json(__('finder_shell.a11y.open_menu'));
        var closeLabel = @json(__('finder_shell.a11y.close_menu'));
        function sync() {
            b.setAttribute('aria-expanded', t.checked ? 'true' : 'false');
            b.setAttribute('aria-label', t.checked ? closeLabel : openLabel);
        }
        t.addEventListener('change', sync);
        b.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); t.checked = !t.checked; sync(); }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && t.checked) { t.checked = false; sync(); }
        });
    })();
    </script>

    <div id="f-loading-status" class="f-loading-status" role="status" aria-live="polite"
         data-text="{{ __('finder_shell.a11y.searching') }}"></div>

    <script src="{{ asset('js/od-tables.js') }}?v={{ @filemtime(public_path('js/od-tables.js')) ?: '1' }}" defer></script>
    <script src="{{ asset('js/finder-loading.js') }}?v={{ @filemtime(public_path('js/finder-loading.js')) ?: '1' }}" defer></script>
    <script src="{{ asset('js/op-actions.js') }}?v={{ @filemtime(public_path('js/op-actions.js')) ?: '1' }}" defer></script>
    @stack('scripts')
</body>
</html>

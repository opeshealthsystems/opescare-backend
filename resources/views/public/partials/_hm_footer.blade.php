{{-- Shared footer of the home page and the explainer pages (landing-home.css). --}}
@php
    $hLocale   = $hLocale ?? app()->getLocale();
    $hLogo     = $hLogo ?? asset('brand/landing/opescare-logo-176.webp');
    $hHealthId = $hHealthId ?? route('public.health-id');
    $hIcon     = $hIcon ?? fn (string $name, int $size = 20) => view('partials.finder.icon', ['name' => $name, 'size' => $size])->render();
@endphp
<footer class="hm-footer">
    <div class="hm-wrap">
        <div class="hm-footer__cols">
            <div>
                <a class="hm-logo hm-logo--light" href="{{ route('public.landing') }}" aria-label="{{ __('home.nav.home') }}">
                    <img src="{{ $hLogo }}" alt="" width="32" height="30" loading="lazy">OpesCare
                </a>
                <p>{{ __('home.footer.tagline') }}</p>
            </div>
            <nav aria-labelledby="hm-f1">
                <h2 id="hm-f1">{{ __('home.footer.find_care') }}</h2>
                <ul role="list">
                    <li><a href="{{ route('public.care-map') }}">{{ __('home.footer.facilities_link') }}</a></li>
                    <li><a href="{{ route('public.network.medicine-finder') }}">{{ __('home.footer.medicines') }}</a></li>
                    <li><a href="{{ route('public.network.blood-finder') }}">{{ __('home.footer.blood') }}</a></li>
                    <li><a href="{{ route('public.health-id') }}">{{ __('home.footer.health_id') }}</a></li>
                    <li><a href="{{ route('public.e-prescription') }}">{{ __('home.footer.eprescription') }}</a></li>
                    <li><a href="{{ route('public.care-map.places') }}">{{ __('home.footer.places') }}</a></li>
                    <li><a href="{{ route('public.care-map.emergency') }}">{{ __('landing.v2.footer.emergency') }}</a></li>
                    <li><a href="{{ route('public.download') }}">{{ __('home.footer.app') }}</a></li>
                </ul>
            </nav>
            <nav aria-labelledby="hm-f2">
                <h2 id="hm-f2">{{ __('home.footer.facilities') }}</h2>
                <ul role="list">
                    <li><a href="{{ route('register.organization') }}">{{ __('home.footer.register') }}</a></li>
                    <li><a href="{{ route('public.care-map') }}">{{ __('home.footer.claim') }}</a></li>
                    <li><a href="{{ route('public.solutions.index') }}">{{ __('home.footer.solutions') }}</a></li>
                    <li><a href="{{ route('public.interoperability') }}">{{ __('home.footer.interop') }}</a></li>
                    <li><a href="{{ route('public.master-patient-index') }}">{{ __('home.footer.mpi') }}</a></li>
                    <li><a href="{{ route('public.solutions.request', ['kind' => 'donation']) }}">{{ __('home.footer.free_systems') }}</a></li>
                </ul>
            </nav>
            <nav aria-labelledby="hm-f3">
                <h2 id="hm-f3">{{ __('home.footer.company') }}</h2>
                <ul role="list">
                    <li><a href="{{ route('public.about') }}">{{ __('home.footer.about') }}</a></li>
                    {{-- The shared partials.seo.footer-facts-link adds facts + sitemap + solutions in one
                         list; here each lives in its own column instead (facts: Company, sitemap:
                         Legal, solutions: Facilities), so nothing is listed twice. --}}
                    <li><a href="{{ route('public.facts') }}">{{ __('facts.footer_link') }}</a></li>
                    <li><a href="{{ route('blog.index') }}">{{ __('home.footer.blog') }}</a></li>
                    <li><a href="{{ route('public.developers') }}">{{ __('home.footer.developers') }}</a></li>
                    <li><a href="{{ route('public.faq') }}">{{ __('landing.footer.link_faq') }}</a></li>
                    <li><a href="{{ route('public.contact') }}">{{ __('home.footer.contact') }}</a></li>
                </ul>
            </nav>
            <nav aria-labelledby="hm-f4">
                <h2 id="hm-f4">{{ __('home.footer.legal') }}</h2>
                <ul role="list">
                    <li><a href="{{ route('public.privacy') }}">{{ __('home.footer.privacy') }}</a></li>
                    <li><a href="{{ route('public.terms') }}">{{ __('home.footer.terms') }}</a></li>
                    <li><a href="{{ route('public.consent') }}">{{ __('landing.v2.footer.consent') }}</a></li>
                    <li><a href="{{ route('public.security') }}">{{ __('landing.v2.footer.security') }}</a></li>
                    <li><a href="{{ route('public.sitemap') }}">{{ __('home.footer.sitemap') }}</a></li>
                </ul>
            </nav>
        </div>
        <div class="hm-footer__bottom">
            <p>{{ __('home.footer.copyright', ['year' => date('Y')]) }}</p>
            <p>
                <a href="{{ route('lang.switch', 'en') }}" lang="en" hreflang="en">English</a>
                <span aria-hidden="true">·</span>
                <a href="{{ route('lang.switch', 'fr') }}" lang="fr" hreflang="fr">Français</a>
            </p>
        </div>
    </div>
</footer>

{{--
    LEGACY platform-marketing shell (landing.css/public.css, Inter, lucide.js).

    The Finder redesign uses layouts.finder + public/assets/finder.css instead.
    This layout is deliberately left intact so unmigrated pages keep rendering;
    do NOT extend it for new or redesigned pages — extend layouts.finder.
    Delete this file once the last page has migrated.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'OpesCare | One Health ID. One Trusted Medical History.')</title>
    <meta name="description" content="@yield('meta_description', 'OpesCare is a digital Health ID and healthcare interoperability platform built to connect patients, hospitals, labs, pharmacies, and insurers.')">
    <meta name="theme-color" content="#0F4C81">

    <!-- Favicon -->
    @include('partials.favicons')
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    {{-- Canonical, hreflang and schema.org — see partials/seo_head --}}
    @include('partials.seo_head')

    <!-- Fonts -->
    <link rel="stylesheet" href="{{ asset('css/od-fonts.css') }}">
    
    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    <link rel="stylesheet" href="{{ asset('css/public.css') }}">
    <link rel="stylesheet" href="{{ asset('css/od-public.css') }}?v={{ @filemtime(public_path('css/od-public.css')) ?: '1' }}">
    
    <!-- Lucide Icons -->
    <script src="{{ asset('js/lucide.min.js') }}"></script>
<script src="{{ asset('js/op-actions.js') }}?v={{ @filemtime(public_path('js/op-actions.js')) ?: '1' }}" defer></script>
    <style>
        .nav-icon{width:.875rem;height:.875rem;margin-right:.5rem;vertical-align:middle;flex-shrink:0}
        .flex-shrink-0{flex-shrink:0}
    </style>
    @yield('head_scripts')
</head>
<body>

    <!-- Header / Navigation -->
    <header class="header">
        <div class="container header-inner">
            <a href="/" class="logo" style="display:flex;align-items:center;gap:0.5rem;text-decoration:none;">
                <x-brand-logo :size="28" />
                <span>OpesCare</span>
            </a>

            {{--
                Five groups, mirroring how the product actually divides:
                CORE (Platform) · NETWORK · CONNECT (Interoperability) ·
                who it is for (Solutions) · and the developer surface.
            --}}
            <nav class="nav">
                <div class="nav-dropdown">
                    <a href="{{ route('public.how-it-works') }}" class="nav-link dropdown-trigger">{{ __('landing.nav.platform', [], app()->getLocale()) }} <i data-lucide="chevron-down" class="icon-xs"></i></a>
                    <div class="dropdown-menu">
                        <a href="{{ route('public.solutions.patients') }}" class="dropdown-item"><i data-lucide="id-card" class="nav-icon"></i>{{ __('landing.nav.health_id', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.how-it-works') }}" class="dropdown-item"><i data-lucide="git-branch-plus" class="nav-icon"></i>{{ __('landing.nav.how_it_works_link', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.solutions.patients') }}#timeline" class="dropdown-item"><i data-lucide="history" class="nav-icon"></i>{{ __('landing.nav.health_record', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.consent') }}" class="dropdown-item"><i data-lucide="shield-check" class="nav-icon"></i>{{ __('landing.nav.trust_access', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.care-map.emergency') }}" class="dropdown-item"><i data-lucide="siren" class="nav-icon"></i>{{ __('landing.nav.emergency_access', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.security') }}" class="dropdown-item"><i data-lucide="lock-keyhole" class="nav-icon"></i>{{ __('landing.nav.security_page', [], app()->getLocale()) }}</a>
                    </div>
                </div>
                <div class="nav-dropdown">
                    <a href="{{ route('public.care-map') }}" class="nav-link dropdown-trigger">{{ __('landing.nav.network', [], app()->getLocale()) }} <i data-lucide="chevron-down" class="icon-xs"></i></a>
                    <div class="dropdown-menu">
                        <a href="{{ route('public.network.medicine-finder') }}" class="dropdown-item"><i data-lucide="map-pin" class="nav-icon"></i>{{ __('landing.nav.medicine_finder', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.network.blood-finder') }}" class="dropdown-item"><i data-lucide="droplet" class="nav-icon"></i>{{ __('landing.nav.blood_finder', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.care-map') }}" class="dropdown-item"><i data-lucide="hospital" class="nav-icon"></i>{{ __('landing.nav.connected_facilities', [], app()->getLocale()) }}</a>
                    </div>
                </div>
                <div class="nav-dropdown">
                    <a href="{{ route('public.interoperability') }}" class="nav-link dropdown-trigger">{{ __('landing.nav.interop', [], app()->getLocale()) }} <i data-lucide="chevron-down" class="icon-xs"></i></a>
                    <div class="dropdown-menu">
                        <a href="{{ route('public.interoperability') }}" class="dropdown-item"><i data-lucide="network" class="nav-icon"></i>{{ __('landing.nav.interop_overview', [], app()->getLocale()) }}</a>
                        <a href="{{ route('docs.api') }}" class="dropdown-item"><i data-lucide="braces" class="nav-icon"></i>Connect API</a>
                        <a href="{{ route('docs.sdk') }}" class="dropdown-item"><i data-lucide="code-2" class="nav-icon"></i>SDK</a>
                        <a href="{{ route('docs.widget') }}" class="dropdown-item"><i data-lucide="panel-top" class="nav-icon"></i>Widget</a>
                        <a href="{{ route('docs.bridge') }}" class="dropdown-item"><i data-lucide="cpu" class="nav-icon"></i>Bridge Agent</a>
                        <a href="{{ route('docs.webhooks') }}" class="dropdown-item"><i data-lucide="radio-tower" class="nav-icon"></i>Webhooks</a>
                    </div>
                </div>
                <div class="nav-dropdown">
                    <a href="{{ route('public.solutions.patients') }}" class="nav-link dropdown-trigger">{{ __('landing.nav.solutions', [], app()->getLocale()) }} <i data-lucide="chevron-down" class="icon-xs"></i></a>
                    <div class="dropdown-menu">
                        <a href="{{ route('public.solutions.patients') }}" class="dropdown-item"><i data-lucide="user" class="nav-icon"></i>{{ __('landing.nav.for_patients', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.solutions.hospitals') }}" class="dropdown-item"><i data-lucide="hospital" class="nav-icon"></i>{{ __('landing.nav.for_hospitals', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.solutions.laboratories') }}" class="dropdown-item"><i data-lucide="flask-conical" class="nav-icon"></i>{{ __('landing.nav.for_labs', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.solutions.pharmacies') }}" class="dropdown-item"><i data-lucide="pill" class="nav-icon"></i>{{ __('landing.nav.for_pharmacies', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.solutions.insurers') }}" class="dropdown-item"><i data-lucide="heart-handshake" class="nav-icon"></i>{{ __('landing.nav.for_insurers', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.solutions.public-health') }}" class="dropdown-item"><i data-lucide="landmark" class="nav-icon"></i>{{ __('landing.nav.for_public_health', [], app()->getLocale()) }}</a>
                    </div>
                </div>
                <div class="nav-dropdown">
                    <a href="{{ route('public.developers') }}" class="nav-link dropdown-trigger">{{ __('landing.nav.developers', [], app()->getLocale()) }} <i data-lucide="chevron-down" class="icon-xs"></i></a>
                    <div class="dropdown-menu">
                        <a href="{{ route('docs.index') }}" class="dropdown-item"><i data-lucide="book-open" class="nav-icon"></i>{{ __('ui_sweep.docs_nav.developer_docs') }}</a>
                        <a href="{{ route('docs.playground') }}" class="dropdown-item"><i data-lucide="play-circle" class="nav-icon"></i>{{ __('ui_sweep.docs_nav.api_playground') }}</a>
                        <a href="{{ route('public.pricing') }}" class="dropdown-item"><i data-lucide="receipt" class="nav-icon"></i>{{ __('pricing.nav_label', [], app()->getLocale()) }}</a>
                    </div>
                </div>
                <div class="nav-dropdown">
                    <a href="{{ route('public.help') }}" class="nav-link dropdown-trigger">{{ __('landing.nav.resources', [], app()->getLocale()) }} <i data-lucide="chevron-down" class="icon-xs"></i></a>
                    <div class="dropdown-menu">
                        <a href="{{ route('public.faq') }}" class="dropdown-item"><i data-lucide="help-circle" class="nav-icon"></i>{{ __('landing.nav.faq', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.help') }}" class="dropdown-item"><i data-lucide="book-open" class="nav-icon"></i>{{ __('landing.nav.help_center', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.status') }}" class="dropdown-item"><i data-lucide="activity" class="nav-icon"></i>{{ __('landing.nav.system_status', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.request-demo') }}" class="dropdown-item"><i data-lucide="presentation" class="nav-icon"></i>{{ __('leads.demo.nav_label', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.contact') }}" class="dropdown-item"><i data-lucide="headset" class="nav-icon"></i>{{ __('landing.nav.contact_support', [], app()->getLocale()) }}</a>
                    </div>
                </div>
            </nav>
            
            <div class="header-actions">
                <div class="lang-switcher header-lang-switcher" style="display:flex;gap:0.5rem;margin-right:1.5rem;font-size:0.75rem;font-weight:700;">
                    <a href="{{ route('lang.switch', 'en') }}" style="color:{{ app()->getLocale()=='en' ? 'var(--color-primary)' : 'var(--color-text-secondary)' }};text-decoration:none;">EN</a>
                    <span style="color:var(--color-border);">|</span>
                    <a href="{{ route('lang.switch', 'fr') }}" style="color:{{ app()->getLocale()=='fr' ? 'var(--color-primary)' : 'var(--color-text-secondary)' }};text-decoration:none;">FR</a>
                </div>

                <div class="header-desktop-cta" style="display:flex;align-items:center;gap:1rem;">
                    <a href="{{ route('login') }}" style="font-size:0.875rem;font-weight:700;color:var(--color-text-secondary);text-decoration:none;">{{ __('auth.login.title') }}</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">{{ __('landing.nav.get_started', [], app()->getLocale()) }}</a>
                </div>

                <button class="mobile-menu-toggle" id="menuToggle" style="display: none; background: none; border: none; color: var(--color-text-primary); cursor: pointer; margin-left: 1rem;">
                    <i data-lucide="menu"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Drawer Backdrop -->
        <div class="mobile-drawer-backdrop" id="drawerBackdrop"></div>
        <!-- Mobile Drawer -->
        <div class="mobile-drawer" id="mobileDrawer">
            <div class="container">
                <div class="mobile-drawer-header">
                    <span class="logo" style="display:flex;align-items:center;gap:0.5rem;">
                        <x-brand-logo :size="24" />
                        <span>OpesCare</span>
                    </span>
                    <button id="closeMenu" style="background: none; border: none; color: var(--color-text-primary);">
                        <i data-lucide="x"></i>
                    </button>
                </div>
                <nav class="mobile-nav">
                    <div class="mobile-nav-group">
                        <span class="mobile-nav-label">{{ __('landing.nav.platform', [], app()->getLocale()) }}</span>
                        <a href="{{ route('public.solutions.patients') }}" class="mobile-nav-link">{{ __('landing.nav.health_id', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.how-it-works') }}" class="mobile-nav-link">{{ __('landing.nav.how_it_works_link', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.solutions.patients') }}#timeline" class="mobile-nav-link">{{ __('landing.nav.health_record', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.consent') }}" class="mobile-nav-link">{{ __('landing.nav.trust_access', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.care-map.emergency') }}" class="mobile-nav-link">{{ __('landing.nav.emergency_access', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.security') }}" class="mobile-nav-link">{{ __('landing.nav.security_page', [], app()->getLocale()) }}</a>
                    </div>
                    <div class="mobile-nav-group">
                        <span class="mobile-nav-label">{{ __('landing.nav.network', [], app()->getLocale()) }}</span>
                        <a href="{{ route('public.network.medicine-finder') }}" class="mobile-nav-link">{{ __('landing.nav.medicine_finder', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.network.blood-finder') }}" class="mobile-nav-link">{{ __('landing.nav.blood_finder', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.care-map') }}" class="mobile-nav-link">{{ __('landing.nav.connected_facilities', [], app()->getLocale()) }}</a>
                    </div>
                    <div class="mobile-nav-group">
                        <span class="mobile-nav-label">{{ __('landing.nav.interop', [], app()->getLocale()) }}</span>
                        <a href="{{ route('public.interoperability') }}" class="mobile-nav-link">{{ __('landing.nav.interop_overview', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.developers') }}" class="mobile-nav-link">{{ __('landing.nav.api_sdk', [], app()->getLocale()) }}</a>
                    </div>
                    <div class="mobile-nav-group">
                        <span class="mobile-nav-label">{{ __('landing.nav.solutions', [], app()->getLocale()) }}</span>
                        <a href="{{ route('public.solutions.patients') }}" class="mobile-nav-link">{{ __('landing.nav.for_patients', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.solutions.hospitals') }}" class="mobile-nav-link">{{ __('landing.nav.for_hospitals', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.solutions.laboratories') }}" class="mobile-nav-link">{{ __('landing.nav.for_labs', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.solutions.pharmacies') }}" class="mobile-nav-link">{{ __('landing.nav.for_pharmacies', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.solutions.insurers') }}" class="mobile-nav-link">{{ __('landing.nav.for_insurers', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.solutions.public-health') }}" class="mobile-nav-link">{{ __('landing.nav.for_public_health', [], app()->getLocale()) }}</a>
                    </div>
                    <div class="mobile-nav-group">
                        <span class="mobile-nav-label">{{ __('landing.nav.resources', [], app()->getLocale()) }}</span>
                        <a href="{{ route('public.faq') }}" class="mobile-nav-link">{{ __('landing.nav.faq', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.help') }}" class="mobile-nav-link">{{ __('landing.nav.help_center', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.status') }}" class="mobile-nav-link">{{ __('landing.nav.system_status', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.request-demo') }}" class="mobile-nav-link">{{ __('leads.demo.nav_label', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.contact') }}" class="mobile-nav-link">{{ __('landing.nav.contact_support', [], app()->getLocale()) }}</a>
                    </div>
                    <div class="mobile-nav-group">
                        <span class="mobile-nav-label">{{ __('landing.nav.company', [], app()->getLocale()) }}</span>
                        <a href="{{ route('public.about') }}" class="mobile-nav-link">{{ __('landing.nav.about', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.security') }}" class="mobile-nav-link">{{ __('landing.nav.security_page', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.privacy') }}" class="mobile-nav-link">{{ __('landing.nav.privacy', [], app()->getLocale()) }}</a>
                        <a href="{{ route('public.terms') }}" class="mobile-nav-link">{{ __('landing.nav.terms', [], app()->getLocale()) }}</a>
                    </div>

                    <div class="mobile-drawer-cta">
                        <a href="{{ route('login') }}" class="btn btn-secondary mobile-drawer-btn">{{ __('auth.login.title', [], app()->getLocale()) }}</a>
                        <a href="{{ route('register') }}" class="btn btn-primary mobile-drawer-btn">{{ __('landing.nav.get_started', [], app()->getLocale()) }}</a>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-grid">

            <!-- Brand col -->
            <div class="footer-logo">
                <a href="{{ route('public.landing') }}" class="logo" style="display:flex;align-items:center;gap:0.5rem;text-decoration:none;">
                    <x-brand-logo :size="26" alt="OpesCare" />
                    <span>OpesCare</span>
                </a>
                <p class="text-muted text-sm" style="margin-top:1rem;line-height:1.6;">{{ __('landing.footer.desc', [], app()->getLocale()) }}</p>
                <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
                    <a href="{{ route('public.status') }}" style="font-size:0.75rem;font-weight:700;color:var(--color-text-secondary);text-decoration:none;display:inline-flex;align-items:center;gap:0.3rem;">
                        <span style="width:6px;height:6px;background:#22C55E;border-radius:50%;display:inline-block;"></span>
                        {{ __('landing.nav.system_status', [], app()->getLocale()) }}
                    </a>
                </div>
            </div>

            <!-- Product col -->
            <div>
                <h4 style="font-size:0.6875rem;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:var(--color-text-secondary);margin-bottom:1.25rem;">{{ __('landing.footer.col_product', [], app()->getLocale()) }}</h4>
                <ul style="list-style:none;padding:0;display:flex;flex-direction:column;gap:0.625rem;">
                    <li><a href="{{ route('public.how-it-works') }}" class="footer-link">{{ __('landing.footer.link_how_it_works', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.solutions.patients') }}" class="footer-link">{{ __('landing.footer.link_health_id', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.solutions.patients') }}#timeline" class="footer-link">{{ __('landing.footer.link_timeline', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.consent') }}" class="footer-link">{{ __('landing.footer.link_consent', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.care-map.emergency') }}" class="footer-link">{{ __('landing.footer.link_emergency', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.network.medicine-finder') }}" class="footer-link">{{ __('landing.footer.link_medication', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.network.blood-finder') }}" class="footer-link">{{ __('landing.footer.link_blood', [], app()->getLocale()) }}</a></li>
                </ul>
            </div>

            <!-- Organisations col -->
            <div>
                <h4 style="font-size:0.6875rem;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:var(--color-text-secondary);margin-bottom:1.25rem;">{{ __('landing.footer.col_orgs', [], app()->getLocale()) }}</h4>
                <ul style="list-style:none;padding:0;display:flex;flex-direction:column;gap:0.625rem;">
                    <li><a href="{{ route('public.solutions.hospitals') }}" class="footer-link">{{ __('landing.footer.link_hospitals', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.solutions.laboratories') }}" class="footer-link">{{ __('landing.footer.link_labs', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.solutions.pharmacies') }}" class="footer-link">{{ __('landing.footer.link_pharmacies', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.solutions.insurers') }}" class="footer-link">{{ __('landing.footer.link_insurers', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.solutions.public-health') }}" class="footer-link">{{ __('landing.footer.link_public_health', [], app()->getLocale()) }}</a></li>
                </ul>
            </div>

            <!-- Developers col -->
            <div>
                <h4 style="font-size:0.6875rem;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:var(--color-text-secondary);margin-bottom:1.25rem;">{{ __('landing.footer.col_devs', [], app()->getLocale()) }}</h4>
                <ul style="list-style:none;padding:0;display:flex;flex-direction:column;gap:0.625rem;">
                    <li><a href="{{ route('public.developers') }}" class="footer-link">{{ __('landing.footer.link_api', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.developers') }}#sdk" class="footer-link">{{ __('landing.footer.link_sdk', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.developers') }}#widget" class="footer-link">{{ __('landing.footer.link_widget', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.developers') }}#bridge" class="footer-link">{{ __('landing.footer.link_bridge', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.developers') }}#webhooks" class="footer-link">{{ __('landing.footer.link_webhooks', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.interoperability') }}" class="footer-link">{{ __('landing.footer.link_interop', [], app()->getLocale()) }}</a></li>
                </ul>
            </div>

            <!-- Company col -->
            <div>
                <h4 style="font-size:0.6875rem;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:var(--color-text-secondary);margin-bottom:1.25rem;">{{ __('landing.footer.col_company', [], app()->getLocale()) }}</h4>
                <ul style="list-style:none;padding:0;display:flex;flex-direction:column;gap:0.625rem;">
                    <li><a href="{{ route('public.about') }}" class="footer-link">{{ __('landing.footer.link_about', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.security') }}" class="footer-link">{{ __('landing.footer.link_security', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.privacy') }}" class="footer-link">{{ __('landing.footer.link_privacy', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.terms') }}" class="footer-link">{{ __('landing.footer.link_terms', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.faq') }}" class="footer-link">{{ __('landing.footer.link_faq', [], app()->getLocale()) }}</a></li>
                    <li><a href="{{ route('public.contact') }}" class="footer-link">{{ __('landing.footer.link_partnerships', [], app()->getLocale()) }}</a></li>
                </ul>
            </div>

        </div>

        <!-- Bottom bar -->
        <div class="container" style="margin-top:var(--space-xl);padding-top:var(--space-lg);border-top:1px solid var(--color-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <p style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--color-text-secondary);">{{ __('landing.footer.copyright', [], app()->getLocale()) }}</p>
            <div style="display:flex;gap:1.25rem;align-items:center;">
                <a href="{{ route('public.privacy') }}" style="font-size:0.75rem;color:var(--color-text-secondary);text-decoration:none;font-weight:600;">{{ __('landing.nav.privacy_short', [], app()->getLocale()) }}</a>
                <a href="{{ route('public.terms') }}" style="font-size:0.75rem;color:var(--color-text-secondary);text-decoration:none;font-weight:600;">{{ __('landing.nav.terms_short', [], app()->getLocale()) }}</a>
                <a href="{{ route('public.contact') }}" style="font-size:0.75rem;color:var(--color-text-secondary);text-decoration:none;font-weight:600;">{{ __('landing.nav.contact', [], app()->getLocale()) }}</a>
                <span style="font-size:0.75rem;color:var(--color-text-secondary);">
                    <a href="{{ route('lang.switch', 'en') }}" style="font-weight:700;color:{{ app()->getLocale()=='en' ? 'var(--color-primary)' : 'var(--color-text-secondary)' }};text-decoration:none;">EN</a>
                    &nbsp;/&nbsp;
                    <a href="{{ route('lang.switch', 'fr') }}" style="font-weight:700;color:{{ app()->getLocale()=='fr' ? 'var(--color-primary)' : 'var(--color-text-secondary)' }};text-decoration:none;">FR</a>
                </span>
            </div>
        </div>
    </footer>

    <!-- JS -->
    <script src="{{ asset('js/landing.js') }}"></script>
    @yield('footer_scripts')
</body>
</html>

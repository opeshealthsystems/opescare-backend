<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', __('docs_ui.meta_title')) — OpesCare</title>
    <meta name="description" content="@yield('meta_description', __('docs_ui.meta_description'))">
    <meta name="theme-color" content="#0F4C81">

    @include('partials.favicons')

    {{-- Self-hosted Inter (no third-party font host) --}}
    <link rel="stylesheet" href="{{ asset('css/od-fonts.css') }}">

    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <script src="{{ asset('js/op-actions.js') }}?v={{ @filemtime(public_path('js/op-actions.js')) ?: '1' }}" defer></script>

    {{-- Content styles (tables, callouts, code tabs) + the OpesCare docs shell and diagrams --}}
    <link rel="stylesheet" href="{{ asset('css/docs.css') }}?v={{ @filemtime(public_path('css/docs.css')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('css/docs-v2.css') }}?v={{ @filemtime(public_path('css/docs-v2.css')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('css/blog-diagrams.css') }}?v={{ @filemtime(public_path('css/blog-diagrams.css')) ?: '1' }}">

    @yield('head')
    {{-- Canonical, hreflang, Open Graph and schema.org --}}
    @include('partials.seo_head')
</head>
<body class="docs-body dx" data-copy-label="{{ __('docs_ui.copy') }}" data-copied-label="{{ __('docs_ui.copied') }}">

<a class="dx-skip" href="#docsContent">{{ __('docs_ui.skip_to_content') }}</a>

<header class="dx-header">
    <div class="dx-header-inner">
        <button class="dx-menu-toggle" id="docsMenuToggle" type="button" aria-controls="docsSidebar" aria-expanded="false" aria-label="{{ __('docs_ui.toggle_nav') }}">
            <i data-lucide="menu" class="dx-ico"></i>
        </button>
        <a href="{{ route('public.landing') }}" class="dx-brand" aria-label="OpesCare">
            <x-brand-logo :size="30" />
            <span class="dx-brand-word">OpesCare</span>
        </a>
        <a href="{{ route('docs.index') }}" class="dx-brand-tag">{{ __('docs_ui.developers') }}</a>

        <nav class="dx-top-links" aria-label="{{ __('docs_ui.top_nav') }}">
            <a href="{{ route('docs.index') }}" @class(['is-active' => request()->routeIs('docs.index')])>{{ __('docs_ui.nav.docs') }}</a>
            <a href="{{ route('docs.architecture') }}" @class(['is-active' => request()->routeIs('docs.architecture', 'docs.flows')])>{{ __('docs_ui.nav.architecture') }}</a>
            <a href="{{ route('docs.api') }}" @class(['is-active' => request()->routeIs('docs.api')])>{{ __('docs_ui.nav.api_reference') }}</a>
            <a href="{{ route('docs.playground') }}" @class(['is-active' => request()->routeIs('docs.playground')])>{{ __('ui_sweep.docs_nav.playground') }}</a>
            <a href="{{ route('public.status') }}">{{ __('ui_sweep.docs_nav.status') }}</a>
        </nav>

        <div class="dx-header-actions">
            <div class="dx-lang" role="group" aria-label="{{ __('docs_ui.language') }}">
                <a href="{{ route('lang.switch', 'en') }}" @class(['is-active' => app()->getLocale() === 'en']) hreflang="en" lang="en">EN</a>
                <a href="{{ route('lang.switch', 'fr') }}" @class(['is-active' => app()->getLocale() === 'fr']) hreflang="fr" lang="fr">FR</a>
            </div>
            <a href="{{ route('public.developers') }}" class="dx-cta">{{ __('docs_ui.get_keys') }}</a>
        </div>
    </div>
</header>

<div class="dx-shell">

    <aside class="dx-nav" id="docsSidebar" aria-label="{{ __('docs_ui.side_nav') }}">
        <nav>
            <div class="dx-nav-section">
                <div class="dx-nav-heading">{{ __('ui_sweep.docs_nav.start_here') }}</div>
                <a href="{{ route('docs.index') }}" @class(['dx-nav-link', 'is-active' => request()->routeIs('docs.index')])><i data-lucide="rocket" class="dx-ico-sm"></i>{{ __('ui_sweep.docs_nav.getting_started') }}</a>
            </div>

            <div class="dx-nav-section">
                <div class="dx-nav-heading">{{ __('docs_ui.nav.architecture_flows') }}</div>
                <a href="{{ route('docs.architecture') }}" @class(['dx-nav-link', 'is-active' => request()->routeIs('docs.architecture')])><i data-lucide="network" class="dx-ico-sm"></i>{{ __('docs_ui.nav.platform_overview') }}</a>
                @foreach (\App\Support\Docs\DocsDiagrams::FLOWS as $f)
                    <a href="{{ route('docs.flows', $f) }}" @class(['dx-nav-link', 'is-active' => request()->routeIs('docs.flows') && request()->route('flow') === $f])><i data-lucide="{{ \App\Support\Docs\DocsDiagrams::ICONS[$f] }}" class="dx-ico-sm"></i>{{ __('docs_ui.dg.'.$f.'.nav') }}</a>
                @endforeach
            </div>

            <div class="dx-nav-section">
                <div class="dx-nav-heading">{{ __('ui_sweep.docs_nav.concepts') }}</div>
                <a href="{{ route('docs.authentication') }}" @class(['dx-nav-link', 'is-active' => request()->routeIs('docs.authentication')])><i data-lucide="key-round" class="dx-ico-sm"></i>{{ __('ui_sweep.docs_nav.authentication') }}</a>
                <a href="{{ route('docs.errors') }}" @class(['dx-nav-link', 'is-active' => request()->routeIs('docs.errors')])><i data-lucide="triangle-alert" class="dx-ico-sm"></i>{{ __('ui_sweep.docs_nav.errors') }}</a>
            </div>

            <div class="dx-nav-section">
                <div class="dx-nav-heading">{{ __('ui_sweep.docs_nav.integrations') }}</div>
                <a href="{{ route('docs.api') }}" @class(['dx-nav-link', 'is-active' => request()->routeIs('docs.api')])><i data-lucide="braces" class="dx-ico-sm"></i>Connect API</a>
                <a href="{{ route('docs.sdk') }}" @class(['dx-nav-link', 'is-active' => request()->routeIs('docs.sdk')])><i data-lucide="package" class="dx-ico-sm"></i>SDK</a>
                <a href="{{ route('docs.bridge') }}" @class(['dx-nav-link', 'is-active' => request()->routeIs('docs.bridge')])><i data-lucide="plug" class="dx-ico-sm"></i>Bridge Agent</a>
                <a href="{{ route('docs.widget') }}" @class(['dx-nav-link', 'is-active' => request()->routeIs('docs.widget')])><i data-lucide="app-window" class="dx-ico-sm"></i>Widget</a>
                <a href="{{ route('docs.webhooks') }}" @class(['dx-nav-link', 'is-active' => request()->routeIs('docs.webhooks')])><i data-lucide="webhook" class="dx-ico-sm"></i>Webhooks</a>
            </div>

            <div class="dx-nav-section">
                <div class="dx-nav-heading">{{ __('ui_sweep.docs_nav.reference') }}</div>
                <a href="{{ route('docs.playground') }}" @class(['dx-nav-link', 'is-active' => request()->routeIs('docs.playground')])><i data-lucide="play-circle" class="dx-ico-sm"></i>{{ __('ui_sweep.docs_nav.interactive_playground') }}</a>
                <a href="{{ asset('openapi.json') }}" class="dx-nav-link" target="_blank" rel="noopener"><i data-lucide="file-json" class="dx-ico-sm"></i>{{ __('ui_sweep.docs_nav.openapi_spec') }}</a>
                <a href="{{ asset('opescare.postman_collection.json') }}" class="dx-nav-link" download><i data-lucide="download" class="dx-ico-sm"></i>Postman</a>
                <a href="{{ route('docs.changelog') }}" @class(['dx-nav-link', 'is-active' => request()->routeIs('docs.changelog')])><i data-lucide="history" class="dx-ico-sm"></i>{{ __('ui_sweep.docs_nav.changelog') }}</a>
                <a href="{{ route('public.developers.pricing') }}" class="dx-nav-link"><i data-lucide="receipt" class="dx-ico-sm"></i>{{ __('ui_sweep.docs_nav.api_plans') }}</a>
                <a href="{{ route('public.sla') }}" class="dx-nav-link"><i data-lucide="badge-check" class="dx-ico-sm"></i>SLA</a>
            </div>
        </nav>
    </aside>

    <main class="dx-main">
        <article class="docs-content dx-content" id="docsContent">
            @yield('content')
        </article>
    </main>

    <aside class="dx-toc" aria-label="{{ __('docs_ui.on_this_page') }}">
        <div class="dx-toc-inner">
            <div class="dx-toc-title">{{ __('docs_ui.on_this_page') }}</div>
            <nav id="docsToc" class="dx-toc-list"></nav>
            <div class="dx-seal-card">
                <img src="{{ asset('images/health-id/seal.webp') }}" alt="" width="56" height="56" class="dx-seal" decoding="async">
                <div>
                    <strong>{{ __('docs_ui.seal_title') }}</strong>
                    <span>{{ __('docs_ui.seal_body') }}</span>
                </div>
            </div>
        </div>
    </aside>
</div>

<footer class="dx-footer">
    <div class="dx-footer-inner">
        <div class="dx-footer-brand">
            <img src="{{ asset('images/health-id/seal.webp') }}" alt="" width="44" height="44" decoding="async">
            <div>
                <strong>OpesCare {{ __('docs_ui.developers') }}</strong>
                <span>{{ __('docs_ui.footer_base', ['url' => $docsBase ?? config('docs.public_url')]) }}</span>
            </div>
        </div>
        <nav class="dx-footer-links" aria-label="{{ __('docs_ui.footer_nav') }}">
            <a href="{{ route('public.landing') }}">opescare.com</a>
            <a href="{{ route('public.developers') }}">{{ __('ui_sweep.docs_nav.developer_hub') }}</a>
            <a href="{{ route('public.status') }}">{{ __('ui_sweep.docs_nav.status') }}</a>
            <a href="{{ route('public.sla') }}">SLA</a>
            <a href="{{ route('public.contact') }}">{{ __('docs_ui.contact') }}</a>
        </nav>
        <p class="dx-copyright">&copy; {{ date('Y') }} OPES Health Systems. {{ __('docs_ui.rights') }}</p>
    </div>
</footer>

<script src="{{ asset('js/docs.js') }}?v={{ @filemtime(public_path('js/docs.js')) ?: '1' }}" @cspNonce defer></script>
<script src="{{ asset('js/blog-diagrams.js') }}?v={{ @filemtime(public_path('js/blog-diagrams.js')) ?: '1' }}" @cspNonce defer></script>
@yield('scripts')
</body>
</html>

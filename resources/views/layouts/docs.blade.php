<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Developer Docs') — OpesCare</title>
    <meta name="description" content="@yield('meta_description', 'OpesCare Developer Documentation — Connect API, SDK, Bridge Agent, Widget, Webhooks.')">

    @include('partials.favicons')

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">

    <!-- Lucide icons -->
    <script src="{{ asset('js/lucide.min.js') }}"></script>
<script src="{{ asset('js/op-actions.js') }}?v={{ @filemtime(public_path('js/op-actions.js')) ?: '1' }}" defer></script>

    <!-- Docs CSS -->
    <link rel="stylesheet" href="{{ asset('css/docs.css') }}?v={{ @filemtime(public_path('css/docs.css')) ?: '1' }}">

    @yield('head')
{{-- Canonical, hreflang, Open Graph and schema.org --}}
@include('partials.seo_head')

</head>
<body class="docs-body">

<!-- Topbar -->
<header class="docs-topbar">
    <button class="docs-menu-toggle" id="docsMenuToggle" aria-label="Toggle navigation">
        <i data-lucide="menu" class="docs-icon-lg"></i>
    </button>
    <a href="{{ route('docs.index') }}" class="docs-topbar-logo">
        <svg width="24" height="24" viewBox="0 0 40 40" fill="none">
            <circle cx="20" cy="20" r="20" fill="#0F4C81"/>
            <path d="M12 20 Q20 10 28 20 Q20 30 12 20Z" fill="white"/>
        </svg>
        OpesCare
        <span class="badge">{{ __('ui_sweep.docs_nav.dev_docs') }}</span>
    </a>
    <div class="docs-topbar-spacer"></div>
    <nav class="docs-topbar-links">
        <a href="{{ route('docs.playground') }}">{{ __('ui_sweep.docs_nav.playground') }}</a>
        <a href="{{ route('docs.changelog') }}">{{ __('ui_sweep.docs_nav.changelog') }}</a>
        <a href="{{ route('public.developers') }}">{{ __('ui_sweep.docs_nav.developer_hub') }}</a>
        <a href="{{ asset('openapi.json') }}" target="_blank">{{ __('ui_sweep.docs_nav.openapi_spec') }}</a>
        <a href="{{ asset('opescare.postman_collection.json') }}" download>Postman</a>
        <a href="{{ route('public.developers.pricing') }}">{{ __('ui_sweep.docs_nav.api_plans') }}</a>
        <a href="{{ route('public.status') }}">{{ __('ui_sweep.docs_nav.status') }}</a>
        <a href="{{ route('public.sla') }}">SLA</a>
    </nav>
</header>

<div class="docs-shell">

    <!-- Sidebar -->
    <aside class="docs-sidebar" id="docsSidebar">
        <nav>
            <div class="docs-nav-section">
                <div class="docs-nav-heading">{{ __('ui_sweep.docs_nav.start_here') }}</div>
                <a href="{{ route('docs.index') }}"
                   class="docs-nav-link {{ request()->routeIs('docs.index') ? 'active' : '' }}">{{ __('ui_sweep.docs_nav.getting_started') }}</a>
            </div>

            <div class="docs-nav-section">
                <div class="docs-nav-heading">{{ __('ui_sweep.docs_nav.concepts') }}</div>
                <a href="{{ route('docs.authentication') }}"
                   class="docs-nav-link {{ request()->routeIs('docs.authentication') ? 'active' : '' }}">{{ __('ui_sweep.docs_nav.authentication') }}</a>
                <a href="{{ route('docs.errors') }}"
                   class="docs-nav-link {{ request()->routeIs('docs.errors') ? 'active' : '' }}">{{ __('ui_sweep.docs_nav.errors') }}</a>
            </div>

            <div class="docs-nav-section">
                <div class="docs-nav-heading">{{ __('ui_sweep.docs_nav.integrations') }}</div>
                <a href="{{ route('docs.api') }}"
                   class="docs-nav-link {{ request()->routeIs('docs.api') ? 'active' : '' }}">Connect API</a>
                <a href="{{ route('docs.sdk') }}"
                   class="docs-nav-link {{ request()->routeIs('docs.sdk') ? 'active' : '' }}">SDK</a>
                <a href="{{ route('docs.bridge') }}"
                   class="docs-nav-link {{ request()->routeIs('docs.bridge') ? 'active' : '' }}">Bridge Agent</a>
                <a href="{{ route('docs.widget') }}"
                   class="docs-nav-link {{ request()->routeIs('docs.widget') ? 'active' : '' }}">Widget</a>
                <a href="{{ route('docs.webhooks') }}"
                   class="docs-nav-link {{ request()->routeIs('docs.webhooks') ? 'active' : '' }}">Webhooks</a>
            </div>

            <div class="docs-nav-section">
                <div class="docs-nav-heading">{{ __('ui_sweep.docs_nav.reference') }}</div>
                <a href="{{ route('docs.playground') }}"
                   class="docs-nav-link {{ request()->routeIs('docs.playground') ? 'active' : '' }}">{{ __('ui_sweep.docs_nav.interactive_playground') }}</a>
                <a href="{{ route('docs.changelog') }}"
                   class="docs-nav-link {{ request()->routeIs('docs.changelog') ? 'active' : '' }}">{{ __('ui_sweep.docs_nav.changelog') }}</a>
            </div>
        </nav>
    </aside>

    <!-- Main content -->
    <main class="docs-content">
        @yield('content')
    </main>

</div>

<script @cspNonce>
// Mobile sidebar toggle
document.getElementById('docsMenuToggle')?.addEventListener('click', function() {
    document.getElementById('docsSidebar').classList.toggle('open');
});

// Language tab persistence
(function() {
    var STORAGE_KEY = 'docs_lang';
    var defaultLang = localStorage.getItem(STORAGE_KEY) || 'curl';

    function activateLang(lang) {
        document.querySelectorAll('.docs-code-tab').forEach(function(tab) {
            tab.classList.toggle('active', tab.dataset.lang === lang);
        });
        document.querySelectorAll('.docs-code-pane').forEach(function(pane) {
            pane.classList.toggle('active', pane.dataset.lang === lang);
        });
        localStorage.setItem(STORAGE_KEY, lang);
    }

    // Apply on load — but don't override tabs that have their own active logic (playground, json-only)
    var hasLangTabs = document.querySelectorAll('.docs-code-tab[data-lang="' + defaultLang + '"]').length > 0;
    if (hasLangTabs) {
        activateLang(defaultLang);
    } else {
        // activate first available tab in each block
        document.querySelectorAll('.docs-code-block').forEach(function(block) {
            var firstTab = block.querySelector('.docs-code-tab');
            if (firstTab && !block.querySelector('.docs-code-tab.active')) {
                firstTab.classList.add('active');
                var pane = block.querySelector('.docs-code-pane[data-lang="' + firstTab.dataset.lang + '"]');
                if (pane) pane.classList.add('active');
            }
        });
    }

    // Tab click handlers
    document.addEventListener('click', function(e) {
        var tab = e.target.closest('.docs-code-tab');
        if (!tab || !tab.dataset.lang) return;

        var lang = tab.dataset.lang;
        var block = tab.closest('.docs-code-block');

        // If this block is language-independent (json, html, text, url), only toggle within this block
        var isIsolated = ['json', 'html', 'text', 'url'].includes(lang);
        if (isIsolated) {
            block.querySelectorAll('.docs-code-tab').forEach(function(t) { t.classList.remove('active'); });
            block.querySelectorAll('.docs-code-pane').forEach(function(p) { p.classList.remove('active'); });
            tab.classList.add('active');
            var pane = block.querySelector('.docs-code-pane[data-lang="' + lang + '"]');
            if (pane) pane.classList.add('active');
        } else {
            activateLang(lang);
        }
    });
})();
</script>

@yield('scripts')

<script @cspNonce>
if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</body>
</html>

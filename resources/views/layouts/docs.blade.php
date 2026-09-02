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

    <!-- Docs CSS -->
    <link rel="stylesheet" href="{{ asset('css/docs.css') }}">

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
            <circle cx="20" cy="20" r="20" fill="#4F46E5"/>
            <path d="M12 20 Q20 10 28 20 Q20 30 12 20Z" fill="white"/>
        </svg>
        OpesCare
        <span class="badge">Dev Docs</span>
    </a>
    <div class="docs-topbar-spacer"></div>
    <nav class="docs-topbar-links">
        <a href="{{ route('docs.playground') }}">Playground</a>
        <a href="{{ route('docs.changelog') }}">Changelog</a>
        <a href="{{ route('public.developers') }}">Developer Hub</a>
        <a href="{{ asset('openapi.json') }}" target="_blank">OpenAPI Spec</a>
        <a href="{{ asset('opescare.postman_collection.json') }}" download>Postman</a>
        <a href="{{ route('public.developers.pricing') }}">API Plans</a>
        <a href="{{ route('public.status') }}">Status</a>
        <a href="{{ route('public.sla') }}">SLA</a>
    </nav>
</header>

<div class="docs-shell">

    <!-- Sidebar -->
    <aside class="docs-sidebar" id="docsSidebar">
        <nav>
            <div class="docs-nav-section">
                <div class="docs-nav-heading">Start Here</div>
                <a href="{{ route('docs.index') }}"
                   class="docs-nav-link {{ request()->routeIs('docs.index') ? 'active' : '' }}">Getting Started</a>
            </div>

            <div class="docs-nav-section">
                <div class="docs-nav-heading">Concepts</div>
                <a href="{{ route('docs.authentication') }}"
                   class="docs-nav-link {{ request()->routeIs('docs.authentication') ? 'active' : '' }}">Authentication</a>
                <a href="{{ route('docs.errors') }}"
                   class="docs-nav-link {{ request()->routeIs('docs.errors') ? 'active' : '' }}">Errors</a>
            </div>

            <div class="docs-nav-section">
                <div class="docs-nav-heading">Integrations</div>
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
                <div class="docs-nav-heading">Reference</div>
                <a href="{{ route('docs.playground') }}"
                   class="docs-nav-link {{ request()->routeIs('docs.playground') ? 'active' : '' }}">Interactive Playground</a>
                <a href="{{ route('docs.changelog') }}"
                   class="docs-nav-link {{ request()->routeIs('docs.changelog') ? 'active' : '' }}">Changelog</a>
            </div>
        </nav>
    </aside>

    <!-- Main content -->
    <main class="docs-content">
        @yield('content')
    </main>

</div>

<script>
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

<script>
if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</body>
</html>

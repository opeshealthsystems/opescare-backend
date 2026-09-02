<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('public.demo.page_title') ?: 'OpesCare Demo Access')</title>
    <meta name="theme-color" content="#0F4C81">
    @include('partials.favicons')
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <style>
        /* ── Demo Layout Overrides ─────────────────────────── */
        body.demo-body {
            background: #f8fafc;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        .demo-wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1.5rem 4rem;
        }
        .demo-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0 2rem;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .demo-brand {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            text-decoration: none;
            color: #0f4c81;
            font-size: 1.125rem;
            font-weight: 700;
        }
        .demo-brand svg { flex-shrink: 0; }
        .demo-topbar-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .demo-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .demo-badge-public  { background: #dbeafe; color: #1e40af; }
        .demo-badge-internal{ background: #fef3c7; color: #92400e; }
        .demo-warning {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-left: 4px solid #f59e0b;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
            margin-bottom: 2rem;
            font-size: 0.875rem;
            color: #78350f;
        }
        .demo-warning svg { flex-shrink: 0; margin-top: 2px; }
        .demo-section-title {
            font-size: 1.375rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        .demo-section-sub {
            font-size: 0.9375rem;
            color: #64748b;
            margin-bottom: 1.75rem;
        }
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2.5rem;
        }
        .demo-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            transition: box-shadow 0.15s, border-color 0.15s;
        }
        .demo-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); border-color: #94a3b8; }
        .demo-card-header {
            display: flex;
            align-items: flex-start;
            gap: 0.875rem;
            margin-bottom: 1rem;
        }
        .demo-card-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 8px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .demo-card-icon.teal   { background: #f0fdfa; color: #0d9488; }
        .demo-card-icon.indigo { background: #eef2ff; color: #4f46e5; }
        .demo-card-icon.amber  { background: #fffbeb; color: #d97706; }
        .demo-card-icon.purple { background: #faf5ff; color: #7c3aed; }
        .demo-card-icon.green  { background: #f0fdf4; color: #16a34a; }
        .demo-card-icon.rose   { background: #fff1f2; color: #e11d48; }
        .demo-card-role {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            line-height: 1.3;
        }
        .demo-card-org {
            font-size: 0.8125rem;
            color: #64748b;
            margin-top: 0.125rem;
        }
        .demo-card-body { font-size: 0.875rem; color: #374151; margin-bottom: 1rem; }
        .demo-card-meta {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 1rem;
            padding: 0.625rem 0.75rem;
            background: #f8fafc;
            border-radius: 6px;
        }
        .demo-card-meta code {
            font-family: 'Fira Code', monospace;
            font-size: 0.8rem;
            color: #1e293b;
            background: #e2e8f0;
            padding: 0.1rem 0.35rem;
            border-radius: 4px;
        }
        .demo-login-btn {
            width: 100%;
            padding: 0.6875rem 1rem;
            background: #0f4c81;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: background 0.15s;
        }
        .demo-login-btn:hover { background: #0c3a60; }
        .demo-limitations {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            font-size: 0.875rem;
            color: #475569;
        }
        .demo-limitations h4 {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
            font-size: 0.9375rem;
        }
        .demo-limitations ul {
            margin: 0;
            padding-left: 1.25rem;
            line-height: 1.8;
        }
        .demo-divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 2rem 0;
        }
        .demo-switch-link {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.875rem;
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }
        .demo-switch-link:hover { text-decoration: underline; }
        .demo-lang-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.8125rem;
            color: #64748b;
            text-decoration: none;
            padding: 0.25rem 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .demo-lang-btn:hover { border-color: #94a3b8; color: #1e293b; }
        .demo-reset-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #fff1f2;
            color: #be123c;
            border: 1px solid #fecdd3;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
        }
        .demo-reset-btn:hover { background: #ffe4e6; }
        @media (max-width: 640px) {
            .demo-grid { grid-template-columns: 1fr; }
            .demo-wrap { padding: 1rem 1rem 3rem; }
        }
    </style>
</head>
<body class="demo-body">

<div class="demo-wrap">

    <div class="demo-topbar">
        <a href="{{ url('/') }}" class="demo-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="22" height="22" aria-hidden="true">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
            </svg>
            OpesCare
        </a>
        <div class="demo-topbar-actions">
            @php $locale = app()->getLocale(); $otherLocale = $locale === 'fr' ? 'en' : 'fr'; $otherLabel = $locale === 'fr' ? 'EN' : 'FR'; @endphp
            <a href="{{ url('/lang/' . $otherLocale) }}" class="demo-lang-btn" aria-label="Switch language">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                {{ $otherLabel }}
            </a>
            @yield('topbar_badge')
        </div>
    </div>

    @yield('content')

</div>

<script src="{{ asset('js/lucide.min.js') }}"></script>
<script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
@yield('scripts')

</body>
</html>

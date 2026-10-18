{{--
    The one layout behind every HTTP error page (401, 402, 403, 404, 419, 429,
    500, 503). Each page sets: $code, $icon (Lucide name), $tone ('light' |
    'dark'), and its strings via errors.pages.<code>.* in lang/{en,fr}/errors.php.
    Optional sections: primary / secondary action, note, footer.
--}}
@php
    $tone  = $tone ?? 'light';
    $key   = "errors.pages.$code";
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-tone="{{ $tone }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $code }} — {{ __("$key.title") }} | OpesCare</title>
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0F4C81">
    @include('partials.favicons')
    @include('public.partials._od_pages_css')
    <script src="{{ asset('js/lucide.min.js') }}"></script>
<script src="{{ asset('js/op-actions.js') }}?v={{ @filemtime(public_path('js/op-actions.js')) ?: '1' }}" defer></script>
    <style>
        /* Same language as the landing page: canvas, one flat white card, ink + muted, one accent. */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --err-bg: #EEF3F9; --err-fg: #0F2340; --err-muted: #5B6B80; --err-line: #E2E8F0; --err-accent: #0F4C81; --err-icon: #0F4C81; }
        html[data-tone="dark"] { --err-icon: #B42318; }
        html[data-tone="warn"] { --err-icon: #B54708; }
        body {
            font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif; background: var(--err-bg); color: var(--err-fg);
            min-height: 100vh; display: flex; flex-direction: column; justify-content: center;
            padding: 48px 16px; -webkit-font-smoothing: antialiased;
        }
        .err-wrap { width: 100%; max-width: 560px; margin: 0 auto; }
        .logo-link { display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-weight: 800; font-size: 20px; color: var(--err-fg); margin-bottom: 24px; }
        .logo-link img { width: 26px; height: 26px; }
        .err-card { background: #fff; border-radius: 16px; padding: 32px 24px; }
        @media (min-width: 640px) { .err-card { padding: 40px; } }
        .err-icon { color: var(--err-icon); margin-bottom: 16px; }
        .err-icon i, .err-icon svg { width: 28px; height: 28px; display: block; }
        .err-code { font-size: 14px; font-weight: 600; color: var(--err-muted); margin-bottom: 8px; }
        .err-title { font-size: clamp(26px, 4vw, 32px); line-height: 1.2; font-weight: 700; letter-spacing: -0.015em; margin-bottom: 12px; }
        .err-body  { font-size: 16px; color: var(--err-muted); max-width: 52ch; line-height: 1.6; margin-bottom: 24px; }
        .err-note  { font-size: 14px; color: var(--err-muted); max-width: 52ch; line-height: 1.6; margin: -8px 0 24px; }
        .btn-row { display: flex; flex-wrap: wrap; gap: 12px; }
        .btn-primary, .btn-ghost {
            display: inline-flex; align-items: center; gap: 8px; min-height: 44px; font-size: 15px; font-weight: 600;
            border-radius: 8px; padding: 0 20px; text-decoration: none; border: 1px solid transparent;
        }
        .btn-primary { background: var(--err-accent); color: #fff; }
        .btn-primary:hover { background: #0B3C68; }
        .btn-ghost { background: #fff; color: var(--err-fg); border-color: var(--err-line); }
        .btn-ghost:hover { border-color: var(--err-fg); }
        .btn-row i, .btn-row svg { width: 16px; height: 16px; }
        .err-footer { margin-top: 24px; font-size: 13px; color: var(--err-muted); }
        .err-footer a { color: var(--err-accent); }
    </style>
</head>
<body>
<div class="err-wrap">
    <a href="{{ url('/') }}" class="logo-link">
        <img src="{{ asset('brand/opescare-favicon.png') }}" alt="">
        OpesCare
    </a>

    <div class="err-card">
    <div class="err-icon"><i data-lucide="{{ $icon }}"></i></div>

    <div class="err-code">{{ $code }}</div>
    <h1 class="err-title">{{ __("$key.title") }}</h1>
    <p class="err-body">{{ __("$key.body") }}</p>
    @hasSection('note')<p class="err-note">@yield('note')</p>@endif

    <div class="btn-row">
        @yield('actions')
    </div>

    </div>

    @hasSection('footer')<div class="err-footer">@yield('footer')</div>@endif
</div>

    <script @cspNonce>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>
</html>

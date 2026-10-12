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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="{{ asset('js/lucide.min.js') }}"></script>
<script src="{{ asset('js/op-actions.js') }}?v={{ @filemtime(public_path('js/op-actions.js')) ?: '1' }}" defer></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --err-bg: #F0F4F8; --err-fg: #0F172A; --err-muted: #64748B; --err-note: #94A3B8;
            --err-code: #E2E8F0; --err-border: #E2E8F0; --err-ghost: #475569; --err-ghost-hover: #0F4C81;
            --err-icon-bg: #EFF6FF; --err-icon-fg: #0F4C81; --err-icon-border: transparent;
        }
        html[data-tone="dark"] {
            --err-bg: #0F172A; --err-fg: #F1F5F9; --err-muted: #94A3B8; --err-note: #64748B;
            --err-code: rgba(255,255,255,.08); --err-border: rgba(255,255,255,.12); --err-ghost: #94A3B8; --err-ghost-hover: #fff;
            --err-icon-bg: rgba(185,28,28,.15); --err-icon-fg: #FCA5A5; --err-icon-border: rgba(185,28,28,.3);
        }
        html[data-tone="warn"] { --err-icon-bg: #FFF7ED; --err-icon-fg: #B45309; --err-icon-border: #FDE68A; }
        body {
            font-family: 'Inter', sans-serif; background: var(--err-bg); color: var(--err-fg);
            min-height: 100vh; display: flex; flex-direction: column; align-items: center;
            justify-content: center; padding: 2rem 1.25rem; text-align: center;
        }
        .err-icon {
            width: 5rem; height: 5rem; background: var(--err-icon-bg); color: var(--err-icon-fg);
            border: 1px solid var(--err-icon-border); border-radius: 1.25rem;
            display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;
        }
        .err-icon i { width: 2.25rem; height: 2.25rem; }
        .err-code { font-size: 6rem; font-weight: 900; color: var(--err-code); line-height: 1; margin-bottom: 0.5rem; letter-spacing: -0.04em; }
        .err-title { font-size: 1.5rem; font-weight: 800; margin-bottom: 0.75rem; }
        .err-body  { font-size: 0.9375rem; color: var(--err-muted); max-width: 440px; line-height: 1.65; margin-bottom: 2rem; }
        .err-body + .err-note { margin-top: -1.5rem; }
        .err-note  { font-size: 0.8125rem; color: var(--err-note); max-width: 440px; line-height: 1.65; margin-bottom: 2rem; }
        .btn-row { display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: center; }
        .btn-primary, .btn-ghost {
            display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.9375rem;
            border-radius: 0.625rem; padding: 0.75rem 1.75rem; text-decoration: none; transition: all .2s;
        }
        .btn-primary { background: #0F4C81; color: #fff; font-weight: 700; }
        .btn-primary:hover { background: #1D6EA8; }
        .btn-ghost { background: transparent; color: var(--err-ghost); font-weight: 600; border: 1.5px solid var(--err-border); }
        .btn-ghost:hover { border-color: #0F4C81; color: var(--err-ghost-hover); }
        .btn-row i { width: 1rem; height: 1rem; }
        .logo-link { display: flex; align-items: center; gap: 0.5rem; text-decoration: none; font-weight: 800; font-size: 1rem; color: var(--err-fg); margin-bottom: 3rem; }
        .logo-link img { width: 26px; height: 26px; }
        .err-footer { margin-top: 2.5rem; font-size: 0.75rem; color: var(--err-note); }
        .err-footer a { color: #0F4C81; text-decoration: none; }
        html[data-tone="dark"] .err-footer a { color: #5EEAD4; }
    </style>
</head>
<body>
    <a href="{{ url('/') }}" class="logo-link">
        <img src="{{ asset('brand/opescare-favicon.png') }}" alt="">
        OpesCare
    </a>

    <div class="err-icon"><i data-lucide="{{ $icon }}"></i></div>

    <div class="err-code">{{ $code }}</div>
    <h1 class="err-title">{{ __("$key.title") }}</h1>
    <p class="err-body">{{ __("$key.body") }}</p>
    @hasSection('note')<p class="err-note">@yield('note')</p>@endif

    <div class="btn-row">
        @yield('actions')
    </div>

    @hasSection('footer')<div class="err-footer">@yield('footer')</div>@endif

    <script @cspNonce>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>
</html>

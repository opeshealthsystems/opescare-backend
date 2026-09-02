<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Server Error | OpesCare</title>
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0F4C81">
    @include('partials.favicons')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0F172A;
            color: #E2E8F0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
            text-align: center;
        }
        .err-icon {
            width: 5rem; height: 5rem;
            background: rgba(185,28,28,.15);
            border: 1px solid rgba(185,28,28,.3);
            border-radius: 1.25rem;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            color: #FCA5A5;
        }
        .err-code { font-size: 6rem; font-weight: 900; color: rgba(255,255,255,.08); line-height: 1; margin-bottom: 0.5rem; letter-spacing: -0.04em; }
        .err-title { font-size: 1.5rem; font-weight: 800; color: #F1F5F9; margin-bottom: 0.75rem; }
        .err-body  { font-size: 0.9375rem; color: #94A3B8; max-width: 440px; line-height: 1.65; margin-bottom: 0.5rem; }
        .err-note  { font-size: 0.8125rem; color: #64748B; max-width: 440px; line-height: 1.65; margin-bottom: 2rem; }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: #0F4C81; color: #fff;
            font-size: 0.9375rem; font-weight: 700;
            border-radius: 0.625rem; padding: 0.75rem 1.75rem;
            text-decoration: none; transition: background .2s;
            margin: 0 0.375rem;
        }
        .btn-primary:hover { background: #1D6EA8; }
        .btn-ghost {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: transparent; color: #94A3B8;
            font-size: 0.9375rem; font-weight: 600; border: 1.5px solid rgba(255,255,255,.12);
            border-radius: 0.625rem; padding: 0.75rem 1.75rem;
            text-decoration: none; transition: all .2s;
            margin: 0 0.375rem;
        }
        .btn-ghost:hover { border-color: #0F4C81; color: #fff; }
        .btn-row { display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: center; }
        .logo-link { display: flex; align-items: center; gap: 0.5rem; text-decoration: none; font-weight: 800; font-size: 1rem; color: #F1F5F9; margin-bottom: 3rem; }
        .logo-link img { width: 26px; height: 26px; }
        .status-ref { margin-top: 2.5rem; font-size: 0.75rem; color: #334155; }
        .status-ref a { color: #5EEAD4; text-decoration: none; }
    </style>
</head>
<body>
    <a href="{{ url('/') }}" class="logo-link">
        <img src="{{ asset('brand/opescare-favicon.png') }}" alt="">
        OpesCare
    </a>

    <div class="err-icon">
        <i data-lucide="server-crash" style="width:2.25rem;height:2.25rem;"></i>
    </div>

    <div class="err-code">500</div>
    <h1 class="err-title">Server error</h1>
    <p class="err-body">Something went wrong on our end. Our team has been notified and is working to fix this as quickly as possible.</p>
    <p class="err-note">Your data is safe. If this is urgent, please contact support directly.</p>

    <div class="btn-row">
        <a href="{{ url('/') }}" class="btn-primary">
            <i data-lucide="home" style="width:1rem;height:1rem;"></i>
            Back to Home
        </a>
        <a href="{{ route('public.contact') }}" class="btn-ghost">
            <i data-lucide="headset" style="width:1rem;height:1rem;"></i>
            Contact Support
        </a>
    </div>

    <div class="status-ref">
        Check our <a href="{{ route('public.status') }}">System Status page</a> for live updates.
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>
</html>

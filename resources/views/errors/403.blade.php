<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Access Denied | OpesCare</title>
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
            background: #F0F4F8;
            color: #0F172A;
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
            background: #FFF7ED;
            border: 1px solid #FDE68A;
            border-radius: 1.25rem;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            color: #B45309;
        }
        .err-code { font-size: 6rem; font-weight: 900; color: #E2E8F0; line-height: 1; margin-bottom: 0.5rem; letter-spacing: -0.04em; }
        .err-title { font-size: 1.5rem; font-weight: 800; color: #0F172A; margin-bottom: 0.75rem; }
        .err-body  { font-size: 0.9375rem; color: #64748B; max-width: 440px; line-height: 1.65; margin-bottom: 2rem; }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: #0F4C81; color: #fff;
            font-size: 0.9375rem; font-weight: 700;
            border-radius: 0.625rem; padding: 0.75rem 1.75rem;
            text-decoration: none; transition: background .2s;
            margin: 0 0.375rem;
        }
        .btn-primary:hover { background: #0A355C; }
        .btn-ghost {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: transparent; color: #475569;
            font-size: 0.9375rem; font-weight: 600; border: 1.5px solid #E2E8F0;
            border-radius: 0.625rem; padding: 0.75rem 1.75rem;
            text-decoration: none; transition: all .2s;
            margin: 0 0.375rem;
        }
        .btn-ghost:hover { border-color: #0F4C81; color: #0F4C81; }
        .btn-row { display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: center; }
        .logo-link { display: flex; align-items: center; gap: 0.5rem; text-decoration: none; font-weight: 800; font-size: 1rem; color: #0F172A; margin-bottom: 3rem; }
        .logo-link img { width: 26px; height: 26px; }
    </style>
</head>
<body>
    <a href="{{ url('/') }}" class="logo-link">
        <img src="{{ asset('brand/opescare-favicon.png') }}" alt="">
        OpesCare
    </a>

    <div class="err-icon">
        <i data-lucide="shield-x" style="width:2.25rem;height:2.25rem;"></i>
    </div>

    <div class="err-code">403</div>
    <h1 class="err-title">Access denied</h1>
    <p class="err-body">You don't have permission to view this page. If you believe this is a mistake, please sign in with the correct account or contact your administrator.</p>

    <div class="btn-row">
        <a href="{{ route('login') }}" class="btn-primary">
            <i data-lucide="log-in" style="width:1rem;height:1rem;"></i>
            Sign In
        </a>
        <a href="{{ url('/') }}" class="btn-ghost">
            <i data-lucide="home" style="width:1rem;height:1rem;"></i>
            Back to Home
        </a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>
</html>

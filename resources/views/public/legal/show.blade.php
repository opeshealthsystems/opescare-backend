<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->title }} — OpesCare Legal</title>
    @include('partials.favicons')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
    <style>
        body { background:#f8fafc; font-family:'Inter',sans-serif; margin:0; }
        .legal-topbar {
            background:#0F4C81; color:#fff;
            padding:14px 24px; display:flex; align-items:center; gap:16px;
        }
        .legal-topbar a { color:rgba(255,255,255,.75); text-decoration:none; font-size:0.88rem; }
        .legal-topbar a:hover { color:#fff; }
        .legal-container { max-width:800px; margin:0 auto; padding:32px 24px; }
        .legal-meta { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px 20px; margin-bottom:24px; display:flex; flex-wrap:wrap; gap:16px; font-size:0.83rem; }
        .legal-meta__item { color:#64748b; }
        .legal-meta__item strong { color:#1e293b; display:block; font-size:0.78rem; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px; }
        .legal-body { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:32px; line-height:1.7; font-size:0.92rem; color:#1e293b; }
        .legal-body h1, .legal-body h2, .legal-body h3 { color:#0F4C81; }
        .legal-body a { color:#7c3aed; }
        .legal-footer { text-align:center; padding:24px; font-size:0.8rem; color:#94a3b8; }
        .no-content { text-align:center; padding:60px; color:#94a3b8; }
    </style>
{{-- Canonical, hreflang, Open Graph and schema.org --}}
@include('partials.seo_head')

</head>
<body>
<div class="legal-topbar">
    <a href="{{ route('public.legal') }}">← Legal Centre</a>
    <span style="opacity:.4;">|</span>
    <span style="font-weight:600;">{{ $document->title }}</span>
</div>

<div class="legal-container">
    <h1 style="font-size:1.5rem;font-weight:800;color:#0F4C81;margin:0 0 16px;">{{ $document->title }}</h1>

    @if($currentVersion)
    <div class="legal-meta">
        <div class="legal-meta__item"><strong>Version</strong>{{ $currentVersion->version }}</div>
        @if($currentVersion->effective_at)
            <div class="legal-meta__item"><strong>Effective</strong>{{ $currentVersion->effective_at->format('d F Y') }}</div>
        @endif
        @if($currentVersion->published_at)
            <div class="legal-meta__item"><strong>Published</strong>{{ $currentVersion->published_at->format('d F Y') }}</div>
        @endif
        <div class="legal-meta__item"><strong>Language</strong>{{ strtoupper($document->language) }}</div>
    </div>

    <div class="legal-body">
        {!! $currentVersion->content_html !!}
    </div>
    @else
    <div class="no-content">
        <p>This document has not been published yet.</p>
        <a href="{{ route('public.legal') }}" style="color:#7c3aed;">← Back to Legal Centre</a>
    </div>
    @endif
</div>

<div class="legal-footer">
    © {{ date('Y') }} OpesCare. All rights reserved.
    <a href="{{ route('public.legal') }}" style="color:#94a3b8;margin-left:12px;">Legal Centre</a>
    <a href="/" style="color:#94a3b8;margin-left:12px;">Portal</a>
</div>
</body>
</html>

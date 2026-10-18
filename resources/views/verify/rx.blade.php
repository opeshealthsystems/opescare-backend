{{--
    GET /verify/rx/{token} — the scan-target for a printed prescription.

    PUBLIC and unauthenticated: whoever holds the paper can open this. Three
    things this file is not allowed to forget.

    1. THE VERDICT IS THE PAGE. A pharmacist reads this across a counter, on a
       phone, in a hurry. The four states are decided in the controller and are
       rendered here as one full-width band with its own colour, its own icon
       and its own shape — never a badge, never merged, and `dispensed` is never
       red. Firing the same alarm at "this was withdrawn" and "you already
       handed this over" is how red stops meaning anything.

    2. THE REFUSAL IS ONE PAGE. Invalid, expired, revoked and made-up tokens all
       arrive here with the same `$error` string, and nothing below may vary
       between them — no timestamp, no echoed token, no "this one at least
       existed" tell. That is why the letterhead stamp is rendered only when
       `$verifiedAt` is set, i.e. only on a successful verification.

    3. NAME AND HEALTH ID, NOTHING ELSE. The projection is built field by field
       in PrescriptionVerifyController. Never add date of birth, sex, phone,
       address, another prescription, or the clinical notes to this view.

    No external requests (fonts, icon CDN) and no JavaScript: this renders on a
    2G phone in a pharmacy, and the icons are inlined Lucide paths so a blocked
    or slow script can never leave the verdict without its mark.
--}}
@php
    /*
     | Lucide (v0.460) path data, inlined. Same icon set as the rest of the
     | platform — the difference is that these do not need the runtime, so the
     | verdict can never render as a bare box.
     */
    $lucidePaths = [
        'shield-check'  => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
        'ban'           => '<circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/>',
        'clock-alert'   => '<path d="M12 6v6l4 2"/><path d="M16 21.16a10 10 0 1 1 5-13.516"/><path d="M20 11.5v6"/><path d="M20 21.5h.01"/>',
        'package-check' => '<path d="m16 16 2 2 4-4"/><path d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14"/><path d="m7.5 4.27 9 5.15"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" x2="12" y1="22" y2="12"/>',
        'circle-x'      => '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>',
        'badge-check'   => '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/>',
        'lock'          => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'user-round'    => '<circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/>',
        'file-text'     => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
        'pill'          => '<path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/>',
        'scan-line'     => '<path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/>',
        'eye-off'       => '<path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/>',
        'move-horizontal' => '<path d="m18 8 4 4-4 4"/><path d="M2 12h20"/><path d="m6 8-4 4 4 4"/>',
    ];

    $icon = function (string $name, string $size = '1.125rem', string $class = '') use ($lucidePaths): string {
        return '<svg class="ic ' . $class . '" style="width:' . $size . ';height:' . $size . '"'
            . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
            . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
            . ($lucidePaths[$name] ?? '') . '</svg>';
    };

    /*
     | The four verdict states, decided in the controller. Each one owns a
     | colour, an icon and a shape; none of them borrows another's.
     */
    $verdicts = [
        'retracted' => ['tone' => 'stop',    'icon' => 'ban',           'title' => __('verify.rx_do_not_dispense'), 'role' => 'alert'],
        'expired'   => ['tone' => 'expired', 'icon' => 'clock-alert',   'title' => __('verify.rx_do_not_dispense'), 'role' => 'alert'],
        'dispensed' => ['tone' => 'done',    'icon' => 'package-check', 'title' => __('verify.rx_dispensed_title'), 'role' => 'status'],
        'valid'     => ['tone' => 'valid',   'icon' => 'shield-check',  'title' => __('verify.rx_valid_title'),     'role' => 'status'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('verify.rx_title') }}</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    @include('partials.favicons')
    <style>
        /* System stack only. A pharmacy counter is the wrong place to depend on
           a font that has to be fetched from another origin. */
        :root {
            --brand:      #0F4C81;
            --brand-deep: #0F2744;
            --ink:        #0F172A;
            --muted:      #64748B;
            --line:       #E2E8F0;
            --line-soft:  #F1F5F9;
            --paper:      #FFFFFF;
            --bg:         #EDF2F7;
            --mono: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, "Liberation Mono", monospace;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html { -webkit-text-size-adjust: 100%; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
            /* The page itself never scrolls sideways; only the medicines table does. */
            overflow-x: hidden;
        }

        .ic { flex-shrink: 0; display: block; }

        a:focus-visible, .doc:focus-visible {
            outline: 3px solid var(--brand);
            outline-offset: 2px;
            border-radius: 4px;
        }

        /* ── Top bar ──────────────────────────────────────────────────────── */
        .topbar {
            background: var(--brand-deep);
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }
        .topbar-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: #fff;
            font-weight: 800;
            font-size: 1rem;
            letter-spacing: -0.01em;
        }
        .topbar-brand img { width: 24px; height: 24px; flex-shrink: 0; }
        .topbar-tag {
            display: none;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            color: #A9C7E4;
        }

        .page-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.25rem 0.875rem 3rem;
        }

        /* ── The document ─────────────────────────────────────────────────── */
        .doc {
            width: 100%;
            max-width: 720px;
            background: var(--paper);
            border-radius: 0.875rem;
            border: 1px solid #DCE5EE;
            border-top: 5px solid var(--brand);
            box-shadow: none;
            overflow: hidden;
        }

        .doc-letterhead {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            padding: 1rem 1.125rem;
            border-bottom: 2px solid var(--brand);
        }
        /* Crest and wordmark are one unit: the longer French platform line must
           push the stamp onto the next row, never orphan the crest above it. */
        .doc-ident {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
            margin-right: auto;
        }
        .doc-crest { width: 40px; height: 40px; flex-shrink: 0; }
        .doc-org { min-width: 0; }
        .doc-org-name {
            font-size: 1.0625rem;
            font-weight: 800;
            color: var(--brand-deep);
            line-height: 1.15;
            letter-spacing: -0.015em;
        }
        .doc-org-sub {
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.085em;
            color: var(--brand);
            margin-top: 0.15rem;
        }
        /* Only ever rendered on a successful verification — see the file note. */
        .doc-stamp {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border: 1.5px solid var(--brand);
            border-radius: 0.5rem;
            padding: 0.3rem 0.5rem;
            color: var(--brand);
            background: #F4F9FD;
        }
        .doc-stamp-label {
            font-size: 0.5625rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            line-height: 1.2;
        }
        .doc-stamp-time {
            font-family: var(--mono);
            font-size: 0.6875rem;
            font-weight: 700;
            line-height: 1.3;
            white-space: nowrap;
        }

        .doc-title { padding: 1rem 1.125rem 0; }
        .doc-title h1 {
            font-size: 1.0625rem;
            font-weight: 800;
            color: var(--brand-deep);
            letter-spacing: -0.015em;
        }
        .doc-title p {
            font-size: 0.8125rem;
            line-height: 1.5;
            color: var(--muted);
            margin-top: 0.3rem;
        }

        .doc-body { padding: 1.125rem 1.125rem 1.5rem; }

        /* ── THE VERDICT ──────────────────────────────────────────────────────
           The loudest object on the page, above every piece of data, legible at
           arm's length. Colour is never the only signal: each state also has its
           own icon and its own words. */
        .verdict {
            border: 2px solid;
            border-radius: 0.875rem;
            overflow: hidden;
            margin-bottom: 1.25rem;
        }
        .verdict-head {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            padding: 1rem 1.125rem;
            color: #fff;
        }
        .verdict-mark {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            background: rgba(255,255,255,.18);
            box-shadow: inset 0 0 0 1.5px rgba(255,255,255,.4);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .verdict-title {
            font-size: clamp(1.375rem, 6.6vw, 2rem);
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: -0.02em;
            text-transform: uppercase;
        }
        .verdict-note {
            padding: 0.875rem 1.125rem;
            font-size: 0.9375rem;
            line-height: 1.55;
            font-weight: 500;
        }

        .verdict--stop    { border-color: #B91C1C; }
        .verdict--stop    .verdict-head { background: #B91C1C; }
        .verdict--stop    .verdict-note { background: #FEF2F2; color: #7F1D1D; }

        .verdict--expired { border-color: #B45309; }
        .verdict--expired .verdict-head { background: #B45309; }
        .verdict--expired .verdict-note { background: #FFFBEB; color: #78350F; }

        /* Deliberately grey. A completed script is not an alarm. */
        .verdict--done    { border-color: #475569; }
        .verdict--done    .verdict-head { background: #334155; }
        .verdict--done    .verdict-note { background: #F1F5F9; color: #1E293B; }

        .verdict--valid   { border-color: #047857; }
        .verdict--valid   .verdict-head { background: #047857; }
        .verdict--valid   .verdict-note { background: #ECFDF5; color: #065F46; }

        /* The single refusal. Nothing below it varies by cause. */
        .verdict--void    { border-color: #7F1D1D; }
        .verdict--void    .verdict-head { background: #7F1D1D; }
        .verdict--void    .verdict-note { background: #FEF2F2; color: #7F1D1D; }

        /* ── Reference ────────────────────────────────────────────────────── */
        .ref {
            text-align: center;
            background: #F8FAFC;
            border: 1.5px dashed #CBD5E1;
            border-radius: 0.875rem;
            padding: 0.875rem 1rem;
        }
        .ref-label {
            font-size: 0.625rem;
            font-weight: 800;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .ref-value {
            font-family: var(--mono);
            font-size: clamp(1.25rem, 5.6vw, 1.625rem);
            font-weight: 800;
            color: var(--brand);
            letter-spacing: 0.06em;
            word-break: break-all;
            margin-top: 0.25rem;
        }
        .ref-hint {
            font-size: 0.75rem;
            line-height: 1.45;
            color: var(--muted);
            margin-top: 0.4rem;
        }

        /* ── Notes (authority / audit / privacy) ──────────────────────────── */
        .note {
            display: flex;
            align-items: flex-start;
            gap: 0.55rem;
            font-size: 0.78125rem;
            line-height: 1.55;
        }
        .note .ic { margin-top: 0.15rem; }
        .note--authority {
            background: #F2F7FC;
            border: 1px solid #CBDFF0;
            border-left: 4px solid var(--brand);
            border-radius: 0.625rem;
            padding: 0.8125rem 0.9375rem;
            color: #1C3A5B;
            margin-top: 1rem;
            font-weight: 500;
        }
        .note--muted { color: var(--muted); margin-top: 0.75rem; }

        .doc-notes {
            margin-top: 1.5rem;
            padding-top: 1.125rem;
            border-top: 1px solid var(--line);
        }

        /* ── Section headings ─────────────────────────────────────────────── */
        .sect-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.6875rem;
            font-weight: 800;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.09em;
            margin: 1.625rem 0 0.75rem;
        }
        .sect-title::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--line);
        }

        /* ── Identity block — the "is this the right person" check ────────── */
        .identity {
            background: #F8FAFC;
            border: 1px solid var(--line);
            border-radius: 0.875rem;
            padding: 0.9375rem 1rem;
            display: grid;
            gap: 0.875rem;
        }
        .identity .field-value { font-size: 1.0625rem; }

        .field-label {
            font-size: 0.65625rem;
            font-weight: 800;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.2rem;
        }
        .field-value {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.35;
            overflow-wrap: anywhere;
        }
        .field-value.mono { font-family: var(--mono); color: var(--brand); letter-spacing: 0.03em; }
        .field-value.muted { font-weight: 600; color: #94A3B8; }

        .field-grid { display: grid; grid-template-columns: 1fr; gap: 0.875rem 1.25rem; }

        /* ── Medicines ────────────────────────────────────────────────────── */
        .scroll-hint {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.71875rem;
            color: var(--muted);
            margin-bottom: 0.5rem;
        }
        .rx-table-wrap {
            overflow-x: auto;
            overscroll-behavior-x: contain;
            -webkit-overflow-scrolling: touch;
            border: 1px solid var(--line);
            border-radius: 0.75rem;
            background: var(--paper);
        }
        table.rx-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.8125rem;
            min-width: 560px;
        }
        table.rx-table thead th {
            text-align: left;
            font-size: 0.65625rem;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            background: #F1F5F9;
            padding: 0.5625rem 0.75rem;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }
        table.rx-table td,
        table.rx-table tbody th {
            padding: 0.6875rem 0.75rem;
            border-bottom: 1px solid var(--line-soft);
            vertical-align: top;
            text-align: left;
            color: #334155;
            background: var(--paper);
            font-size: 0.8125rem;
        }
        table.rx-table tbody tr:last-child td,
        table.rx-table tbody tr:last-child th { border-bottom: none; }
        /* The drug name stays put while the rest of the row scrolls, so a
           pharmacist never loses which medicine a quantity belongs to. */
        table.rx-table thead th:first-child,
        table.rx-table tbody th {
            position: sticky;
            left: 0;
            z-index: 1;
            box-shadow: 1px 0 0 var(--line);
            min-width: 9.5rem;
        }
        table.rx-table tbody th { font-weight: 700; color: var(--ink); }

        .pill {
            display: inline-flex;
            align-items: center;
            font-size: 0.65625rem;
            font-weight: 800;
            border-radius: 999px;
            padding: 0.2rem 0.5rem;
            white-space: nowrap;
        }
        .pill-dispensed { background: #D1FAE5; color: #065F46; }
        .pill-pending   { background: #E0F2FE; color: #075985; }
        .pill-stopped   { background: #FEE2E2; color: #991B1B; }
        .pill-neutral   { background: #F1F5F9; color: #475569; }
        .pill-when { font-size: 0.65625rem; color: #94A3B8; margin-top: 0.25rem; white-space: nowrap; }

        .rx-empty { padding: 1.125rem; text-align: center; font-size: 0.875rem; color: var(--muted); }

        /* ── Footer ───────────────────────────────────────────────────────── */
        .verify-footer {
            text-align: center;
            font-size: 0.75rem;
            color: #7A8CA0;
            margin-top: 1.5rem;
            line-height: 2;
        }
        .verify-footer a { color: var(--brand); text-decoration: none; font-weight: 600; }
        .verify-footer a:hover { text-decoration: underline; }
        .verify-footer .sep { padding: 0 0.4rem; color: #B6C3D1; }

        @@media screen and (min-width: 480px) {
            .topbar { padding: 0.8125rem 1.5rem; }
            .topbar-tag { display: inline-flex; }
            .page-wrap { padding: 2rem 1.25rem 3.5rem; }
            .doc-letterhead, .doc-title, .doc-body { padding-left: 1.75rem; padding-right: 1.75rem; }
            .doc-body { padding-bottom: 1.75rem; }
            .verdict-head { padding: 1.125rem 1.375rem; }
            .verdict-note { padding: 1rem 1.375rem; }
            .field-grid { grid-template-columns: 1fr 1fr; }
            .identity { grid-template-columns: 1fr 1fr; }
            .scroll-hint { display: none; }
        }
        @@media screen and (min-width: 640px) {
            .doc-crest { width: 46px; height: 46px; }
            .doc-org-name { font-size: 1.1875rem; }
            .doc-title h1 { font-size: 1.25rem; }
        }

        /* Motion is not used on this page; this is a guard against anything
           inherited or added later. */
        @@media all and (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.001ms !important;
                scroll-behavior: auto !important;
            }
        }

        /* A pharmacist may staple this to the script. Keep the verdict readable. */
        @@media print {
            body { background: #fff; }
            .topbar, .verify-footer, .scroll-hint { display: none !important; }
            .page-wrap { padding: 0; }
            .doc { box-shadow: none; border-radius: 0; max-width: none; }
            .verdict, .verdict-head, .verdict-note, .pill {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .rx-table-wrap { overflow: visible; }
            table.rx-table { min-width: 0; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <a href="{{ url('/') }}" class="topbar-brand">
        <img src="{{ asset('brand/opescare-favicon.png') }}" alt="" width="24" height="24">
        <span>OpesCare</span>
    </a>
    <span class="topbar-tag">{!! $icon('scan-line', '0.875rem') !!}{{ __('verify.rx_doc_kicker') }}</span>
</header>

<div class="page-wrap">

    <article class="doc">

        {{-- Letterhead. The stamp is present only when something was actually
             verified, which also keeps every refusal byte-identical. --}}
        <div class="doc-letterhead">
            <div class="doc-ident">
                <img class="doc-crest" src="{{ asset('brand/opescare-favicon.png') }}" alt="" width="46" height="46">
                <div class="doc-org">
                    <div class="doc-org-name">OpesCare</div>
                    <div class="doc-org-sub">{{ __('verify.rx_platform_line') }}</div>
                </div>
            </div>
            @if($verifiedAt ?? null)
                <div class="doc-stamp">
                    {!! $icon('badge-check', '1.125rem') !!}
                    <div>
                        <div class="doc-stamp-label">{{ __('verify.rx_verified_label') }}</div>
                        <div class="doc-stamp-time">{{ $verifiedAt->translatedFormat('d M Y · H:i') }} UTC</div>
                    </div>
                </div>
            @endif
        </div>

        <div class="doc-title">
            <h1>{{ __('verify.rx_heading') }}</h1>
            <p>{{ __('verify.rx_subheading') }}</p>
        </div>

        <div class="doc-body">

            @if(($error ?? null) || $rx === null)
                {{-- ONE refusal for invalid, expired, revoked and unknown. The
                     controller hands every one of them the same string. --}}
                <div class="verdict verdict--void" role="alert">
                    <div class="verdict-head">
                        <span class="verdict-mark">{!! $icon('circle-x', '1.75rem') !!}</span>
                        <h2 class="verdict-title">{{ __('verify.rx_invalid_title') }}</h2>
                    </div>
                    <p class="verdict-note">{{ $error ?? __('verify.rx_invalid_body') }}</p>
                </div>

                <div class="note note--authority">
                    {!! $icon('lock', '1rem') !!}
                    <span>{{ __('verify.rx_authority_note') }}</span>
                </div>
            @else
                @php $verdict = $verdicts[$rx->state] ?? $verdicts['valid']; @endphp

                {{-- The verdict, before any data. --}}
                <div class="verdict verdict--{{ $verdict['tone'] }}" role="{{ $verdict['role'] }}">
                    <div class="verdict-head">
                        <span class="verdict-mark">{!! $icon($verdict['icon'], '1.75rem') !!}</span>
                        <h2 class="verdict-title">{{ $verdict['title'] }}</h2>
                    </div>
                    <p class="verdict-note">
                        @switch($rx->state)
                            @case('retracted')
                                {{ __('verify.rx_retracted_body', ['status' => $rx->status_label]) }}
                                @break
                            @case('expired')
                                {{ __('verify.rx_expired_body') }}
                                @break
                            @case('dispensed')
                                {{ __('verify.rx_dispensed_body') }}
                                @break
                            @default
                                {{ __('verify.rx_valid_body') }}
                        @endswitch
                    </p>
                </div>

                <div class="ref">
                    <div class="ref-label">{{ __('verify.rx_reference_label') }}</div>
                    <div class="ref-value">{{ $rx->reference ?: __('verify.rx_no_reference') }}</div>
                    <div class="ref-hint">{{ __('verify.rx_reference_hint') }}</div>
                </div>

                <div class="note note--authority">
                    {!! $icon('lock', '1rem') !!}
                    <span>{{ __('verify.rx_authority_note') }}</span>
                </div>

                {{-- Name and Health ID. Nothing else about the patient exists on
                     this page, and nothing else may be added to it. --}}
                <h2 class="sect-title">{!! $icon('user-round', '0.875rem') !!}{{ __('verify.rx_section_patient') }}</h2>
                <div class="identity">
                    <div>
                        <div class="field-label">{{ __('verify.rx_patient') }}</div>
                        <div class="field-value">{{ $rx->patient_name ?: __('verify.rx_not_recorded') }}</div>
                    </div>
                    <div>
                        <div class="field-label">{{ __('verify.rx_health_id') }}</div>
                        <div class="field-value mono">{{ $rx->patient_health_id ?: __('verify.rx_not_recorded') }}</div>
                    </div>
                </div>

                <h2 class="sect-title">{!! $icon('file-text', '0.875rem') !!}{{ __('verify.rx_section_order') }}</h2>
                <div class="field-grid">
                    <div>
                        <div class="field-label">{{ __('verify.rx_facility') }}</div>
                        <div class="field-value">{{ $rx->facility_name ?: __('verify.rx_not_recorded') }}</div>
                    </div>
                    <div>
                        <div class="field-label">{{ __('verify.rx_prescriber') }}</div>
                        <div class="field-value">{{ $rx->prescriber_name ?: __('verify.rx_not_recorded') }}</div>
                    </div>
                    <div>
                        <div class="field-label">{{ __('verify.rx_prescribed_at') }}</div>
                        <div class="field-value">{{ $rx->prescribed_at ? $rx->prescribed_at->translatedFormat('d M Y H:i') : __('verify.rx_not_recorded') }}</div>
                    </div>
                    <div>
                        <div class="field-label">{{ __('verify.rx_expires_at') }}</div>
                        <div class="field-value">{{ $rx->expires_at ? $rx->expires_at->translatedFormat('d M Y') : __('verify.rx_no_expiry') }}</div>
                    </div>
                    <div>
                        <div class="field-label">{{ __('verify.rx_status') }}</div>
                        <div class="field-value">{{ $rx->status_label }}</div>
                    </div>
                    <div>
                        <div class="field-label">{{ __('verify.rx_dispensed_at') }}</div>
                        <div class="field-value {{ $rx->dispensed_at ? '' : 'muted' }}">
                            {{ $rx->dispensed_at ? $rx->dispensed_at->translatedFormat('d M Y H:i') : __('verify.rx_not_dispensed') }}
                        </div>
                    </div>
                </div>

                <h2 class="sect-title">{!! $icon('pill', '0.875rem') !!}{{ __('verify.rx_section_medicines') }}</h2>
                @unless($rx->items->isEmpty())
                    <p class="scroll-hint">{!! $icon('move-horizontal', '0.875rem') !!}{{ __('verify.rx_table_scroll_hint') }}</p>
                @endunless
                {{-- The table scrolls inside this box; the page never does. --}}
                <div class="rx-table-wrap" tabindex="0" role="region" aria-label="{{ __('verify.rx_medicines_region') }}">
                    @if($rx->items->isEmpty())
                        <div class="rx-empty">{{ __('verify.rx_no_items') }}</div>
                    @else
                        <table class="rx-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('verify.rx_col_medicine') }}</th>
                                    <th scope="col">{{ __('verify.rx_col_dose') }}</th>
                                    <th scope="col">{{ __('verify.rx_col_frequency') }}</th>
                                    <th scope="col">{{ __('verify.rx_col_route') }}</th>
                                    <th scope="col">{{ __('verify.rx_col_duration') }}</th>
                                    <th scope="col">{{ __('verify.rx_col_quantity') }}</th>
                                    <th scope="col">{{ __('verify.rx_col_status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rx->items as $item)
                                    <tr>
                                        <th scope="row">{{ $item->drug_name ?: __('verify.rx_not_recorded') }}</th>
                                        <td>{{ $item->dose ?: '—' }}</td>
                                        <td>{{ $item->frequency ?: '—' }}</td>
                                        <td>{{ $item->route ?: '—' }}</td>
                                        <td>{{ $item->duration_days ? __('verify.rx_days', ['count' => $item->duration_days]) : '—' }}</td>
                                        <td>{{ $item->quantity ?: '—' }}</td>
                                        <td>
                                            @php
                                                $pill = match ($item->status) {
                                                    'dispensed' => 'pill-dispensed',
                                                    'pending'   => 'pill-pending',
                                                    'voided', 'cancelled', 'entered_in_error' => 'pill-stopped',
                                                    default     => 'pill-neutral',
                                                };
                                            @endphp
                                            <span class="pill {{ $pill }}">{{ $item->status_label }}</span>
                                            @if($item->dispensed_at)
                                                <div class="pill-when">{{ $item->dispensed_at->translatedFormat('d M Y') }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="doc-notes">
                    <div class="note note--muted">
                        {!! $icon('shield-check', '0.875rem') !!}
                        <span>{{ __('verify.rx_audit_note') }}</span>
                    </div>
                    <div class="note note--muted">
                        {!! $icon('eye-off', '0.875rem') !!}
                        <span>{{ __('verify.rx_privacy_note') }}</span>
                    </div>
                </div>
            @endif

        </div>
    </article>

    <div class="verify-footer">
        <a href="{{ route('verify.health-id') }}">{{ __('verify.footer_manual_verify') }}</a>
        <span class="sep">·</span>
        <a href="{{ route('public.help') }}">{{ __('verify.footer_help') }}</a>
        <span class="sep">·</span>
        <a href="{{ route('public.contact') }}">{{ __('verify.footer_contact') }}</a>
    </div>

</div>

</body>
</html>

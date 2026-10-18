<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('verify.qr_title') }}</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0E1729">
    @include('partials.favicons')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @include('public.partials._od_pages_css')
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <style>
        {{--
            The provider's side of the OPESCARE Secure Health ID: the page a
            scanner lands on. Same visual language as the credential in the
            patient app (assets under public/images/health-id, cut from the
            same library), on the brand palette — navy ground, #0F4C81 accent,
            white result card. Four states: verified · expired · revoked · invalid.
        --}}
        :root {
            --navy: #0E1729; --deep: #0C3F6C; --brand: #0F4C81; --soft: #EAF1F8; --light: #3D77AC;
            --ink: #1A2338; --muted: #5B6472; --faint: #93989F; --line: #E6EAF0; --surface: #FFFFFF;
            --ok: #1E8E5A; --warn: #B8860B; --warn-bg: #FBF3DF; --bad: #D64545; --bad-bg: #FCE8E8;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { min-height: 100%; }
        body {
            font-family: Inter, 'Segoe UI', system-ui, sans-serif;
            color: var(--ink);
            background: var(--navy);
            display: flex; flex-direction: column; min-height: 100vh; position: relative; overflow-x: hidden;
        }
        .bg { position: fixed; inset: 0; pointer-events: none; z-index: 0; }
        .bg .field { display: none; } /* glow layers removed: flat navy ground only */
        .bg .helix { display: none; } /* glow layers removed: flat navy ground only */
        .bg .wave { display: none; } /* glow layers removed: flat navy ground only */

        .topbar { position: relative; z-index: 1; padding: .9rem 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .brand { display: flex; align-items: center; gap: .6rem; color: #fff; text-decoration: none; font-weight: 700; font-size: 1.05rem; }
        .brand img { width: 28px; height: 28px; border-radius: 8px; background: #fff; padding: 3px; }
        .brand small { display: block; font-size: .62rem; font-weight: 500; letter-spacing: .16em; text-transform: uppercase; color: #C8DCEF; }
        .eyebrow { font-size: .62rem; letter-spacing: .18em; text-transform: uppercase; color: #C8DCEF; font-weight: 600; }

        .wrap { position: relative; z-index: 1; flex: 1; display: flex; flex-direction: column; align-items: center; padding: 1.25rem 1rem 3rem; }
        .card { width: 100%; max-width: 520px; background: var(--surface); border-radius: 16px; overflow: hidden; animation: rise .35s ease; }
        @keyframes rise { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

        .head { position: relative; padding: 1.4rem 1.5rem 1.25rem; color: #fff; background: var(--deep); overflow: hidden; }
        .head::before { content: ""; position: absolute; inset: 0; background: url("{{ asset('images/health-id/guilloche-texture.png') }}") center/cover no-repeat; opacity: .42; }
        .head .corner { position: absolute; width: 18px; height: 18px; border: 2px solid rgba(234,241,248,.6); }
        .head .tl { top: 10px; left: 10px; border-right: 0; border-bottom: 0; border-top-left-radius: 8px; }
        .head .tr { top: 10px; right: 10px; border-left: 0; border-bottom: 0; border-top-right-radius: 8px; }
        .head .bl { bottom: 10px; left: 10px; border-right: 0; border-top: 0; border-bottom-left-radius: 8px; }
        .head .br { bottom: 10px; right: 10px; border-left: 0; border-top: 0; border-bottom-right-radius: 8px; }
        .head-row { position: relative; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .head h1 { font-size: 1.2rem; font-weight: 700; line-height: 1.25; margin-top: .2rem; }
        .head p { font-size: .8rem; color: #D6E6F5; margin-top: .3rem; max-width: 34ch; }
        .seal { width: 72px; height: 72px; flex-shrink: 0; }

        .state { display: flex; align-items: center; gap: 1rem; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--line); }
        .state img { width: 64px; height: 64px; flex-shrink: 0; }
        .state h2 { font-size: 1.15rem; font-weight: 700; line-height: 1.2; }
        .state p  { font-size: .85rem; color: var(--muted); margin-top: .2rem; }
        .state.verified h2 { color: var(--ok); }
        .state.expired  h2 { color: var(--warn); }
        .state.revoked  h2, .state.invalid h2 { color: var(--bad); }

        .body { padding: 1.25rem 1.5rem 1.5rem; display: grid; gap: 1rem; }

        .holder { display: flex; align-items: center; gap: .9rem; padding: 1rem; border: 1px solid var(--line); border-radius: 16px; background: #FFFFFF; }
        .avatar { width: 52px; height: 52px; border-radius: 50%; background: var(--soft); color: var(--brand); display: grid; place-items: center; font-weight: 700; font-size: 1.05rem; flex-shrink: 0; border: 2px solid #fff; box-shadow: 0 0 0 1px var(--line); }
        .holder .name { font-weight: 700; font-size: 1.05rem; line-height: 1.2; }
        .holder .hid  { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .92rem; font-weight: 600; letter-spacing: .06em; color: var(--brand); margin-top: .15rem; word-break: break-all; }

        .rows { display: grid; grid-template-columns: repeat(3, 1fr); border: 1px solid var(--line); border-radius: 16px; overflow: hidden; }
        .rows > div { padding: .85rem .9rem; border-left: 1px solid var(--line); }
        .rows > div:first-child { border-left: 0; }
        .label { display: flex; align-items: center; gap: .35rem; font-size: .66rem; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--faint); }
        .label i { width: .8rem; height: .8rem; }
        .value { font-size: .95rem; font-weight: 600; margin-top: .25rem; }

        .meta { display: grid; gap: .55rem; padding: .25rem 0 0; }
        .meta div { display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; font-size: .85rem; }
        .meta dt { color: var(--muted); display: flex; align-items: center; gap: .4rem; }
        .meta dt i { width: .9rem; height: .9rem; color: var(--brand); }
        .meta dd { font-weight: 600; text-align: right; font-variant-numeric: tabular-nums; }
        .meta dd.mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: .08em; color: var(--brand); }

        .note { display: flex; gap: .6rem; align-items: flex-start; font-size: .8rem; color: var(--muted); padding: .9rem 1rem; border-radius: 14px; background: var(--soft); }
        .note i { width: 1rem; height: 1rem; flex-shrink: 0; color: var(--brand); margin-top: .1rem; }
        .note.bad { background: var(--bad-bg); color: #7A2626; } .note.bad i { color: var(--bad); }
        .note.warn { background: var(--warn-bg); color: #6B4E00; } .note.warn i { color: var(--warn); }

        .cta { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; width: 100%; padding: .85rem 1.25rem; border-radius: 8px; background: var(--brand); color: #fff; font-weight: 600; font-size: .95rem; text-decoration: none; }
        .cta i { width: 1rem; height: 1rem; }

        .sig { display: flex; align-items: center; justify-content: center; gap: 1.4rem; padding: 1.1rem 1.5rem 0; }
        .sig img { height: 44px; width: auto; opacity: .9; }
        .sig .s { height: 54px; }
        .sigline { text-align: center; font-size: .66rem; letter-spacing: .16em; text-transform: uppercase; color: var(--faint); font-weight: 600; padding: .5rem 1.5rem 1.4rem; }

        .footer { position: relative; z-index: 1; text-align: center; font-size: .78rem; color: #A9BBD0; margin-top: 1.5rem; }
        .footer a { color: #fff; text-decoration: none; font-weight: 600; }

        @media (max-width: 540px) {
            .head { padding: 1.2rem 1.15rem 1.1rem; }
            .state, .body { padding-left: 1.15rem; padding-right: 1.15rem; }
            .rows { grid-template-columns: 1fr; }
            .rows > div { border-left: 0; border-top: 1px solid var(--line); }
            .rows > div:first-child { border-top: 0; }
        }
        @media (prefers-reduced-motion: reduce) { .card { animation: none; } }
    </style>
</head>
<body>
<div class="bg" aria-hidden="true"><div class="field"></div><div class="helix"></div><div class="wave"></div></div>

<header class="topbar">
    <a href="{{ url('/') }}" class="brand">
        <img src="{{ asset('brand/opescare-mark-128.png') }}" alt="">
        <span>OpesCare<small>{{ __('verify.secure_eyebrow') }}</small></span>
    </a>
    <span class="eyebrow">{{ __('verify.badge_provider_tool') }}</span>
</header>

<main class="wrap">
    @php
        $glyph = match ($state) {
            'verified' => 'indicator-verified',
            'expired'  => 'indicator-expired',
            'revoked'  => 'indicator-revoked',
            default    => 'indicator-invalid',
        };
        $stateTitle = match ($state) {
            'verified' => __('verify.result_verified'),
            'expired'  => __('verify.qr_expired_title'),
            'revoked'  => __('verify.qr_revoked_title'),
            default    => __('verify.qr_invalid_title'),
        };
        $stateBody = match ($state) {
            'verified' => __('verify.result_valid_sub'),
            'expired'  => __('verify.qr_expired_body'),
            'revoked'  => __('verify.qr_revoked_body'),
            default    => __('verify.qr_invalid_body'),
        };
    @endphp

    <article class="card" aria-labelledby="qr-title">
        <div class="head">
            <span class="corner tl"></span><span class="corner tr"></span><span class="corner bl"></span><span class="corner br"></span>
            <div class="head-row">
                <div>
                    <span class="eyebrow">{{ __('verify.secure_eyebrow') }}</span>
                    <h1 id="qr-title">{{ __('verify.qr_heading') }}</h1>
                    <p>{{ __('verify.qr_subheading') }}</p>
                </div>
                <img class="seal" src="{{ asset('images/health-id/seal.png') }}" alt="">
            </div>
        </div>

        <section class="state {{ $state }}" role="status" aria-live="polite">
            <img src="{{ asset('images/health-id/' . $glyph . '.png') }}" alt="">
            <div>
                <h2>{{ $stateTitle }}</h2>
                <p>{{ $stateBody }}</p>
            </div>
        </section>

        <div class="body">
            @if($state === 'verified' && $result)
                @php
                    $initials = collect(preg_split('/\s+/', trim((string) $result->name)))->filter()->take(2)
                        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '—';
                    $sexKey = strtolower((string) ($result->sex ?? ''));
                    $sexLabel = in_array($sexKey, ['male', 'female', 'other'], true) ? __('verify.sex_' . $sexKey) : '—';
                @endphp
                <div class="holder">
                    <div class="avatar" aria-hidden="true">{{ $initials }}</div>
                    <div>
                        <div class="name">{{ $result->name ?? '—' }}</div>
                        <div class="hid">{{ $result->health_id ?? '—' }}</div>
                    </div>
                </div>

                <div class="rows">
                    <div>
                        <div class="label"><i data-lucide="calendar"></i>{{ __('verify.result_dob') }}</div>
                        <div class="value">{{ isset($result->dob) ? \Carbon\Carbon::parse($result->dob)->translatedFormat('d M Y') : '—' }}</div>
                    </div>
                    <div>
                        <div class="label"><i data-lucide="user"></i>{{ __('verify.result_sex') }}</div>
                        <div class="value">{{ $sexLabel }}</div>
                    </div>
                    <div>
                        <div class="label"><i data-lucide="droplet"></i>{{ __('verify.result_blood_type') }}</div>
                        <div class="value">{{ $result->blood_type ?? '—' }}</div>
                    </div>
                </div>

                <dl class="meta">
                    <div><dt><i data-lucide="clock"></i>{{ __('verify.meta_verified_at') }}</dt><dd>{{ $verified_at->translatedFormat('d M Y, H:i') }}</dd></div>
                    @if($expires_at)
                    <div><dt><i data-lucide="timer"></i>{{ __('verify.meta_valid_until') }}</dt><dd>{{ $expires_at->translatedFormat('H:i') }}</dd></div>
                    @endif
                    @if($reference)
                    <div><dt><i data-lucide="hash"></i>{{ __('verify.meta_reference') }}</dt><dd class="mono">{{ $reference }}</dd></div>
                    @endif
                </dl>

                <div class="note"><i data-lucide="lock"></i><span>{{ __('verify.qr_provider_note') }} {{ __('verify.qr_scan_note') }}</span></div>
            @else
                <div class="note {{ $state === 'expired' ? 'warn' : 'bad' }}"><i data-lucide="{{ $state === 'expired' ? 'clock' : 'x-circle' }}"></i><span>{{ __('verify.qr_token_expired_ui') }}</span></div>
                <a class="cta" href="{{ route('verify.health-id') }}"><i data-lucide="search"></i>{{ __('verify.footer_manual_verify') }}</a>
            @endif
        </div>

        <div class="sig" aria-hidden="true">
            <img src="{{ asset('images/health-id/fingerprint.png') }}" alt="">
            <img class="s" src="{{ asset('images/health-id/verification-shield.png') }}" alt="">
            <img src="{{ asset('images/health-id/africa.png') }}" alt="">
        </div>
        <div class="sigline">{{ __('verify.secure_eyebrow') }}</div>
    </article>

    <div class="footer">
        <p>
            <a href="{{ route('verify.health-id') }}">{{ __('verify.footer_manual_verify') }}</a>
            &nbsp;·&nbsp;
            <a href="{{ route('public.help') }}">{{ __('verify.footer_help') }}</a>
            &nbsp;·&nbsp;
            <a href="{{ route('public.contact') }}">{{ __('verify.footer_contact') }}</a>
        </p>
    </div>
</main>

<script @cspNonce>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
</body>
</html>

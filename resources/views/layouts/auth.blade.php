{{--
    OpesCare Finder — auth shell (mobile-first).

    Single-column card on --f-ground: slim header (brand + EN/FR switcher),
    centered content column, help footer. Styling comes from the foundation
    tokens in public/assets/finder.css plus the scoped .f-auth layer below —
    the old split-sidebar chrome and its webfonts are gone.

    public/css/auth.css stays linked ONLY for pages that still wear the legacy
    classes (organization/developer/hospital registration, the portal
    profile-completion pages, the demo panel). The .f-auth layer loads after it
    and re-bases the shared form vocabulary (.auth-input, .auth-btn, alerts…)
    onto the foundation tokens, so legacy pages sit coherently inside the new
    chrome without any behavioural change. Remove that link once those pages
    are migrated.

    Behaviour contract (unchanged — this surface is security-hardened):
      - every form keeps its field names, CSRF, routes, throttles and flash keys
      - password toggles on redesigned pages use [data-f-toggle] (inline JS
        below); legacy pages keep [data-toggle-password] via public/js/auth.js
      - icons on redesigned pages come from partials/finder/icon (inline SVG);
        lucide.min.js remains only for legacy pages' <i data-lucide> tags
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    @hasSection('title')<title>@yield('title') — OpesCare Finder</title>@else<title>{{ __('finder_shell.meta.default_title') }}</title>@endif
    <meta name="description" content="{{ __('finder_shell.meta.default_description') }}">
    <meta name="robots" content="noindex,nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Favicons + theme-color #0F4C81 --}}
    @include('partials.favicons')
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    {{-- Foundation tokens + components (cache-busted by mtime) --}}
    <link rel="stylesheet" href="{{ asset('assets/finder.css') }}?v={{ @filemtime(public_path('assets/finder.css')) ?: '1' }}">
    {{-- Legacy auth classes — see note above; loads BEFORE the .f-auth layer --}}
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ @filemtime(public_path('css/auth.css')) ?: '1' }}">

    <style>
    /* == .f-auth — auth-surface layer on the foundation tokens ============= */
    body.f-auth{min-height:100vh;min-height:100dvh;display:flex;flex-direction:column;margin:0;background:var(--f-ground);color:var(--f-ink);font-family:var(--f-font)}

    /* Chrome */
    .f-auth__header{background:var(--f-surface);border-bottom:1px solid var(--f-line)}
    .f-auth__row{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:var(--f-header-h)}
    .f-auth__main{flex:1;width:100%;padding:24px 16px 40px}
    @media (min-width:640px){.f-auth__main{padding-top:40px}}
    .f-auth__footer{padding:0 16px 28px;text-align:center;font-size:.8125rem;color:var(--f-muted)}
    .f-auth__footer p{margin:0}
    .f-auth__footer a{color:var(--f-brand);font-weight:700;text-decoration:none}
    .f-auth__footer a:hover{text-decoration:underline}

    /* Column: aligns back-links and cards; card carries the surface */
    .f-auth-col{width:100%;max-width:400px;margin-inline:auto}
    .f-auth-col--wide{max-width:40rem}
    .f-auth-card{background:var(--f-surface);border:1px solid var(--f-line);border-radius:16px;padding:24px 20px;box-shadow:var(--f-shadow)}
    @media (min-width:480px){.f-auth-card{padding:32px 28px}}
    .f-auth-card--center{text-align:center}

    /* Headings */
    .f-auth-title{font-size:1.375rem;line-height:1.25;font-weight:800;letter-spacing:-.015em;color:var(--f-ink);margin:0 0 6px}
    .f-auth-sub{font-size:.9375rem;line-height:1.55;color:var(--f-muted);margin:0 0 20px}
    .f-auth-badge{width:48px;height:48px;border-radius:14px;background:var(--f-wash);color:var(--f-brand);display:flex;align-items:center;justify-content:center;margin-bottom:16px}
    .f-auth-badge--center{margin-inline:auto}
    .f-auth-badge--good{background:var(--f-good-wash);color:var(--f-good)}
    .f-auth-badge--warn{background:var(--f-warn-wash);color:var(--f-warn)}
    .f-auth-badge--danger{background:var(--f-danger-wash);color:var(--f-danger)}

    /* Fields — .f-input/.f-label/.f-field come from the foundation */
    .f-auth-labelrow{display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:6px}
    .f-auth-labelrow .f-label{margin-bottom:0}
    .f-auth-labelrow a{font-size:.8125rem;font-weight:700;color:var(--f-brand);text-decoration:none}
    .f-auth-labelrow a:hover{text-decoration:underline}
    .f-auth-error{display:flex;align-items:flex-start;gap:6px;font-weight:600}
    .f-auth-error .f-icon{margin-top:2px}

    /* Password visibility toggle */
    .f-auth-pass{position:relative}
    .f-auth-pass .f-input{padding-right:52px}
    .f-auth-toggle{position:absolute;right:5px;top:4px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;border:none;background:transparent;border-radius:9px;color:var(--f-muted);cursor:pointer;padding:0}
    .f-auth-toggle:hover{background:var(--f-wash);color:var(--f-brand-ink)}
    .f-auth-toggle span{display:inline-flex}
    .f-auth-toggle .f-auth-toggle__hide{display:none}
    .f-auth-toggle.is-on .f-auth-toggle__show{display:none}
    .f-auth-toggle.is-on .f-auth-toggle__hide{display:inline-flex}

    /* Checkbox row */
    .f-auth-check{display:flex;align-items:flex-start;gap:10px;margin:2px 0 16px}
    .f-auth-check input[type=checkbox]{width:18px;height:18px;margin:2px 0 0;accent-color:var(--f-brand);flex-shrink:0}
    .f-auth-check label{font-size:.875rem;line-height:1.5;color:var(--f-ink-soft)}

    /* Alerts (flash + validation summaries) */
    .f-auth-alert{display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:var(--f-r);font-size:.875rem;line-height:1.5;margin-bottom:16px}
    .f-auth-alert .f-icon{margin-top:2px}
    .f-auth-alert ul{margin:0;padding-left:18px}
    .f-auth-alert--danger{background:var(--f-danger-wash);color:var(--f-danger)}
    .f-auth-alert--success{background:var(--f-good-wash);color:var(--f-good)}
    .f-auth-alert--warn{background:var(--f-warn-wash);color:var(--f-warn)}

    /* Big CTAs — the foundation button, sized for thumbs on this surface */
    .f-auth-col .f-btn--block{min-height:50px;font-size:1rem;font-weight:700}

    /* Secondary actions under / inside the card */
    .f-auth-alt{margin-top:20px;text-align:center;font-size:.875rem;color:var(--f-muted)}
    .f-auth-alt p{margin:0}
    .f-auth-alt a{color:var(--f-brand);font-weight:700;text-decoration:none}
    .f-auth-alt a:hover{text-decoration:underline}
    .f-auth-alt--divide{border-top:1px solid var(--f-line);padding-top:16px}
    .f-auth-back{display:inline-flex;align-items:center;gap:6px;font-size:.875rem;font-weight:650;color:var(--f-muted);text-decoration:none;margin-bottom:12px}
    .f-auth-back:hover{color:var(--f-brand-ink)}

    /* Quiet informational notes */
    .f-auth-note{display:flex;align-items:flex-start;gap:8px;margin-top:20px;font-size:.8125rem;line-height:1.55;color:var(--f-muted);text-align:left}
    .f-auth-note .f-icon{color:var(--f-brand);margin-top:2px}
    .f-auth-note p{margin:0}
    .f-auth-fine{font-size:.8125rem;line-height:1.55;color:var(--f-muted);margin:16px 0 0}
    .f-auth-fine a{color:var(--f-brand);font-weight:650}
    .f-auth-benefit{display:flex;align-items:flex-start;gap:8px;background:var(--f-wash);border-radius:var(--f-r);padding:10px 12px;font-size:.8125rem;line-height:1.5;color:var(--f-brand-ink);margin:0 0 20px}
    .f-auth-benefit .f-icon{margin-top:1px;color:var(--f-brand)}

    /* Key/value meta boxes (invite summary, pending application) */
    .f-auth-meta{background:var(--f-ground);border:1px solid var(--f-line);border-radius:var(--f-r);padding:14px 16px;margin-bottom:20px;text-align:left}
    .f-auth-meta__title{font-size:.6875rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--f-brand);margin:0 0 8px}
    .f-auth-meta__row{display:flex;align-items:baseline;justify-content:space-between;gap:12px;padding:6px 0;border-bottom:1px solid var(--f-line);font-size:.8125rem}
    .f-auth-meta__row:last-child{border-bottom:none}
    .f-auth-meta__row--block{flex-direction:column;align-items:stretch;gap:4px}
    .f-auth-meta__label{color:var(--f-muted);font-weight:500;flex-shrink:0}
    .f-auth-meta__value{color:var(--f-ink);font-weight:650;text-align:right;overflow-wrap:anywhere}
    .f-auth-meta__value--brand{color:var(--f-brand);font-weight:800}
    .f-auth-meta__value--warn{color:var(--f-warn)}
    .f-auth-meta__value--mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:700}
    .f-auth-meta__note{font-size:.8125rem;line-height:1.5;color:var(--f-muted);background:var(--f-surface);border:1px dashed var(--f-line);border-radius:8px;padding:10px 12px;text-align:left}

    /* Section heading inside long forms */
    .f-auth-section{font-size:.6875rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--f-brand);margin:20px 0 10px}

    /* Two side-by-side fields that stack on small screens */
    .f-auth-2col{display:grid;grid-template-columns:1fr;gap:0 14px}
    @media (min-width:480px){.f-auth-2col{grid-template-columns:1fr 1fr}}

    /* Account-type selector (signup) */
    .f-auth-grid{display:grid;grid-template-columns:1fr;gap:10px;margin-top:4px}
    @media (min-width:560px){.f-auth-grid{grid-template-columns:1fr 1fr}}
    .f-auth-choice{display:flex;align-items:center;gap:12px;padding:13px 14px;border:1px solid var(--f-line);border-radius:var(--f-r);background:var(--f-surface);text-decoration:none;transition:border-color .15s ease,box-shadow .15s ease}
    .f-auth-choice:hover{border-color:var(--f-brand);box-shadow:var(--f-shadow)}
    .f-auth-choice__icon{width:40px;height:40px;border-radius:11px;background:var(--f-wash);color:var(--f-brand);display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .f-auth-choice__body{flex:1;min-width:0}
    .f-auth-choice__title{display:block;font-size:.9375rem;font-weight:700;color:var(--f-ink);line-height:1.3}
    .f-auth-choice__desc{display:block;font-size:.8125rem;color:var(--f-muted);line-height:1.45;margin-top:2px}
    .f-auth-choice__go{color:var(--f-muted);flex-shrink:0}
    .f-auth-choice:hover .f-auth-choice__go{color:var(--f-brand)}

    /* Facility selector cards (names/ids are load-bearing for its JS) */
    .f-auth .facility-card{display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px solid var(--f-line);border-radius:var(--f-r);background:var(--f-surface);margin-bottom:10px;cursor:pointer;transition:border-color .15s ease,background-color .15s ease}
    .f-auth .facility-card:hover,.f-auth .facility-card.active{border-color:var(--f-brand);background:var(--f-wash)}
    .f-auth .facility-card.disabled{opacity:.55;cursor:not-allowed;pointer-events:none}
    .f-auth .facility-card__icon{width:40px;height:40px;border-radius:11px;background:var(--f-wash);color:var(--f-brand);display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .f-auth .facility-card__body{flex:1;min-width:0}
    .f-auth .facility-card__name{font-size:.9375rem;font-weight:700;color:var(--f-ink);line-height:1.3}
    .f-auth .facility-card__meta{font-size:.8125rem;color:var(--f-muted)}

    /* == Legacy compatibility — re-base old auth classes onto the tokens ==
       Pages not yet redesigned (organization/developer/hospital registration,
       portal profile completion) keep their markup; these rules override
       css/auth.css so they sit coherently inside the new chrome. */
    .f-auth .auth-card{max-width:40rem;margin-inline:auto;background:var(--f-surface);border:1px solid var(--f-line);border-radius:16px;box-shadow:var(--f-shadow);padding:24px 20px}
    @media (min-width:480px){.f-auth .auth-card{padding:32px 28px}}
    .f-auth .auth-headline{font-size:1.375rem;line-height:1.25;font-weight:800;letter-spacing:-.015em;color:var(--f-ink)}
    .f-auth .auth-subheadline,.f-auth .auth-card__sub{color:var(--f-muted)}
    .f-auth .auth-input,.f-auth .form-input{height:48px;padding:0 14px;border:1px solid var(--f-line);border-radius:var(--f-r);background:var(--f-surface);color:var(--f-ink);font-size:1rem}
    .f-auth textarea.auth-input,.f-auth textarea.form-input{height:auto;min-height:96px;padding-block:12px}
    .f-auth .auth-input:focus,.f-auth .form-input:focus{border-color:var(--f-brand);box-shadow:0 0 0 3px var(--f-wash)}
    .f-auth .auth-input--error,.f-auth .auth-input-error,.f-auth .form-input--error{border-color:var(--f-danger)}
    .f-auth .auth-label,.f-auth .form-label{color:var(--f-ink-soft)}
    .f-auth .auth-field-error,.f-auth .field-error{color:var(--f-danger)}
    .f-auth .auth-btn{min-height:48px;border-radius:var(--f-r);width:100%}
    .f-auth .stepper-actions .auth-btn{width:auto}
    .f-auth .auth-btn-primary{background:var(--f-brand);color:#fff}
    .f-auth .auth-btn-primary:hover{background:var(--f-brand-ink)}
    .f-auth .auth-btn-secondary{background:var(--f-surface);color:var(--f-brand);border:1px solid var(--f-line)}
    .f-auth .auth-btn-secondary:hover{border-color:var(--f-brand);background:var(--f-wash)}
    .f-auth .auth-alert-danger,.f-auth .auth-alert--error{background:var(--f-danger-wash);color:var(--f-danger);border:none}
    .f-auth .auth-alert-success,.f-auth .auth-alert--success{background:var(--f-good-wash);color:var(--f-good);border:none}
    .f-auth .auth-footer-link,.f-auth .auth-alt-action a{color:var(--f-brand)}
    .f-auth .demo-panel{width:100%;max-width:400px;margin:24px auto 0}
    .f-auth .auth-hidden{display:none !important}
    </style>
</head>
<body class="f-auth">

    <a class="f-skip" href="#f-main">{{ __('finder_shell.a11y.skip_to_content') }}</a>

    <header class="f-auth__header">
        <div class="f-container f-auth__row">
            <a href="{{ route('public.landing') }}" class="f-brand" aria-label="{{ __('finder_shell.a11y.home') }}">
                @include('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 22, 'class' => 'f-brand__mark'])
                <span>OpesCare&nbsp;<span class="f-brand__finder">Finder</span></span>
            </a>

            <div class="f-lang" aria-label="{{ __('finder_shell.a11y.language') }}">
                <a href="{{ route('lang.switch', 'en') }}" @class(['is-active' => app()->getLocale() === 'en']) lang="en" hreflang="en">EN</a>
                <a href="{{ route('lang.switch', 'fr') }}" @class(['is-active' => app()->getLocale() === 'fr']) lang="fr" hreflang="fr">FR</a>
            </div>
        </div>
    </header>

    <main id="f-main" class="f-auth__main">
        @yield('content')
    </main>

    <footer class="f-auth__footer">
        <p>{{ __('finder_auth.chrome.need_help') }}
            <a href="{{ route('public.contact') }}">{{ __('finder_auth.chrome.contact_support') }}</a>
        </p>
    </footer>

    @yield('scripts')

    {{-- Password visibility toggle for redesigned pages ([data-f-toggle]).
         Minimal by design; everything else on this surface works without JS. --}}
    <script @cspNonce>
    (function () {
        'use strict';
        document.querySelectorAll('[data-f-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.getAttribute('data-f-toggle'));
                if (!input) return;
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.classList.toggle('is-on', show);
                btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            });
        });
    })();
    </script>

    {{-- Legacy pages only: <i data-lucide> icons, [data-toggle-password] and
         the demo panel are wired by these two files. Redesigned pages do not
         depend on them. Remove both once the legacy pages are migrated. --}}
    <script src="{{ asset('js/lucide.min.js') }}?v={{ @filemtime(public_path('js/lucide.min.js')) ?: '1' }}" defer></script>
<script src="{{ asset('js/op-actions.js') }}?v={{ @filemtime(public_path('js/op-actions.js')) ?: '1' }}" defer></script>
    <script src="{{ asset('js/auth.js') }}?v={{ @filemtime(public_path('js/auth.js')) ?: '1' }}" defer></script>
</body>
</html>

@extends('layouts.finder')

@section('title', __('account_deletion.web.page_title'))
@section('description', __('account_deletion.web.meta_description'))

{{--
    Publicly reachable account-deletion instructions.

    Google Play requires this URL: a reviewer, or someone who already
    uninstalled the app, must be able to find out how to delete their account
    without signing in. Every number on the page comes from
    AccountDeletionService via the controller, so the page and the app can
    never quote different grace periods.

    Every string goes through __('account_deletion.*'), which is 1:1 EN/FR and
    gated by php scripts/i18n-audit.php. Brand colour #0F4C81; no emoji, no
    icon font — the checkmarks and the rule below are CSS, not characters.
--}}

@section('content')

    <style>
        .ad-wrap { width: 100%; max-width: 860px; margin-inline: auto; padding-inline: 16px; }
        .ad-hero { padding: clamp(40px, 7vw, 72px) 0 1.5rem; background: var(--f-surface); text-align: center; }
        .ad-hero h1 { font-size: clamp(1.6rem, 4vw, 2.4rem); line-height: 1.15; margin: 0 0 .75rem; }
        .ad-badge {
            display: inline-block; font-size: .75rem; font-weight: 700; letter-spacing: .05em;
            text-transform: uppercase; padding: .35rem 1rem; border-radius: 999px;
            background: rgba(15, 76, 129, .12); color: #0F4C81; margin-bottom: 1rem;
        }
        .ad-lede { color: var(--f-muted); max-width: 640px; margin: 0 auto; }
        .ad-body { padding: 2.5rem 0 4rem; }
        .ad-section { margin-bottom: 2.75rem; }
        .ad-section h2 { font-size: 1.25rem; margin: 0 0 .85rem; color: #0F4C81; }
        .ad-steps { list-style: none; margin: 0; padding: 0; counter-reset: adstep; }
        .ad-steps li {
            position: relative; padding: 0 0 1rem 2.75rem; counter-increment: adstep;
            line-height: 1.6;
        }
        .ad-steps li::before {
            content: counter(adstep);
            position: absolute; left: 0; top: -2px;
            width: 1.9rem; height: 1.9rem; border-radius: 999px;
            display: grid; place-items: center;
            background: #0F4C81; color: #fff; font-size: .8125rem; font-weight: 700;
        }
        .ad-card {
            border: 1px solid var(--f-line); border-radius: 12px;
            padding: 1.25rem 1.35rem; background: var(--f-surface);
        }
        .ad-card + .ad-card { margin-top: .85rem; }
        .ad-card h3 { margin: 0 0 .5rem; font-size: 1rem; }
        .ad-card p { margin: 0; color: var(--f-muted); line-height: 1.6; }
        .ad-list { margin: .5rem 0 0; padding-left: 1.15rem; color: var(--f-muted); line-height: 1.7; }
        .ad-kept { border-left: 3px solid #0F4C81; padding-left: 1rem; }
        .ad-cannot { border-left: 3px solid #B45309; padding-left: 1rem; }
        .ad-meta { font-size: .8125rem; color: var(--f-muted); border-top: 1px solid var(--f-line); padding-top: 1rem; }
        .ad-contact a { color: #0F4C81; font-weight: 600; }
    </style>

    <header class="ad-hero">
        <div class="ad-wrap">
            <div class="ad-badge">{{ __('account_deletion.web.badge') }}</div>
            <h1>{{ __('account_deletion.web.hero_title') }}</h1>
            <p class="ad-lede">{{ __('account_deletion.web.hero_subtitle') }}</p>
        </div>
    </header>

    <div class="ad-body">
        <div class="ad-wrap">

            {{-- How --}}
            <section class="ad-section">
                <h2>{{ __('account_deletion.web.how_title') }}</h2>
                <p style="color:var(--f-muted);margin-top:0;">{{ __('account_deletion.web.how_intro') }}</p>
                <ol class="ad-steps">
                    <li>{{ __('account_deletion.web.step_app') }}</li>
                    <li>{{ __('account_deletion.web.step_confirm') }}</li>
                    <li>{{ __('account_deletion.web.step_grace', ['days' => $graceDays]) }}</li>
                </ol>
            </section>

            {{-- Erased --}}
            <section class="ad-section">
                <h2>{{ __('account_deletion.erased.title') }}</h2>
                <p style="color:var(--f-muted);margin-top:0;">{{ __('account_deletion.erased.note') }}</p>
                <ul class="ad-list">
                    @foreach (['sign_in', 'password', 'pin', 'sessions', 'devices', 'phone', 'email', 'address', 'emergency_contact', 'national_id', 'referral_code'] as $item)
                        <li>{{ __('account_deletion.erased.items.' . $item) }}</li>
                    @endforeach
                </ul>
            </section>

            {{-- De-identified --}}
            <section class="ad-section">
                <h2>{{ __('account_deletion.deidentified.title') }}</h2>
                <p style="color:var(--f-muted);margin-top:0;">{{ __('account_deletion.deidentified.note') }}</p>
                <ul class="ad-list">
                    @foreach (['name', 'record_marked'] as $item)
                        <li>{{ __('account_deletion.deidentified.items.' . $item) }}</li>
                    @endforeach
                </ul>
            </section>

            {{-- Retained --}}
            <section class="ad-section ad-kept">
                <h2>{{ __('account_deletion.retained.title') }}</h2>
                <p style="color:var(--f-muted);margin-top:0;">{{ __('account_deletion.retained.note') }}</p>
                <p style="color:var(--f-muted);">{{ __('account_deletion.retained.legal_basis', ['years' => $retentionYears]) }}</p>
            </section>

            {{-- What we cannot delete. Named on the public page too, not only
                 in the app, so nobody has to install anything to find it out. --}}
            <section class="ad-section ad-cannot">
                <h2>{{ __('account_deletion.cannot.title') }}</h2>
                <ul class="ad-list">
                    @foreach (['clinical_events', 'access_logs', 'facility_copies', 'fiscal'] as $item)
                        <li>{{ __('account_deletion.cannot.items.' . $item) }}</li>
                    @endforeach
                </ul>
            </section>

            {{-- Timing --}}
            <section class="ad-section">
                <h2>{{ __('account_deletion.web.timing_title') }}</h2>
                <div class="ad-card">
                    <p>{{ __('account_deletion.web.timing_body', ['days' => $graceDays, 'years' => $retentionYears]) }}</p>
                </div>
            </section>

            {{-- Assisted route --}}
            <section class="ad-section">
                <h2>{{ __('account_deletion.web.no_app_title') }}</h2>
                <div class="ad-card ad-contact">
                    <p>{{ __('account_deletion.web.no_app_body', ['days' => $graceDays]) }}</p>
                    <p style="margin-top:.75rem;">
                        <strong>{{ __('account_deletion.web.contact_label') }}:</strong>
                        <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                    </p>
                </div>
            </section>

            <p class="ad-meta">
                {{ __('account_deletion.web.last_updated') }}: {{ $lastUpdated }}
            </p>

        </div>
    </div>

@endsection

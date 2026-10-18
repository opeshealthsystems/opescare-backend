@extends('layouts.finder')

@section('title', __('public.terms_page.page_title'))
@section('description', __('public.terms_page.meta_description'))

@push('head')
    @include('public.partials._od_pages_css')
@endpush

@section('content')

    {{--
        Shell restyle only — the legal wording below is untouched. This bridge
        maps the legacy public.css helper classes and --color-* variables the
        document markup uses onto the finder design tokens, so the text renders
        correctly inside layouts.finder without editing a single clause.
    --}}
    <style>
        :root {
            --color-primary: var(--f-brand);
            --color-text-primary: var(--f-ink);
            --color-text-muted: var(--f-muted);
            --color-border: var(--f-line);
            --color-surface: var(--f-surface);
        }
        .container { width: 100%; max-width: 1080px; margin-inline: auto; padding-inline: 16px; }
        .content-header { padding: clamp(40px, 7vw, 72px) 0 1.5rem; background: var(--f-surface); text-align: center; }
        .content-header h1 { font-size: clamp(1.6rem, 4vw, 2.4rem); line-height: 1.15; }
        .content-body { padding: 2rem 0 4rem; }
        .badge { display: inline-block; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; padding: 0.35rem 1rem; border-radius: 999px; }
        .text-muted { color: var(--f-muted); }
        .text-primary { color: var(--f-brand); }
        .font-bold { font-weight: 700; }
        .rich-text h2 { margin-top: 2.5rem; }
        .rich-text h3 { margin-top: 1.5rem; }
        .rich-text table { border-collapse: collapse; width: 100%; }
        .rich-text th, .rich-text td { border: 1px solid var(--f-line); padding: 0.5rem 0.75rem; text-align: left; }
    </style>

    <header class="content-header">
        <div class="container">
            <div class="badge odp-mb-16">{{ __('public.terms_page.badge') }}</div>
            <h1>{{ __('public.terms_page.hero_title') }}</h1>
            <p class="text-muted odp-measure">
                {{ __('public.terms_page.hero_subtitle') }}
            </p>
            <div class="odp-links">
                <span><strong>{{ __('public.terms_page.effective_date') }}</strong> {{ __('legal_terms.effective_date_value') }}</span>
                <span><strong>{{ __('public.terms_page.last_updated') }}</strong> {{ __('legal_terms.last_updated_value') }}</span>
                <span><strong>{{ __('public.terms_page.version') }}</strong> 2.0</span>
                <span><strong>{{ __('public.terms_page.governing_law_label') }}</strong> {{ __('legal_terms.governing_law_value') }}</span>
            </div>
        </div>
    </header>

    {{-- Quick nav --}}
    <section class="odp-band">
        <div class="container">
            <p class="odp-kicker">{{ __('public.terms_page.sections_label') }}</p>
            <div class="odp-row">
                @foreach([
                    'parties'              => 'parties',
                    'platform'             => 'platform',
                    'regulatory'           => 'regulatory',
                    'eligibility'          => 'eligibility',
                    'provider-obligations' => 'provider_obligations',
                    'patient-obligations'  => 'patient_obligations',
                    'data'                 => 'data',
                    'aup'                  => 'aup',
                    'ip'                   => 'ip',
                    'availability'         => 'availability',
                    'liability'            => 'liability',
                    'indemnity'            => 'indemnity',
                    'governing-law'        => 'governing_law',
                    'termination'          => 'termination',
                    'contact'              => 'contact',
                ] as $anchor => $key)
                <a class="odp-tag" href="#{{ $anchor }}">{{ __('legal_terms.nav.'.$key) }}</a>
                @endforeach
            </div>
        </div>
    </section>

    {{--
        Legal copy lives in lang/{en,fr}/legal_terms.php. Those strings are
        trusted, author-controlled HTML (inline strong/em/a only), hence the
        unescaped echoes. This page does not pass through LegalHtmlSanitizer;
        that sanitizer guards the DB-stored documents in public/legal/show.
    --}}
    <section class="content-body">
        <div class="container rich-text odp-wide">

            {{-- Acceptance banner --}}
            <div class="odp-panel odp-panel--warn odp-mb-48">
                <div class="odp-row odp-row--top">
                    <span class="odp-icon--warn odp-shrink">@include('partials.finder.icon', ['name' => 'alert-triangle', 'size' => 20])</span>
                    <p class="odp-m0">{!! __('legal_terms.acceptance') !!}</p>
                </div>
            </div>

            {{-- 1. Parties --}}
            <h2 id="parties">{{ __('legal_terms.s1_title') }}</h2>
            <p>{!! __('legal_terms.s1_provider') !!}</p>
            <p>{!! __('legal_terms.s1_user') !!}</p>
            <ul>
                @foreach(__('legal_terms.s1_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>

            {{-- 2. Platform Scope --}}
            <h2 id="platform">{{ __('legal_terms.s2_title') }}</h2>
            <p>{!! __('legal_terms.s2_intro') !!}</p>
            <ul>
                @foreach(__('legal_terms.s2_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>
            <p>{!! __('legal_terms.s2_not_provider') !!}</p>
            <p>{!! __('legal_terms.s2_strategy') !!}</p>

            {{-- 3. Regulatory Framework --}}
            <h2 id="regulatory">{{ __('legal_terms.s3_title') }}</h2>
            <p>{{ __('legal_terms.s3_intro') }}</p>
            <div class="odp-stack odp-mt-16 odp-mb-16">
                @foreach(__('legal_terms.s3_laws') as $law)
                <div class="odp-panel">
                    <p class="odp-bold">{{ $law['law'] }}</p>
                    <p class="odp-m0 odp-soft">{{ $law['desc'] }}</p>
                </div>
                @endforeach
            </div>

            {{-- 4. Eligibility --}}
            <h2 id="eligibility">{{ __('legal_terms.s4_title') }}</h2>
            <p>{{ __('legal_terms.s4_intro') }}</p>
            <ul>
                @foreach(__('legal_terms.s4_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>
            <p>{{ __('legal_terms.s4_outro') }}</p>

            {{-- 5. Healthcare Provider Obligations --}}
            <h2 id="provider-obligations">{{ __('legal_terms.s5_title') }}</h2>
            <p>{{ __('legal_terms.s5_intro') }}</p>
            <ul>
                @foreach(__('legal_terms.s5_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>

            {{-- 6. Patient Obligations --}}
            <h2 id="patient-obligations">{{ __('legal_terms.s6_title') }}</h2>
            <p>{{ __('legal_terms.s6_intro') }}</p>
            <ul>
                @foreach(__('legal_terms.s6_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>

            {{-- 7. Data Processing --}}
            <h2 id="data">{{ __('legal_terms.s7_title') }}</h2>
            <p>{!! __('legal_terms.s7_privacy', ['url' => e(route('public.privacy'))]) !!}</p>
            <p>{!! __('legal_terms.s7_dpa') !!}</p>
            <p>{!! __('legal_terms.s7_ownership') !!}</p>

            {{-- 8. Acceptable Use --}}
            <h2 id="aup">{{ __('legal_terms.s8_title') }}</h2>

            <h3>{{ __('legal_terms.s81_title') }}</h3>
            <ul>
                @foreach(__('legal_terms.s81_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>

            <h3>{{ __('legal_terms.s82_title') }}</h3>
            <p>{{ __('legal_terms.s82_intro') }}</p>
            <ul>
                @foreach(__('legal_terms.s82_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>

            {{-- 9. Intellectual Property --}}
            <h2 id="ip">{{ __('legal_terms.s9_title') }}</h2>
            @foreach(__('legal_terms.s9_paras') as $para)<p>{!! $para !!}</p>@endforeach

            {{-- 10. Service Availability --}}
            <h2 id="availability">{{ __('legal_terms.s10_title') }}</h2>
            <p>{!! __('legal_terms.s10_target', ['url' => e(route('public.status'))]) !!}</p>
            @foreach(__('legal_terms.s10_paras') as $para)<p>{!! $para !!}</p>@endforeach

            {{-- 11. Limitation of Liability --}}
            <h2 id="liability">{{ __('legal_terms.s11_title') }}</h2>
            <p>{{ __('legal_terms.s11_intro') }}</p>
            <ul>
                @foreach(__('legal_terms.s11_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>
            <p>{{ __('legal_terms.s11_outro') }}</p>

            {{-- 12. Indemnification --}}
            <h2 id="indemnity">{{ __('legal_terms.s12_title') }}</h2>
            <p>{{ __('legal_terms.s12_intro') }}</p>
            <ul>
                @foreach(__('legal_terms.s12_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>

            {{-- 13. Force Majeure --}}
            <h2>{{ __('legal_terms.s13_title') }}</h2>
            <p>{{ __('legal_terms.s13_body') }}</p>

            {{-- 14. Governing Law --}}
            <h2 id="governing-law">{{ __('legal_terms.s14_title') }}</h2>
            @foreach(__('legal_terms.s14_paras') as $para)<p>{!! $para !!}</p>@endforeach

            {{-- 15. Termination --}}
            <h2 id="termination">{{ __('legal_terms.s15_title') }}</h2>

            <h3>{{ __('legal_terms.s151_title') }}</h3>
            <p>{{ __('legal_terms.s151_intro') }}</p>
            <ul>
                @foreach(__('legal_terms.s151_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>
            <p>{{ __('legal_terms.s151_outro') }}</p>

            <h3>{{ __('legal_terms.s152_title') }}</h3>
            <p>{!! __('legal_terms.s152_body') !!}</p>

            <h3>{{ __('legal_terms.s153_title') }}</h3>
            <p>{{ __('legal_terms.s153_intro') }}</p>
            <ul>
                @foreach(__('legal_terms.s153_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>

            {{-- 16. Changes to Terms --}}
            <h2>{{ __('legal_terms.s16_title') }}</h2>
            <p>{!! __('legal_terms.s16_body') !!}</p>

            {{-- 17. Miscellaneous --}}
            <h2>{{ __('legal_terms.s17_title') }}</h2>
            @foreach(__('legal_terms.s17_paras') as $para)<p>{!! $para !!}</p>@endforeach

            {{-- 18. Contact --}}
            <h2 id="contact">{{ __('legal_terms.s18_title') }}</h2>
            <div class="odp-panel">
                <p class="odp-m0">{!! __('legal_terms.s18_card') !!}</p>
            </div>

            {{-- Footer --}}
            <div class="odp-foot">
                <p class="odp-mb-8"><strong>{{ __('public.terms_page.effective_date') }}</strong> {{ __('legal_terms.effective_date_value') }} &nbsp;|&nbsp; <strong>{{ __('public.terms_page.version') }}</strong> 2.0 &nbsp;|&nbsp; <strong>{{ __('public.terms_page.last_updated') }}</strong> {{ __('legal_terms.last_updated_value') }}</p>
                <p class="odp-m0"><strong>{{ __('public.terms_page.governing_law_label') }}</strong> {{ __('legal_terms.governing_law_value') }} &nbsp;|&nbsp; <strong>{{ __('public.terms_page.jurisdiction') }}</strong> {{ __('legal_terms.jurisdiction_value') }} &nbsp;|&nbsp; <strong>{{ __('public.terms_page.languages') }}</strong> {{ __('legal_terms.languages_value') }}</p>
            </div>

        </div>
    </section>

@endsection

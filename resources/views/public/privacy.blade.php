@extends('layouts.finder')

@section('title', __('public.privacy_page.page_title'))
@section('description', __('public.privacy_page.meta_description'))

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
            <div class="badge odp-mb-16">{{ __('public.privacy_page.badge') }}</div>
            <h1>{{ __('public.privacy_page.hero_title') }}</h1>
            <p class="text-muted odp-measure">
                {{ __('public.privacy_page.hero_subtitle') }}
            </p>
            <div class="odp-links">
                <span><strong>{{ __('public.privacy_page.effective_date') }}</strong> {{ __('legal_privacy.effective_date_value') }}</span>
                <span><strong>{{ __('public.privacy_page.last_updated') }}</strong> {{ __('legal_privacy.last_updated_value') }}</span>
                <span><strong>{{ __('public.privacy_page.version') }}</strong> 2.1</span>
            </div>
        </div>
    </header>

    {{-- Quick nav --}}
    <section class="odp-band">
        <div class="container">
            <p class="odp-kicker">{{ __('public.privacy_page.jump_to') }}</p>
            <div class="odp-row">
                @foreach([
                    'controller'     => 'controller',
                    'legal-basis'    => 'legal_basis',
                    'data-collected' => 'data',
                    'purposes'       => 'purposes',
                    'sharing'        => 'sharing',
                    'retention'      => 'retention',
                    'security'       => 'security',
                    'rights'         => 'rights',
                    'children'       => 'children',
                    'emergency'      => 'emergency',
                    'supervisory'    => 'supervisory',
                    'contact'        => 'contact',
                ] as $anchor => $key)
                <a class="odp-tag" href="#{{ $anchor }}">{{ __('legal_privacy.nav.'.$key) }}</a>
                @endforeach
            </div>
        </div>
    </section>

    {{--
        Legal copy lives in lang/{en,fr}/legal_privacy.php. Those strings are
        trusted, author-controlled HTML (inline strong/em/a only), hence the
        unescaped echoes. This page does not pass through LegalHtmlSanitizer;
        that sanitizer guards the DB-stored documents in public/legal/show.
    --}}
    <section class="content-body">
        <div class="container rich-text odp-wide">

            {{-- 0. Regulatory alignment notice --}}
            <div class="odp-panel odp-mb-48">
                <div class="odp-row odp-row--top">
                    <span class="odp-shrink">@include('partials.finder.icon', ['name' => 'shield-check', 'size' => 24])</span>
                    <div>
                        <p class="odp-bold odp-mb-8">{{ __('legal_privacy.reg_title') }}</p>
                        <p class="odp-m0 odp-soft">{!! __('legal_privacy.reg_body') !!}</p>
                    </div>
                </div>
            </div>

            {{-- 1. Data Controller --}}
            <h2 id="controller">{{ __('legal_privacy.s1_title') }}</h2>
            <p>{{ __('legal_privacy.s1_intro') }}</p>
            <div class="odp-panel">
                <p class="odp-m0">{!! __('legal_privacy.s1_card') !!}</p>
            </div>
            <p>{!! __('legal_privacy.s1_body') !!}</p>

            {{-- 2. Legal Basis --}}
            <h2 id="legal-basis">{{ __('legal_privacy.s2_title') }}</h2>
            <p>{{ __('legal_privacy.s2_intro') }}</p>
            <ul>
                @foreach(__('legal_privacy.s2_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>

            {{-- 3. Data Collected --}}
            <h2 id="data-collected">{{ __('legal_privacy.s3_title') }}</h2>

            <h3>{{ __('legal_privacy.s31_title') }}</h3>
            <ul>
                @foreach(__('legal_privacy.s31_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>

            <h3>{{ __('legal_privacy.s32_title') }}</h3>
            <p class="odp-panel odp-panel--warn">{!! __('legal_privacy.s32_note') !!}</p>
            <ul>
                @foreach(__('legal_privacy.s32_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>

            <h3>{{ __('legal_privacy.s33_title') }}</h3>
            <ul>
                @foreach(__('legal_privacy.s33_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>

            <h3>{{ __('legal_privacy.s34_title') }}</h3>
            <ul>
                @foreach(__('legal_privacy.s34_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>

            <h3>{{ __('legal_privacy.s35_title') }}</h3>
            <p>{{ __('legal_privacy.s35_body') }}</p>

            {{-- 4. Purposes --}}
            <h2 id="purposes">{{ __('legal_privacy.s4_title') }}</h2>
            <table class="odp-table odp-stack-sm">
                <thead>
                    <tr class="odp-panel">
                        <th>{{ __('legal_privacy.s4_col_purpose') }}</th>
                        <th>{{ __('legal_privacy.s4_col_basis') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(__('legal_privacy.s4_rows') as $row)
                    <tr>
                        <td>{{ $row['purpose'] }}</td>
                        <td class="odp-key">{{ $row['basis'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- 5. Data Sharing --}}
            <h2 id="sharing">{{ __('legal_privacy.s5_title') }}</h2>
            @foreach(['s51', 's52', 's53', 's54', 's55'] as $sub)
            <h3>{{ __('legal_privacy.'.$sub.'_title') }}</h3>
            <p>{!! __('legal_privacy.'.$sub.'_body') !!}</p>
            @if($sub === 's53')
            <p>{{ __('legal_privacy.s53_named_intro') }}</p>
            <ul>
                @foreach(__('legal_privacy.s53_named_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>
            <p>{{ __('legal_privacy.s53_named_outro') }}</p>
            @endif
            @endforeach

            <h3>{{ __('legal_privacy.s56_title') }}</h3>
            <ul>
                @foreach(__('legal_privacy.s56_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>

            {{-- 6. Retention --}}
            <h2 id="retention">{{ __('legal_privacy.s6_title') }}</h2>
            <p>{{ __('legal_privacy.s6_intro') }}</p>
            <ul>
                @foreach(__('legal_privacy.s6_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>
            <p>{{ __('legal_privacy.s6_outro') }}</p>

            {{-- 7. Security --}}
            <h2 id="security">{{ __('legal_privacy.s7_title') }}</h2>
            <p>{{ __('legal_privacy.s7_intro') }}</p>
            <ul>
                @foreach(__('legal_privacy.s7_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>
            <p>{!! __('legal_privacy.s7_outro', ['url' => e(route('public.security'))]) !!}</p>

            {{-- 8. Rights --}}
            <h2 id="rights">{{ __('legal_privacy.s8_title') }}</h2>
            <p>{{ __('legal_privacy.s8_intro') }}</p>

            <div class="odp-stack odp-mt-24 odp-mb-16">
                @foreach(__('legal_privacy.s8_rights') as $right)
                <div class="odp-panel">
                    <p class="odp-bold odp-mb-8">{{ $right['title'] }}</p>
                    <p class="odp-m0 odp-soft">{{ $right['body'] }}</p>
                </div>
                @endforeach
            </div>

            <p>{!! __('legal_privacy.s8_outro') !!}</p>

            {{-- 9. Special Protections --}}
            <h2>{{ __('legal_privacy.s9_title') }}</h2>
            <p>{{ __('legal_privacy.s9_intro') }}</p>
            <ul>
                @foreach(__('legal_privacy.s9_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>

            {{-- 10. Children --}}
            <h2 id="children">{{ __('legal_privacy.s10_title') }}</h2>
            <p>{{ __('legal_privacy.s10_intro') }}</p>
            <ul>
                @foreach(__('legal_privacy.s10_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>

            {{-- 11. Emergency Access --}}
            <h2 id="emergency">{{ __('legal_privacy.s11_title') }}</h2>
            <p>{!! __('legal_privacy.s11_intro') !!}</p>
            <ul>
                @foreach(__('legal_privacy.s11_profile') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>
            <p>{{ __('legal_privacy.s11_every') }}</p>
            <ul>
                @foreach(__('legal_privacy.s11_events') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>
            <p>{!! __('legal_privacy.s11_outro') !!}</p>

            {{-- 12. Supervisory Authority --}}
            <h2 id="supervisory">{{ __('legal_privacy.s12_title') }}</h2>
            <p>{{ __('legal_privacy.s12_intro') }}</p>
            <div class="odp-panel">
                <p class="odp-m0">{!! __('legal_privacy.s12_card') !!}</p>
            </div>
            <p>{!! __('legal_privacy.s12_outro') !!}</p>

            {{-- 13. International Transfers --}}
            <h2>{{ __('legal_privacy.s13_title') }}</h2>
            <p>{{ __('legal_privacy.s13_intro') }}</p>
            <ul>
                @foreach(__('legal_privacy.s13_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>

            {{-- 14. Cookies and Tracking --}}
            <h2>{{ __('legal_privacy.s14_title') }}</h2>
            <p>{{ __('legal_privacy.s14_intro') }}</p>
            <ul>
                @foreach(__('legal_privacy.s14_items') as $item)<li>{!! $item !!}</li>@endforeach
            </ul>
            <p>{{ __('legal_privacy.s14_outro') }}</p>

            {{-- 15. Changes --}}
            <h2>{{ __('legal_privacy.s15_title') }}</h2>
            <p>{{ __('legal_privacy.s15_body') }}</p>

            {{-- 16. Contact --}}
            <h2 id="contact">{{ __('legal_privacy.s16_title') }}</h2>
            <p>{{ __('legal_privacy.s16_intro') }}</p>
            <div class="odp-panel">
                <p class="odp-m0">{!! __('legal_privacy.s16_card') !!}</p>
            </div>

            {{-- Footer note --}}
            <div class="odp-foot">
                <p class="odp-mb-8">{!! __('legal_privacy.foot_versions') !!}</p>
                <p class="odp-m0">{{ __('public.privacy_page.governing_law') }} {{ __('legal_privacy.governing_law_value') }} &nbsp;|&nbsp; {{ __('public.privacy_page.footer_note') }} &nbsp;|&nbsp; {{ __('legal_privacy.foot_legal') }} <a href="mailto:legal@opeshealthsystems.com">legal@opeshealthsystems.com</a></p>
            </div>

        </div>
    </section>

@endsection

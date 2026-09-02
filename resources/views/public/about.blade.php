@extends('layouts.public')

@section('title', __('public.about_page.page_title'))
@section('meta_description', __('public.about_page.meta_description'))

@section('head_scripts')
<style>
.about-stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
@media (max-width: 480px) { .about-stats-grid { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')

    {{-- Hero --}}
    <header class="content-header">
        <div class="container">
            <div class="badge" style="background:rgba(20,184,166,.15);color:#0F766E;margin-bottom:1rem;">{{ __('public.about_page.badge') }}</div>
            <h1>{{ __('public.about.hero_title') }}</h1>
            <p class="text-muted" style="max-width:800px;margin:0 auto;font-size:1.2rem;">
                {{ __('public.about.hero_subtitle') }}
            </p>
            <div style="margin-top:2.5rem;display:flex;justify-content:center;flex-wrap:wrap;gap:1rem;">
                <a href="{{ route('public.contact') }}" class="btn btn-primary">{{ __('public.about_page.hero_cta_demo') }}</a>
                <a href="{{ route('public.how-it-works') }}" class="btn btn-secondary">{{ __('landing.nav.how_it_works') }}</a>
            </div>
        </div>
    </header>

    {{-- The problem we solve --}}
    <section class="content-body">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <h2>{{ __('public.about.why_title') }}</h2>
                    <p class="text-muted" style="font-size:1.0625rem;line-height:1.75;">{{ __('public.about.why_content') }}</p>
                    <p class="text-muted" style="font-size:1.0625rem;line-height:1.75;margin-top:1rem;">
                        {{ __('public.about_page.interop_body') }}
                    </p>
                </div>
                <div class="hero-visual">
                    <div style="display:grid;gap:1rem;">
                        @foreach([
                            ['icon'=>'file-x','label'=>__('public.about_page.prob_lost_books')],
                            ['icon'=>'refresh-ccw','label'=>__('public.about_page.prob_repeated_labs')],
                            ['icon'=>'eye-off','label'=>__('public.about_page.prob_no_context')],
                            ['icon'=>'unplug','label'=>__('public.about_page.prob_disconnected')],
                            ['icon'=>'clock','label'=>__('public.about_page.prob_delays')],
                        ] as $prob)
                        <div style="display:flex;align-items:center;gap:1rem;background:#FFF7ED;border:1px solid #FED7AA;border-radius:.875rem;padding:1rem 1.25rem;">
                            <div style="width:2rem;height:2rem;background:#FEF3C7;color:#C2410C;border-radius:.5rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i data-lucide="{{ $prob['icon'] }}" style="width:.875rem;height:.875rem;"></i>
                            </div>
                            <span style="font-size:.9375rem;font-weight:500;color:#7C2D12;">{{ $prob['label'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Mission --}}
    <section class="section" style="background:#0F2744;">
        <div class="container" style="max-width:860px;text-align:center;">
            <i data-lucide="target" style="width:3rem;height:3rem;color:#14B8A6;margin-bottom:1.5rem;"></i>
            <div style="font-size:.8125rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#38BDF8;margin-bottom:1rem;">Our Mission</div>
            <h2 style="color:#fff;font-size:1.875rem;line-height:1.3;margin-bottom:1.5rem;">
                "{{ __('public.about.mission_content') }}"
            </h2>
            <p style="color:rgba(255,255,255,.65);font-size:1rem;">{{ __('public.about.mission_title') }}</p>
        </div>
    </section>

    {{-- What OpesCare is --}}
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2>{{ __('public.about_page.what_title') }}</h2>
                <p class="text-muted">{{ __('public.about_page.what_subtitle') }}</p>
            </div>
            <div class="card-grid">
                @foreach([
                    ['icon'=>'id-card','title'=>__('public.about_page.module_health_id_title'),'desc'=>__('public.about_page.module_health_id_desc')],
                    ['icon'=>'history','title'=>__('public.about_page.module_timeline_title'),'desc'=>__('public.about_page.module_timeline_desc')],
                    ['icon'=>'shield-check','title'=>__('public.about_page.module_consent_title'),'desc'=>__('public.about_page.module_consent_desc')],
                    ['icon'=>'network','title'=>__('public.about_page.module_interop_title'),'desc'=>__('public.about_page.module_interop_desc')],
                    ['icon'=>'pill','title'=>__('public.about_page.module_meds_title'),'desc'=>__('public.about_page.module_meds_desc')],
                    ['icon'=>'file-text','title'=>__('public.about_page.module_billing_title'),'desc'=>__('public.about_page.module_billing_desc')],
                ] as $module)
                <div class="card">
                    <div class="card-icon" style="background:rgba(15,76,129,.08);color:#0F4C81;"><i data-lucide="{{ $module['icon'] }}"></i></div>
                    <h3>{{ $module['title'] }}</h3>
                    <p>{{ $module['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Values --}}
    <section class="section" style="background:#F0F9FF;">
        <div class="container">
            <div class="section-header">
                <h2>{{ __('public.about_page.values_title') }}</h2>
                <p class="text-muted">{{ __('public.about_page.values_subtitle') }}</p>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;max-width:960px;margin:0 auto;">
                @foreach([
                    ['icon'=>'heart-handshake','color'=>'#0F4C81','title'=>__('public.about_page.val_patient_title'),'desc'=>__('public.about_page.val_patient_desc')],
                    ['icon'=>'lock','color'=>'#0F766E','title'=>__('public.about_page.val_privacy_title'),'desc'=>__('public.about_page.val_privacy_desc')],
                    ['icon'=>'link','color'=>'#6D28D9','title'=>__('public.about_page.val_interop_title'),'desc'=>__('public.about_page.val_interop_desc')],
                    ['icon'=>'scale','color'=>'#C2410C','title'=>__('public.about_page.val_accountability_title'),'desc'=>__('public.about_page.val_accountability_desc')],
                ] as $val)
                <div style="display:flex;gap:1rem;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:1.25rem;padding:1.75rem;">
                    <div style="width:2.75rem;height:2.75rem;background:{{ $val['color'] }}1A;color:{{ $val['color'] }};border-radius:.875rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="{{ $val['icon'] }}" style="width:1.25rem;height:1.25rem;"></i>
                    </div>
                    <div>
                        <h4 style="margin:0 0 .4rem;font-size:1rem;">{{ $val['title'] }}</h4>
                        <p class="text-muted" style="font-size:.875rem;margin:0;">{{ $val['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Who builds it --}}
    <section class="section">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <div class="badge" style="background:rgba(15,76,129,.08);color:#0F4C81;margin-bottom:1rem;">{{ __('public.about_page.who_badge') }}</div>
                    <h2>{{ __('public.about.built_by_title') }}</h2>
                    <p class="text-muted" style="font-size:1.0625rem;line-height:1.75;">{{ __('public.about.built_by_content') }}</p>
                    <p class="text-muted" style="font-size:1.0625rem;line-height:1.75;margin-top:1rem;">
                        {{ __('public.about_page.who_body') }}
                    </p>
                    <div style="margin-top:2rem;">
                        <a href="https://opeshealthsystems.com" target="_blank" rel="noopener" class="btn btn-primary">{{ __('public.about_page.who_cta') }}</a>
                    </div>
                </div>
                <div class="hero-visual">
                    <div style="background:#0F2744;border-radius:1.5rem;padding:2rem;color:#fff;">
                        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#38BDF8;margin-bottom:1.5rem;">{{ __('public.about_page.stats_label') }}</div>
                        <div class="about-stats-grid" style="display:grid;gap:1.25rem;">
                            @foreach([
                                ['val'=>'6+','label'=>__('public.about_page.stat_integrations')],
                                ['val'=>'5','label'=>__('public.about_page.stat_stakeholders')],
                                ['val'=>'2','label'=>__('public.about_page.stat_languages')],
                                ['val'=>'∞','label'=>__('public.about_page.stat_audit')],
                            ] as $stat)
                            <div style="background:rgba(255,255,255,.07);border-radius:1rem;padding:1.25rem;text-align:center;">
                                <div style="font-size:1.875rem;font-weight:900;color:#fff;margin-bottom:.25rem;">{{ $stat['val'] }}</div>
                                <div style="font-size:.75rem;color:#94a3b8;">{{ $stat['label'] }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="section" style="background:#F8FAFC;text-align:center;border-top:1px solid #e2e8f0;">
        <div class="container" style="max-width:640px;">
            <h2>{{ __('public.about.footer_cta_title') }}</h2>
            <p class="text-muted" style="margin-bottom:2rem;">{{ __('public.about_page.footer_cta_body') }}</p>
            <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:1rem;">
                <a href="{{ route('public.contact') }}" class="btn btn-primary">{{ __('public.about.footer_cta_btn') }}</a>
                <a href="{{ route('register.organization') }}" class="btn btn-secondary">{{ __('public.about_page.footer_cta_register') }}</a>
            </div>
        </div>
    </section>

@endsection

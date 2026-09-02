@extends('layouts.public')

@section('title', __('public.how_page.page_title'))
@section('meta_description', __('seo.meta.how_it_works'))

@section('content')
    <header class="content-header">
        <div class="container">
            <div class="badge" style="background-color: var(--color-teal-light); color: var(--color-teal); margin-bottom: 1rem;">{{ __('public.how_page.badge') }}</div>
            <h1>{{ __('public.how_it_works.hero_title') }}</h1>
            <p class="text-muted" style="max-width: 800px; margin: 0 auto; font-size: 1.25rem;">
                {{ __('public.how_it_works.hero_subtitle') }}
            </p>
        </div>
    </header>

    <section class="content-body" style="background-color: var(--color-background);">
        <div class="container">
            <div class="card-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">
                <!-- Step 1 -->
                <div class="card">
                    <div class="card-icon" style="background-color: var(--color-primary); color: white;">1</div>
                    <h3>{{ __('public.how_it_works.step1_title') }}</h3>
                    <p>{{ __('public.how_it_works.step1_desc') }}</p>
                </div>
                <!-- Step 2 -->
                <div class="card">
                    <div class="card-icon" style="background-color: var(--color-primary); color: white;">2</div>
                    <h3>{{ __('public.how_it_works.step2_title') }}</h3>
                    <p>{{ __('public.how_it_works.step2_desc') }}</p>
                </div>
                <!-- Step 3 -->
                <div class="card">
                    <div class="card-icon" style="background-color: var(--color-primary); color: white;">3</div>
                    <h3>{{ __('public.how_it_works.step3_title') }}</h3>
                    <p>{{ __('public.how_it_works.step3_desc') }}</p>
                </div>
                <!-- Step 4 -->
                <div class="card">
                    <div class="card-icon" style="background-color: var(--color-primary); color: white;">4</div>
                    <h3>{{ __('public.how_it_works.step4_title') }}</h3>
                    <p>{{ __('public.how_it_works.step4_desc') }}</p>
                </div>
                <!-- Step 5 -->
                <div class="card">
                    <div class="card-icon" style="background-color: var(--color-primary); color: white;">5</div>
                    <h3>{{ __('public.how_it_works.step5_title') }}</h3>
                    <p>{{ __('public.how_it_works.step5_desc') }}</p>
                </div>
                <!-- Step 6 -->
                <div class="card">
                    <div class="card-icon" style="background-color: var(--color-primary); color: white;">6</div>
                    <h3>{{ __('public.how_it_works.step6_title') }}</h3>
                    <p>{{ __('public.how_it_works.step6_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <h2>{{ __('public.how_it_works.integration_title') }}</h2>
                    <p class="text-muted">{{ __('public.how_it_works.integration_desc') }}</p>
                    <div class="feature-list mt-6" style="margin-top: 1.5rem; display: grid; gap: 1rem;">
                        <div class="feature-item"><i data-lucide="braces"></i> Direct API Integration</div>
                        <div class="feature-item"><i data-lucide="code"></i> Connect SDK</div>
                        <div class="feature-item"><i data-lucide="panel-top"></i> Connect Widget</div>
                        <div class="feature-item"><i data-lucide="network"></i> Bridge Agent</div>
                    </div>
                </div>
                <div class="hero-visual">
                    <div style="background:#0F2744;border-radius:1.5rem;padding:2rem;color:#fff;">
                        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#38BDF8;margin-bottom:1.25rem;">{{ __('public.how_page.integration_options') }}</div>
                        <div style="display:grid;gap:.75rem;">
                            @foreach([
                                ['icon'=>'braces','label'=>__('public.how_page.integration_api')],
                                ['icon'=>'code-2','label'=>__('public.how_page.integration_sdk')],
                                ['icon'=>'panel-top','label'=>__('public.how_page.integration_widget')],
                                ['icon'=>'cpu','label'=>__('public.how_page.integration_bridge')],
                                ['icon'=>'layout-dashboard','label'=>'OpesCare Lite'],
                            ] as $item)
                            <a href="{{ route('public.developers') }}" style="display:flex;align-items:center;gap:.875rem;background:rgba(255,255,255,.07);border-radius:.75rem;padding:.875rem 1rem;text-decoration:none;">
                                <i data-lucide="{{ $item['icon'] }}" style="width:1rem;height:1rem;color:#14B8A6;flex-shrink:0;"></i>
                                <span style="font-size:.875rem;color:#e2e8f0;">{{ $item['label'] }}</span>
                            </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="background:#0F2744;color:#fff;text-align:center;">
        <div class="container" style="max-width:640px;">
            <h2 style="color:#fff;margin-bottom:1rem;">{{ __('public.how_page.cta_title') }}</h2>
            <p style="color:rgba(255,255,255,.75);margin-bottom:2rem;">{{ __('public.how_page.cta_body') }}</p>
            <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:1rem;">
                <a href="{{ route('public.contact') }}" class="btn btn-primary">{{ __('public.how_page.cta_demo') }}</a>
                <a href="{{ route('public.developers') }}" class="btn" style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.25);">{{ __('public.how_page.cta_dev_docs') }}</a>
            </div>
        </div>
    </section>
@endsection

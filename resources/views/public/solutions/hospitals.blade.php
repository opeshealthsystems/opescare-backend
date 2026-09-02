@extends('layouts.public')

@section('title', __('public.sol_hospitals.page_title'))
@section('meta_description', __('seo.meta.sol_hospitals'))

@section('content')
    <header class="content-header">
        <div class="container">
            <div class="badge" style="background-color: var(--color-primary-light); color: var(--color-primary); margin-bottom: 1rem;">{{ __('public.sol_hospitals.badge') }}</div>
            <h1>{{ __('public.solutions.hospitals.hero_title') }}</h1>
            <p class="text-muted" style="max-width: 800px; margin: 0 auto; font-size: 1.25rem;">
                {{ __('public.solutions.hospitals.hero_subtitle') }}
            </p>
            <div style="margin-top: 2.5rem;">
                <a href="{{ route('public.contact') }}" class="btn btn-primary">{{ __('landing.nav.demo') }}</a>
            </div>
        </div>
    </header>

    <section class="content-body">
        <div class="container">
            <div class="section-header">
                <h2>{{ __('public.sol_hospitals.benefits_title') }}</h2>
                <p>{{ __('public.sol_hospitals.benefits_subtitle') }}</p>
            </div>

            <div class="card-grid">
                <div class="card">
                    <div class="card-icon"><i data-lucide="users"></i></div>
                    <h3>{{ __('public.sol_hospitals.benefit_dedup_title') }}</h3>
                    <p>{{ __('public.sol_hospitals.benefit_dedup_desc') }}</p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="history"></i></div>
                    <h3>{{ __('public.sol_hospitals.benefit_history_title') }}</h3>
                    <p>{{ __('public.sol_hospitals.benefit_history_desc') }}</p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="share-2"></i></div>
                    <h3>{{ __('public.sol_hospitals.benefit_referral_title') }}</h3>
                    <p>{{ __('public.sol_hospitals.benefit_referral_desc') }}</p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="shield-check"></i></div>
                    <h3>{{ __('public.sol_hospitals.benefit_audit_title') }}</h3>
                    <p>{{ __('public.sol_hospitals.benefit_audit_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="background-color: var(--color-primary-dark); color: white;">
        <div class="container">
            <div class="section-header">
                <h2 style="color: white;">{{ __('public.sol_hospitals.workflow_title') }}</h2>
            </div>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-icon-wrapper" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);">1</div>
                    <h4 style="color: white;">{{ __('public.sol_hospitals.step1_title') }}</h4>
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.875rem;">{{ __('public.sol_hospitals.step1_desc') }}</p>
                </div>
                <div class="step-card">
                    <div class="step-icon-wrapper" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);">2</div>
                    <h4 style="color: white;">{{ __('public.sol_hospitals.step2_title') }}</h4>
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.875rem;">{{ __('public.sol_hospitals.step2_desc') }}</p>
                </div>
                <div class="step-card">
                    <div class="step-icon-wrapper" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);">3</div>
                    <h4 style="color: white;">{{ __('public.sol_hospitals.step3_title') }}</h4>
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.875rem;">{{ __('public.sol_hospitals.step3_desc') }}</p>
                </div>
                <div class="step-card">
                    <div class="step-icon-wrapper" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);">4</div>
                    <h4 style="color: white;">{{ __('public.sol_hospitals.step4_title') }}</h4>
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.875rem;">{{ __('public.sol_hospitals.step4_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container text-center">
            <h2>{{ __('public.sol_hospitals.cta_title') }}</h2>
            <p class="text-muted" style="margin-bottom: 2rem;">{{ __('public.sol_hospitals.cta_body') }}</p>
            <a href="{{ route('public.contact') }}" class="btn btn-primary btn-lg">{{ __('public.sol_hospitals.btn_demo') }}</a>
        </div>
    </section>
@endsection

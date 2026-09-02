@extends('layouts.public')

@section('title', __('public.sol_patients.page_title'))
@section('meta_description', __('seo.meta.sol_patients'))

@section('content')
    <header class="content-header">
        <div class="container">
            <div class="badge" style="background-color: var(--color-primary-light); color: var(--color-primary); margin-bottom: 1rem;">{{ __('public.sol_patients.badge') }}</div>
            <h1>{{ __('public.solutions.patients.hero_title') }}</h1>
            <p class="text-muted" style="max-width: 800px; margin: 0 auto; font-size: 1.25rem;">
                {{ __('public.solutions.patients.hero_subtitle') }}
            </p>
        </div>
    </header>

    <section class="content-body">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <div class="card-icon" style="background-color: var(--color-primary-light); color: var(--color-primary);"><i data-lucide="id-card"></i></div>
                    <h2>{{ __('public.sol_patients.health_id_title') }}</h2>
                    <p class="text-muted">{{ __('public.sol_patients.health_id_desc') }}</p>
                    
                    <div class="mt-8 p-6 bg-white border border-border rounded-2xl shadow-sm" style="margin-top: 2rem; padding: 1.5rem; background: white; border: 1px solid var(--color-border); border-radius: 1rem;">
                        <div class="flex items-center gap-4" style="display: flex; align-items: center; gap: 1rem;">
                            <div class="h-12 w-12 bg-primary rounded-full flex items-center justify-center text-white" style="width: 3rem; height: 3rem; background: var(--color-primary); border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center;">AN</div>
                            <div>
                                <h4 style="margin: 0;">Amina Nkeng</h4>
                                <p class="text-xs text-muted" style="font-size: 0.75rem; margin: 0; font-family: monospace;">ID: CM-HID-7KQ9-MP42-X8D1</p>
                            </div>
                            <div style="margin-left: auto;">
                                <i data-lucide="qr-code" class="icon-md"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="card">
                        <div class="card-icon"><i data-lucide="history"></i></div>
                        <h3>{{ __('public.solutions.patients.history_title') }}</h3>
                        <p>{{ __('public.solutions.patients.history_desc') }}</p>
                    </div>
                    <div class="card mt-6" style="margin-top: 1.5rem;">
                        <div class="card-icon"><i data-lucide="shield-check"></i></div>
                        <h3>{{ __('public.solutions.patients.control_title') }}</h3>
                        <p>{{ __('public.solutions.patients.control_desc') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="background-color: var(--color-background);">
        <div class="container">
            <div class="card-grid">
                <div class="card">
                    <div class="card-icon"><i data-lucide="siren"></i></div>
                    <h3>{{ __('public.sol_patients.emergency_title') }}</h3>
                    <p>{{ __('public.sol_patients.emergency_desc') }}</p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="map-pin-check"></i></div>
                    <h3>{{ __('public.sol_patients.meds_title') }}</h3>
                    <p>{{ __('public.sol_patients.meds_desc') }}</p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="droplets"></i></div>
                    <h3>{{ __('public.sol_patients.blood_title') }}</h3>
                    <p>{{ __('public.sol_patients.blood_desc') }}</p>
                </div>
            </div>
            
            <div style="margin-top: 4rem; padding: 1.25rem 2rem; background-color: var(--color-primary-light); border-radius: 1rem; display:flex; align-items:center; gap:1rem;">
                <i data-lucide="info" style="width:1.25rem;height:1.25rem;color:#0F4C81;flex-shrink:0;"></i>
                <p class="text-muted" style="font-size: 0.875rem;margin:0;"><strong>{{ __('public.sol_patients.safety_note') }}</strong> {{ __('public.sol_patients.safety_body') }}</p>
            </div>
        </div>
    </section>

    <section class="section" style="background:#0F2744;color:#fff;text-align:center;">
        <div class="container" style="max-width:640px;">
            <i data-lucide="id-card" style="width:3rem;height:3rem;color:#14B8A6;margin-bottom:1.5rem;"></i>
            <h2 style="color:#fff;margin-bottom:1rem;">{{ __('public.sol_patients.cta_title') }}</h2>
            <p style="color:rgba(255,255,255,.75);margin-bottom:2rem;">{{ __('public.sol_patients.cta_body') }}</p>
            <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:1rem;">
                <a href="{{ route('register.patient') }}" class="btn btn-primary">{{ __('public.sol_patients.btn_register') }}</a>
                <a href="{{ route('public.consent') }}" class="btn" style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.25);">{{ __('public.sol_patients.btn_consent') }}</a>
            </div>
        </div>
    </section>
@endsection

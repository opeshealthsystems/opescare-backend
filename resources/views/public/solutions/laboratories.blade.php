@extends('layouts.public')

@section('title', __('public.sol_labs.page_title'))
@section('meta_description', __('seo.meta.sol_laboratories'))

@section('content')
    <header class="content-header">
        <div class="container">
            <div class="badge" style="background-color: var(--color-primary-light); color: var(--color-primary); margin-bottom: 1rem;">{{ __('public.sol_labs.badge') }}</div>
            <h1>{{ __('public.sol_labs.hero_title') }}</h1>
            <p class="text-muted" style="max-width: 800px; margin: 0 auto; font-size: 1.25rem;">
                {{ __('public.sol_labs.hero_subtitle') }}
            </p>
        </div>
    </header>

    <section class="content-body">
        <div class="container">
            <div class="card-grid">
                <div class="card">
                    <div class="card-icon"><i data-lucide="flask-conical"></i></div>
                    <h3>{{ __('public.sol_labs.card_orders_title') }}</h3>
                    <p>{{ __('public.sol_labs.card_orders_desc') }}</p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="barcode"></i></div>
                    <h3>{{ __('public.sol_labs.card_tracking_title') }}</h3>
                    <p>{{ __('public.sol_labs.card_tracking_desc') }}</p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="badge-check"></i></div>
                    <h3>{{ __('public.sol_labs.card_valid_title') }}</h3>
                    <p>{{ __('public.sol_labs.card_valid_desc') }}</p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="history"></i></div>
                    <h3>{{ __('public.sol_labs.card_timeline_title') }}</h3>
                    <p>{{ __('public.sol_labs.card_timeline_desc') }}</p>
                </div>
            </div>
            
            <div class="feature-list mt-12" style="margin-top: 3rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                <div class="feature-item"><i data-lucide="triangle-alert"></i> {{ __('public.sol_labs.feat_critical') }}</div>
                <div class="feature-item"><i data-lucide="file-pen-line"></i> {{ __('public.sol_labs.feat_amendments') }}</div>
                <div class="feature-item"><i data-lucide="cable"></i> {{ __('public.sol_labs.feat_external') }}</div>
            </div>
        </div>
    </section>

    <section class="section" style="background:#0F2744;color:#fff;text-align:center;">
        <div class="container" style="max-width:640px;">
            <h2 style="color:#fff;margin-bottom:1rem;">{{ __('public.sol_labs.cta_title') }}</h2>
            <p style="color:rgba(255,255,255,.75);margin-bottom:2rem;">{{ __('public.sol_labs.cta_body') }}</p>
            <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:1rem;">
                <a href="{{ route('register.organization') }}" class="btn btn-primary">{{ __('public.sol_labs.btn_register') }}</a>
                <a href="{{ route('public.developers') }}" class="btn" style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.25);">{{ __('public.sol_labs.btn_integration') }}</a>
            </div>
        </div>
    </section>
@endsection

@extends('layouts.public')

@section('title', 'OpesCare for Insurers | Controlled Access & Claims Support')
@section('meta_description', __('seo.meta.sol_insurers'))

@section('content')
    <header class="content-header">
        <div class="container">
            <div class="badge" style="background-color: var(--color-primary-light); color: var(--color-primary); margin-bottom: 1rem;">For Insurers</div>
            <h1>Insurance workflows with controlled access to necessary information.</h1>
            <p class="text-muted" style="max-width: 800px; margin: 0 auto; font-size: 1.25rem;">
                OpesCare can support eligibility checks, claims, preauthorization, and documentation while protecting patients from unnecessary exposure of their full medical history.
            </p>
        </div>
    </header>

    <section class="content-body">
        <div class="container">
            <div class="card-grid">
                <div class="card">
                    <div class="card-icon"><i data-lucide="shield-check"></i></div>
                    <h3>Coverage Verification</h3>
                    <p>Hospitals can verify patient eligibility and coverage limits in real-time, reducing rejected claims and financial delays.</p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="file-text"></i></div>
                    <h3>Claim Submission</h3>
                    <p>Streamline claim processing with digital documentation and automated submission workflows directly from clinical events.</p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="lock"></i></div>
                    <h3>Minimum Necessary Access</h3>
                    <p>Insurers receive only the specific clinical data required for a claim, upholding the highest standards of patient privacy.</p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="file-search"></i></div>
                    <h3>Audit & Accountability</h3>
                    <p>Full transparency into who requested data, what was shared, and the legal basis for the exchange.</p>
                </div>
            </div>
            
            <div style="margin-top: 4rem; padding: 2.5rem; background-color: var(--color-primary-dark); color: white; border-radius: 1.5rem;">
                <div style="display:flex;align-items:flex-start;gap:1rem;">
                    <i data-lucide="lock" style="width:1.5rem;height:1.5rem;color:#14B8A6;flex-shrink:0;margin-top:.15rem;"></i>
                    <div>
                        <h3 style="color: white; margin-top: 0;">Data Minimisation Policy</h3>
                        <p style="color: rgba(255,255,255,0.8); font-size: 1.0625rem; margin:0;">
                            Insurers do not automatically receive full patient history. OpesCare supports scoped access based on role, purpose, policy, and patient consent where required. This ensures that sensitive medical data is never exposed unnecessarily.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="background:#F8FAFC;border-top:1px solid #e2e8f0;">
        <div class="container">
            <div class="section-header">
                <h2>Integration methods for insurers</h2>
                <p class="text-muted">Insurers can connect to the OpesCare platform using the method that fits their technical infrastructure.</p>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.5rem;max-width:860px;margin:0 auto;">
                @foreach([
                    ['icon'=>'braces','title'=>'Connect API','desc'=>'Direct API integration for enterprise insurers with their own development teams.'],
                    ['icon'=>'panel-top','title'=>'Connect Widget','desc'=>'Embed a secure patient lookup and eligibility check directly into your web portal.'],
                    ['icon'=>'layout-dashboard','title'=>'OpesCare Lite','desc'=>'A browser portal for smaller insurers or claims teams without custom systems.'],
                ] as $m)
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:1.25rem;padding:1.75rem;text-align:center;">
                    <div style="width:2.5rem;height:2.5rem;background:rgba(15,76,129,.08);color:#0F4C81;border-radius:.875rem;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i data-lucide="{{ $m['icon'] }}" style="width:1.1rem;height:1.1rem;"></i>
                    </div>
                    <h4 style="margin:0 0 .5rem;font-size:1rem;">{{ $m['title'] }}</h4>
                    <p style="font-size:.875rem;color:#64748b;margin:0;">{{ $m['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section" style="background:#0F2744;color:#fff;text-align:center;">
        <div class="container" style="max-width:640px;">
            <h2 style="color:#fff;margin-bottom:1rem;">Connect your insurance organisation</h2>
            <p style="color:rgba(255,255,255,.75);margin-bottom:2rem;">Contact us to discuss eligibility verification, pre-authorisation workflows, and claims integration for your health insurance operations.</p>
            <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:1rem;">
                <a href="{{ route('register.organization') }}" class="btn btn-primary">Register Your Organisation</a>
                <a href="{{ route('public.contact') }}" class="btn" style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.25);">Contact Us</a>
            </div>
        </div>
    </section>
@endsection

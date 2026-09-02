@extends('layouts.public')

@section('title', 'For Public Health Authorities | OpesCare Aggregated Insights')
@section('meta_description', 'OpesCare supports public health authorities with anonymised disease surveillance, immunisation tracking, notifiable disease reporting, and population-level health insights.')

@section('content')

    {{-- Hero --}}
    <header class="content-header">
        <div class="container">
            <div class="badge" style="background:rgba(20,184,166,.15);color:#0F766E;margin-bottom:1rem;">Population Health</div>
            <h1>Aggregated insights for safer, healthier communities.</h1>
            <p class="text-muted" style="max-width:760px;margin:0 auto;font-size:1.2rem;">
                OpesCare provides anonymised and aggregated data tools to help public health authorities and researchers understand disease trends, track immunisation coverage, and coordinate emergency responses — while protecting individual privacy.
            </p>
            <div style="margin-top:2.5rem;display:flex;justify-content:center;flex-wrap:wrap;gap:1rem;">
                <a href="{{ route('public.contact') }}" class="btn btn-primary">Request a Demo</a>
                <a href="{{ route('public.security') }}" class="btn btn-secondary">Privacy &amp; Security</a>
            </div>
        </div>
    </header>

    {{-- Key capabilities --}}
    <section class="content-body">
        <div class="container">
            <div class="section-header">
                <h2>Public health capabilities</h2>
                <p class="text-muted">Purpose-built tools for authorities at district, regional, and national levels.</p>
            </div>
            <div class="card-grid">
                <div class="card">
                    <div class="card-icon" style="background:rgba(15,76,129,.08);color:#0F4C81;"><i data-lucide="line-chart"></i></div>
                    <h3>Disease Surveillance</h3>
                    <p>Real-time detection of disease outbreaks through aggregated clinical encounter data across multiple facilities — without exposing individual patient records.</p>
                </div>
                <div class="card">
                    <div class="card-icon" style="background:rgba(15,76,129,.08);color:#0F4C81;"><i data-lucide="syringe"></i></div>
                    <h3>Immunisation Tracking</h3>
                    <p>Monitor vaccination coverage at facility, district, and national levels. Identify under-immunised populations and support campaign planning.</p>
                </div>
                <div class="card">
                    <div class="card-icon" style="background:rgba(15,76,129,.08);color:#0F4C81;"><i data-lucide="bell-ring"></i></div>
                    <h3>Notifiable Disease Reporting</h3>
                    <p>Facilities can submit structured notifiable disease reports directly through OpesCare. Reports are validated, timestamped, and escalated to the relevant authority.</p>
                </div>
                <div class="card">
                    <div class="card-icon" style="background:rgba(15,76,129,.08);color:#0F4C81;"><i data-lucide="microscope"></i></div>
                    <h3>Clinical Research Support</h3>
                    <p>Access anonymised longitudinal datasets for clinical trials and public health studies with institutional review board approval and ethics documentation.</p>
                </div>
                <div class="card">
                    <div class="card-icon" style="background:rgba(15,76,129,.08);color:#0F4C81;"><i data-lucide="droplets"></i></div>
                    <h3>Blood Supply Monitoring</h3>
                    <p>Track blood availability and compatibility across registered blood banks and hospitals. Coordinate responses to shortages in real time.</p>
                </div>
                <div class="card">
                    <div class="card-icon" style="background:rgba(15,76,129,.08);color:#0F4C81;"><i data-lucide="shield-check"></i></div>
                    <h3>Ethical Compliance</h3>
                    <p>Built-in de-identification protocols and role-based access ensure that population health tools operate within international research ethics standards.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Privacy guarantee --}}
    <section class="section" style="background:#F0FDF4;">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <div class="badge" style="background:rgba(16,185,129,.1);color:#065F46;margin-bottom:1rem;">Privacy Guarantee</div>
                    <h2>Population insights without individual exposure</h2>
                    <p class="text-muted" style="margin-bottom:1.5rem;">
                        Public health data access in OpesCare uses strict de-identification and aggregation thresholds. Individual patient records are never directly accessible to public health analysts — only aggregate counts, trends, and anonymised cohort data.
                    </p>
                    <ul style="list-style:none;padding:0;margin:0;display:grid;gap:.75rem;">
                        <li style="display:flex;gap:.75rem;"><i data-lucide="check-circle" style="width:1.1rem;height:1.1rem;color:#10b981;flex-shrink:0;margin-top:.15rem;"></i> K-anonymity enforced — no group smaller than k=5 is reported</li>
                        <li style="display:flex;gap:.75rem;"><i data-lucide="check-circle" style="width:1.1rem;height:1.1rem;color:#10b981;flex-shrink:0;margin-top:.15rem;"></i> All research access requires documented IRB approval</li>
                        <li style="display:flex;gap:.75rem;"><i data-lucide="check-circle" style="width:1.1rem;height:1.1rem;color:#10b981;flex-shrink:0;margin-top:.15rem;"></i> Export logs retained for audit and accountability</li>
                        <li style="display:flex;gap:.75rem;"><i data-lucide="check-circle" style="width:1.1rem;height:1.1rem;color:#10b981;flex-shrink:0;margin-top:.15rem;"></i> No re-identification allowed under terms of access</li>
                    </ul>
                </div>
                <div class="hero-visual">
                    <div style="background:#0F2744;border-radius:1.5rem;padding:2rem;color:#fff;">
                        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#4ADE80;margin-bottom:1.5rem;">Anonymised Dashboard</div>
                        <div style="display:grid;gap:.75rem;">
                            @foreach([
                                ['label'=>'Malaria cases (last 30 days)','value'=>'1,247','trend'=>'+3.2%'],
                                ['label'=>'Vaccination coverage (DPT3)','value'=>'78.4%','trend'=>'+0.8%'],
                                ['label'=>'Notifiable reports submitted','value'=>'34','trend'=>''],
                                ['label'=>'Blood shortage alerts active','value'=>'2','trend'=>''],
                            ] as $stat)
                            <div style="background:rgba(255,255,255,.07);border-radius:.75rem;padding:1rem;display:flex;justify-content:space-between;align-items:center;">
                                <span style="font-size:.8125rem;color:#cbd5e1;">{{ $stat['label'] }}</span>
                                <div style="text-align:right;">
                                    <span style="font-weight:700;font-size:1rem;">{{ $stat['value'] }}</span>
                                    @if($stat['trend'])<span style="font-size:.75rem;color:#4ADE80;margin-left:.5rem;">{{ $stat['trend'] }}</span>@endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="section" style="background:#0F2744;color:#fff;text-align:center;">
        <div class="container" style="max-width:640px;">
            <h2 style="color:#fff;margin-bottom:1rem;">Connect your public health authority to OpesCare</h2>
            <p style="color:rgba(255,255,255,.75);margin-bottom:2rem;">Contact us to discuss a pilot, data sharing agreement, or integration with your existing disease surveillance systems.</p>
            <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:1rem;">
                <a href="{{ route('public.contact') }}" class="btn btn-primary">Request Partnership Discussion</a>
                <a href="{{ route('public.security') }}" class="btn" style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.25);">Security Architecture</a>
            </div>
        </div>
    </section>

@endsection

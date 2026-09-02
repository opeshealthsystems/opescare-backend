@extends('layouts.public')

@section('title', __('public.sec_page.page_title'))
@section('meta_description', __('public.sec_page.meta_description'))

@section('content')
    <header class="content-header">
        <div class="container">
            <div class="badge" style="background-color: var(--color-primary-light); color: var(--color-primary); margin-bottom: 1rem;">{{ __('public.sec_page.badge') }}</div>
            <h1>{{ __('public.sec_page.hero_title') }}</h1>
            <p class="text-muted" style="max-width: 800px; margin: 0 auto; font-size: 1.25rem;">
                {{ __('public.sec_page.hero_subtitle') }}
            </p>
        </div>
    </header>

    <section class="content-body">
        <div class="container">
            <div class="card-grid">
                <div class="card">
                    <div class="card-icon"><i data-lucide="lock"></i></div>
                    <h3>{{ __('public.sec_page.card_encryption_title') }}</h3>
                    <p>{{ __('public.sec_page.card_encryption_desc') }}</p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="file-search"></i></div>
                    <h3>{{ __('public.sec_page.card_audit_title') }}</h3>
                    <p>{{ __('public.sec_page.card_audit_desc') }}</p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="shield-check"></i></div>
                    <h3>{{ __('public.sec_page.card_consent_title') }}</h3>
                    <p>{{ __('public.sec_page.card_consent_desc') }}</p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="globe"></i></div>
                    <h3>{{ __('public.sec_page.card_compliance_title') }}</h3>
                    <p>{{ __('public.sec_page.card_compliance_desc') }}</p>
                </div>
            </div>

            <div style="margin-top: 6rem; padding: 3rem; background-color: var(--color-background); border-radius: 1.5rem; border: 1px solid var(--color-border);">
                <div class="hero-grid">
                    <div class="hero-content">
                        <h2>{{ __('public.sec_page.infra_title') }}</h2>
                        <p class="text-muted">{{ __('public.sec_page.infra_subtitle') }}</p>
                        <ul style="list-style:none;padding:0;margin:1.5rem 0 0;display:grid;gap:.75rem;">
                            <li style="display:flex;gap:.75rem;"><i data-lucide="check-circle" style="width:1.1rem;height:1.1rem;color:#14B8A6;flex-shrink:0;margin-top:.15rem;"></i> {{ __('public.sec_page.infra_pentest') }}</li>
                            <li style="display:flex;gap:.75rem;"><i data-lucide="check-circle" style="width:1.1rem;height:1.1rem;color:#14B8A6;flex-shrink:0;margin-top:.15rem;"></i> {{ __('public.sec_page.infra_zerotrust') }}</li>
                            <li style="display:flex;gap:.75rem;"><i data-lucide="check-circle" style="width:1.1rem;height:1.1rem;color:#14B8A6;flex-shrink:0;margin-top:.15rem;"></i> {{ __('public.sec_page.infra_mfa') }}</li>
                            <li style="display:flex;gap:.75rem;"><i data-lucide="check-circle" style="width:1.1rem;height:1.1rem;color:#14B8A6;flex-shrink:0;margin-top:.15rem;"></i> {{ __('public.sec_page.infra_failover') }}</li>
                            <li style="display:flex;gap:.75rem;"><i data-lucide="check-circle" style="width:1.1rem;height:1.1rem;color:#14B8A6;flex-shrink:0;margin-top:.15rem;"></i> {{ __('public.sec_page.infra_audit') }}</li>
                        </ul>
                    </div>
                    <div class="hero-visual" style="display:flex;justify-content:center;align-items:center;">
                        <div style="position:relative;width:14rem;height:14rem;">
                            <div style="position:absolute;inset:0;border-radius:50%;background:rgba(15,76,129,.06);display:flex;align-items:center;justify-content:center;">
                                <i data-lucide="shield-check" style="width:6rem;height:6rem;color:#0F4C81;opacity:.15;"></i>
                            </div>
                            <div style="position:absolute;inset:1.5rem;border-radius:50%;background:rgba(15,76,129,.08);display:flex;align-items:center;justify-content:center;">
                                <i data-lucide="shield-check" style="width:4rem;height:4rem;color:#0F4C81;opacity:.3;"></i>
                            </div>
                            <div style="position:absolute;inset:3rem;border-radius:50%;background:#0F4C81;display:flex;align-items:center;justify-content:center;">
                                <i data-lucide="shield-check" style="width:2.5rem;height:2.5rem;color:#fff;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Technical controls --}}
    <section class="section" style="background:#F8FAFC;">
        <div class="container">
            <div class="section-header">
                <h2>{{ __('public.sec_page.tech_title') }}</h2>
                <p class="text-muted">{{ __('public.sec_page.tech_subtitle') }}</p>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.5rem;max-width:960px;margin:0 auto;">
                @foreach([
                    ['icon'=>'lock','title'=>__('public.sec_page.ctrl_aes_title'),'desc'=>__('public.sec_page.ctrl_aes_desc')],
                    ['icon'=>'shield','title'=>__('public.sec_page.ctrl_tls_title'),'desc'=>__('public.sec_page.ctrl_tls_desc')],
                    ['icon'=>'key-round','title'=>__('public.sec_page.ctrl_oauth_title'),'desc'=>__('public.sec_page.ctrl_oauth_desc')],
                    ['icon'=>'fingerprint','title'=>__('public.sec_page.ctrl_otp_title'),'desc'=>__('public.sec_page.ctrl_otp_desc')],
                    ['icon'=>'scan','title'=>__('public.sec_page.ctrl_owasp_title'),'desc'=>__('public.sec_page.ctrl_owasp_desc')],
                    ['icon'=>'file-search','title'=>__('public.sec_page.ctrl_log_title'),'desc'=>__('public.sec_page.ctrl_log_desc')],
                ] as $ctrl)
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:1.25rem;padding:1.75rem;">
                    <div style="width:2.5rem;height:2.5rem;background:rgba(15,76,129,.08);color:#0F4C81;border-radius:.875rem;display:flex;align-items:center;justify-content:center;margin-bottom:1rem;">
                        <i data-lucide="{{ $ctrl['icon'] }}" style="width:1.1rem;height:1.1rem;"></i>
                    </div>
                    <h4 style="margin:0 0 .4rem;font-size:1rem;">{{ $ctrl['title'] }}</h4>
                    <p class="text-muted" style="font-size:.875rem;margin:0;">{{ $ctrl['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Regulatory compliance section --}}
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2>{{ __('public.sec_page.reg_title') }}</h2>
                <p class="text-muted">{{ __('public.sec_page.reg_subtitle') }}</p>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;max-width:1060px;margin:0 auto;">
                @foreach([
                    ['flag','Cameroon Law No. 2010/012','Cybersecurity and Personal Data Protection Act — governs lawful processing, consent requirements, data subject rights, and breach notification obligations.'],
                    ['building-2','MINSANTE Digital Health Strategy 2026–2030','National roadmap for Cameroon health digitalization, EHR interoperability, and telemedicine. OpesCare is architected to serve as an interoperability backbone.'],
                    ['heart-pulse','WHO Global Strategy on Digital Health 2020–2025','World Health Organization international framework for patient-centred digital health, data governance, and health system interoperability.'],
                    ['code-2','HL7 FHIR R4','Fast Healthcare Interoperability Resources version 4 — the global standard for structured health data exchange, used for all OpesCare API health record payloads.'],
                    ['shield','African Union Malabo Convention','African regional data protection framework setting minimum standards for personal data processing and cross-border data transfers within Africa.'],
                    ['scroll-text','ISO/IEC 27001 Principles','Information security management principles applied to infrastructure, access control, and incident response throughout the OpesCare platform.'],
                ] as [$icon,$title,$desc])
                <div style="background:#fff;border:1px solid #E2E8F0;border-radius:1.25rem;padding:1.75rem;">
                    <div style="width:2.5rem;height:2.5rem;background:rgba(15,76,129,.08);color:#0F4C81;border-radius:.875rem;display:flex;align-items:center;justify-content:center;margin-bottom:1rem;">
                        <i data-lucide="{{ $icon }}" style="width:1.1rem;height:1.1rem;"></i>
                    </div>
                    <h4 style="margin:0 0 .4rem;font-size:1rem;">{{ $title }}</h4>
                    <p class="text-muted" style="font-size:.875rem;margin:0;">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Data breach protocol --}}
    <section class="section" style="background:#F8FAFC;">
        <div class="container" style="max-width:860px;">
            <div class="section-header">
                <h2>{{ __('public.sec_page.breach_title') }}</h2>
                <p class="text-muted">{{ __('public.sec_page.breach_subtitle') }}</p>
            </div>
            <div style="display:grid;gap:1rem;">
                @foreach([
                    ['0–1 h','Detection & Containment','Automated monitoring systems detect and flag anomalous access events. On-call security team is immediately alerted to contain the incident.'],
                    ['1–24 h','Internal Assessment','Security team assesses the scope, affected data categories, and number of data subjects. Clinical impact assessment is initiated.'],
                    ['24–72 h','Regulatory Notification','ANTIC (Cameroon cybersecurity authority) and, where relevant, MINSANTE are notified within 72 hours of confirmed breach, as required by law.'],
                    ['≤ 72 h','Patient Notification','Affected patients are notified directly via their registered contact with clear information about what data was involved, what we are doing, and what steps they can take.'],
                    ['Post-event','Review & Remediation','Full post-incident review is conducted. Technical and procedural improvements are implemented and documented.'],
                ] as [$time,$step,$desc])
                <div style="display:flex;gap:1.25rem;align-items:flex-start;background:#fff;border:1px solid #E2E8F0;border-radius:1rem;padding:1.25rem 1.5rem;">
                    <div style="min-width:4rem;padding:4px 10px;background:#0F2744;color:#fff;border-radius:6px;font-size:0.75rem;font-weight:700;text-align:center;flex-shrink:0;">{{ $time }}</div>
                    <div>
                        <p style="margin:0 0 0.25rem;font-weight:700;color:#0F2744;">{{ $step }}</p>
                        <p style="margin:0;font-size:0.875rem;color:#4B5563;">{{ $desc }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Responsible disclosure --}}
    <section class="section">
        <div class="container" style="max-width:760px;">
            <div style="background:#0F2744;border-radius:1.5rem;padding:3rem;color:#fff;text-align:center;">
                <i data-lucide="bug" style="width:2.5rem;height:2.5rem;color:#14B8A6;margin-bottom:1.5rem;"></i>
                <h2 style="color:#fff;margin-bottom:1rem;">{{ __('public.sec_page.disclosure_title') }}</h2>
                <p style="color:rgba(255,255,255,.75);margin-bottom:1.5rem;">{{ __('public.sec_page.disclosure_body') }}</p>
                <div style="display:flex;justify-content:center;gap:1rem;flex-wrap:wrap;">
                    <a href="mailto:security@opeshealthsystems.com" class="btn btn-primary">{{ __('public.sec_page.btn_report') }}</a>
                    <a href="{{ route('public.privacy') }}" class="btn" style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.25);">{{ __('public.sec_page.btn_privacy') }}</a>
                </div>
            </div>
        </div>
    </section>
@endsection

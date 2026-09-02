@extends('layouts.demo')

@php $l = app()->getLocale(); $t = fn(string $k, string $fallback = '') => __("public.demo.{$k}", [], $l) ?: $fallback; @endphp

@section('title', $t('page_title', 'OpesCare Demo Access') . ' — Internal')

@section('topbar_badge')
    <span class="demo-badge demo-badge-internal" aria-label="Internal demo mode">
        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        {{ $t('internal_label', 'Internal Demo') }}
    </span>
@endsection

@section('content')

<style>
.demo-warning-sub { margin-top:.25rem;font-size:.8rem;opacity:.85; }
.di-pw-notice { background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.75rem;font-size:.875rem;color:#166534;display:flex;align-items:center;gap:.625rem; }
.di-pw-notice code { background:#dcfce7;padding:.1rem .4rem;border-radius:4px;font-size:.85rem; }
.di-page-head { margin-bottom:2rem; }
.di-dev-card { border-color:#0ea5e9 !important;grid-column:span 2; }
.di-dev-icon { background:#e0f2fe;color:#0284c7; }
.di-dev-inner { display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:.75rem; }
.di-dev-portal { background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:.875rem; }
.di-dev-portal-label { font-size:.75rem;font-weight:600;color:#0369a1;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem; }
.di-dev-portal-desc { font-size:.8rem;color:#0c4a6e;margin-bottom:.75rem; }
.di-dev-meta-gap { margin-bottom:.75rem; }
.di-btn-sky { background:#0284c7;width:100%; }
.di-dev-creds { background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:.875rem; }
.di-dev-creds-label { font-size:.75rem;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem; }
.di-creds-meta { margin-bottom:.5rem; }
.di-creds-endpoint { font-size:.72rem; }
.di-creds-scopes { font-size:.775rem;color:#64748b;margin-bottom:.5rem;line-height:1.5; }
.di-sandbox-warn { background:#fef9c3;border:1px solid #fde047;border-radius:6px;padding:.5rem .625rem;font-size:.775rem;color:#713f12; }
.di-admin-card { border-color:#fbbf24 !important; }
.di-btn-admin { background:#0F4C81; }
.di-reset-section { background:#fff1f2;border:1px solid #fecdd3;border-radius:10px;padding:1.25rem 1.5rem;margin-bottom:2rem; }
.di-reset-title { font-weight:600;color:#9f1239;margin-bottom:.5rem;font-size:.9375rem;display:flex;align-items:center;gap:.5rem; }
.di-reset-desc { font-size:.875rem;color:#be123c;margin-bottom:.875rem; }
.di-footer { text-align:center;padding-top:1rem;font-size:.8125rem;color:#94a3b8; }
.di-footer a { color:#64748b;text-decoration:none; }
@media (max-width:640px) { .di-dev-inner { grid-template-columns:1fr; } .di-dev-card { grid-column:span 1; } }
</style>

{{-- Warning Banner --}}
<div class="demo-warning" role="alert">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    <div>
        <strong>{{ $t('warning_banner', 'DEMO ENVIRONMENT') }}</strong>
        <div class="demo-warning-sub">Internal demo — For product team, developers, and QA only. All data is fake.</div>
    </div>
</div>

{{-- Header --}}
<div class="di-page-head">
    <h1 class="demo-section-title">{{ $t('page_title', 'OpesCare Demo Access') }} — {{ $t('internal_label', 'Internal') }}</h1>
    <p class="demo-section-sub">{{ $t('internal_desc', 'Full-access demo for the product team, developers, and QA.') }}</p>
    <a href="{{ route('demo.public') }}" class="demo-switch-link">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        {{ $t('switch_public', 'View Public Demo') }}
    </a>
</div>

{{-- Common Password Notice --}}
<div class="di-pw-notice">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
    <span>{{ $t('common_password', 'Common demo password') }}: <code>DemoPass!2026</code></span>
</div>

{{-- All roles grid --}}
<div class="demo-grid" role="list">

    {{-- Patient --}}
    <div class="demo-card" role="listitem">
        <div class="demo-card-header">
            <div class="demo-card-icon teal" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_patient', 'Patient') }}</div>
                <div class="demo-card-org">{{ $t('org_patient', 'Demo Patient Portal') }}</div>
            </div>
        </div>
        <div class="demo-card-meta">
            <div><strong>Email:</strong> <code>demo.patient@opescare.test</code></div>
            <div><strong>Health ID:</strong> <code>OC-DEMO-PAT-0001</code></div>
        </div>
        <form method="POST" action="{{ route('demo.login-as') }}">
            @csrf
            <input type="hidden" name="role" value="patient">
            <input type="hidden" name="email" value="demo.patient@opescare.test">
            <input type="hidden" name="mode" value="internal">
            <button type="submit" class="demo-login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                {{ $t('login_as', 'Login as') }} {{ $t('role_patient', 'Patient') }}
            </button>
        </form>
    </div>

    {{-- Doctor --}}
    <div class="demo-card" role="listitem">
        <div class="demo-card-header">
            <div class="demo-card-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"/><path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"/><circle cx="20" cy="10" r="2"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_doctor', 'Doctor') }}</div>
                <div class="demo-card-org">{{ $t('org_hospital', 'Demo Central Hospital') }}</div>
            </div>
        </div>
        <div class="demo-card-meta">
            <div><strong>Email:</strong> <code>demo.doctor@opescare.test</code></div>
        </div>
        <form method="POST" action="{{ route('demo.login-as') }}">
            @csrf
            <input type="hidden" name="role" value="doctor">
            <input type="hidden" name="email" value="demo.doctor@opescare.test">
            <input type="hidden" name="mode" value="internal">
            <button type="submit" class="demo-login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                {{ $t('login_as', 'Login as') }} {{ $t('role_doctor', 'Doctor') }}
            </button>
        </form>
    </div>

    {{-- Multi-Facility Doctor --}}
    <div class="demo-card" role="listitem">
        <div class="demo-card-header">
            <div class="demo-card-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_multi_doctor', 'Multi-Facility Doctor') }}</div>
                <div class="demo-card-org">3 Demo Facilities</div>
            </div>
        </div>
        <div class="demo-card-meta">
            <div><strong>Email:</strong> <code>demo.multi.doctor@opescare.test</code></div>
            <div><strong>Facilities:</strong> Central · City Clinic · Specialist</div>
        </div>
        <form method="POST" action="{{ route('demo.login-as') }}">
            @csrf
            <input type="hidden" name="role" value="multi_doctor">
            <input type="hidden" name="email" value="demo.multi.doctor@opescare.test">
            <input type="hidden" name="mode" value="internal">
            <button type="submit" class="demo-login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                {{ $t('login_as', 'Login as') }} {{ $t('role_multi_doctor', 'Multi-Facility Doctor') }}
            </button>
        </form>
    </div>

    {{-- Nurse --}}
    <div class="demo-card" role="listitem">
        <div class="demo-card-header">
            <div class="demo-card-icon green" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_nurse', 'Nurse') }}</div>
                <div class="demo-card-org">{{ $t('org_hospital', 'Demo Central Hospital') }}</div>
            </div>
        </div>
        <div class="demo-card-meta">
            <div><strong>Email:</strong> <code>demo.nurse@opescare.test</code></div>
        </div>
        <form method="POST" action="{{ route('demo.login-as') }}">
            @csrf
            <input type="hidden" name="role" value="nurse">
            <input type="hidden" name="email" value="demo.nurse@opescare.test">
            <input type="hidden" name="mode" value="internal">
            <button type="submit" class="demo-login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                {{ $t('login_as', 'Login as') }} {{ $t('role_nurse', 'Nurse') }}
            </button>
        </form>
    </div>

    {{-- Hospital Admin --}}
    <div class="demo-card" role="listitem">
        <div class="demo-card-header">
            <div class="demo-card-icon indigo" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 6v4"/><path d="M14 14H10"/><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_hospital_admin', 'Hospital Admin') }}</div>
                <div class="demo-card-org">{{ $t('org_hospital', 'Demo Central Hospital') }}</div>
            </div>
        </div>
        <div class="demo-card-meta">
            <div><strong>Email:</strong> <code>demo.facility.admin@opescare.test</code></div>
        </div>
        <form method="POST" action="{{ route('demo.login-as') }}">
            @csrf
            <input type="hidden" name="role" value="facility_admin">
            <input type="hidden" name="email" value="demo.facility.admin@opescare.test">
            <input type="hidden" name="mode" value="internal">
            <button type="submit" class="demo-login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                {{ $t('login_as', 'Login as') }} {{ $t('role_hospital_admin', 'Hospital Admin') }}
            </button>
        </form>
    </div>

    {{-- Pharmacist --}}
    <div class="demo-card" role="listitem">
        <div class="demo-card-header">
            <div class="demo-card-icon purple" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_pharmacy', 'Pharmacist') }}</div>
                <div class="demo-card-org">{{ $t('org_pharmacy', 'DemoCare Pharmacy') }}</div>
            </div>
        </div>
        <div class="demo-card-meta">
            <div><strong>Email:</strong> <code>demo.pharmacist@opescare.test</code></div>
        </div>
        <form method="POST" action="{{ route('demo.login-as') }}">
            @csrf
            <input type="hidden" name="role" value="pharmacist">
            <input type="hidden" name="email" value="demo.pharmacist@opescare.test">
            <input type="hidden" name="mode" value="internal">
            <button type="submit" class="demo-login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                {{ $t('login_as', 'Login as') }} {{ $t('role_pharmacy', 'Pharmacist') }}
            </button>
        </form>
    </div>

    {{-- Lab --}}
    <div class="demo-card" role="listitem">
        <div class="demo-card-header">
            <div class="demo-card-icon amber" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 3h6l3 7H6L9 3z"/><path d="M6 10 4.68 16.39a2.1 2.1 0 0 0 2.06 2.61h10.52a2.1 2.1 0 0 0 2.06-2.61L18 10"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_lab', 'Lab Validator') }}</div>
                <div class="demo-card-org">{{ $t('org_lab', 'Demo Diagnostic Laboratory') }}</div>
            </div>
        </div>
        <div class="demo-card-meta">
            <div><strong>Email:</strong> <code>demo.labtech@opescare.test</code></div>
        </div>
        <form method="POST" action="{{ route('demo.login-as') }}">
            @csrf
            <input type="hidden" name="role" value="lab_tech">
            <input type="hidden" name="email" value="demo.labtech@opescare.test">
            <input type="hidden" name="mode" value="internal">
            <button type="submit" class="demo-login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                {{ $t('login_as', 'Login as') }} {{ $t('role_lab', 'Lab Validator') }}
            </button>
        </form>
    </div>

    {{-- Insurance --}}
    <div class="demo-card" role="listitem">
        <div class="demo-card-header">
            <div class="demo-card-icon rose" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_insurance', 'Insurance Officer') }}</div>
                <div class="demo-card-org">{{ $t('org_insurance', 'DemoCare Insurance') }}</div>
            </div>
        </div>
        <div class="demo-card-meta">
            <div><strong>Email:</strong> <code>demo.insurance@opescare.test</code></div>
        </div>
        <form method="POST" action="{{ route('demo.login-as') }}">
            @csrf
            <input type="hidden" name="role" value="insurance">
            <input type="hidden" name="email" value="demo.insurance@opescare.test">
            <input type="hidden" name="mode" value="internal">
            <button type="submit" class="demo-login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                {{ $t('login_as', 'Login as') }} {{ $t('role_insurance', 'Insurance Officer') }}
            </button>
        </form>
    </div>

    {{-- Public Health Officer —— internal only --}}
    <div class="demo-card" role="listitem">
        <div class="demo-card-header">
            <div class="demo-card-icon indigo" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_public_health', 'Public Health Officer') }}</div>
                <div class="demo-card-org">{{ $t('org_public_health', 'Demo Public Health Unit') }}</div>
            </div>
        </div>
        <div class="demo-card-meta">
            <div><strong>Email:</strong> <code>demo.publichealth@opescare.test</code></div>
        </div>
        <div class="demo-card-body">Draft and simulate notifiable disease reports, blood shortage alerts, and public health surveillance drafts. No real government submission.</div>
        <form method="POST" action="{{ route('demo.login-as') }}">
            @csrf
            <input type="hidden" name="role" value="public_health">
            <input type="hidden" name="email" value="demo.publichealth@opescare.test">
            <input type="hidden" name="mode" value="internal">
            <button type="submit" class="demo-login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                {{ $t('login_as', 'Login as') }} {{ $t('role_public_health', 'Public Health Officer') }}
            </button>
        </form>
    </div>

    {{-- Developer / API Partner --}}
    <div class="demo-card di-dev-card" role="listitem" aria-label="Developer and API access">
        <div class="demo-card-header">
            <div class="demo-card-icon di-dev-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_developer', 'Developer / API Partner') }}</div>
                <div class="demo-card-org">OpesCare Connect — Sandbox Environment</div>
            </div>
        </div>

        {{-- Two-column layout: portal login | api credentials --}}
        <div class="di-dev-inner">

            {{-- Left: Portal login --}}
            <div class="di-dev-portal">
                <div class="di-dev-portal-label">
                    <i data-lucide="globe" style="width:.8rem;height:.8rem;display:inline;vertical-align:middle;margin-right:.25rem;" aria-hidden="true"></i>
                    Developer Portal
                </div>
                <div class="demo-card-meta di-dev-meta-gap">
                    <div><strong>Email:</strong> <code>demo.developer@opescare.test</code></div>
                    <div><strong>Password:</strong> <code>DemoPass!2026</code></div>
                </div>
                <div class="di-dev-portal-desc">Access the API Connect portal, manage sandbox apps, view webhook logs, and explore integration docs.</div>
                <form method="POST" action="{{ route('demo.login-as') }}">
                    @csrf
                    <input type="hidden" name="role" value="developer">
                    <input type="hidden" name="email" value="demo.developer@opescare.test">
                    <input type="hidden" name="mode" value="internal">
                    <button type="submit" class="demo-login-btn di-btn-sky">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                        Open Developer Portal
                    </button>
                </form>
            </div>

            {{-- Right: API credentials --}}
            <div class="di-dev-creds">
                <div class="di-dev-creds-label">
                    <i data-lucide="key-round" style="width:.8rem;height:.8rem;display:inline;vertical-align:middle;margin-right:.25rem;" aria-hidden="true"></i>
                    Sandbox API Credentials
                </div>
                <div class="demo-card-meta di-creds-meta">
                    <div><strong>Endpoint:</strong> <code class="di-creds-endpoint">POST /api/v1/connect/auth/token</code></div>
                    <div><strong>client_id:</strong> <code>demo_dev_sandbox</code></div>
                    <div><strong>client_secret:</strong> <code>demo_secret_sandbox_2026</code></div>
                    <div><strong>grant_type:</strong> <code>client_credentials</code></div>
                </div>
                <div class="di-creds-scopes">
                    <strong>Scopes:</strong> pharmacy_stock.sync &middot; blood_stock.sync &middot; lab_results.push &middot; records.push &middot; patients.search
                </div>
                <div class="di-sandbox-warn">
                    <i data-lucide="triangle-alert" style="width:.8rem;height:.8rem;display:inline;vertical-align:middle;margin-right:.25rem;" aria-hidden="true"></i>
                    Sandbox only — no real data is written or read.
                </div>
            </div>
        </div>
    </div>

    {{-- Platform Admin --}}
    <div class="demo-card di-admin-card" role="listitem">
        <div class="demo-card-header">
            <div class="demo-card-icon purple" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_admin', 'Platform Admin') }}</div>
                <div class="demo-card-org">{{ $t('org_platform', 'OpesCare Platform') }}</div>
            </div>
        </div>
        <div class="demo-card-meta">
            <div><strong>Email:</strong> <code>demo.admin@opescare.test</code></div>
        </div>
        <div class="demo-card-body">Access the full governance dashboard, role management, demo partner governance, and platform-level admin tools. Production-like capabilities on demo data only.</div>
        <form method="POST" action="{{ route('demo.login-as') }}">
            @csrf
            <input type="hidden" name="role" value="platform_admin">
            <input type="hidden" name="email" value="demo.admin@opescare.test">
            <input type="hidden" name="mode" value="internal">
            <button type="submit" class="demo-login-btn di-btn-admin">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                {{ $t('login_as', 'Login as') }} {{ $t('role_admin', 'Platform Admin') }}
            </button>
        </form>
    </div>

</div>{{-- /.demo-grid --}}

<hr class="demo-divider">

{{-- Demo Reset Section --}}
<div class="di-reset-section">
    <h4 class="di-reset-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
        {{ $t('reset_title', 'Demo Reset') }}
    </h4>
    <p class="di-reset-desc">{{ $t('reset_desc', 'Reset all demo data to its default state.') }}</p>
    <form method="POST" action="{{ url('/artisan/demo-reset') }}" onsubmit="return confirm('This will reset all demo data. Continue?');">
        @csrf
        <button type="submit" class="demo-reset-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
            {{ $t('reset_btn', 'Reset Demo Data') }}
        </button>
    </form>
</div>

{{-- Limitations --}}
<div class="demo-limitations">
    <h4>{{ $t('limitations_title', 'Known Demo Limitations') }}</h4>
    <ul>
        <li>SMS, email, and push notifications are simulated — no real messages are sent.</li>
        <li>Payments, insurance submissions, and government reports are simulated only.</li>
        <li>API key generation creates temporary demo-only keys — no production credentials.</li>
        <li>All actions are scoped to demo data and cannot affect production records.</li>
        <li>Internal demo sessions expire after 2 hours.</li>
        <li>Demo reset revokes all active sessions and reseeds data from scratch.</li>
    </ul>
</div>

<div class="di-footer">
    {{ $t('warning_data_note', 'DEMO DATA — NOT REAL PATIENT INFORMATION') }}
    &nbsp;·&nbsp;
    <a href="{{ url('/') }}">Return to OpesCare</a>
</div>

@endsection

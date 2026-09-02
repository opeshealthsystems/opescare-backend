@extends('layouts.demo')

@php $l = app()->getLocale(); $t = fn(string $k, string $fallback = '') => __("public.demo.{$k}", [], $l) ?: $fallback; @endphp

@section('title', $t('page_title', 'OpesCare Demo Access'))

@section('topbar_badge')
    <span class="demo-badge demo-badge-public" aria-label="Public demo mode">
        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 8 12 12 14 14"/></svg>
        {{ $t('public_label', 'Public Demo') }}
    </span>
@endsection

@section('content')

<style>
.demo-warning-sub { margin-top:.25rem;font-size:.8rem;opacity:.85; }
.dp-page-head { margin-bottom:2rem; }
.dp-dev-card { border-color:#0ea5e9 !important;grid-column:span 2; }
.dp-dev-icon { background:#e0f2fe;color:#0284c7; }
.dp-dev-inner { display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:.75rem; }
.dp-dev-portal { background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:.875rem; }
.dp-dev-portal-label { font-size:.75rem;font-weight:600;color:#0369a1;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem; }
.dp-dev-portal-desc { font-size:.8rem;color:#0c4a6e;margin-bottom:.75rem; }
.dp-dev-meta-gap { margin-bottom:.75rem; }
.dp-btn-sky { background:#0284c7;width:100%; }
.dp-dev-creds { background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:.875rem; }
.dp-dev-creds-label { font-size:.75rem;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem; }
.dp-creds-meta { margin-bottom:.5rem; }
.dp-creds-endpoint { font-size:.72rem; }
.dp-creds-scopes { font-size:.775rem;color:#64748b;margin-bottom:.5rem;line-height:1.5; }
.dp-sandbox-warn { background:#fef9c3;border:1px solid #fde047;border-radius:6px;padding:.5rem .625rem;font-size:.775rem;color:#713f12; }
.dp-limitations-icon { vertical-align:middle;margin-right:.375rem; }
.dp-footer { text-align:center;padding-top:1rem;font-size:.8125rem;color:#94a3b8; }
.dp-footer a { color:#64748b;text-decoration:none; }
@media (max-width:640px) { .dp-dev-inner { grid-template-columns:1fr; } .dp-dev-card { grid-column:span 1; } }
</style>

{{-- Warning Banner --}}
<div class="demo-warning" role="alert" aria-live="polite">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    <div>
        <strong>{{ $t('warning_banner', 'DEMO ENVIRONMENT') }}</strong>
        <div class="demo-warning-sub">{{ $t('warning_data_note', 'DEMO DATA — NOT REAL PATIENT INFORMATION') }}</div>
    </div>
</div>

{{-- Page Header --}}
<div class="dp-page-head">
    <h1 class="demo-section-title">{{ $t('page_title', 'OpesCare Demo Access') }}</h1>
    <p class="demo-section-sub">{{ $t('page_subtitle', 'Explore OpesCare using safe demo accounts and fake healthcare data.') }}</p>
    <a href="{{ route('demo.internal') }}" class="demo-switch-link">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
        {{ $t('switch_internal', 'View Internal Demo') }}
    </a>
</div>

{{-- Role Cards Grid --}}
<div class="demo-grid" role="list">

    {{-- Patient --}}
    <div class="demo-card" role="listitem">
        <div class="demo-card-header">
            <div class="demo-card-icon teal" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
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
        <div class="demo-card-body">View Health ID, QR code, timeline, lab results, prescriptions, consent and access logs.</div>
        <form method="POST" action="{{ route('demo.login-as') }}">
            @csrf
            <input type="hidden" name="role" value="patient">
            <input type="hidden" name="email" value="demo.patient@opescare.test">
            <input type="hidden" name="mode" value="public">
            <button type="submit" class="demo-login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                {{ $t('login_as', 'Login as') }} {{ $t('role_patient', 'Patient') }}
            </button>
        </form>
    </div>

    {{-- Guardian --}}
    <div class="demo-card" role="listitem">
        <div class="demo-card-header">
            <div class="demo-card-icon teal" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/><line x1="12" y1="16" x2="12" y2="22"/><line x1="9" y1="19" x2="15" y2="19"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_guardian', 'Guardian') }}</div>
                <div class="demo-card-org">{{ $t('org_guardian', 'Demo Guardian Portal') }}</div>
            </div>
        </div>
        <div class="demo-card-meta">
            <div><strong>Email:</strong> <code>demo.guardian@opescare.test</code></div>
            <div><strong>Dependent:</strong> <code>OC-DEMO-CHILD-0001</code></div>
        </div>
        <div class="demo-card-body">Approve consent for dependents, view their timeline and access logs.</div>
        <form method="POST" action="{{ route('demo.login-as') }}">
            @csrf
            <input type="hidden" name="role" value="guardian">
            <input type="hidden" name="email" value="demo.guardian@opescare.test">
            <input type="hidden" name="mode" value="public">
            <button type="submit" class="demo-login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                {{ $t('login_as', 'Login as') }} {{ $t('role_guardian', 'Guardian') }}
            </button>
        </form>
    </div>

    {{-- Doctor --}}
    <div class="demo-card" role="listitem">
        <div class="demo-card-header">
            <div class="demo-card-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"/><path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"/><circle cx="20" cy="10" r="2"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_doctor', 'Doctor') }}</div>
                <div class="demo-card-org">{{ $t('org_hospital', 'Demo Central Hospital') }}</div>
            </div>
        </div>
        <div class="demo-card-meta">
            <div><strong>Email:</strong> <code>demo.doctor@opescare.test</code></div>
            <div><strong>Name:</strong> Dr. Amara Diallo</div>
        </div>
        <div class="demo-card-body">Search patients, request consent, view approved summary, create consultation, order labs, issue prescriptions.</div>
        <form method="POST" action="{{ route('demo.login-as') }}">
            @csrf
            <input type="hidden" name="role" value="doctor">
            <input type="hidden" name="email" value="demo.doctor@opescare.test">
            <input type="hidden" name="mode" value="public">
            <button type="submit" class="demo-login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                {{ $t('login_as', 'Login as') }} {{ $t('role_doctor', 'Doctor') }}
            </button>
        </form>
    </div>

    {{-- Nurse --}}
    <div class="demo-card" role="listitem">
        <div class="demo-card-header">
            <div class="demo-card-icon green" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_nurse', 'Nurse') }}</div>
                <div class="demo-card-org">{{ $t('org_hospital', 'Demo Central Hospital') }}</div>
            </div>
        </div>
        <div class="demo-card-meta">
            <div><strong>Email:</strong> <code>demo.nurse@opescare.test</code></div>
            <div><strong>Name:</strong> Nurse Fatou Traoré</div>
        </div>
        <div class="demo-card-body">View triage queue, record vital signs, write nursing notes, simulate medication administration.</div>
        <form method="POST" action="{{ route('demo.login-as') }}">
            @csrf
            <input type="hidden" name="role" value="nurse">
            <input type="hidden" name="email" value="demo.nurse@opescare.test">
            <input type="hidden" name="mode" value="public">
            <button type="submit" class="demo-login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                {{ $t('login_as', 'Login as') }} {{ $t('role_nurse', 'Nurse') }}
            </button>
        </form>
    </div>

    {{-- Hospital Admin --}}
    <div class="demo-card" role="listitem">
        <div class="demo-card-header">
            <div class="demo-card-icon indigo" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6v4"/><path d="M14 14H10"/><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_hospital_admin', 'Hospital Admin') }}</div>
                <div class="demo-card-org">{{ $t('org_hospital', 'Demo Central Hospital') }}</div>
            </div>
        </div>
        <div class="demo-card-meta">
            <div><strong>Email:</strong> <code>demo.facility.admin@opescare.test</code></div>
        </div>
        <div class="demo-card-body">View facility dashboard, staff list, departments, services, audit summaries, integration status, and reports.</div>
        <form method="POST" action="{{ route('demo.login-as') }}">
            @csrf
            <input type="hidden" name="role" value="facility_admin">
            <input type="hidden" name="email" value="demo.facility.admin@opescare.test">
            <input type="hidden" name="mode" value="public">
            <button type="submit" class="demo-login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                {{ $t('login_as', 'Login as') }} {{ $t('role_hospital_admin', 'Hospital Admin') }}
            </button>
        </form>
    </div>

    {{-- Pharmacist --}}
    <div class="demo-card" role="listitem">
        <div class="demo-card-header">
            <div class="demo-card-icon purple" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_pharmacy', 'Pharmacist') }}</div>
                <div class="demo-card-org">{{ $t('org_pharmacy', 'DemoCare Pharmacy') }}</div>
            </div>
        </div>
        <div class="demo-card-meta">
            <div><strong>Email:</strong> <code>demo.pharmacist@opescare.test</code></div>
        </div>
        <div class="demo-card-body">View demo prescriptions, dispense medicine, manage pharmacy stock, simulate stock sync and reservations.</div>
        <form method="POST" action="{{ route('demo.login-as') }}">
            @csrf
            <input type="hidden" name="role" value="pharmacist">
            <input type="hidden" name="email" value="demo.pharmacist@opescare.test">
            <input type="hidden" name="mode" value="public">
            <button type="submit" class="demo-login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                {{ $t('login_as', 'Login as') }} {{ $t('role_pharmacy', 'Pharmacist') }}
            </button>
        </form>
    </div>

    {{-- Lab --}}
    <div class="demo-card" role="listitem">
        <div class="demo-card-header">
            <div class="demo-card-icon amber" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h6l3 7H6L9 3z"/><path d="M6 10 4.68 16.39a2.1 2.1 0 0 0 2.06 2.61h10.52a2.1 2.1 0 0 0 2.06-2.61L18 10"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_lab', 'Lab Validator') }}</div>
                <div class="demo-card-org">{{ $t('org_lab', 'Demo Diagnostic Laboratory') }}</div>
            </div>
        </div>
        <div class="demo-card-meta">
            <div><strong>Email:</strong> <code>demo.labtech@opescare.test</code></div>
        </div>
        <div class="demo-card-body">View lab orders, collect samples, enter and validate results, release results, trigger critical alerts.</div>
        <form method="POST" action="{{ route('demo.login-as') }}">
            @csrf
            <input type="hidden" name="role" value="lab_tech">
            <input type="hidden" name="email" value="demo.labtech@opescare.test">
            <input type="hidden" name="mode" value="public">
            <button type="submit" class="demo-login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                {{ $t('login_as', 'Login as') }} {{ $t('role_lab', 'Lab Validator') }}
            </button>
        </form>
    </div>

    {{-- Insurance --}}
    <div class="demo-card" role="listitem">
        <div class="demo-card-header">
            <div class="demo-card-icon rose" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_insurance', 'Insurance Officer') }}</div>
                <div class="demo-card-org">{{ $t('org_insurance', 'DemoCare Insurance') }}</div>
            </div>
        </div>
        <div class="demo-card-meta">
            <div><strong>Email:</strong> <code>demo.insurance@opescare.test</code></div>
        </div>
        <div class="demo-card-body">View demo claims, check eligibility, review preauthorization, approve/query/reject claims — minimum-necessary record access only.</div>
        <form method="POST" action="{{ route('demo.login-as') }}">
            @csrf
            <input type="hidden" name="role" value="insurance">
            <input type="hidden" name="email" value="demo.insurance@opescare.test">
            <input type="hidden" name="mode" value="public">
            <button type="submit" class="demo-login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                {{ $t('login_as', 'Login as') }} {{ $t('role_insurance', 'Insurance Officer') }}
            </button>
        </form>
    </div>

    {{-- Developer / API Partner --}}
    <div class="demo-card dp-dev-card" role="listitem" aria-label="Developer and API access">
        <div class="demo-card-header">
            <div class="demo-card-icon dp-dev-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            </div>
            <div>
                <div class="demo-card-role">{{ $t('role_developer', 'Developer / API Partner') }}</div>
                <div class="demo-card-org">OpesCare Connect — Sandbox Environment</div>
            </div>
        </div>

        <div class="dp-dev-inner">

            {{-- Left: Portal login --}}
            <div class="dp-dev-portal">
                <div class="dp-dev-portal-label">
                    <i data-lucide="globe" style="width:.8rem;height:.8rem;display:inline;vertical-align:middle;margin-right:.25rem;" aria-hidden="true"></i>
                    Developer Portal
                </div>
                <div class="demo-card-meta dp-dev-meta-gap">
                    <div><strong>Email:</strong> <code>demo.developer@opescare.test</code></div>
                    <div><strong>Password:</strong> <code>DemoPass!2026</code></div>
                </div>
                <div class="dp-dev-portal-desc">Access the API Connect portal, manage sandbox apps, view webhook logs, and explore integration docs.</div>
                <form method="POST" action="{{ route('demo.login-as') }}">
                    @csrf
                    <input type="hidden" name="role" value="developer">
                    <input type="hidden" name="email" value="demo.developer@opescare.test">
                    <input type="hidden" name="mode" value="public">
                    <button type="submit" class="demo-login-btn dp-btn-sky">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                        Open Developer Portal
                    </button>
                </form>
            </div>

            {{-- Right: Sandbox API credentials --}}
            <div class="dp-dev-creds">
                <div class="dp-dev-creds-label">
                    <i data-lucide="key-round" style="width:.8rem;height:.8rem;display:inline;vertical-align:middle;margin-right:.25rem;" aria-hidden="true"></i>
                    Sandbox API Credentials
                </div>
                <div class="demo-card-meta dp-creds-meta">
                    <div><strong>Endpoint:</strong> <code class="dp-creds-endpoint">POST /api/v1/connect/auth/token</code></div>
                    <div><strong>client_id:</strong> <code>demo_dev_sandbox</code></div>
                    <div><strong>client_secret:</strong> <code>demo_secret_sandbox_2026</code></div>
                    <div><strong>grant_type:</strong> <code>client_credentials</code></div>
                </div>
                <div class="dp-creds-scopes">
                    <strong>Scopes:</strong> pharmacy_stock.sync &middot; blood_stock.sync &middot; lab_results.push &middot; records.push &middot; patients.search
                </div>
                <div class="dp-sandbox-warn">
                    <i data-lucide="triangle-alert" style="width:.8rem;height:.8rem;display:inline;vertical-align:middle;margin-right:.25rem;" aria-hidden="true"></i>
                    Sandbox only — no real data is written or read.
                </div>
            </div>
        </div>
    </div>

</div>{{-- /.demo-grid --}}

<hr class="demo-divider">

{{-- Limitations --}}
<div class="demo-limitations">
    <h4>
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="dp-limitations-icon" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        {{ $t('limitations_title', 'Known Demo Limitations') }}
    </h4>
    <ul>
        <li>SMS, email, and push notifications are simulated — no real messages are sent.</li>
        <li>Payments, insurance submissions, and government reports are simulated only.</li>
        <li>API key generation creates temporary demo-only keys.</li>
        <li>All actions are scoped to demo data and cannot affect production records.</li>
        <li>Demo session expires after 30 minutes of inactivity.</li>
    </ul>
</div>

<div class="dp-footer">
    {{ $t('warning_data_note', 'DEMO DATA — NOT REAL PATIENT INFORMATION') }}
    &nbsp;·&nbsp;
    <a href="{{ url('/') }}">Return to OpesCare</a>
</div>

@endsection

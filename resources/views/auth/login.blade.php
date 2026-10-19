@extends('layouts.auth')

@section('title', __('login_page.head_title'))
@section('auth_bare', '1')
@section('body_class', 'f-login')

@push('head')
    {{-- The background is above the fold: preload it, never lazy-load it. --}}
    <link rel="preload" as="image" href="{{ asset('brand/login/background.webp') }}" type="image/webp">
    <link rel="stylesheet" href="{{ asset('css/login-page.css') }}?v={{ @filemtime(public_path('css/login-page.css')) ?: '1' }}">
@endpush

{{--
    Sign-in page, redesigned 2026-10-18 to the owner's references
    (docs/design/login-2026-10-18). Two columns on desktop (brand panel left,
    white card right); brand header above the card on phones.

    Behaviour contract (unchanged): POST login.submit, CSRF, throttle, flash
    keys, old() input, password toggle via [data-f-toggle] (layout script with
    a CSP nonce), demo panel markup and ids untouched. The identifier field is
    name="login" (email OR Health ID); an error raised on either "login" or the
    legacy "email" key is shown under it.
--}}
@section('content')
@php
    $loginError = $errors->first('login') ?: $errors->first('email');
@endphp
<div class="lg-page">
    <div class="lg-topbar">
        <nav class="lg-lang" aria-label="{{ __('login_page.language') }}">
            @include('partials.finder.icon', ['name' => 'globe', 'size' => 18])
            <a href="{{ route('lang.switch', 'en') }}" @class(['is-active' => app()->getLocale() === 'en']) lang="en" hreflang="en">English</a>
            <span aria-hidden="true">|</span>
            <a href="{{ route('lang.switch', 'fr') }}" @class(['is-active' => app()->getLocale() === 'fr']) lang="fr" hreflang="fr">Français</a>
        </nav>
    </div>

    <div class="lg-grid">
        {{-- Brand panel --}}
        <section class="lg-brand" aria-labelledby="lg-brand-name">
            <div class="lg-caps">
                <p>{{ __('login_page.caps_values') }}</p>
                <span class="lg-rule" aria-hidden="true"></span>
                <p>{{ __('login_page.caps_promise') }}</p>
            </div>

            <a href="{{ route('public.landing') }}" class="lg-mark" aria-label="{{ __('login_page.home') }}">
                <img src="{{ asset('brand/opescare-logo-512.png') }}" width="512" height="512" alt="" fetchpriority="high">
            </a>
            <p class="lg-wordmark" id="lg-brand-name">OPES<span>CARE</span></p>
            <p class="lg-tagline">{{ __('login_page.brand_tagline') }}</p>
            <span class="lg-rule lg-rule--center" aria-hidden="true"></span>
            <p class="lg-lead">{{ __('login_page.brand_lead') }}</p>

            <ul class="lg-features" aria-label="{{ __('login_page.features_label') }}">
                <li>@include('partials.finder.icon', ['name' => 'fingerprint', 'size' => 34])<span>{{ __('login_page.feature_identity') }}</span></li>
                <li>@include('partials.finder.icon', ['name' => 'network', 'size' => 34])<span>{{ __('login_page.feature_connected') }}</span></li>
                <li>@include('partials.finder.icon', ['name' => 'shield-check', 'size' => 34])<span>{{ __('login_page.feature_trusted') }}</span></li>
                <li>@include('partials.finder.icon', ['name' => 'chart-column', 'size' => 34])<span>{{ __('login_page.feature_communities') }}</span></li>
            </ul>
            <ul class="lg-values" aria-label="{{ __('login_page.values_label') }}">
                <li>@include('partials.finder.icon', ['name' => 'lock', 'size' => 20])<span>{{ __('login_page.value_secure') }}</span></li>
                <li>@include('partials.finder.icon', ['name' => 'database', 'size' => 20])<span>{{ __('login_page.value_interop') }}</span></li>
                <li>@include('partials.finder.icon', ['name' => 'users', 'size' => 20])<span>{{ __('login_page.value_people') }}</span></li>
                <li>@include('partials.finder.icon', ['name' => 'earth', 'size' => 20])<span>{{ __('login_page.value_africa') }}</span></li>
            </ul>
        </section>

        {{-- Sign-in card --}}
        <section class="lg-card" aria-labelledby="lg-title">
            <h1 class="lg-title" id="lg-title">{{ __('login_page.welcome') }}</h1>
            <p class="lg-sub">{{ __('login_page.subtitle') }}</p>

            @if(session('success'))
                <div class="f-auth-alert f-auth-alert--success" role="status">
                    @include('partials.finder.icon', ['name' => 'check', 'size' => 18])
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if(session('error'))
                <div class="f-auth-alert f-auth-alert--danger" role="alert">
                    @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            <form action="{{ route('login.submit') }}" method="POST" novalidate class="lg-form">
                @csrf

                <div class="lg-field">
                    <label for="login" class="lg-label">{{ __('login_page.identifier') }}</label>
                    <div class="lg-control">
                        @include('partials.finder.icon', ['name' => 'mail', 'size' => 20, 'class' => 'lg-control__icon'])
                        <input
                            type="text"
                            id="login"
                            name="login"
                            class="lg-input{{ $loginError ? ' is-invalid' : '' }}"
                            required
                            autofocus
                            autocomplete="username"
                            autocapitalize="none"
                            spellcheck="false"
                            aria-describedby="login-hint{{ $loginError ? ' login-error' : '' }}"
                            @if($loginError) aria-invalid="true" @endif
                            value="{{ old('login', old('email')) }}"
                        >
                    </div>
                    <p class="lg-hint" id="login-hint">{{ __('login_page.identifier_hint') }}</p>
                    @if($loginError)
                        <p class="lg-error" id="login-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $loginError }}</p>
                    @endif
                </div>

                <div class="lg-field">
                    <label for="password" class="lg-label">{{ __('login_page.password') }}</label>
                    <div class="lg-control f-auth-pass">
                        @include('partials.finder.icon', ['name' => 'lock', 'size' => 20, 'class' => 'lg-control__icon'])
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="lg-input{{ $errors->has('password') ? ' is-invalid' : '' }}"
                            required
                            autocomplete="current-password"
                            @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        >
                        @include('auth.partials.password_toggle', ['for' => 'password'])
                    </div>
                    @error('password')
                        <p class="lg-error" id="password-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</p>
                    @enderror
                </div>

                <div class="lg-row">
                    <label class="lg-check" for="remember">
                        <input type="checkbox" id="remember" name="remember" @checked(old('remember'))>
                        <span>{{ __('login_page.remember') }}</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="lg-link">{{ __('login_page.forgot') }}</a>
                </div>

                <button type="submit" class="lg-submit">
                    @include('partials.finder.icon', ['name' => 'log-in', 'size' => 20])
                    <span>{{ __('login_page.submit') }}</span>
                </button>
            </form>

            <div class="lg-or" role="separator"><span>{{ __('login_page.or') }}</span></div>

            {{-- "Sign in with OPESCARD" (button + scanner) is a separate partial. --}}
            @includeIf('auth.partials.opescard-scan')

            <p class="lg-alt">{{ __('login_page.no_account') }}
                <a href="{{ route('register') }}" class="lg-link">{{ __('login_page.create_account') }}</a>
            </p>

            {{-- The Android build, offered only when this host actually holds it. --}}
            @php $apkPath = public_path('downloads/opescare-finder.apk'); @endphp
            @if(is_file($apkPath))
            <div class="f-auth-app lg-app">
                <a class="f-auth-app__btn" href="{{ url('/downloads/opescare-finder.apk') }}"
                   download="OpesCare.apk" rel="nofollow">
                    @include('partials.finder.icon', ['name' => 'download', 'size' => 18])
                    <span>{{ __('onboarding.login.download_app') }}</span>
                </a>
                <p class="f-auth-app__meta">
                    {{ __('onboarding.login.download_app_meta', ['size' => number_format(filesize($apkPath) / 1048576, 0)]) }}
                </p>
            </div>
            @endif

            <ul class="lg-badges" aria-label="{{ __('login_page.badges_label') }}">
                <li><span class="lg-badge__ring">@include('partials.finder.icon', ['name' => 'id-card', 'size' => 22])</span><span>{{ __('login_page.badge_health_id') }}</span></li>
                <li><span class="lg-badge__ring">@include('partials.finder.icon', ['name' => 'lock-keyhole', 'size' => 22])</span><span>{{ __('login_page.badge_encrypted') }}</span></li>
                <li><span class="lg-badge__ring">@include('partials.finder.icon', ['name' => 'file-check', 'size' => 22])</span><span>{{ __('login_page.badge_consent') }}</span></li>
                <li><span class="lg-badge__ring">@include('partials.finder.icon', ['name' => 'languages', 'size' => 22])</span><span>{{ __('login_page.badge_bilingual') }}</span></li>
            </ul>
        </section>
    </div>

    {{-- Demo One-Click Login Panel — visible only when OPESCARE_DEMO_MODE=true.
         Legacy block: styled by css/auth.css, wired by js/auth.js + lucide;
         markup, ids and data-* attributes are load-bearing — do not rename. --}}
    @if(config('demo.enabled'))
    <form id="demoLoginForm" method="POST" action="{{ route('demo.login-as') }}" class="auth-hidden">
        @csrf
        <input type="hidden" id="demoRoleInput"  name="role"  value="">
        <input type="hidden" id="demoEmailInput" name="email" value="">
        <input type="hidden"                      name="mode"  value="public">
    </form>

    <div class="demo-panel" id="demoPanel">
        <div class="demo-panel-header open" id="demoPanelHeader" data-demo-panel-toggle>
            <div class="demo-panel-header-left">
                <span class="demo-badge">{{ __('ui_sweep.login.demo_badge') }}</span>
                <div>
                    <strong>{{ __('ui_sweep.login.try_demo') }}</strong>
                    <span>One-click access — no password needed</span>
                </div>
            </div>
            <i data-lucide="chevron-up" id="demoPanelChevron"></i>
        </div>

        <div class="demo-panel-body open" id="demoPanelBody">
            <div class="demo-notice">
                <i data-lucide="info"></i>
                <span>{{ __('public.demo.sandbox_notice') }}</span>
            </div>

            <div class="demo-tabs" role="tablist">
                <button class="demo-tab-btn active" type="button" data-demo-tab="clinical">
                    <i data-lucide="users"></i> {{ __('public.demo.tab_staff') }}
                </button>
                <button class="demo-tab-btn" type="button" data-demo-tab="facility">
                    <i data-lucide="building-2"></i> {{ __('public.demo.tab_facility') }}
                </button>
                <button class="demo-tab-btn" type="button" data-demo-tab="types">
                    <i data-lucide="hospital"></i> {{ __('public.demo.tab_facility_types') }}
                </button>
                <button class="demo-tab-btn" type="button" data-demo-tab="patient">
                    <i data-lucide="user"></i> {{ __('public.demo.tab_patient') }}
                </button>
                <button class="demo-tab-btn" type="button" data-demo-tab="developer">
                    <i data-lucide="code-2"></i> {{ __('public.demo.tab_developer') }}
                </button>
            </div>

            <div class="demo-tab-pane active" id="demo-tab-clinical" role="tabpanel">
                <div class="demo-btn-grid">
                    <button type="button" class="demo-login-btn demo-btn-clinical" data-demo-role="doctor" data-demo-email="demo.doctor@opescare.test">
                        <i data-lucide="stethoscope"></i>
                        <span>Dr. Amara Diallo</span>
                        <small>{{ __('public.demo.role_doctor') }}</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-clinical" data-demo-role="nurse" data-demo-email="demo.nurse@opescare.test">
                        <i data-lucide="heart-pulse"></i>
                        <span>Nurse Fatou Traoré</span>
                        <small>{{ __('public.demo.role_nurse') }}</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-clinical" data-demo-role="pharmacist" data-demo-email="demo.pharmacist@opescare.test">
                        <i data-lucide="pill"></i>
                        <span>Aïcha Coulibaly</span>
                        <small>{{ __('ui_sweep.login.pharmacist') }}</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-clinical" data-demo-role="pharmacy_manager" data-demo-email="demo.pharmacy.manager@opescare.test">
                        <i data-lucide="store"></i>
                        <span>Demo — Pharmacy Manager</span>
                        <small>{{ __('portal.pharmacy_manager_role') }}</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-clinical" data-demo-role="labtech" data-demo-email="demo.labtech@opescare.test">
                        <i data-lucide="flask-conical"></i>
                        <span>Lab Tech Boubacar Keïta</span>
                        <small>{{ __('ui_sweep.login.lab_technician') }}</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-clinical" data-demo-role="receptionist" data-demo-email="demo.receptionist@opescare.test">
                        <i data-lucide="monitor"></i>
                        <span>Front Desk — Aminata</span>
                        <small>{{ __('ui_sweep.login.receptionist_ops') }}</small>
                    </button>
                </div>
            </div>

            <div class="demo-tab-pane" id="demo-tab-facility" role="tabpanel">
                <div class="demo-btn-grid">
                    <button type="button" class="demo-login-btn demo-btn-facility" data-demo-role="facility_admin" data-demo-email="demo.facility.admin@opescare.test">
                        <i data-lucide="building-2"></i>
                        <span>Admin Mariam Touré</span>
                        <small>{{ __('ui_sweep.login.facility_admin') }}</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-facility" data-demo-role="facility_ceo" data-demo-email="demo.facility.ceo@opescare.test">
                        <i data-lucide="briefcase"></i>
                        <span>CEO Seydou Ouédraogo</span>
                        <small>{{ __('ui_sweep.login.ceo') }}</small>
                    </button>
                </div>
            </div>

            {{-- One admin/manager login per facility type — DemoFacilityTypesSeeder --}}
            <div class="demo-tab-pane" id="demo-tab-types" role="tabpanel">
                <div class="demo-btn-grid">
                    @foreach ([
                        ['hospital',          'hospital_admin',   'demo.hospital.admin@opescare.test',     'hospital',      'Hôpital Central de Yaoundé'],
                        ['clinic',            'clinic_admin',     'demo.clinic.admin@opescare.test',       'stethoscope',   'Clinique Demo Bonapriso'],
                        ['health_center',     'facility_admin',   'demo.healthcenter.admin@opescare.test', 'heart-pulse',   'Centre de Santé Demo Nkolbisson'],
                        ['laboratory',        'lab_manager',      'demo.lab.manager@opescare.test',        'flask-conical', 'Laboratoire Demo Akwa'],
                        ['pharmacy',          'pharmacy_manager', 'demo.pharmacy.manager@opescare.test',   'pill',          'Pharmacy ESSEC'],
                        ['dental',            'facility_admin',   'demo.dental.admin@opescare.test',       'smile',         'Cabinet Dentaire Demo Bastos'],
                        ['imaging_center',    'facility_admin',   'demo.imaging.admin@opescare.test',      'scan',          "Centre d'Imagerie Demo Bonanjo"],
                        ['eye_clinic',        'clinic_admin',     'demo.eye.admin@opescare.test',          'eye',           'Clinique Ophtalmologique Demo'],
                        ['diagnostic_center', 'facility_admin',   'demo.diagnostic.admin@opescare.test',   'activity',      'Centre de Diagnostic Demo'],
                        ['maternity',         'facility_admin',   'demo.maternity.admin@opescare.test',    'baby',          'Maternité Demo Essos'],
                    ] as [$type, $role, $email, $icon, $tenant])
                    <button type="button" class="demo-login-btn demo-btn-facility" data-demo-role="{{ $role }}" data-demo-email="{{ $email }}">
                        <i data-lucide="{{ $icon }}"></i>
                        <span>{{ $tenant }}</span>
                        <small>{{ __('public.demo.type_' . $type) }}</small>
                    </button>
                    @endforeach
                </div>
            </div>

            <div class="demo-tab-pane" id="demo-tab-patient" role="tabpanel">
                <div class="demo-btn-grid">
                    <button type="button" class="demo-login-btn demo-btn-patient" data-demo-role="patient" data-demo-email="demo.patient@opescare.test">
                        <i data-lucide="user"></i>
                        <span>{{ __('ui_sweep.login.demo_patient') }}</span>
                        <small>{{ __('public.demo.role_patient_provisional') }}</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-patient" data-demo-role="patient" data-demo-email="demo.reviewer@opescare.test">
                        <i data-lucide="shield-check"></i>
                        <span>{{ __('ui_sweep.login.demo_reviewer') }}</span>
                        <small>{{ __('public.demo.role_patient_verified') }}</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-patient" data-demo-role="patient" data-demo-email="demo.boy@opescare.test">
                        <i data-lucide="baby"></i>
                        <span>{{ __('ui_sweep.login.demo_boy') }}</span>
                        <small>{{ __('public.demo.role_child_boy') }}</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-patient" data-demo-role="patient" data-demo-email="demo.girl@opescare.test">
                        <i data-lucide="baby"></i>
                        <span>{{ __('ui_sweep.login.demo_girl') }}</span>
                        <small>{{ __('public.demo.role_child_girl') }}</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-patient" data-demo-role="guardian" data-demo-email="demo.guardian@opescare.test">
                        <i data-lucide="users"></i>
                        <span>{{ __('ui_sweep.login.demo_guardian') }}</span>
                        <small>{{ __('public.demo.role_guardian_parent') }}</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-patient" data-demo-role="patient" data-demo-email="demo.grandmother@opescare.test">
                        <i data-lucide="user"></i>
                        <span>{{ __('ui_sweep.login.demo_grandmother') }}</span>
                        <small>{{ __('public.demo.role_grandmother') }}</small>
                    </button>
                </div>
            </div>


            <div class="demo-tab-pane" id="demo-tab-developer" role="tabpanel">
                <div class="demo-btn-grid">
                    <button type="button" class="demo-login-btn demo-btn-developer" data-demo-role="developer" data-demo-email="demo.developer@opescare.test">
                        <i data-lucide="code-2"></i>
                        <span>{{ __('ui_sweep.login.api_developer') }}</span>
                        <small>{{ __('ui_sweep.login.developer_portal') }}</small>
                    </button>
                </div>
                <div class="demo-sandbox-creds">
                    <strong>{{ __('ui_sweep.login.sandbox_credentials') }}</strong><br>
                    <code>client_id: demo_dev_sandbox</code><br>
                    <code>client_secret: demo_secret_sandbox_2026</code><br>
                    <code>POST /api/v1/connect/auth/token</code>
                </div>
            </div>
        </div>{{-- /demo-panel-body --}}
    </div>{{-- /demo-panel --}}
    @endif

    <footer class="lg-footer">
        <p>{{ __('login_page.copyright', ['year' => 2026]) }}</p>
        <nav aria-label="{{ __('login_page.footer_label') }}">
            <a href="{{ route('public.privacy') }}">{{ __('login_page.privacy') }}</a>
            <span aria-hidden="true">|</span>
            <a href="{{ route('public.terms') }}">{{ __('login_page.terms') }}</a>
            <span aria-hidden="true">|</span>
            <a href="{{ Route::has('public.support') ? route('public.support') : route('public.contact') }}">{{ __('login_page.support') }}</a>
        </nav>
    </footer>
</div>
@endsection

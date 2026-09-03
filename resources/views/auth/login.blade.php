@extends('layouts.auth')

@section('title', __('onboarding.login.head_title'))

@section('content')
    <div class="f-auth-col">
        <div class="f-auth-card">
            <h1 class="f-auth-title">{{ __('onboarding.login.welcome_back') }}</h1>
            <p class="f-auth-sub">{{ __('onboarding.login.subheadline') }}</p>

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

            <form action="{{ route('login.submit') }}" method="POST" novalidate>
                @csrf

                <div class="f-field">
                    <label for="email" class="f-label">{{ __('onboarding.login.email_or_phone') }}</label>
                    <input
                        type="text"
                        id="email"
                        name="email"
                        class="f-input{{ $errors->has('email') ? ' f-input--invalid' : '' }}"
                        required
                        autofocus
                        autocomplete="username"
                        value="{{ old('email') }}"
                    >
                    @error('email')
                        <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                    @enderror
                </div>

                <div class="f-field">
                    <div class="f-auth-labelrow">
                        <label for="password" class="f-label">{{ __('onboarding.common.password') }}</label>
                        <a href="{{ route('password.request') }}">{{ __('onboarding.login.forgot') }}</a>
                    </div>
                    <div class="f-auth-pass">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="f-input{{ $errors->has('password') ? ' f-input--invalid' : '' }}"
                            required
                            autocomplete="current-password"
                        >
                        @include('auth.partials.password_toggle', ['for' => 'password'])
                    </div>
                    @error('password')
                        <span class="f-error f-auth-error">@include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15]){{ $message }}</span>
                    @enderror
                </div>

                <div class="f-auth-check">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">{{ __('onboarding.login.remember') }}</label>
                </div>

                <button type="submit" class="f-btn f-btn--primary f-btn--block">
                    @include('partials.finder.icon', ['name' => 'log-in', 'size' => 18])
                    {{ __('onboarding.login.submit_signin') }}
                </button>
            </form>

            <div class="f-auth-note">
                @include('partials.finder.icon', ['name' => 'shield-check', 'size' => 16])
                <p>{{ __('onboarding.login.security_note') }}</p>
            </div>
        </div>

        <div class="f-auth-alt">
            <p>{{ __('onboarding.login.no_account') }}
                <a href="{{ route('register') }}">{{ __('onboarding.login.create_account') }}</a>
            </p>
        </div>
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
                <span class="demo-badge">DEMO</span>
                <div>
                    <strong>Try the Demo</strong>
                    <span>One-click access — no password needed</span>
                </div>
            </div>
            <i data-lucide="chevron-up" id="demoPanelChevron"></i>
        </div>

        <div class="demo-panel-body open" id="demoPanelBody">
            <div class="demo-notice">
                <i data-lucide="info"></i>
                <span>Sandbox environment — demo data only. Resets periodically. Do not enter real patient data.</span>
            </div>

            <div class="demo-tabs" role="tablist">
                <button class="demo-tab-btn active" type="button" data-demo-tab="clinical">
                    <i data-lucide="users"></i> Staff
                </button>
                <button class="demo-tab-btn" type="button" data-demo-tab="facility">
                    <i data-lucide="building-2"></i> Facility
                </button>
                <button class="demo-tab-btn" type="button" data-demo-tab="patient">
                    <i data-lucide="user"></i> Patient
                </button>
                <button class="demo-tab-btn" type="button" data-demo-tab="admin">
                    <i data-lucide="settings-2"></i> Admin
                </button>
                <button class="demo-tab-btn" type="button" data-demo-tab="developer">
                    <i data-lucide="code-2"></i> Developer
                </button>
            </div>

            <div class="demo-tab-pane active" id="demo-tab-clinical" role="tabpanel">
                <div class="demo-btn-grid">
                    <button type="button" class="demo-login-btn demo-btn-clinical" data-demo-role="pharmacist" data-demo-email="demo.pharmacist@opescare.test">
                        <i data-lucide="pill"></i>
                        <span>Aïcha Coulibaly</span>
                        <small>Pharmacist</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-clinical" data-demo-role="receptionist" data-demo-email="demo.receptionist@opescare.test">
                        <i data-lucide="monitor"></i>
                        <span>Front Desk — Aminata</span>
                        <small>Receptionist · Operations Desk</small>
                    </button>
                </div>
            </div>

            <div class="demo-tab-pane" id="demo-tab-facility" role="tabpanel">
                <div class="demo-btn-grid">
                    <button type="button" class="demo-login-btn demo-btn-facility" data-demo-role="facility_admin" data-demo-email="demo.facility.admin@opescare.test">
                        <i data-lucide="building-2"></i>
                        <span>Admin Mariam Touré</span>
                        <small>Facility Administrator</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-facility" data-demo-role="facility_ceo" data-demo-email="demo.facility.ceo@opescare.test">
                        <i data-lucide="briefcase"></i>
                        <span>CEO Seydou Ouédraogo</span>
                        <small>Chief Executive Officer</small>
                    </button>
                </div>
            </div>


            <div class="demo-tab-pane" id="demo-tab-patient" role="tabpanel">
                <div class="demo-btn-grid">
                    <button type="button" class="demo-login-btn demo-btn-patient" data-demo-role="patient" data-demo-email="demo.patient@opescare.test">
                        <i data-lucide="user"></i>
                        <span>Jean Dupont</span>
                        <small>Patient</small>
                    </button>
                </div>
            </div>

            <div class="demo-tab-pane" id="demo-tab-admin" role="tabpanel">
                <div class="demo-btn-grid">
                    <button type="button" class="demo-login-btn demo-btn-admin" data-demo-role="platform_admin" data-demo-email="demo.admin@opescare.test">
                        <i data-lucide="settings-2"></i>
                        <span>Platform Admin</span>
                        <small>Super Administrator</small>
                    </button>
                </div>
            </div>

            <div class="demo-tab-pane" id="demo-tab-developer" role="tabpanel">
                <div class="demo-btn-grid">
                    <button type="button" class="demo-login-btn demo-btn-developer" data-demo-role="developer" data-demo-email="demo.developer@opescare.test">
                        <i data-lucide="code-2"></i>
                        <span>API Developer</span>
                        <small>Developer Portal</small>
                    </button>
                </div>
                <div class="demo-sandbox-creds">
                    <strong>Sandbox API Credentials</strong><br>
                    <code>client_id: demo_dev_sandbox</code><br>
                    <code>client_secret: demo_secret_sandbox_2026</code><br>
                    <code>POST /api/v1/connect/auth/token</code>
                </div>
            </div>
        </div>{{-- /demo-panel-body --}}
    </div>{{-- /demo-panel --}}
    @endif
@endsection

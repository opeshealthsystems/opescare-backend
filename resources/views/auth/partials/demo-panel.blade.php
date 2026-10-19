{{--
    Demo one-click sign-in. Rendered ONLY by auth/login when
    OPESCARE_DEMO_MODE=true on a non-production host (see the guard there).
    Production never renders it: demo accounts do not exist on opescare.com.
--}}
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

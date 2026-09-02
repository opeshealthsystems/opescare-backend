<?php $__env->startSection('title', __('onboarding.login.head_title')); ?>

<?php $__env->startSection('content'); ?>
    <div class="auth-card">
        <div class="auth-card__head">
            <h1 class="auth-card__title"><?php echo e(__('onboarding.login.welcome_back')); ?></h1>
            <p class="auth-card__sub"><?php echo e(__('onboarding.login.subheadline')); ?></p>
        </div>

        <?php if(session('success')): ?>
            <div class="auth-alert auth-alert-success">
                <i data-lucide="badge-check"></i>
                <div><?php echo e(session('success')); ?></div>
            </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
            <div class="auth-alert auth-alert-danger">
                <i data-lucide="alert-circle"></i>
                <div><?php echo e(session('error')); ?></div>
            </div>
        <?php endif; ?>

        <form action="<?php echo e(route('login.submit')); ?>" method="POST" class="auth-form">
            <?php echo csrf_field(); ?>

            <div class="auth-form-group">
                <label for="email" class="auth-label"><?php echo e(__('onboarding.login.email_or_phone')); ?></label>
                <div class="auth-input-icon-wrap">
                    <i data-lucide="mail" class="auth-input-icon"></i>
                    <input
                        type="text"
                        id="email"
                        name="email"
                        class="auth-input auth-input--icon<?php echo e($errors->has('email') ? ' auth-input-error' : ''); ?>"
                        placeholder="name@facility.org or +123..."
                        required
                        autofocus
                        value="<?php echo e(old('email')); ?>"
                    >
                </div>
                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="auth-form-group">
                <div class="auth-label auth-label--row">
                    <span><?php echo e(__('onboarding.common.password')); ?></span>
                    <a href="<?php echo e(route('password.request')); ?>" class="auth-label-link">
                        <?php echo e(__('onboarding.login.forgot')); ?>

                    </a>
                </div>
                <div class="auth-pass-wrapper auth-input-icon-wrap">
                    <i data-lucide="lock" class="auth-input-icon"></i>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="auth-input auth-input--icon<?php echo e($errors->has('password') ? ' auth-input-error' : ''); ?>"
                        required
                        placeholder="••••••••"
                    >
                    <button type="button" class="auth-pass-toggle" data-toggle-password="password">
                        <i data-lucide="eye" id="password-toggle-icon"></i>
                    </button>
                </div>
                <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="auth-checkbox-group">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember" class="auth-checkbox-label"><?php echo e(__('onboarding.login.remember')); ?></label>
            </div>

            <button type="submit" class="auth-btn auth-btn-primary auth-btn--mt">
                <i data-lucide="log-in"></i>
                <span><?php echo e(__('onboarding.login.submit_signin')); ?></span>
            </button>
        </form>

        <div class="auth-security-block">
            <i data-lucide="shield-check"></i>
            <p><?php echo e(__('onboarding.login.security_note')); ?></p>
        </div>
    </div>

    <div class="auth-footer-links auth-footer-links--mt">
        <p><?php echo e(__('onboarding.login.no_account')); ?>

            <a href="<?php echo e(route('register')); ?>" class="auth-footer-link">
                <?php echo e(__('onboarding.login.create_account')); ?>

            </a>
        </p>
    </div>

    
    <?php if(config('demo.enabled')): ?>
    <form id="demoLoginForm" method="POST" action="<?php echo e(route('demo.login-as')); ?>" class="auth-hidden">
        <?php echo csrf_field(); ?>
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
                    <i data-lucide="stethoscope"></i> Clinical
                </button>
                <button class="demo-tab-btn" type="button" data-demo-tab="facility">
                    <i data-lucide="building-2"></i> Facility
                </button>
                <button class="demo-tab-btn" type="button" data-demo-tab="insurance">
                    <i data-lucide="shield-check"></i> Insurance
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
                    <button type="button" class="demo-login-btn demo-btn-clinical" data-demo-role="doctor" data-demo-email="demo.doctor@opescare.test">
                        <i data-lucide="stethoscope"></i>
                        <span>Dr. Amara Diallo</span>
                        <small>General Practitioner</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-clinical" data-demo-role="multi_doctor" data-demo-email="demo.multi.doctor@opescare.test">
                        <i data-lucide="network"></i>
                        <span>Dr. Kofi Mensah</span>
                        <small>Multi-Facility Doctor</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-clinical" data-demo-role="nurse" data-demo-email="demo.nurse@opescare.test">
                        <i data-lucide="heart-pulse"></i>
                        <span>Nurse Fatou Traoré</span>
                        <small>Clinical Nurse</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-clinical" data-demo-role="specialist" data-demo-email="demo.specialist@opescare.test">
                        <i data-lucide="microscope"></i>
                        <span>Dr. Ibrahim Sow</span>
                        <small>Specialist</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-clinical" data-demo-role="pharmacist" data-demo-email="demo.pharmacist@opescare.test">
                        <i data-lucide="pill"></i>
                        <span>Aïcha Coulibaly</span>
                        <small>Pharmacist</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-clinical" data-demo-role="labtech" data-demo-email="demo.labtech@opescare.test">
                        <i data-lucide="flask-conical"></i>
                        <span>Boubacar Keïta</span>
                        <small>Lab Technician</small>
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
                    <button type="button" class="demo-login-btn demo-btn-facility" data-demo-role="finance" data-demo-email="demo.finance@opescare.test">
                        <i data-lucide="bar-chart-2"></i>
                        <span>Finance Officer Kadiatou</span>
                        <small>Finance &amp; Billing</small>
                    </button>
                </div>
            </div>

            <div class="demo-tab-pane" id="demo-tab-insurance" role="tabpanel">
                <div class="demo-btn-grid">
                    <button type="button" class="demo-login-btn demo-btn-insurance" data-demo-role="insurance_claims" data-demo-email="demo.insurance@opescare.test">
                        <i data-lucide="file-check-2"></i>
                        <span>Oumar Ba</span>
                        <small>Claims Officer</small>
                    </button>
                    <button type="button" class="demo-login-btn demo-btn-insurance" data-demo-role="insurance_preauth" data-demo-email="demo.preauth@opescare.test">
                        <i data-lucide="clipboard-check"></i>
                        <span>Awa (Preauth)</span>
                        <small>Pre-Auth Reviewer</small>
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
                    <button type="button" class="demo-login-btn demo-btn-patient" data-demo-role="guardian" data-demo-email="demo.guardian@opescare.test">
                        <i data-lucide="users"></i>
                        <span>Marie Dupont</span>
                        <small>Guardian / Family</small>
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
        </div>
    </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/auth/login.blade.php ENDPATH**/ ?>
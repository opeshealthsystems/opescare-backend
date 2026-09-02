<?php $__env->startSection('title', __('onboarding.developer.title')); ?>

<?php $__env->startSection('content'); ?>
    <div class="auth-card">

        <a href="<?php echo e(route('register')); ?>" class="back-link" style="display:inline-flex;margin-bottom:1.5rem;">
            <i data-lucide="arrow-left"></i>
            <?php echo e(__('onboarding.common.back')); ?>

        </a>

        <div class="auth-step-head">
            <div class="auth-step-icon">
                <i data-lucide="code-2"></i>
            </div>
            <h1 class="auth-step-title"><?php echo e(__('onboarding.developer.title')); ?></h1>
            <p class="auth-step-sub"><?php echo e(__('onboarding.developer.subtitle')); ?></p>
        </div>

        <?php if(session('success')): ?>
            <div class="auth-alert auth-alert-success">
                <i data-lucide="badge-check"></i>
                <div><?php echo e(session('success')); ?></div>
            </div>
            <div style="text-align:center;margin-top:2rem;">
                <a href="<?php echo e(route('public.landing')); ?>" class="auth-btn auth-btn-primary">
                    <i data-lucide="arrow-left"></i>
                    <span><?php echo e(__('onboarding.common.back_to_home')); ?></span>
                </a>
            </div>
        <?php else: ?>
            <?php if(session('error')): ?>
                <div class="auth-alert auth-alert-danger">
                    <i data-lucide="triangle-alert"></i>
                    <div><?php echo e(session('error')); ?></div>
                </div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="auth-alert auth-alert-danger">
                    <i data-lucide="triangle-alert"></i>
                    <ul style="margin:0;padding-left:1.25rem;">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?php echo e(route('register.developer.submit')); ?>" method="POST" class="auth-form">
                <?php echo csrf_field(); ?>

                <!-- Section 1: Contact Information -->
                <div class="form-section">
                    <div class="form-section-title"><?php echo e(__('onboarding.org.contact_sec')); ?></div>

                    <div class="auth-form-row">
                        <div class="auth-form-group">
                            <label for="name" class="auth-label"><?php echo e(__('onboarding.common.full_name')); ?> *</label>
                            <input type="text" id="name" name="name"
                                   class="auth-input<?php echo e($errors->has('name') ? ' auth-input-error' : ''); ?>" required>
                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="auth-form-group">
                            <label for="organization" class="auth-label"><?php echo e(__('onboarding.developer.org_lbl')); ?> *</label>
                            <input type="text" id="organization" name="organization"
                                   class="auth-input<?php echo e($errors->has('organization') ? ' auth-input-error' : ''); ?>" required>
                            <?php $__errorArgs = ['organization'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <div class="auth-form-row">
                        <div class="auth-form-group">
                            <label for="role" class="auth-label"><?php echo e(__('onboarding.developer.role_lbl')); ?> *</label>
                            <input type="text" id="role" name="role"
                                   class="auth-input<?php echo e($errors->has('role') ? ' auth-input-error' : ''); ?>"
                                   required placeholder="Lead Interoperability Engineer, Product Owner...">
                            <?php $__errorArgs = ['role'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="auth-form-group">
                            <label for="country" class="auth-label"><?php echo e(__('onboarding.patient.country')); ?> *</label>
                            <input type="text" id="country" name="country"
                                   class="auth-input<?php echo e($errors->has('country') ? ' auth-input-error' : ''); ?>"
                                   required value="<?php echo e(old('country', 'Canada')); ?>">
                            <?php $__errorArgs = ['country'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <div class="auth-form-row">
                        <div class="auth-form-group">
                            <label for="email" class="auth-label"><?php echo e(__('onboarding.common.email')); ?> *</label>
                            <input type="email" id="email" name="email"
                                   class="auth-input<?php echo e($errors->has('email') ? ' auth-input-error' : ''); ?>" required>
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
                            <label for="phone" class="auth-label"><?php echo e(__('onboarding.common.phone')); ?> *</label>
                            <input type="tel" id="phone" name="phone"
                                   class="auth-input<?php echo e($errors->has('phone') ? ' auth-input-error' : ''); ?>" required>
                            <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Technical Parameters -->
                <div class="form-section">
                    <div class="form-section-title"><?php echo e(__('onboarding.developer.sec_vendor')); ?></div>

                    <div class="auth-form-group">
                        <label for="system_type" class="auth-label"><?php echo e(__('onboarding.developer.system_type_lbl')); ?> *</label>
                        <select id="system_type" name="system_type"
                                class="auth-input<?php echo e($errors->has('system_type') ? ' auth-input-error' : ''); ?>" required>
                            <option value="" disabled selected><?php echo e(__('onboarding.common.select_option')); ?></option>
                            <option value="HIS">Hospital Information System (HIS)</option>
                            <option value="LIS">Laboratory Information System (LIS)</option>
                            <option value="PHARMA">Pharmacy Stock / Dispensing System</option>
                            <option value="INSURANCE">Insurance Claims Management Engine</option>
                            <option value="BLOOD">Blood Bank Registry Platform</option>
                            <option value="MOBILE">Consumer Health Mobile App</option>
                            <option value="OTHER">Other Health Interoperability System</option>
                        </select>
                        <?php $__errorArgs = ['system_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="auth-form-group">
                        <label for="data_flow" class="auth-label"><?php echo e(__('onboarding.developer.expected_flow_lbl')); ?> *</label>
                        <select id="data_flow" name="data_flow"
                                class="auth-input<?php echo e($errors->has('data_flow') ? ' auth-input-error' : ''); ?>" required>
                            <option value="" disabled selected><?php echo e(__('onboarding.common.select_option')); ?></option>
                            <option value="PULL_SUMMARY">Pull patient CCDA/FHIR summaries</option>
                            <option value="PUSH_ENCOUNTERS">Push clinical encounters</option>
                            <option value="PUSH_RESULTS">Push validated lab results</option>
                            <option value="PUSH_PRESCRIPTIONS">Push validated prescriptions</option>
                            <option value="SYNC_INVENTORY">Sync pharmacy/medicine stock</option>
                            <option value="SYNC_BLOOD">Sync blood bank availability logs</option>
                            <option value="WEBHOOKS">Receive webhook dispatch events</option>
                        </select>
                        <?php $__errorArgs = ['data_flow'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="auth-form-group">
                        <label for="integration_purpose" class="auth-label"><?php echo e(__('onboarding.developer.purpose_lbl')); ?> *</label>
                        <textarea id="integration_purpose" name="integration_purpose"
                                  class="auth-input<?php echo e($errors->has('integration_purpose') ? ' auth-input-error' : ''); ?>"
                                  style="min-height:80px;resize:vertical;" required
                                  placeholder="Briefly describe the clinical use case or data synchronizations required..."></textarea>
                        <?php $__errorArgs = ['integration_purpose'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="auth-form-row">
                        <div class="auth-form-group">
                            <label class="auth-label"><?php echo e(__('onboarding.developer.sandbox_lbl')); ?></label>
                            <div class="radio-group">
                                <label class="radio-label"><input type="radio" name="sandbox" value="yes" checked> Yes</label>
                                <label class="radio-label"><input type="radio" name="sandbox" value="no"> No</label>
                            </div>
                        </div>
                        <div class="auth-form-group">
                            <label class="auth-label"><?php echo e(__('onboarding.developer.production_lbl')); ?></label>
                            <div class="radio-group">
                                <label class="radio-label"><input type="radio" name="production" value="yes"> Yes</label>
                                <label class="radio-label"><input type="radio" name="production" value="no" checked> No</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Security Notice -->
                <div class="notice-api">
                    <i data-lucide="shield-check"></i>
                    <p><?php echo e(__('onboarding.developer.safety_notice')); ?></p>
                </div>

                <div class="auth-checkbox-group">
                    <input type="checkbox" id="accept_vendor_terms" name="accept_vendor_terms" required>
                    <label for="accept_vendor_terms" class="auth-checkbox-label"><?php echo e(__('onboarding.developer.terms_label')); ?> *</label>
                </div>
                <?php $__errorArgs = ['accept_vendor_terms'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                <button type="submit" class="auth-btn auth-btn-primary">
                    <i data-lucide="code-2"></i>
                    <span><?php echo e(__('onboarding.developer.cta_btn')); ?></span>
                </button>
            </form>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\auth\register\developer.blade.php ENDPATH**/ ?>
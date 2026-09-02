<?php $__env->startSection('title', __('auth.register.hospital.title')); ?>

<?php $__env->startSection('content'); ?>
    <div class="auth-card">

        <a href="<?php echo e(route('register')); ?>" class="back-link" style="display:inline-flex;margin-bottom:1.5rem;">
            <i data-lucide="arrow-left"></i>
            <?php echo e(__('onboarding.common.back')); ?>

        </a>

        <div class="auth-step-head">
            <div class="auth-step-icon">
                <i data-lucide="hospital"></i>
            </div>
            <h1 class="auth-step-title"><?php echo e(__('auth.register.hospital.title')); ?></h1>
            <p class="auth-step-sub">Onboard your facility to the OpesCare interoperability network.</p>
        </div>

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

        <form action="#" method="POST" class="auth-form">
            <?php echo csrf_field(); ?>

            <div class="form-section">
                <div class="form-section-title">Facility Details</div>

                <div class="auth-form-row">
                    <div class="auth-form-group">
                        <label for="facility_name" class="auth-label"><?php echo e(__('auth.register.hospital.name')); ?> *</label>
                        <input type="text" id="facility_name" name="facility_name"
                               class="auth-input<?php echo e($errors->has('facility_name') ? ' auth-input-error' : ''); ?>"
                               value="<?php echo e(old('facility_name')); ?>" required>
                        <?php $__errorArgs = ['facility_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="auth-form-group">
                        <label for="facility_type" class="auth-label"><?php echo e(__('auth.register.hospital.type')); ?> *</label>
                        <select id="facility_type" name="facility_type"
                                class="auth-input<?php echo e($errors->has('facility_type') ? ' auth-input-error' : ''); ?>" required>
                            <option value="hospital" <?php echo e(old('facility_type') == 'hospital' ? 'selected' : ''); ?>>Hospital</option>
                            <option value="clinic"   <?php echo e(old('facility_type') == 'clinic'   ? 'selected' : ''); ?>>Clinic</option>
                            <option value="lab"      <?php echo e(old('facility_type') == 'lab'      ? 'selected' : ''); ?>>Laboratory</option>
                            <option value="pharmacy" <?php echo e(old('facility_type') == 'pharmacy' ? 'selected' : ''); ?>>Pharmacy</option>
                        </select>
                        <?php $__errorArgs = ['facility_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div class="auth-form-group">
                    <label for="license_number" class="auth-label"><?php echo e(__('auth.register.hospital.license')); ?> *</label>
                    <input type="text" id="license_number" name="license_number"
                           class="auth-input<?php echo e($errors->has('license_number') ? ' auth-input-error' : ''); ?>"
                           value="<?php echo e(old('license_number')); ?>" required>
                    <?php $__errorArgs = ['license_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title">Administrator Contact</div>

                <div class="auth-form-group">
                    <label for="admin_name" class="auth-label"><?php echo e(__('auth.register.hospital.admin_name')); ?> *</label>
                    <input type="text" id="admin_name" name="admin_name"
                           class="auth-input<?php echo e($errors->has('admin_name') ? ' auth-input-error' : ''); ?>"
                           value="<?php echo e(old('admin_name')); ?>" required>
                    <?php $__errorArgs = ['admin_name'];
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
                        <label for="admin_email" class="auth-label"><?php echo e(__('auth.register.hospital.admin_email')); ?> *</label>
                        <input type="email" id="admin_email" name="admin_email"
                               class="auth-input<?php echo e($errors->has('admin_email') ? ' auth-input-error' : ''); ?>"
                               value="<?php echo e(old('admin_email')); ?>" required>
                        <?php $__errorArgs = ['admin_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="auth-form-group">
                        <label for="admin_phone" class="auth-label"><?php echo e(__('auth.register.hospital.admin_phone')); ?> *</label>
                        <input type="tel" id="admin_phone" name="admin_phone"
                               class="auth-input<?php echo e($errors->has('admin_phone') ? ' auth-input-error' : ''); ?>"
                               value="<?php echo e(old('admin_phone')); ?>" required>
                        <?php $__errorArgs = ['admin_phone'];
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

            <button type="submit" class="auth-btn auth-btn-primary">
                <i data-lucide="hospital"></i>
                <span><?php echo e(__('auth.register.hospital.submit')); ?></span>
            </button>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\auth\register\hospital.blade.php ENDPATH**/ ?>
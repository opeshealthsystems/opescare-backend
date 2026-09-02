<?php $__env->startSection('title', __('onboarding.complete.title')); ?>



<?php $__env->startSection('content'); ?>
    <div class="auth-card">
        <h1 class="auth-headline"><?php echo e(__('onboarding.complete.headline')); ?></h1>
        <p class="auth-subheadline"><?php echo e(__('onboarding.complete.subheadline')); ?></p>

        <?php if($errors->any()): ?>
            <div class="auth-alert auth-alert--error" role="alert">
                <i data-lucide="alert-triangle" style="width:1.05rem;height:1.05rem;flex-shrink:0;"></i>
                <ul style="margin:0;padding-left:1rem;">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('portals.patient.complete-profile.store')); ?>" novalidate>
            <?php echo csrf_field(); ?>

            <div class="auth-row">
                <div class="auth-field">
                    <label for="first_name" class="auth-label"><?php echo e(__('onboarding.complete.first_name')); ?></label>
                    <input type="text" id="first_name" name="first_name" required autofocus
                           autocomplete="given-name" class="auth-input <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> auth-input--error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           value="<?php echo e(old('first_name')); ?>">
                    <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="auth-field-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="auth-field">
                    <label for="last_name" class="auth-label"><?php echo e(__('onboarding.complete.last_name')); ?></label>
                    <input type="text" id="last_name" name="last_name" required
                           autocomplete="family-name" class="auth-input <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> auth-input--error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           value="<?php echo e(old('last_name')); ?>">
                    <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="auth-field-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="auth-row">
                <div class="auth-field">
                    <label for="dob" class="auth-label"><?php echo e(__('onboarding.complete.dob')); ?></label>
                    <input type="date" id="dob" name="dob" required max="<?php echo e(now()->toDateString()); ?>"
                           class="auth-input <?php $__errorArgs = ['dob'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> auth-input--error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('dob')); ?>">
                    <?php $__errorArgs = ['dob'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="auth-field-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="auth-field">
                    <label for="sex" class="auth-label"><?php echo e(__('onboarding.complete.sex')); ?></label>
                    <select id="sex" name="sex" required class="auth-input <?php $__errorArgs = ['sex'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> auth-input--error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <option value=""><?php echo e(__('onboarding.complete.sex_choose')); ?></option>
                        <option value="male"   <?php echo e(old('sex') === 'male' ? 'selected' : ''); ?>><?php echo e(__('onboarding.patient.sex_male')); ?></option>
                        <option value="female" <?php echo e(old('sex') === 'female' ? 'selected' : ''); ?>><?php echo e(__('onboarding.patient.sex_female')); ?></option>
                    </select>
                    <?php $__errorArgs = ['sex'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="auth-field-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="auth-field">
                <label for="phone" class="auth-label"><?php echo e(__('onboarding.complete.phone')); ?></label>
                <input type="tel" id="phone" name="phone" required autocomplete="tel"
                       class="auth-input <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> auth-input--error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('phone')); ?>">
                <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="auth-field-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="auth-field">
                <label for="city" class="auth-label"><?php echo e(__('onboarding.complete.city')); ?></label>
                <input type="text" id="city" name="city" autocomplete="address-level2"
                       class="auth-input" value="<?php echo e(old('city')); ?>">
            </div>

            
            <details class="auth-details">
                <summary class="auth-details__summary"><?php echo e(__('onboarding.complete.emergency_toggle')); ?></summary>
                <div class="auth-row">
                    <div class="auth-field">
                        <label for="emergency_name" class="auth-label"><?php echo e(__('onboarding.complete.emergency_name')); ?></label>
                        <input type="text" id="emergency_name" name="emergency_name" class="auth-input" value="<?php echo e(old('emergency_name')); ?>">
                    </div>
                    <div class="auth-field">
                        <label for="emergency_phone" class="auth-label"><?php echo e(__('onboarding.complete.emergency_phone')); ?></label>
                        <input type="tel" id="emergency_phone" name="emergency_phone" class="auth-input" value="<?php echo e(old('emergency_phone')); ?>">
                    </div>
                </div>
                <div class="auth-field">
                    <label for="emergency_relationship" class="auth-label"><?php echo e(__('onboarding.complete.emergency_relationship')); ?></label>
                    <input type="text" id="emergency_relationship" name="emergency_relationship" class="auth-input" value="<?php echo e(old('emergency_relationship')); ?>">
                </div>
            </details>

            <button type="submit" class="auth-btn auth-btn-primary auth-btn--mt">
                <?php echo e(__('onboarding.complete.submit')); ?>

            </button>
        </form>

        <p class="auth-fineprint"><?php echo e(__('onboarding.complete.health_id_note')); ?></p>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/patient/complete_profile.blade.php ENDPATH**/ ?>
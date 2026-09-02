<?php $__env->startSection('title', __('onboarding.guardian.complete_title')); ?>



<?php $__env->startSection('content'); ?>
    <div class="auth-card">
        <h1 class="auth-headline"><?php echo e(__('onboarding.guardian.complete_headline')); ?></h1>
        <p class="auth-subheadline"><?php echo e(__('onboarding.guardian.complete_subheadline')); ?></p>

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

        <form method="POST" action="<?php echo e(route('portals.guardian.complete-profile.store')); ?>" novalidate>
            <?php echo csrf_field(); ?>

            <h2 class="auth-section-title"><?php echo e(__('onboarding.guardian.sec_guardian')); ?></h2>

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
                    <label for="dob" class="auth-label"><?php echo e(__('onboarding.complete.dob')); ?></label>
                    <input type="date" id="dob" name="dob" max="<?php echo e(now()->toDateString()); ?>"
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
            </div>

            <h2 class="auth-section-title"><?php echo e(__('onboarding.guardian.sec_dependent')); ?></h2>

            <div class="auth-row">
                <div class="auth-field">
                    <label for="dep_name" class="auth-label"><?php echo e(__('onboarding.guardian.dep_name_lbl')); ?></label>
                    <input type="text" id="dep_name" name="dep_name" required
                           class="auth-input <?php $__errorArgs = ['dep_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> auth-input--error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('dep_name')); ?>">
                    <?php $__errorArgs = ['dep_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="auth-field-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="auth-field">
                    <label for="dep_relationship" class="auth-label"><?php echo e(__('onboarding.guardian.relationship_lbl')); ?></label>
                    <input type="text" id="dep_relationship" name="dep_relationship" required
                           class="auth-input <?php $__errorArgs = ['dep_relationship'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> auth-input--error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('dep_relationship')); ?>">
                    <?php $__errorArgs = ['dep_relationship'];
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
                    <label for="dep_dob" class="auth-label"><?php echo e(__('onboarding.guardian.dep_dob_lbl')); ?></label>
                    <input type="date" id="dep_dob" name="dep_dob" max="<?php echo e(now()->toDateString()); ?>"
                           class="auth-input" value="<?php echo e(old('dep_dob')); ?>">
                </div>
                <div class="auth-field">
                    <label for="dep_sex" class="auth-label"><?php echo e(__('onboarding.guardian.dep_sex_lbl')); ?></label>
                    <select id="dep_sex" name="dep_sex" class="auth-input">
                        <option value=""><?php echo e(__('onboarding.complete.sex_choose')); ?></option>
                        <option value="male"   <?php echo e(old('dep_sex') === 'male' ? 'selected' : ''); ?>><?php echo e(__('onboarding.patient.sex_male')); ?></option>
                        <option value="female" <?php echo e(old('dep_sex') === 'female' ? 'selected' : ''); ?>><?php echo e(__('onboarding.patient.sex_female')); ?></option>
                    </select>
                </div>
            </div>

            <div class="auth-field">
                <label for="dep_health_id" class="auth-label"><?php echo e(__('onboarding.guardian.dep_health_id')); ?></label>
                <input type="text" id="dep_health_id" name="dep_health_id" class="auth-input"
                       placeholder="CM-HID-XXXX-XXXX-XXXX" value="<?php echo e(old('dep_health_id')); ?>">
                <span class="auth-field-hint"><?php echo e(__('onboarding.guardian.dep_health_id_hint')); ?></span>
            </div>

            <div class="auth-field">
                <label for="access_reason" class="auth-label"><?php echo e(__('onboarding.guardian.reason_lbl')); ?></label>
                <textarea id="access_reason" name="access_reason" rows="3" class="auth-input"><?php echo e(old('access_reason')); ?></textarea>
            </div>

            <button type="submit" class="auth-btn auth-btn-primary auth-btn--mt">
                <?php echo e(__('onboarding.guardian.complete_submit')); ?>

            </button>
        </form>

        <p class="auth-fineprint"><?php echo e(__('onboarding.guardian.verification_note')); ?></p>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\guardian\complete_profile.blade.php ENDPATH**/ ?>
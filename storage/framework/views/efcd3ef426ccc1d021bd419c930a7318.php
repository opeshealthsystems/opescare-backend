<?php $__env->startSection('title', __('onboarding.patient.title')); ?>



<?php $__env->startSection('content'); ?>
    <a href="<?php echo e(route('register')); ?>" class="auth-back-link">
        <i data-lucide="arrow-left" style="width:1rem;height:1rem;"></i>
        <?php echo e(__('onboarding.patient.back')); ?>

    </a>

    <div class="auth-card">
        <h1 class="auth-headline"><?php echo e(__('onboarding.patient.headline')); ?></h1>
        <p class="auth-subheadline"><?php echo e(__('onboarding.patient.subheadline')); ?></p>

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

        <form method="POST" action="<?php echo e(route('register.patient.submit')); ?>" novalidate>
            <?php echo csrf_field(); ?>

            
            <input type="hidden" name="ref" value="<?php echo e(request('ref')); ?>">

            <div class="auth-field">
                <label for="email" class="auth-label"><?php echo e(__('onboarding.patient.email')); ?></label>
                <input type="email" id="email" name="email" required autofocus
                       autocomplete="email" inputmode="email"
                       class="auth-input <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> auth-input--error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       value="<?php echo e(old('email')); ?>">
                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="auth-field-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="auth-field">
                <label for="password" class="auth-label"><?php echo e(__('onboarding.patient.password')); ?></label>
                <input type="password" id="password" name="password" required
                       autocomplete="new-password" minlength="8"
                       class="auth-input <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> auth-input--error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                <span class="auth-field-hint"><?php echo e(__('onboarding.patient.password_hint')); ?></span>
                <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="auth-field-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="auth-field">
                <label for="password_confirmation" class="auth-label"><?php echo e(__('onboarding.patient.password_confirm')); ?></label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       autocomplete="new-password" minlength="8" class="auth-input">
            </div>

            <button type="submit" class="auth-btn auth-btn-primary auth-btn--mt">
                <?php echo e(__('onboarding.patient.submit')); ?>

            </button>
        </form>

        
        <p class="auth-fineprint">
            <?php echo __('onboarding.patient.terms_notice', [
                'terms'   => '<a href="' . route('public.terms') . '">' . e(__('onboarding.patient.terms_link')) . '</a>',
                'privacy' => '<a href="' . route('public.privacy') . '">' . e(__('onboarding.patient.privacy_link')) . '</a>',
            ]); ?>

        </p>

        <p class="auth-fineprint">
            <?php echo e(__('onboarding.patient.next_step_note')); ?>

        </p>

        <p class="auth-alt-action">
            <?php echo e(__('onboarding.patient.have_account')); ?>

            <a href="<?php echo e(route('login')); ?>"><?php echo e(__('onboarding.patient.sign_in')); ?></a>
        </p>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\auth\register\patient.blade.php ENDPATH**/ ?>
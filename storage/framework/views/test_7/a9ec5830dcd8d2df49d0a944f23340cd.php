<?php $__env->startSection('title', __('onboarding.patient.title')); ?>



<?php $__env->startSection('content'); ?>
    <div class="f-auth-col">
        <a href="<?php echo e(route('register')); ?>" class="f-auth-back">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-left', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('onboarding.patient.back')); ?>

        </a>

        <div class="f-auth-card">
            <h1 class="f-auth-title"><?php echo e(__('onboarding.patient.headline')); ?></h1>
            <p class="f-auth-sub"><?php echo e(__('onboarding.patient.subheadline')); ?></p>

            
            <p class="f-auth-benefit">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'calendar', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('finder_auth.signup.benefit')); ?>

            </p>

            <?php if($errors->any()): ?>
                <div class="f-auth-alert f-auth-alert--danger" role="alert">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <ul>
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('register.patient.submit')); ?>" novalidate>
                <?php echo csrf_field(); ?>

                
                <input type="hidden" name="ref" value="<?php echo e(request('ref')); ?>">

                <div class="f-field">
                    <label for="email" class="f-label"><?php echo e(__('onboarding.patient.email')); ?></label>
                    <input type="email" id="email" name="email" required autofocus
                           autocomplete="email" inputmode="email"
                           class="f-input <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> f-input--invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           value="<?php echo e(old('email')); ?>">
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <span class="f-error f-auth-error"><?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php echo e($message); ?></span>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="f-field">
                    <label for="password" class="f-label"><?php echo e(__('onboarding.patient.password')); ?></label>
                    <div class="f-auth-pass">
                        <input type="password" id="password" name="password" required
                               autocomplete="new-password" minlength="8"
                               class="f-input <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> f-input--invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <?php echo $__env->make('auth.partials.password_toggle', ['for' => 'password'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <span class="f-hint"><?php echo e(__('onboarding.patient.password_hint')); ?></span>
                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <span class="f-error f-auth-error"><?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php echo e($message); ?></span>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="f-field">
                    <label for="password_confirmation" class="f-label"><?php echo e(__('onboarding.patient.password_confirm')); ?></label>
                    <div class="f-auth-pass">
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                               autocomplete="new-password" minlength="8" class="f-input">
                        <?php echo $__env->make('auth.partials.password_toggle', ['for' => 'password_confirmation'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>

                <button type="submit" class="f-btn f-btn--primary f-btn--block">
                    <?php echo e(__('onboarding.patient.submit')); ?>

                </button>
            </form>

            
            <p class="f-auth-fine">
                <?php echo __('onboarding.patient.terms_notice', [
                    'terms'   => '<a href="' . route('public.terms') . '">' . e(__('onboarding.patient.terms_link')) . '</a>',
                    'privacy' => '<a href="' . route('public.privacy') . '">' . e(__('onboarding.patient.privacy_link')) . '</a>',
                ]); ?>

            </p>

            <p class="f-auth-fine">
                <?php echo e(__('onboarding.patient.next_step_note')); ?>

            </p>

            <div class="f-auth-alt f-auth-alt--divide">
                <p><?php echo e(__('onboarding.patient.have_account')); ?>

                    <a href="<?php echo e(route('login')); ?>"><?php echo e(__('onboarding.patient.sign_in')); ?></a>
                </p>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/auth/register/patient.blade.php ENDPATH**/ ?>
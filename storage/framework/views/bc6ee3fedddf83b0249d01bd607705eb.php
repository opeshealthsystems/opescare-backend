<?php $__env->startSection('title', $invalid ?? false ? __('passwords.invalid_link.title') : __('onboarding.forgot.reset_title')); ?>

<?php $__env->startSection('content'); ?>
    <div class="auth-card">

        <?php if($invalid ?? false): ?>

            <div class="auth-card__head">
                <h1 class="auth-card__title"><?php echo e(__('passwords.invalid_link.title')); ?></h1>
            </div>

            <div class="auth-alert auth-alert-danger">
                <i data-lucide="link-2-off"></i>
                <div><?php echo e(__('passwords.invalid_link.body', ['minutes' => $ttl ?? 60])); ?></div>
            </div>

            <p class="auth-card__sub"><?php echo e(__('passwords.invalid_link.next')); ?></p>

            <div class="auth-back-action">
                <a href="<?php echo e(route('password.request')); ?>" class="auth-btn auth-btn-primary">
                    <i data-lucide="rotate-ccw"></i>
                    <span><?php echo e(__('passwords.invalid_link.cta')); ?></span>
                </a>
            </div>

            <div class="auth-footer-links auth-footer-links--mt">
                <a href="<?php echo e(route('login')); ?>" class="auth-back-link">
                    <i data-lucide="arrow-left"></i>
                    <?php echo e(__('onboarding.selector.signin')); ?>

                </a>
            </div>

        <?php else: ?>

            <div class="auth-card__head">
                <h1 class="auth-card__title"><?php echo e(__('onboarding.forgot.reset_title')); ?></h1>
                <p class="auth-card__sub"><?php echo e(__('passwords.form.intro', ['email' => $email])); ?></p>
            </div>

            <?php if(session('error')): ?>
                <div class="auth-alert auth-alert-danger">
                    <i data-lucide="alert-circle"></i>
                    <div><?php echo e(session('error')); ?></div>
                </div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="auth-alert auth-alert-danger">
                    <i data-lucide="alert-circle"></i>
                    <ul class="auth-alert-list">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?php echo e(route('password.update', $token)); ?>" method="POST" class="auth-form">
                <?php echo csrf_field(); ?>

                
                <input type="hidden" name="email" value="<?php echo e($email); ?>">

                <div class="auth-form-group">
                    <label for="password" class="auth-label"><?php echo e(__('onboarding.forgot.new_pass')); ?> *</label>
                    <div class="auth-pass-wrapper auth-input-icon-wrap">
                        <i data-lucide="lock" class="auth-input-icon"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="auth-input auth-input--icon<?php echo e($errors->has('password') ? ' auth-input-error' : ''); ?>"
                            required
                            minlength="8"
                            autocomplete="new-password"
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

                <div class="auth-form-group">
                    <label for="password_confirmation" class="auth-label"><?php echo e(__('onboarding.forgot.confirm_new')); ?> *</label>
                    <div class="auth-pass-wrapper auth-input-icon-wrap">
                        <i data-lucide="lock" class="auth-input-icon"></i>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="auth-input auth-input--icon<?php echo e($errors->has('password_confirmation') ? ' auth-input-error' : ''); ?>"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            placeholder="••••••••"
                        >
                        <button type="button" class="auth-pass-toggle" data-toggle-password="password_confirmation">
                            <i data-lucide="eye" id="password_confirmation-toggle-icon"></i>
                        </button>
                    </div>
                    <?php $__errorArgs = ['password_confirmation'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="auth-privacy-note">
                    <strong><?php echo e(__('passwords.form.policy_title')); ?></strong>
                    <ul class="auth-policy-list">
                        <li><?php echo e(__('passwords.form.policy_length')); ?></li>
                        <li><?php echo e(__('passwords.form.policy_unique')); ?></li>
                        <li><?php echo e(__('passwords.form.policy_private')); ?></li>
                    </ul>
                </div>

                <button type="submit" class="auth-btn auth-btn-primary auth-btn--mt">
                    <i data-lucide="check-circle"></i>
                    <span><?php echo e(__('onboarding.forgot.reset_cta')); ?></span>
                </button>
            </form>

            <div class="auth-footer-links auth-footer-links--mt">
                <a href="<?php echo e(route('login')); ?>" class="auth-back-link">
                    <i data-lucide="arrow-left"></i>
                    <?php echo e(__('onboarding.selector.signin')); ?>

                </a>
            </div>

        <?php endif; ?>

    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\auth\reset_password.blade.php ENDPATH**/ ?>
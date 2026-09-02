<?php $__env->startSection('title', __('onboarding.forgot.title')); ?>

<?php $__env->startSection('content'); ?>
    <div class="auth-card">
        <div class="auth-card__head">
            <h1 class="auth-card__title"><?php echo e(__('onboarding.forgot.title')); ?></h1>
            
            <p class="auth-card__sub"><?php echo e(__('passwords.request.desc')); ?></p>
        </div>

        <?php if(session('success')): ?>
            <div class="auth-alert auth-alert-success">
                <i data-lucide="badge-check"></i>
                <div><?php echo e(session('success')); ?></div>
            </div>

            <div class="auth-back-action">
                <a href="<?php echo e(route('login')); ?>" class="auth-btn auth-btn-secondary">
                    <i data-lucide="arrow-left"></i>
                    <span><?php echo e(__('onboarding.selector.signin')); ?></span>
                </a>
            </div>
        <?php else: ?>
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

            <form action="<?php echo e(route('password.email')); ?>" method="POST" class="auth-form">
                <?php echo csrf_field(); ?>

                <div class="auth-form-group">
                    <label for="email" class="auth-label"><?php echo e(__('onboarding.common.email')); ?> *</label>
                    <div class="auth-input-icon-wrap">
                        <i data-lucide="mail" class="auth-input-icon"></i>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="auth-input auth-input--icon<?php echo e($errors->has('email') ? ' auth-input-error' : ''); ?>"
                            required
                            autofocus
                            autocomplete="email"
                            inputmode="email"
                            maxlength="180"
                            placeholder="name@email.com"
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

                <div class="auth-privacy-note">
                    <?php echo e(__('onboarding.forgot.privacy_note')); ?>

                </div>

                <button type="submit" class="auth-btn auth-btn-primary auth-btn--mt">
                    <i data-lucide="send"></i>
                    <span><?php echo e(__('onboarding.forgot.cta')); ?></span>
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

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\auth\forgot_password.blade.php ENDPATH**/ ?>
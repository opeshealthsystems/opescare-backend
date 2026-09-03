<?php $__env->startSection('title', __('finder_auth.mfa.title')); ?>

<?php $__env->startSection('content'); ?>
    <div class="f-auth-col">
        <div class="f-auth-card">
            <div class="f-auth-badge">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'shield-check', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>

            <h1 class="f-auth-title"><?php echo e(__('finder_auth.mfa.title')); ?></h1>
            <p class="f-auth-sub">
                <?php if($setupRequired ?? false): ?>
                    <?php echo e(__('finder_auth.mfa.setup_required')); ?>

                <?php else: ?>
                    <?php echo e(__('finder_auth.mfa.enter_code')); ?>

                <?php endif; ?>
            </p>

            <?php if(session('error')): ?>
                <div class="f-auth-alert f-auth-alert--danger" role="alert">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div><?php echo e(session('error')); ?></div>
                </div>
            <?php endif; ?>

            <?php if(! ($setupRequired ?? false)): ?>
            <form action="<?php echo e(route('mfa.challenge.submit')); ?>" method="POST" novalidate>
                <?php echo csrf_field(); ?>

                <div class="f-field">
                    <label for="code" class="f-label"><?php echo e(__('finder_auth.mfa.code_label')); ?></label>
                    <input
                        type="text"
                        id="code"
                        name="code"
                        class="f-input<?php echo e($errors->has('code') ? ' f-input--invalid' : ''); ?>"
                        style="text-align:center;letter-spacing:.3em;font-size:1.375rem;font-weight:700"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        placeholder="000000"
                        required
                        autofocus
                    >
                    <?php $__errorArgs = ['code'];
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

                <button type="submit" class="f-btn f-btn--primary f-btn--block">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('finder_auth.mfa.verify')); ?>

                </button>
            </form>
            <?php endif; ?>

            <div class="f-auth-alt f-auth-alt--divide">
                <p><?php echo e(__('finder_auth.mfa.lost_access')); ?>

                    <a href="<?php echo e(route('public.contact')); ?>"><?php echo e(__('finder_auth.mfa.contact_support')); ?></a>
                </p>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\auth\mfa_challenge.blade.php ENDPATH**/ ?>
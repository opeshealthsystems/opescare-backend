<?php $__env->startSection('title', __('auth.otp_unavailable.page_title')); ?>



<?php $__env->startSection('content'); ?>
    <div class="f-auth-col">
        <div class="f-auth-card">
            <div class="f-auth-badge">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'shield-check', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>

            <h1 class="f-auth-title"><?php echo e(__('auth.otp_unavailable.title')); ?></h1>

            <?php if(session('error')): ?>
                <div class="f-auth-alert f-auth-alert--danger" role="alert">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div><?php echo e(session('error')); ?></div>
                </div>
            <?php endif; ?>

            <p class="f-auth-sub" style="margin-bottom:12px"><?php echo e(__('auth.otp_unavailable.body')); ?></p>
            <p class="f-auth-sub"><?php echo e(__('auth.otp_unavailable.next')); ?></p>

            <a href="<?php echo e(route('login')); ?>" class="f-btn f-btn--primary f-btn--block">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'log-in', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('auth.otp_unavailable.cta_signin')); ?>

            </a>

            <div class="f-auth-note">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'shield-check', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <p><?php echo e(__('auth.otp_unavailable.security_note')); ?></p>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/auth/verify_otp.blade.php ENDPATH**/ ?>
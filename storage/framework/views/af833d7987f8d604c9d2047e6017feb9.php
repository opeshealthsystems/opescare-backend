<?php $__env->startSection('title', __('onboarding.guardian.pending_title')); ?>



<?php $__env->startSection('content'); ?>
    <div class="auth-card">
        <div class="auth-status-icon" aria-hidden="true">
            <i data-lucide="clock" style="width:2.5rem;height:2.5rem;"></i>
        </div>

        <h1 class="auth-headline"><?php echo e(__('onboarding.guardian.pending_headline')); ?></h1>
        <p class="auth-subheadline"><?php echo e(__('onboarding.guardian.pending_body')); ?></p>

        <?php if(session('success')): ?>
            <div class="auth-alert auth-alert--success" role="status">
                <i data-lucide="check-circle" style="width:1.05rem;height:1.05rem;flex-shrink:0;"></i>
                <span><?php echo e(session('success')); ?></span>
            </div>
        <?php endif; ?>

        <p class="auth-fineprint"><?php echo e(__('onboarding.guardian.pending_next')); ?></p>

        <p class="auth-alt-action">
            <a href="<?php echo e(route('public.contact')); ?>"><?php echo e(__('onboarding.guardian.pending_contact')); ?></a>
        </p>

        <form method="POST" action="<?php echo e(route('logout')); ?>">
            <?php echo csrf_field(); ?>
            <button type="submit" class="auth-btn auth-btn-secondary auth-btn--mt">
                <?php echo e(__('onboarding.guardian.pending_signout')); ?>

            </button>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\guardian\pending.blade.php ENDPATH**/ ?>
<?php $__env->startSection('title', __('onboarding.suspended.title')); ?>

<?php $__env->startSection('content'); ?>
    <div class="f-auth-col">
        <div class="f-auth-card f-auth-card--center">
            <div class="f-auth-badge f-auth-badge--danger f-auth-badge--center">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>

            <h1 class="f-auth-title"><?php echo e(__('onboarding.suspended.title')); ?></h1>
            <p class="f-auth-sub"><?php echo e(__('onboarding.suspended.desc')); ?></p>

            
            <div class="f-auth-meta">
                <p class="f-auth-meta__title"><?php echo e(__('onboarding.suspended.security_warning')); ?></p>
                <p class="f-auth-fine" style="margin:0"><?php echo e(__('finder_auth.suspended.note')); ?></p>
            </div>

            <a href="<?php echo e(route('public.contact')); ?>" class="f-btn f-btn--primary f-btn--block" style="margin-bottom:10px">
                <?php echo e(__('onboarding.brand.contact_support')); ?>

            </a>

            <a href="<?php echo e(route('login')); ?>" class="f-btn f-btn--block">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-left', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('onboarding.selector.signin')); ?>

            </a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\auth\account_suspended.blade.php ENDPATH**/ ?>
<?php $__env->startSection('title', __('auth.portal_unavailable.page_title')); ?>

<?php $__env->startSection('content'); ?>
    
    <div class="f-auth-col">
        <div class="f-auth-card f-auth-card--center">
            <div class="f-auth-badge f-auth-badge--warn f-auth-badge--center">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'clock', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>

            <h1 class="f-auth-title"><?php echo e(__('auth.portal_unavailable.title')); ?></h1>
            <p class="f-auth-sub" style="margin-bottom:12px"><?php echo e(__('auth.portal_unavailable.body')); ?></p>
            <p class="f-auth-sub"><?php echo e(__('auth.portal_unavailable.next')); ?></p>

            <a href="<?php echo e(route('public.contact')); ?>" class="f-btn f-btn--primary f-btn--block" style="margin-bottom:10px">
                <?php echo e(__('auth.portal_unavailable.cta_contact')); ?>

            </a>

            <form method="POST" action="<?php echo e(route('logout')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="f-btn f-btn--block">
                    <?php echo e(__('auth.portal_unavailable.cta_signout')); ?>

                </button>
            </form>

            <p class="f-auth-fine" style="margin-top:20px">
                <?php echo e(__('auth.portal_unavailable.signed_in_as', ['email' => auth()->user()?->email ?? '—'])); ?>

            </p>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/auth/portal_unavailable.blade.php ENDPATH**/ ?>
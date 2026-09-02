<?php $__env->startSection('title', __('auth.portal_unavailable.page_title')); ?>

<?php $__env->startSection('content'); ?>
    
    <div style="max-width:34rem;margin:0 auto;text-align:center;">

        <div style="width:3.5rem;height:3.5rem;margin:0 auto 1.5rem;display:flex;align-items:center;justify-content:center;background:#FEF3C7;border-radius:1rem;">
            <i data-lucide="clock" style="width:1.75rem;height:1.75rem;color:#B45309;"></i>
        </div>

        <h1 style="font-size:1.5rem;font-weight:800;margin-bottom:0.75rem;color:var(--color-text-primary,#0F172A);">
            <?php echo e(__('auth.portal_unavailable.title')); ?>

        </h1>

        <p style="color:var(--color-text-secondary,#475569);line-height:1.6;margin-bottom:1.5rem;">
            <?php echo e(__('auth.portal_unavailable.body')); ?>

        </p>

        <p style="color:var(--color-text-secondary,#475569);line-height:1.6;margin-bottom:2rem;font-size:0.9375rem;">
            <?php echo e(__('auth.portal_unavailable.next')); ?>

        </p>

        
        <div style="display:flex;justify-content:center;gap:0.75rem;flex-wrap:wrap;">
            <a href="<?php echo e(route('public.contact')); ?>" class="auth-btn auth-btn-primary" style="text-decoration:none;">
                <?php echo e(__('auth.portal_unavailable.cta_contact')); ?>

            </a>
            <form method="POST" action="<?php echo e(route('logout')); ?>" style="display:inline;">
                <?php echo csrf_field(); ?>
                <button type="submit" class="auth-btn auth-btn-secondary">
                    <?php echo e(__('auth.portal_unavailable.cta_signout')); ?>

                </button>
            </form>
        </div>

        <p style="margin-top:2rem;font-size:0.8125rem;color:var(--color-text-muted,#94A3B8);">
            <?php echo e(__('auth.portal_unavailable.signed_in_as', ['email' => auth()->user()?->email ?? '—'])); ?>

        </p>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/auth/portal_unavailable.blade.php ENDPATH**/ ?>
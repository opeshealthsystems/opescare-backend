<?php $__env->startSection('title', __('onboarding.pending.title')); ?>

<?php $__env->startSection('content'); ?>
    <div class="f-auth-col">
        <div class="f-auth-card f-auth-card--center">
            <div class="f-auth-badge f-auth-badge--warn f-auth-badge--center">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'clock', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>

            <h1 class="f-auth-title"><?php echo e(__('onboarding.pending.title')); ?></h1>
            <p class="f-auth-sub"><?php echo e(__('onboarding.pending.desc')); ?></p>

            <div class="f-auth-meta">
                <p class="f-auth-meta__title"><?php echo e(__('onboarding.pending.card_header')); ?></p>

                <div class="f-auth-meta__row">
                    <span class="f-auth-meta__label"><?php echo e(__('onboarding.pending.ref_number')); ?></span>
                    <span class="f-auth-meta__value f-auth-meta__value--mono"><?php echo e($ref_code); ?></span>
                </div>

                <div class="f-auth-meta__row">
                    <span class="f-auth-meta__label"><?php echo e(__('onboarding.pending.org_name')); ?></span>
                    <span class="f-auth-meta__value"><?php echo e($org_name); ?></span>
                </div>

                <div class="f-auth-meta__row">
                    <span class="f-auth-meta__label"><?php echo e(__('onboarding.pending.submitted_date')); ?></span>
                    <span class="f-auth-meta__value"><?php echo e($submitted_date); ?></span>
                </div>

                <div class="f-auth-meta__row">
                    <span class="f-auth-meta__label"><?php echo e(__('onboarding.pending.status_label')); ?></span>
                    <span class="f-badge f-badge--warn"><?php echo e(__('onboarding.pending.status_under_review')); ?></span>
                </div>

                <div class="f-auth-meta__row f-auth-meta__row--block">
                    <span class="f-auth-meta__label"><?php echo e(__('onboarding.pending.admin_notes')); ?></span>
                    <div class="f-auth-meta__note"><?php echo e($admin_note ?? __('onboarding.pending.default_admin_note')); ?></div>
                </div>
            </div>

            <a href="<?php echo e(route('public.contact')); ?>" class="f-btn f-btn--primary f-btn--block" style="margin-bottom:10px">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'info', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('onboarding.pending.cta_support')); ?>

            </a>

            <a href="<?php echo e(route('public.landing')); ?>" class="f-btn f-btn--block">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-left', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('onboarding.common.back_to_home')); ?>

            </a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\auth\pending_approval.blade.php ENDPATH**/ ?>
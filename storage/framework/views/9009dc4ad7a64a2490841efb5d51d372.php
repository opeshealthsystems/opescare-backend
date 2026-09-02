<?php $__env->startSection('title', __('public.adm_cc_ff_title')); ?>
<?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('breadcrumb_home', __('public.adm_cc_ff_breadcrumb_home')); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.admin')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.adm_cc_ff_breadcrumb_section')); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.adm_cc_ff_heading')); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.adm_cc_ff_subtitle')); ?></p>
    </div>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div><?php echo e(session('error')); ?></div></div>
<?php endif; ?>

<div class="panel">
    <?php if($flags->count() === 0): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="toggle-right"></i></div>
            <h3><?php echo e(__('public.adm_cc_ff_empty_heading')); ?></h3>
            <p><?php echo e(__('public.adm_cc_ff_empty_body')); ?></p>
        </div>
    <?php else: ?>
    <div class="panel-body">
        <?php $__currentLoopData = $flags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $flag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="toggle-row">
            <div class="toggle-row__body">
                <div class="toggle-row__title"><?php echo e($flag->label); ?></div>
                <div class="toggle-row__desc">
                    <span class="code-token"><?php echo e($flag->key); ?></span>
                    <span class="badge badge-neutral badge-sm"><?php echo e($flag->scope); ?></span>
                    <?php if($flag->updated_by): ?><span class="td-muted">· <?php echo e(__('public.adm_cc_ff_updated_by')); ?> <?php echo e($flag->updated_by); ?></span><?php endif; ?>
                </div>
            </div>
            <span class="badge <?php echo e($flag->enabled ? 'badge-success' : 'badge-neutral'); ?> badge-sm">
                <?php echo e($flag->enabled ? __('public.adm_cc_ff_badge_enabled') : __('public.adm_cc_ff_badge_disabled')); ?>

            </span>
            <form method="POST" action="<?php echo e(route('portals.admin.cc.feature_flags.toggle', urlencode($flag->key))); ?>" class="inline-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="enabled" value="<?php echo e($flag->enabled ? '0' : '1'); ?>">
                <label class="switch">
                    <input type="checkbox" <?php echo e($flag->enabled ? 'checked' : ''); ?> onchange="this.form.submit()" aria-label="Toggle <?php echo e($flag->label); ?>">
                    <span class="switch__track"></span>
                </label>
            </form>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\control_center\feature_flags.blade.php ENDPATH**/ ?>
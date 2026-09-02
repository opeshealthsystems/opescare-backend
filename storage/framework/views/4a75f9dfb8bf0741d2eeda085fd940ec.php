<?php $__env->startSection('title', __('public.adm_legal_minor_page_title')); ?>
<?php $__env->startSection('sidebar'); ?> <?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="breadcrumb">
        <i data-lucide="chevron-right"></i>
    <span><?php echo e(__('public.adm_legal_minor_breadcrumb_span')); ?></span>
</div>

<div class="page-head">
    <h2><?php echo e(__('public.adm_legal_minor_h2')); ?></h2>
</div>
<p class="td-muted mb-6"><?php echo e(__('public.adm_legal_minor_subtitle')); ?></p>

<div class="panel">
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr><th><?php echo e(__('public.adm_legal_closures_col_patient')); ?></th><th><?php echo e(__('public.adm_legal_minor_col_dob')); ?></th><th><?php echo e(__('public.adm_legal_minor_col_turns18')); ?></th><th><?php echo e(__('public.adm_legal_minor_col_days_until_18')); ?></th><th><?php echo e(__('public.adm_legal_col_status')); ?></th></tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $transitions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $days = $t->daysUntil18();
                        $daysBadge = $days <= 30 ? 'badge-danger' : ($days <= 90 ? 'badge-warning' : 'badge-success');
                    ?>
                    <tr>
                        <td data-label="<?php echo e(__('public.adm_legal_closures_col_patient')); ?>" class="td-strong">
                            <?php echo e($t->patient?->first_name); ?> <?php echo e($t->patient?->last_name); ?>

                            <div class="code-muted"><?php echo e($t->patient?->health_id); ?></div>
                        </td>
                        <td data-label="<?php echo e(__('public.adm_legal_minor_col_dob')); ?>"><?php echo e($t->date_of_birth->format('d M Y')); ?></td>
                        <td data-label="<?php echo e(__('public.adm_legal_minor_col_turns18')); ?>" class="td-strong"><?php echo e($t->turns_18_on->format('d M Y')); ?></td>
                        <td data-label="<?php echo e(__('public.adm_legal_minor_col_days_until_18')); ?>">
                            <span class="badge <?php echo e($daysBadge); ?> badge-sm">
                                <?php if($days < 0): ?> <?php echo e(__('public.adm_legal_minor_overdue')); ?> <?php elseif($days === 0): ?> <?php echo e(__('public.adm_legal_minor_today')); ?> <?php else: ?> <?php echo e($days); ?> <?php echo e(__('public.adm_legal_minor_days')); ?> <?php endif; ?>
                            </span>
                        </td>
                        <td data-label="<?php echo e(__('public.adm_legal_col_status')); ?>">
                            <span class="badge badge--<?php echo e($t->statusColor()); ?> badge-sm"><?php echo e(\App\Support\Enums::label($t->status)); ?></span>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="5" class="td-muted empty-cell"><?php echo e(__('public.adm_legal_minor_empty')); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<div class="mt-6"><?php echo e($transitions->links()); ?></div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\legal\minor_transitions.blade.php ENDPATH**/ ?>
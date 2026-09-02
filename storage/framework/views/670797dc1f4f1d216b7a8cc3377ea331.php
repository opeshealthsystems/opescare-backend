<?php $__env->startSection('title', __('public.adm_legal_complaints_page_title')); ?>
<?php $__env->startSection('sidebar'); ?> <?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="breadcrumb">
        <i data-lucide="chevron-right"></i>
    <span><?php echo e(__('public.adm_legal_complaints_breadcrumb_span')); ?></span>
</div>

<div class="page-head">
    <h2><?php echo e(__('public.adm_legal_complaints_h2')); ?></h2>
</div>
<p class="td-muted mb-6"><?php echo e(__('public.adm_legal_complaints_subtitle')); ?></p>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if(session('error')): ?><div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>

<div class="panel">
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr><th><?php echo e(__('public.adm_legal_complaints_col_complainant')); ?></th><th><?php echo e(__('public.adm_legal_complaints_col_type')); ?></th><th><?php echo e(__('public.adm_legal_complaints_col_description')); ?></th><th><?php echo e(__('public.adm_legal_col_status')); ?></th><th><?php echo e(__('public.adm_legal_complaints_col_received')); ?></th><th class="row-actions"></th></tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $complaints; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td data-label="<?php echo e(__('public.adm_legal_complaints_col_complainant')); ?>" class="td-strong">
                            <?php echo e($c->complainant_name ?: ($c->patient?->first_name . ' ' . $c->patient?->last_name) ?: '—'); ?>

                            <?php if($c->complainant_email): ?>
                                <div class="td-muted code-muted"><?php echo e($c->complainant_email); ?></div>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?php echo e(__('public.adm_legal_complaints_col_type')); ?>">
                            <span class="badge badge-warning badge-sm"><?php echo e(ucwords(str_replace('_', ' ', $c->complaint_type))); ?></span>
                        </td>
                        <td data-label="<?php echo e(__('public.adm_legal_complaints_col_description')); ?>"><?php echo e(\Illuminate\Support\Str::limit($c->description, 80)); ?></td>
                        <td data-label="<?php echo e(__('public.adm_legal_col_status')); ?>">
                            <span class="badge badge--<?php echo e($c->statusColor()); ?> badge-sm"><?php echo e(\App\Support\Enums::label($c->status)); ?></span>
                        </td>
                        <td data-label="<?php echo e(__('public.adm_legal_complaints_col_received')); ?>" class="td-muted"><?php echo e($c->created_at->format('d M Y')); ?></td>
                        <td class="row-actions"></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="td-muted empty-cell"><?php echo e(__('public.adm_legal_complaints_empty')); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<div class="mt-6"><?php echo e($complaints->links()); ?></div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\legal\complaints.blade.php ENDPATH**/ ?>
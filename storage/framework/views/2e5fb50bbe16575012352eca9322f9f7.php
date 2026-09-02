<?php $__env->startSection('title', __('public.adm_legal_closures_page_title')); ?>
<?php $__env->startSection('sidebar'); ?> <?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="breadcrumb">
        <i data-lucide="chevron-right"></i>
    <span><?php echo e(__('public.adm_legal_closures_breadcrumb_span')); ?></span>
</div>

<div class="page-head">
    <h2><?php echo e(__('public.adm_legal_closures_h2')); ?></h2>
</div>
<p class="td-muted mb-6"><?php echo e(__('public.adm_legal_closures_subtitle')); ?></p>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if(session('error')): ?><div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>

<div class="panel">
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr><th><?php echo e(__('public.adm_legal_closures_col_patient')); ?></th><th><?php echo e(__('public.adm_legal_closures_col_reason')); ?></th><th><?php echo e(__('public.adm_legal_closures_col_delete_req')); ?></th><th><?php echo e(__('public.adm_legal_closures_col_export_req')); ?></th><th><?php echo e(__('public.adm_legal_col_status')); ?></th><th><?php echo e(__('public.adm_legal_closures_col_requested')); ?></th><th class="row-actions"></th></tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td data-label="<?php echo e(__('public.adm_legal_closures_col_patient')); ?>" class="td-strong">
                            <?php echo e($req->patient?->first_name); ?> <?php echo e($req->patient?->last_name); ?>

                            <div class="code-muted"><?php echo e($req->patient?->health_id); ?></div>
                        </td>
                        <td data-label="<?php echo e(__('public.adm_legal_closures_col_reason')); ?>"><?php echo e(\Illuminate\Support\Str::limit($req->reason, 60) ?: '—'); ?></td>
                        <td data-label="<?php echo e(__('public.adm_legal_closures_col_delete_req')); ?>">
                            <?php if($req->data_delete_requested): ?><span class="badge badge-danger badge-sm"><?php echo e(__('admin_extra.yes', [], app()->getLocale()) ?: 'Yes'); ?></span><?php else: ?><span class="td-muted"><?php echo e(__('admin_extra.no', [], app()->getLocale()) ?: 'No'); ?></span><?php endif; ?>
                        </td>
                        <td data-label="<?php echo e(__('public.adm_legal_closures_col_export_req')); ?>">
                            <?php if($req->data_export_requested): ?><span class="badge badge-primary badge-sm"><?php echo e(__('admin_extra.yes', [], app()->getLocale()) ?: 'Yes'); ?></span><?php else: ?><span class="td-muted"><?php echo e(__('admin_extra.no', [], app()->getLocale()) ?: 'No'); ?></span><?php endif; ?>
                        </td>
                        <td data-label="<?php echo e(__('public.adm_legal_col_status')); ?>">
                            <span class="badge badge--<?php echo e($req->statusColor()); ?> badge-sm"><?php echo e(\App\Support\Enums::label($req->status)); ?></span>
                        </td>
                        <td data-label="<?php echo e(__('public.adm_legal_closures_col_requested')); ?>" class="td-muted"><?php echo e($req->created_at->format('d M Y')); ?></td>
                        <td class="row-actions"></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="td-muted empty-cell"><?php echo e(__('public.adm_legal_closures_empty')); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<div class="mt-6"><?php echo e($requests->links()); ?></div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\legal\closures.blade.php ENDPATH**/ ?>
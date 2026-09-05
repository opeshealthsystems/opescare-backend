<?php $__env->startSection('title', __('public.pharmacy_portal.page_title', [], app()->getLocale()) ?: 'Controlled Substances'); ?>
<?php $__env->startSection('sidebar_user_role', __('public.pharmacy_portal.role_label', [], app()->getLocale()) ?: 'Pharmacist'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home', [], app()->getLocale()) ?: 'Pharmacy Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.pharmacy.dashboard')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.pharmacy_portal.breadcrumb_section_controlled', [], app()->getLocale()) ?: 'Controlled Substances'); ?>
<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-head">
    <h2><?php echo e(__('public.pharmacy_portal.page_heading_controlled', [], $l) ?: 'Controlled substances'); ?></h2>
    <p class="page-subtitle"><?php echo e(__('public.pharmacy_portal.page_subtitle_controlled', [], $l) ?: 'Stock overview and recent dispensing log for controlled drugs.'); ?></p>
</div>

<div class="field-grid">

    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="lock"></i> <?php echo e(__('public.pharmacy_portal.panel_controlled_stock', [], $l) ?: 'Controlled drug stock'); ?></h3></div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th><?php echo e(__('public.pharmacy_portal.col_drug', [], $l) ?: 'Drug'); ?></th>
                    <th><?php echo e(__('public.pharmacy_portal.col_form', [], $l) ?: 'Form'); ?></th>
                    <th><?php echo e(__('public.pharmacy_portal.col_qty', [], $l) ?: 'Qty'); ?></th>
                    <th><?php echo e(__('public.pharmacy_portal.col_status', [], $l) ?: 'Status'); ?></th>
                </tr></thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $controlled; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $drug): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td data-label="<?php echo e(__('public.pharmacy_portal.col_drug', [], $l) ?: 'Drug'); ?>" class="td-strong"><?php echo e($drug->medicine_name); ?></td>
                        <td data-label="<?php echo e(__('public.pharmacy_portal.col_form', [], $l) ?: 'Form'); ?>" class="td-muted"><?php echo e($drug->form); ?> <?php echo e($drug->strength); ?></td>
                        <td data-label="<?php echo e(__('public.pharmacy_portal.col_qty', [], $l) ?: 'Qty'); ?>" class="td-strong"><?php echo e($drug->available_quantity); ?></td>
                        <td data-label="<?php echo e(__('public.pharmacy_portal.col_status', [], $l) ?: 'Status'); ?>">
                            <span class="badge badge-<?php echo e(match($drug->stock_status) { 'in_stock' => 'success', 'low_stock' => 'warning', 'out_of_stock' => 'danger', default => 'neutral' }); ?>">
                                <?php echo e(\App\Support\Enums::label($drug->stock_status, 'stock_status')); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="4" class="td-muted empty-cell"><?php echo e(__('public.pharmacy_portal.no_controlled', [], $l) ?: 'No controlled substances on record.'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="history"></i> <?php echo e(__('public.pharmacy_portal.panel_dispensing_log', [], $l) ?: 'Recent dispensing log'); ?></h3></div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th><?php echo e(__('public.pharmacy_portal.col_patient', [], $l) ?: 'Patient'); ?></th>
                    <th><?php echo e(__('public.pharmacy_portal.col_items', [], $l) ?: 'Items'); ?></th>
                    <th><?php echo e(__('public.pharmacy_portal.col_dispensed', [], $l) ?: 'Dispensed'); ?></th>
                </tr></thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $recentRx; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td data-label="<?php echo e(__('public.pharmacy_portal.col_patient', [], $l) ?: 'Patient'); ?>" class="td-strong"><?php echo e($rx->patient?->full_name ?? '—'); ?></td>
                        <td data-label="<?php echo e(__('public.pharmacy_portal.col_items', [], $l) ?: 'Items'); ?>" class="td-muted"><?php echo e($rx->items->count()); ?> <?php echo e(__('public.pharmacy_portal.lbl_items_count', [], $l) ?: 'item(s)'); ?></td>
                        <td data-label="<?php echo e(__('public.pharmacy_portal.col_dispensed', [], $l) ?: 'Dispensed'); ?>" class="td-muted"><?php echo e($rx->dispensed_at?->format('d M Y H:i') ?? '—'); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="3" class="td-muted empty-cell"><?php echo e(__('public.pharmacy_portal.no_dispensing_records', [], $l) ?: 'No dispensing records.'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/pharmacy/controlled.blade.php ENDPATH**/ ?>
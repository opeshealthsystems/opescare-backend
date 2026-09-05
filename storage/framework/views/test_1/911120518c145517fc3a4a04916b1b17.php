<?php $__env->startSection('title', __('public.pharmacy_portal.page_title', [], app()->getLocale()) ?: 'Pharmacy Portal'); ?>

<?php $__env->startSection('sidebar_user_role', __('public.pharmacy_portal.role_label', [], app()->getLocale()) ?: 'Pharmacist'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.pharmacy_portal.page_title', [], app()->getLocale()) ?: 'Pharmacy Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.pharmacy.dashboard')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.pharmacy_portal.nav_dashboard', [], app()->getLocale()) ?: 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>

<div class="page-head">
    <h2><?php echo e(__('public.pharmacy_portal.page_heading', [], app()->getLocale()) ?: 'Pharmacy dashboard'); ?></h2>
    <p class="page-subtitle"><?php echo e(__('public.pharmacy_portal.page_subtitle', [], app()->getLocale()) ?: "Today's prescription queue, stock alerts, and dispensing activity."); ?></p>
    <div class="page-head__spacer"></div>
    <a href="<?php echo e(route('portals.pharmacy.prescriptions')); ?>" class="btn btn-primary btn-sm">
        <i data-lucide="clipboard-list"></i> <?php echo e(__('public.pharmacy_portal.btn_view_queue', [], app()->getLocale()) ?: 'View full queue'); ?>

    </a>
</div>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>


<div class="stat-grid mb-6">
    <a href="<?php echo e(route('portals.pharmacy.prescriptions')); ?>" class="stat-card stat-card--warning">
        <div class="stat-card__label"><?php echo e(__('public.pharmacy_portal.stat_pending_rx', [], app()->getLocale()) ?: 'Pending Rx'); ?></div>
        <div class="stat-card__value"><?php echo e($stats['pending_rx'] ?? 0); ?></div>
    </a>
    <a href="<?php echo e(route('portals.pharmacy.prescriptions', ['status' => 'dispensed'])); ?>" class="stat-card stat-card--success">
        <div class="stat-card__label"><?php echo e(__('public.pharmacy_portal.stat_dispensed_today', [], app()->getLocale()) ?: 'Dispensed today'); ?></div>
        <div class="stat-card__value"><?php echo e($stats['dispensed_today'] ?? 0); ?></div>
    </a>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'inventory_ops')): ?>
        <?php endif; ?>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'inventory_ops')): ?>
        <?php endif; ?>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'inventory_ops')): ?>
        <?php endif; ?>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'inventory_ops')): ?>
        <?php endif; ?>
</div>

<div class="field-grid">

    
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="clipboard-list"></i> <?php echo e(__('public.pharmacy_portal.panel_pending_rx', [], app()->getLocale()) ?: 'Pending prescriptions'); ?></h3>
            <a href="<?php echo e(route('portals.pharmacy.prescriptions')); ?>" class="btn btn-secondary btn-sm"><?php echo e(__('public.portal.view_all', [], app()->getLocale()) ?: 'View all'); ?></a>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th><?php echo e(__('public.portal.col_patient', [], app()->getLocale()) ?: 'Patient'); ?></th>
                    <th><?php echo e(__('public.portal.col_status', [], app()->getLocale()) ?: 'Status'); ?></th>
                    <th class="row-actions"></th>
                </tr></thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $pendingRx; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td data-label="<?php echo e(__('public.portal.col_patient', [], app()->getLocale()) ?: 'Patient'); ?>">
                        <span class="td-strong"><?php echo e($rx->patient?->full_name ?? '—'); ?></span>
                        <div class="td-muted"><?php echo e($rx->items()->count()); ?> <?php echo e(__('public.pharmacy_portal.lbl_items', [], app()->getLocale()) ?: 'item(s)'); ?> &middot; <?php echo e($rx->created_at?->diffForHumans()); ?></div>
                    </td>
                    <td data-label="<?php echo e(__('public.portal.col_status', [], app()->getLocale()) ?: 'Status'); ?>"><span class="badge badge-<?php echo e($rx->statusColor()); ?>"><?php echo e(\App\Support\Enums::label($rx->status)); ?></span></td>
                    <td class="row-actions" data-label="">
                        <form method="POST" action="<?php echo e(route('portals.pharmacy.dispense', $rx->id)); ?>" class="inline-form"><?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('<?php echo e(__('public.pharmacy_portal.confirm_dispense', [], app()->getLocale()) ?: 'Mark as dispensed?'); ?>')">
                                <?php echo e(__('public.pharmacy_portal.btn_dispense', [], app()->getLocale()) ?: 'Dispense'); ?>

                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="3" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="check-circle-2" class="empty-state-icon"></i>
                        <p><?php echo e(__('public.pharmacy_portal.no_pending_rx', [], app()->getLocale()) ?: 'All prescriptions are up to date.'); ?></p>
                    </div>
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="alert-triangle"></i> <?php echo e(__('public.pharmacy_portal.panel_stock_alerts', [], app()->getLocale()) ?: 'Stock alerts'); ?></h3>
            <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'inventory_ops')): ?>
                        <?php endif; ?>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th><?php echo e(__('public.pharmacy_portal.col_medicine', [], app()->getLocale()) ?: 'Medicine'); ?></th>
                    <th><?php echo e(__('public.portal.col_status', [], app()->getLocale()) ?: 'Status'); ?></th>
                </tr></thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $alerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $drug): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td data-label="<?php echo e(__('public.pharmacy_portal.col_medicine', [], app()->getLocale()) ?: 'Medicine'); ?>">
                        <span class="td-strong"><?php echo e($drug->medicine_name); ?></span>
                        <div class="td-muted"><?php echo e($drug->generic_name); ?> &middot; <?php echo e($drug->form); ?> <?php echo e($drug->strength); ?></div>
                    </td>
                    <td data-label="<?php echo e(__('public.portal.col_status', [], app()->getLocale()) ?: 'Status'); ?>">
                        <span class="badge badge-<?php echo e($drug->is_expired ? 'danger' : ($drug->stock_status === 'out_of_stock' ? 'danger' : 'warning')); ?>">
                            <?php if($drug->is_expired): ?><?php echo e(__('public.pharmacy_portal.lbl_expired', [], app()->getLocale()) ?: 'Expired'); ?><?php else: ?>@enum($drug->stock_status, 'stock_status')<?php endif; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="2" class="td-muted empty-cell"><?php echo e(__('public.pharmacy_portal.no_stock_alerts', [], app()->getLocale()) ?: 'No stock alerts.'); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/pharmacy/dashboard.blade.php ENDPATH**/ ?>
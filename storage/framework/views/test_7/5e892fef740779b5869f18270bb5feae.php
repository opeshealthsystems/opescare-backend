<?php $__env->startSection('title', __('public.pharmacy_portal.page_title', [], app()->getLocale()) ?: 'Prescription Queue'); ?>
<?php $__env->startSection('sidebar_role_badge'); ?>
<div class="sidebar-role-badge">
    <i data-lucide="pill"></i>
    <?php echo e(__('public.pharmacy_portal.role_badge', [], app()->getLocale()) ?: 'Pharmacy'); ?>

</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('sidebar_user_role', __('public.pharmacy_portal.role_label', [], app()->getLocale()) ?: 'Pharmacist'); ?>
<?php $__env->startSection('sidebar_nav'); ?>
<?php echo $__env->make('portals.pharmacy._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home', [], app()->getLocale()) ?: 'Pharmacy Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.pharmacy.dashboard')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.pharmacy_portal.breadcrumb_section_rx', [], app()->getLocale()) ?: 'Prescription Queue'); ?>
<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-head">
    <h2><?php echo e(__('public.pharmacy_portal.page_heading_rx', [], $l) ?: 'Prescription queue'); ?></h2>
    <p class="page-subtitle"><?php echo e(__('public.pharmacy_portal.page_subtitle_rx', [], $l) ?: 'All prescriptions awaiting dispensing at this facility.'); ?></p>
</div>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>

<form method="GET" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="search" placeholder="<?php echo e(__('public.pharmacy_portal.ph_search_patient', [], $l) ?: 'Patient name…'); ?>" value="<?php echo e(request('search')); ?>" aria-label="<?php echo e(__('public.aria_search_patient')); ?>">
    </label>
    <select name="status" class="filter-select" aria-label="<?php echo e(__('public.aria_status')); ?>" onchange="this.form.submit()">
        <option value=""><?php echo e(__('public.pharmacy_portal.filter_pending_all', [], $l) ?: 'Pending (all)'); ?></option>
        <option value="active" <?php echo e(request('status') === 'active' ? 'selected' : ''); ?>><?php echo e(__('public.pharmacy_portal.filter_status_active', [], $l) ?: 'Active'); ?></option>
        <option value="partially_dispensed" <?php echo e(request('status') === 'partially_dispensed' ? 'selected' : ''); ?>><?php echo e(__('public.pharmacy_portal.filter_status_partial', [], $l) ?: 'Partially dispensed'); ?></option>
        <option value="dispensed" <?php echo e(request('status') === 'dispensed' ? 'selected' : ''); ?>><?php echo e(__('public.pharmacy_portal.filter_status_dispensed', [], $l) ?: 'Dispensed'); ?></option>
        <option value="cancelled" <?php echo e(request('status') === 'cancelled' ? 'selected' : ''); ?>><?php echo e(__('public.pharmacy_portal.filter_status_cancelled', [], $l) ?: 'Cancelled'); ?></option>
        <option value="expired" <?php echo e(request('status') === 'expired' ? 'selected' : ''); ?>><?php echo e(__('public.pharmacy_portal.filter_status_expired', [], $l) ?: 'Expired'); ?></option>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="filter"></i> <?php echo e(__('public.pharmacy_portal.btn_filter', [], $l) ?: 'Filter'); ?></button>
    <?php if(request()->hasAny(['status','search'])): ?>
        <a href="<?php echo e(route('portals.pharmacy.prescriptions')); ?>" class="btn btn-ghost btn-sm"><?php echo e(__('public.pharmacy_portal.btn_clear', [], $l) ?: 'Clear'); ?></a>
    <?php endif; ?>
</form>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?php echo e(__('public.pharmacy_portal.col_patient', [], $l) ?: 'Patient'); ?></th>
                    <th><?php echo e(__('public.pharmacy_portal.col_items', [], $l) ?: 'Items'); ?></th>
                    <th><?php echo e(__('public.pharmacy_portal.col_prescribed', [], $l) ?: 'Prescribed'); ?></th>
                    <th><?php echo e(__('public.pharmacy_portal.col_expires', [], $l) ?: 'Expires'); ?></th>
                    <th><?php echo e(__('public.pharmacy_portal.col_status', [], $l) ?: 'Status'); ?></th>
                    <th class="row-actions"><?php echo e(__('public.pharmacy_portal.col_action', [], $l) ?: 'Action'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $prescriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td data-label="<?php echo e(__('public.pharmacy_portal.col_patient', [], $l) ?: 'Patient'); ?>">
                        <span class="td-strong"><?php echo e($rx->patient?->full_name ?? '—'); ?></span>
                        <div class="td-muted"><?php echo e($rx->patient?->health_id ?? ''); ?></div>
                    </td>
                    <td data-label="<?php echo e(__('public.pharmacy_portal.col_items', [], $l) ?: 'Items'); ?>">
                        <?php $__currentLoopData = $rx->items->take(2); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div><?php echo e($item->drug_name); ?> <?php echo e($item->dosage); ?></div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php if($rx->items->count() > 2): ?>
                            <div class="td-muted">+<?php echo e($rx->items->count() - 2); ?> <?php echo e(__('public.pharmacy_portal.lbl_items', [], $l) ?: 'more'); ?></div>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?php echo e(__('public.pharmacy_portal.col_prescribed', [], $l) ?: 'Prescribed'); ?>" class="td-muted"><?php echo e($rx->prescribed_at?->format('d M Y H:i') ?? $rx->created_at?->format('d M Y')); ?></td>
                    <td data-label="<?php echo e(__('public.pharmacy_portal.col_expires', [], $l) ?: 'Expires'); ?>">
                        <?php if($rx->expires_at): ?>
                            <?php if($rx->expires_at->isPast()): ?><span class="badge badge-danger badge-sm"><?php echo e($rx->expires_at->format('d M Y')); ?></span>
                            <?php else: ?><span class="td-muted"><?php echo e($rx->expires_at->format('d M Y')); ?></span><?php endif; ?>
                        <?php else: ?>
                            <span class="td-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?php echo e(__('public.pharmacy_portal.col_status', [], $l) ?: 'Status'); ?>"><span class="badge badge-<?php echo e($rx->statusColor()); ?>"><?php echo e(\App\Support\Enums::label($rx->status)); ?></span></td>
                    <td class="row-actions" data-label="<?php echo e(__('public.pharmacy_portal.col_action', [], $l) ?: 'Action'); ?>">
                        <?php if(in_array($rx->status, ['active', 'partially_dispensed'])): ?>
                        <form method="POST" action="<?php echo e(route('portals.pharmacy.dispense', $rx->id)); ?>" class="inline-form"><?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('<?php echo e(__('public.pharmacy_portal.confirm_dispense_msg', [], $l) ?: 'Mark prescription as fully dispensed?'); ?>')">
                                <i data-lucide="check"></i> <?php echo e(__('public.pharmacy_portal.btn_dispense', [], $l) ?: 'Dispense'); ?>

                            </button>
                        </form>
                        <?php else: ?>
                        <span class="td-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="6" class="td-muted empty-cell"><?php echo e(__('public.pharmacy_portal.no_prescriptions', [], $l) ?: 'No prescriptions found.'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="panel-body"><?php echo e($prescriptions->links()); ?></div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/pharmacy/prescriptions.blade.php ENDPATH**/ ?>
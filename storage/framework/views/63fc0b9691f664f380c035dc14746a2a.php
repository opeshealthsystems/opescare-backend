<?php $__env->startSection('title', __('staff_inv.title_pharmacy', [], app()->getLocale()) ?: 'Pharmacy Inventory'); ?>

<?php $__env->startSection('sidebar_role_badge'); ?>
<div class="sidebar-role-badge"><?php echo e(__('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff'); ?></div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('sidebar_user_role', __('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff'); ?>

<?php $__env->startSection('sidebar_nav'); ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('staff_inv.nav_lbl_overview', [], app()->getLocale()) ?: 'Overview'); ?></div>
    <a href="<?php echo e(route('portals.staff')); ?>" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span><?php echo e(__('public.portal.nav_dashboard', [], app()->getLocale()) ?: 'Dashboard'); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('staff_inv.nav_lbl_clinical', [], app()->getLocale()) ?: 'Clinical'); ?></div>
    <a href="<?php echo e(route('portals.staff.appointments')); ?>" class="sidebar-link">
        <i data-lucide="calendar-check-2"></i>
        <span><?php echo e(__('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments'); ?></span>
    </a>
        </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('staff_inv.nav_lbl_hr', [], app()->getLocale()) ?: 'HR & Staff'); ?></div>
                </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('staff_inv.nav_lbl_inventory', [], app()->getLocale()) ?: 'Inventory'); ?></div>
        <a href="<?php echo e(route('portals.staff.inventory.blood')); ?>" class="sidebar-link">
        <i data-lucide="droplets"></i>
        <span><?php echo e(__('public.portal.nav_inventory_blood', [], app()->getLocale()) ?: 'Blood Bank'); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('staff_inv.nav_lbl_supply_chain', [], app()->getLocale()) ?: 'Supply Chain'); ?></div>
    </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('staff_inv.nav_lbl_operations', [], app()->getLocale()) ?: 'Operations'); ?></div>
                        </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.staff')); ?>
<?php $__env->startSection('breadcrumb_section', __('staff_inv.breadcrumb_pharmacy', [], app()->getLocale()) ?: 'Pharmacy Inventory'); ?>

<?php $__env->startSection('content'); ?>

<div class="page-head">
    <h2><?php echo e(__('public.stf_inv_pharm_title')); ?></h2>
    <div class="page-head__spacer"></div>
</div>
<p class="page-subtitle mb-6"><?php echo e(__('public.stf_inv_pharm_subtitle')); ?></p>

<?php if(session('success')): ?>
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div><?php echo e(session('error')); ?></div></div>
<?php endif; ?>


<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value"><?php echo e($summary['total']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.stf_inv_pharm_stat_total')); ?></div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value"><?php echo e($summary['in_stock']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.stf_inv_pharm_stat_in_stock')); ?></div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value"><?php echo e($summary['low_stock']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.stf_inv_pharm_stat_low_stock')); ?></div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value"><?php echo e($summary['out_of_stock']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.stf_inv_pharm_stat_out')); ?></div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value"><?php echo e($summary['expired']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.stf_inv_pharm_stat_expired')); ?></div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value"><?php echo e($summary['recalled']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.stf_inv_pharm_stat_recalled')); ?></div>
    </div>
</div>


<form method="GET" class="filter-bar">
    <select name="stock_status" class="filter-select">
        <option value=""><?php echo e(__('public.stf_inv_pharm_all_statuses')); ?></option>
        <?php $__currentLoopData = ['in_stock','low_stock','out_of_stock']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($s); ?>" <?php echo e(request('stock_status') === $s ? 'selected' : ''); ?>><?php echo e(\App\Support\Enums::label($s, 'stock_status')); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <?php if($forms->isNotEmpty()): ?>
    <select name="form" class="filter-select">
        <option value=""><?php echo e(__('public.stf_inv_pharm_all_forms')); ?></option>
        <?php $__currentLoopData = $forms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($f); ?>" <?php echo e(request('form') === $f ? 'selected' : ''); ?>><?php echo e($f); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <?php endif; ?>
    <select name="is_expired" class="filter-select">
        <option value=""><?php echo e(__('staff_inv.filter_all', [], app()->getLocale()) ?: 'All'); ?></option>
        <option value="1" <?php echo e(request('is_expired') === '1' ? 'selected' : ''); ?>><?php echo e(__('public.stf_inv_pharm_expired_only')); ?></option>
        <option value="0" <?php echo e(request('is_expired') === '0' ? 'selected' : ''); ?>><?php echo e(__('public.stf_inv_pharm_not_expired')); ?></option>
    </select>
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="search" placeholder="<?php echo e(__('public.stf_inv_pharm_ph_search')); ?>" value="<?php echo e(request('search')); ?>">
    </label>
    <button type="submit" class="btn btn-primary btn-sm">
        <i data-lucide="filter"></i> <?php echo e(__('public.stf_inv_pharm_filter_btn')); ?>

    </button>
    </form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        <?php if($items->isEmpty()): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="pill"></i></div>
                <h3><?php echo e(__('public.stf_inv_pharm_empty_title')); ?></h3>
                <p><?php echo e(__('public.stf_inv_pharm_empty_desc')); ?></p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?php echo e(__('public.stf_inv_pharm_col_medicine')); ?></th>
                            <th><?php echo e(__('public.stf_inv_pharm_col_form')); ?></th>
                            <th><?php echo e(__('public.stf_inv_pharm_col_qty')); ?></th>
                            <th><?php echo e(__('public.stf_inv_pharm_col_status')); ?></th>
                            <th><?php echo e(__('public.stf_inv_pharm_col_flags')); ?></th>
                            <th><?php echo e(__('public.stf_inv_pharm_col_updated')); ?></th>
                            <th><?php echo e(__('public.stf_inv_pharm_col_actions')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $sBadge = match($item->stock_status) {
                                'in_stock'     => 'badge-success',
                                'low_stock'    => 'badge-warning',
                                'out_of_stock' => 'badge-danger',
                                default        => 'badge-neutral',
                            };
                        ?>
                        <tr>
                            <td data-label="<?php echo e(__('public.stf_inv_pharm_col_medicine')); ?>">
                                <span class="td-strong"><?php echo e($item->medicine_name); ?></span>
                                <div class="td-muted"><?php echo e($item->generic_name); ?></div>
                            </td>
                            <td data-label="<?php echo e(__('public.stf_inv_pharm_col_form')); ?>"><?php echo e($item->form); ?> · <?php echo e($item->strength); ?></td>
                            <td data-label="<?php echo e(__('public.stf_inv_pharm_col_qty')); ?>"><span class="td-strong"><?php echo e(number_format($item->available_quantity)); ?></span></td>
                            <td data-label="<?php echo e(__('public.stf_inv_pharm_col_status')); ?>">
                                <span class="badge <?php echo e($sBadge); ?>"><?php echo e(\App\Support\Enums::label($item->stock_status, 'stock_status')); ?></span>
                            </td>
                            <td data-label="<?php echo e(__('public.stf_inv_pharm_col_flags')); ?>">
                                <?php if($item->is_expired): ?>   <span class="badge badge-danger badge-sm"><?php echo e(__('public.stf_inv_pharm_flag_expired')); ?></span> <?php endif; ?>
                                <?php if($item->is_recalled): ?>  <span class="badge badge-warning badge-sm"><?php echo e(__('public.stf_inv_pharm_flag_recalled')); ?></span> <?php endif; ?>
                                <?php if($item->is_quarantined): ?> <span class="badge badge-warning badge-sm"><?php echo e(__('public.stf_inv_pharm_flag_quarantine')); ?></span> <?php endif; ?>
                                <?php if(!$item->is_expired && !$item->is_recalled && !$item->is_quarantined): ?>
                                    <span class="td-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?php echo e(__('public.stf_inv_pharm_col_updated')); ?>" class="td-muted">
                                <?php echo e(\Carbon\Carbon::parse($item->last_stock_update)->format('M d, H:i')); ?>

                            </td>
                            <td data-label="<?php echo e(__('public.stf_inv_pharm_col_actions')); ?>">
                                <div class="row-actions-inline">
                                    <button type="button" class="btn btn-primary btn-xs"
                                        onclick="openRestockModal('<?php echo e($item->id); ?>', '<?php echo e(addslashes($item->medicine_name)); ?>')">
                                        <i data-lucide="plus"></i>
                                        <?php echo e(__('public.stf_inv_pharm_btn_restock')); ?>

                                    </button>
                                    <button type="button" class="btn btn-ghost btn-xs"
                                        onclick="openDispenseModal('<?php echo e($item->id); ?>', '<?php echo e(addslashes($item->medicine_name)); ?>')">
                                        <i data-lucide="minus"></i>
                                        <?php echo e(__('public.stf_inv_pharm_btn_dispense')); ?>

                                    </button>
                                    <button type="button" class="btn btn-ghost btn-xs"
                                        onclick="openFlagModal('<?php echo e($item->id); ?>', '<?php echo e(addslashes($item->medicine_name)); ?>', <?php echo e($item->is_expired ? 1 : 0); ?>, <?php echo e($item->is_recalled ? 1 : 0); ?>, <?php echo e($item->is_quarantined ? 1 : 0); ?>)">
                                        <i data-lucide="flag"></i>
                                        <?php echo e(__('public.stf_inv_pharm_btn_flags')); ?>

                                    </button>
                                    
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>



<div id="restock-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="plus"></i> <?php echo e(__('public.stf_inv_pharm_restock_title')); ?></h3>
        <form id="restock-form" method="POST" action="">
            <?php echo csrf_field(); ?>
            <div class="modal__body">
                <p id="restock-name"></p>
                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.stf_inv_pharm_lbl_qty_add')); ?></label>
                    <input type="number" name="quantity" class="form-control" required min="1" value="1">
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeRestockModal()"><?php echo e(__('public.stf_inv_pharm_cancel')); ?></button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="plus"></i>
                    <?php echo e(__('public.stf_inv_pharm_btn_restock')); ?>

                </button>
            </div>
        </form>
    </div>
</div>


<div id="dispense-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="minus"></i> <?php echo e(__('public.stf_inv_pharm_dispense_title')); ?></h3>
        <form id="dispense-form" method="POST" action="">
            <?php echo csrf_field(); ?>
            <div class="modal__body">
                <p id="dispense-name"></p>
                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.stf_inv_pharm_lbl_qty_dispense')); ?></label>
                    <input type="number" name="quantity" class="form-control" required min="1" value="1">
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeDispenseModal()"><?php echo e(__('public.stf_inv_pharm_cancel')); ?></button>
                <button type="submit" class="btn btn-warning btn-sm">
                    <i data-lucide="minus"></i>
                    <?php echo e(__('public.stf_inv_pharm_btn_dispense_confirm')); ?>

                </button>
            </div>
        </form>
    </div>
</div>


<div id="flag-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="flag"></i> <?php echo e(__('public.stf_inv_pharm_flags_title')); ?></h3>
        <form id="flag-form" method="POST" action="">
            <?php echo csrf_field(); ?>
            <div class="modal__body">
                <p id="flag-name"></p>
                <label class="form-check">
                    <input type="checkbox" id="flag-expired" name="is_expired" value="1"> <?php echo e(__('public.stf_inv_pharm_lbl_expired')); ?>

                </label>
                <label class="form-check">
                    <input type="checkbox" id="flag-recalled" name="is_recalled" value="1"> <?php echo e(__('public.stf_inv_pharm_lbl_recalled')); ?>

                </label>
                <label class="form-check">
                    <input type="checkbox" id="flag-quarantined" name="is_quarantined" value="1"> <?php echo e(__('public.stf_inv_pharm_lbl_quarantined')); ?>

                </label>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeFlagModal()"><?php echo e(__('public.stf_inv_pharm_cancel')); ?></button>
                <button type="submit" class="btn btn-primary btn-sm"><?php echo e(__('public.stf_inv_pharm_btn_save_flags')); ?></button>
            </div>
        </form>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    var baseUrl = '<?php echo e(url('/portals/staff/inventory/pharmacy')); ?>';


    function openRestockModal(id, name) {
        document.getElementById('restock-name').textContent = name;
        document.getElementById('restock-form').action = baseUrl + '/' + id + '/restock';
        document.getElementById('restock-modal').removeAttribute('hidden');
    }
    function closeRestockModal() { document.getElementById('restock-modal').setAttribute('hidden',''); }
    document.getElementById('restock-modal').addEventListener('click', function(e) { if(e.target===this) closeRestockModal(); });

    function openDispenseModal(id, name) {
        document.getElementById('dispense-name').textContent = name;
        document.getElementById('dispense-form').action = baseUrl + '/' + id + '/dispense';
        document.getElementById('dispense-modal').removeAttribute('hidden');
    }
    function closeDispenseModal() { document.getElementById('dispense-modal').setAttribute('hidden',''); }
    document.getElementById('dispense-modal').addEventListener('click', function(e) { if(e.target===this) closeDispenseModal(); });

    function openFlagModal(id, name, expired, recalled, quarantined) {
        document.getElementById('flag-name').textContent = name;
        document.getElementById('flag-form').action = baseUrl + '/' + id + '/flag';
        document.getElementById('flag-expired').checked = !!expired;
        document.getElementById('flag-recalled').checked = !!recalled;
        document.getElementById('flag-quarantined').checked = !!quarantined;
        document.getElementById('flag-modal').removeAttribute('hidden');
    }
    function closeFlagModal() { document.getElementById('flag-modal').setAttribute('hidden',''); }
    document.getElementById('flag-modal').addEventListener('click', function(e) { if(e.target===this) closeFlagModal(); });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\staff\inventory\pharmacy.blade.php ENDPATH**/ ?>
<?php $__env->startSection('title', __('staff_inv.title_blood', [], app()->getLocale()) ?: 'Blood Bank Inventory'); ?>

<?php $__env->startSection('sidebar_role_badge'); ?>
<div class="sidebar-role-badge"><?php echo e(__('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff'); ?></div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('sidebar_user_role', __('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff'); ?>

<?php $__env->startSection('sidebar_nav'); ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.staff_portal.sidebar_lbl_overview', [], app()->getLocale()) ?: 'Overview'); ?></div>
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
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'inventory_ops')): ?>
        <?php endif; ?>
    <a href="<?php echo e(route('portals.staff.inventory.blood')); ?>" class="sidebar-link active">
        <i data-lucide="droplets"></i>
        <span><?php echo e(__('public.portal.nav_inventory_blood', [], app()->getLocale()) ?: 'Blood Bank'); ?></span>
    </a>
</div>
<?php if (\Illuminate\Support\Facades\Blade::check('feature', 'inventory_ops')): ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('staff_inv.nav_lbl_supply_chain', [], app()->getLocale()) ?: 'Supply Chain'); ?></div>
    </div>
<?php endif; ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('staff_inv.nav_lbl_operations', [], app()->getLocale()) ?: 'Operations'); ?></div>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'billing')): ?>
        <?php endif; ?>
                    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.staff')); ?>
<?php $__env->startSection('breadcrumb_section', __('staff_inv.breadcrumb_blood', [], app()->getLocale()) ?: 'Blood Bank'); ?>

<?php $__env->startSection('head'); ?>
<?php echo $__env->make('portals.pharmacy._supply_styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-head">
    <h2><?php echo e(__('public.stf_inv_blood_title')); ?></h2>
    <div class="page-head__spacer"></div>
    <button type="button" class="btn btn-primary btn-sm fs-tap" onclick="openUpsertModal()">
        <i data-lucide="plus-circle"></i>
        <?php echo e(__('public.stf_inv_blood_update_stock_btn')); ?>

    </button>
</div>
<p class="page-subtitle mb-6"><?php echo e(__('public.stf_inv_blood_subtitle')); ?></p>

<?php if(session('success')): ?>
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div><?php echo e(session('error')); ?></div></div>
<?php endif; ?>


<?php if($publishesToFinder): ?>
    <div class="alert alert-info mb-6">
        <i data-lucide="radio-tower"></i>
        <div><?php echo e(__('public.stf_inv_blood_publishes_notice')); ?></div>
    </div>
<?php else: ?>
    <div class="alert alert-warning mb-6">
        <i data-lucide="triangle-alert"></i>
        <div><?php echo e(__('public.stf_inv_blood_no_listing_notice')); ?></div>
    </div>
<?php endif; ?>


<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value"><?php echo e($summary['total_units']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.stf_inv_blood_stat_total')); ?></div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value"><?php echo e($summary['groups_covered']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.stf_inv_blood_stat_groups')); ?></div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value"><?php echo e($summary['expired']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.stf_inv_blood_stat_expired')); ?></div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value"><?php echo e($summary['unsafe']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.stf_inv_blood_stat_unsafe')); ?></div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value"><?php echo e($summary['quarantined']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.stf_inv_blood_stat_quarantined')); ?></div>
    </div>
</div>


<form method="GET" action="<?php echo e(route('portals.staff.inventory.blood')); ?>" class="filter-bar">
    <select name="blood_group" class="filter-select">
        <option value=""><?php echo e(__('public.stf_inv_blood_all_groups')); ?></option>
        <?php $__currentLoopData = ['O+','O-','A+','A-','B+','B-','AB+','AB-']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($g); ?>" <?php echo e(request('blood_group') === $g ? 'selected' : ''); ?>><?php echo e($g); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <select name="component" class="filter-select">
        <option value=""><?php echo e(__('public.stf_inv_blood_all_components')); ?></option>
        <?php $__currentLoopData = ['whole_blood','packed_red_cells','fresh_frozen_plasma','platelets']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($c); ?>" <?php echo e(request('component') === $c ? 'selected' : ''); ?>><?php echo e(\App\Support\Enums::label($c, 'blood_component')); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">
        <i data-lucide="filter"></i> <?php echo e(__('public.stf_inv_blood_filter_btn')); ?>

    </button>
    <a href="<?php echo e(route('portals.staff.inventory.blood')); ?>" class="btn btn-ghost btn-sm"><?php echo e(__('public.stf_inv_blood_clear_btn')); ?></a>
</form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        <?php if($items->isEmpty()): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="droplets"></i></div>
                <h3><?php echo e(__('public.stf_inv_blood_empty_title')); ?></h3>
                <p><?php echo e(__('public.stf_inv_blood_empty_desc')); ?></p>
                <button type="button" class="btn btn-primary btn-sm mt-6 fs-tap" onclick="openUpsertModal()"><?php echo e(__('public.stf_inv_blood_update_stock_btn')); ?></button>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?php echo e(__('public.stf_inv_blood_col_group')); ?></th>
                            <th><?php echo e(__('public.stf_inv_blood_col_component')); ?></th>
                            <th><?php echo e(__('public.stf_inv_blood_col_units')); ?></th>
                            <th><?php echo e(__('public.stf_inv_blood_col_flags')); ?></th>
                            <th><?php echo e(__('public.stf_inv_blood_col_updated')); ?></th>
                            <th><?php echo e(__('public.stf_inv_blood_col_actions')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $qtyBadge = $item->available_units <= 0 ? 'badge-danger' : ($item->available_units <= 5 ? 'badge-warning' : 'badge-success');
                        ?>
                        <tr>
                            <td data-label="<?php echo e(__('public.stf_inv_blood_col_group')); ?>">
                                
                                <span class="fs-bgroup-tag"><?php echo e($item->blood_group); ?></span>
                            </td>
                            <td data-label="<?php echo e(__('public.stf_inv_blood_col_component')); ?>">
                                <span class="badge badge-neutral"><?php echo e(\App\Support\Enums::label($item->component)); ?></span>
                            </td>
                            <td data-label="<?php echo e(__('public.stf_inv_blood_col_units')); ?>">
                                <span class="badge <?php echo e($qtyBadge); ?>"><?php echo e($item->available_units); ?></span> <?php echo e(__('public.stf_inv_blood_units_label')); ?>

                            </td>
                            <td data-label="<?php echo e(__('public.stf_inv_blood_col_flags')); ?>">
                                <?php if($item->is_expired): ?>    <span class="badge badge-danger badge-sm"><?php echo e(__('public.stf_inv_blood_flag_expired')); ?></span> <?php endif; ?>
                                <?php if($item->is_unsafe): ?>     <span class="badge badge-danger badge-sm"><?php echo e(__('public.stf_inv_blood_flag_unsafe')); ?></span> <?php endif; ?>
                                <?php if($item->is_quarantined): ?><span class="badge badge-warning badge-sm"><?php echo e(__('public.stf_inv_blood_flag_quarantine')); ?></span> <?php endif; ?>
                                <?php if(!$item->is_expired && !$item->is_unsafe && !$item->is_quarantined): ?>
                                    
                                    <span class="fs-screen-ok"><?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 12], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php echo e(__('finder_supply.blood_screening_clear')); ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?php echo e(__('public.stf_inv_blood_col_updated')); ?>" class="td-muted">
                                <?php echo e(\Carbon\Carbon::parse($item->last_stock_update)->format('M d, H:i')); ?>

                            </td>
                            <td data-label="<?php echo e(__('public.stf_inv_blood_col_actions')); ?>">
                                <div class="row-actions-inline">
                                    <button type="button" class="btn btn-primary btn-xs fs-tap"
                                        onclick="openAdjustModal('<?php echo e($item->id); ?>', '<?php echo e($item->blood_group); ?>', '<?php echo e(addslashes($item->component)); ?>', 'add')">
                                        <i data-lucide="plus"></i>
                                        <?php echo e(__('public.stf_inv_blood_btn_add')); ?>

                                    </button>
                                    <button type="button" class="btn btn-ghost btn-xs fs-tap"
                                        onclick="openAdjustModal('<?php echo e($item->id); ?>', '<?php echo e($item->blood_group); ?>', '<?php echo e(addslashes($item->component)); ?>', 'subtract')">
                                        <i data-lucide="minus"></i>
                                        <?php echo e(__('public.stf_inv_blood_btn_use')); ?>

                                    </button>
                                    <button type="button" class="btn btn-ghost btn-xs fs-tap"
                                        onclick="openBloodFlagModal('<?php echo e($item->id); ?>', '<?php echo e($item->blood_group); ?> <?php echo e(addslashes($item->component)); ?>', <?php echo e($item->is_expired ? 1 : 0); ?>, <?php echo e($item->is_unsafe ? 1 : 0); ?>, <?php echo e($item->is_quarantined ? 1 : 0); ?>)">
                                        <i data-lucide="flag"></i>
                                        <?php echo e(__('public.stf_inv_blood_btn_flags')); ?>

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


<div id="upsert-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="droplets"></i> <?php echo e(__('public.stf_inv_blood_modal_set_title')); ?></h3>
        <form method="POST" action="<?php echo e(route('portals.staff.inventory.blood.upsert')); ?>">
            <?php echo csrf_field(); ?>
            <div class="modal__body">
                <p><?php echo e(__('public.stf_inv_blood_modal_set_note')); ?></p>
                <div class="form-group">
                    
                    <label class="form-label"><?php echo e(__('public.stf_inv_blood_lbl_group')); ?></label>
                    <div class="fs-bgrid" role="radiogroup" aria-label="<?php echo e(__('finder_supply.blood_group_pick')); ?>">
                        <?php $__currentLoopData = ['O+','O-','A+','A-','B+','B-','AB+','AB-']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label class="fs-bchip">
                                <input type="radio" name="blood_group" value="<?php echo e($g); ?>" <?php echo e($g === 'O+' ? 'checked' : ''); ?> required>
                                <span><?php echo e($g); ?></span>
                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.stf_inv_blood_lbl_component')); ?></label>
                    <select name="component" class="form-control" required>
                        <?php $__currentLoopData = ['whole_blood','packed_red_cells','fresh_frozen_plasma','platelets']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($c); ?>"><?php echo e(\App\Support\Enums::label($c, 'blood_component')); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="upsert-units"><?php echo e(__('public.stf_inv_blood_lbl_units')); ?></label>
                    <div class="fs-stepper">
                        <button type="button" aria-label="<?php echo e(__('finder_supply.stepper_minus')); ?>" onclick="fsStep('upsert-units', -1)">&minus;</button>
                        <input type="number" id="upsert-units" name="available_units" required min="0" inputmode="numeric" value="0">
                        <button type="button" aria-label="<?php echo e(__('finder_supply.stepper_plus')); ?>" onclick="fsStep('upsert-units', 1)">+</button>
                    </div>
                </div>
                <?php if($publishesToFinder): ?>
                
                <div class="fs-publish-note">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'droplet', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <span><?php echo e(__('finder_supply.blood_publish_note')); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm fs-tap" onclick="closeUpsertModal()"><?php echo e(__('public.stf_inv_blood_cancel')); ?></button>
                <button type="submit" class="btn btn-primary btn-sm fs-tap">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('public.stf_inv_blood_btn_save')); ?>

                </button>
            </div>
        </form>
    </div>
</div>


<div id="adjust-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <h3 id="adjust-title" class="modal__title"><i data-lucide="plus-minus"></i> <?php echo e(__('public.stf_inv_blood_adjust_title')); ?></h3>
        <form id="adjust-form" method="POST" action="">
            <?php echo csrf_field(); ?>
            <input type="hidden" id="adjust-direction" name="direction" value="add">
            <div class="modal__body">
                <p id="adjust-label"></p>
                <div class="form-group">
                    <label class="form-label" for="adjust-units"><?php echo e(__('public.stf_inv_blood_lbl_adj_units')); ?></label>
                    <div class="fs-stepper">
                        <button type="button" aria-label="<?php echo e(__('finder_supply.stepper_minus')); ?>" onclick="fsStep('adjust-units', -1)">&minus;</button>
                        <input type="number" id="adjust-units" name="units" required min="1" inputmode="numeric" value="1">
                        <button type="button" aria-label="<?php echo e(__('finder_supply.stepper_plus')); ?>" onclick="fsStep('adjust-units', 1)">+</button>
                    </div>
                </div>
                <?php if($publishesToFinder): ?>
                <div class="fs-publish-note">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'droplet', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <span><?php echo e(__('finder_supply.blood_publish_note')); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm fs-tap" onclick="closeAdjustModal()"><?php echo e(__('public.stf_inv_blood_cancel')); ?></button>
                <button type="submit" id="adjust-btn" class="btn btn-primary btn-sm fs-tap"><?php echo e(__('public.stf_inv_blood_btn_confirm')); ?></button>
            </div>
        </form>
    </div>
</div>


<div id="blood-flag-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="flag"></i> <?php echo e(__('public.stf_inv_blood_flags_title')); ?></h3>
        <form id="blood-flag-form" method="POST" action="">
            <?php echo csrf_field(); ?>
            <div class="modal__body">
                <p id="blood-flag-label"></p>
                <label class="form-check">
                    <input type="checkbox" id="bf-expired" name="is_expired" value="1"> <?php echo e(__('public.stf_inv_blood_lbl_expired')); ?>

                </label>
                <label class="form-check">
                    <input type="checkbox" id="bf-unsafe" name="is_unsafe" value="1"> <?php echo e(__('public.stf_inv_blood_lbl_unsafe')); ?>

                </label>
                <label class="form-check">
                    <input type="checkbox" id="bf-quarantined" name="is_quarantined" value="1"> <?php echo e(__('public.stf_inv_blood_lbl_quarantined')); ?>

                </label>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeBloodFlagModal()"><?php echo e(__('public.stf_inv_blood_cancel')); ?></button>
                <button type="submit" class="btn btn-primary btn-sm"><?php echo e(__('public.stf_inv_blood_btn_save_flags')); ?></button>
            </div>
        </form>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    var bloodBase = '<?php echo e(url('/portals/staff/inventory/blood')); ?>';

    // Big-thumb stepper: nudge a numeric input without leaving the modal.
    function fsStep(id, delta) {
        var input = document.getElementById(id);
        var min   = parseInt(input.min || '0', 10);
        var next  = (parseInt(input.value, 10) || 0) + delta;
        input.value = Math.max(min, next);
    }

    function openUpsertModal()  { document.getElementById('upsert-modal').removeAttribute('hidden'); }
    function closeUpsertModal() { document.getElementById('upsert-modal').setAttribute('hidden',''); }
    document.getElementById('upsert-modal').addEventListener('click', function(e) { if(e.target===this) closeUpsertModal(); });

    function openAdjustModal(id, group, component, direction) {
        document.getElementById('adjust-label').textContent = group + ' · ' + component.replace(/_/g,' ');
        document.getElementById('adjust-title').textContent = direction === 'add' ? '<?php echo e(__('staff_inv.js_add_units', [], app()->getLocale()) ?: 'Add Units'); ?>' : '<?php echo e(__('staff_inv.js_use_units', [], app()->getLocale()) ?: 'Use Units'); ?>';
        document.getElementById('adjust-direction').value = direction;
        document.getElementById('adjust-form').action = bloodBase + '/' + id + '/adjust';
        var btn = document.getElementById('adjust-btn');
        btn.className = direction === 'add' ? 'btn btn-primary btn-sm fs-tap' : 'btn btn-warning btn-sm fs-tap';
        btn.textContent = direction === 'add' ? '<?php echo e(__('staff_inv.js_add', [], app()->getLocale()) ?: 'Add'); ?>' : '<?php echo e(__('staff_inv.js_use', [], app()->getLocale()) ?: 'Use'); ?>';
        document.getElementById('adjust-modal').removeAttribute('hidden');
    }
    function closeAdjustModal() { document.getElementById('adjust-modal').setAttribute('hidden',''); }
    document.getElementById('adjust-modal').addEventListener('click', function(e) { if(e.target===this) closeAdjustModal(); });

    function openBloodFlagModal(id, label, expired, unsafe, quarantined) {
        document.getElementById('blood-flag-label').textContent = label;
        document.getElementById('blood-flag-form').action = bloodBase + '/' + id + '/flag';
        document.getElementById('bf-expired').checked = !!expired;
        document.getElementById('bf-unsafe').checked = !!unsafe;
        document.getElementById('bf-quarantined').checked = !!quarantined;
        document.getElementById('blood-flag-modal').removeAttribute('hidden');
    }
    function closeBloodFlagModal() { document.getElementById('blood-flag-modal').setAttribute('hidden',''); }
    document.getElementById('blood-flag-modal').addEventListener('click', function(e) { if(e.target===this) closeBloodFlagModal(); });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\staff\inventory\blood.blade.php ENDPATH**/ ?>
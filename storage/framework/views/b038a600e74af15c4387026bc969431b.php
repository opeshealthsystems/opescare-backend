<?php $__env->startSection('title', __('public.adm_cc_set_title')); ?>
<?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('breadcrumb_home', __('public.adm_cc_set_breadcrumb_home')); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.admin')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.adm_cc_set_breadcrumb_section')); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.adm_cc_set_heading')); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.adm_cc_set_subtitle')); ?></p>
    </div>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div><?php echo e(session('error')); ?></div></div>
<?php endif; ?>

<?php $__empty_1 = true; $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group => $settings): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="sliders-horizontal"></i> <?php echo e(ucwords($group)); ?></h3>
    </div>
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th><?php echo e(__('public.adm_cc_set_col_key')); ?></th><th><?php echo e(__('public.adm_cc_set_col_value')); ?></th><th><?php echo e(__('public.adm_cc_set_col_type')); ?></th><th><?php echo e(__('public.adm_cc_set_col_description')); ?></th><th class="row-actions"></th>
                </tr></thead>
                <tbody>
                    <?php $__currentLoopData = $settings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $setting): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td data-label="Key"><span class="code-token"><?php echo e($setting['key']); ?></span></td>
                        <td data-label="Value" class="td-strong"><?php echo e($setting['value']); ?></td>
                        <td data-label="Type"><span class="badge badge-neutral badge-sm"><?php echo e($setting['value_type']); ?></span></td>
                        <td data-label="Description" class="td-muted"><?php echo e($setting['description'] ?? '—'); ?></td>
                        <td class="row-actions">
                            <button type="button" class="btn btn-ghost btn-sm"
                                onclick="openEditModal('<?php echo e($setting['key']); ?>', '<?php echo e(addslashes($setting['value'])); ?>')">
                                <i data-lucide="pencil"></i> <?php echo e(__('public.adm_cc_set_btn_edit')); ?>

                            </button>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="sliders-horizontal"></i></div>
        <h3><?php echo e(__('public.adm_cc_set_empty_heading')); ?></h3>
        <p><?php echo e(__('public.adm_cc_set_empty_body')); ?></p>
    </div>
<?php endif; ?>


<div id="edit-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--sm">
        <div class="modal-fixed__head">
            <h3 class="modal-fixed__title"><?php echo e(__('public.adm_cc_set_edit_modal_heading')); ?></h3>
            <button type="button" class="icon-btn" aria-label="<?php echo e(__('public.aria_close')); ?>" onclick="closeEditModal()"><i data-lucide="x"></i></button>
        </div>
        <form method="POST" action="<?php echo e(route('portals.admin.cc.settings.update')); ?>">
            <?php echo csrf_field(); ?>
            <div class="form-group mb-3">
                <label class="form-label"><?php echo e(__('public.adm_cc_set_edit_modal_lbl_key')); ?></label>
                <input type="text" id="edit-key" name="key" class="form-control" readonly>
            </div>
            <div class="form-group mb-4">
                <label class="form-label form-label-required"><?php echo e(__('public.adm_cc_set_edit_modal_lbl_value')); ?></label>
                <input type="text" id="edit-value" name="value" class="form-control" required maxlength="500">
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeEditModal()"><?php echo e(__('public.adm_cc_set_edit_modal_btn_cancel')); ?></button>
                <button type="submit" class="btn btn-primary btn-sm"><?php echo e(__('public.adm_cc_set_edit_modal_btn_save')); ?></button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    function openEditModal(key, value) {
        document.getElementById('edit-key').value = key;
        document.getElementById('edit-value').value = value;
        document.getElementById('edit-modal').classList.add('open');
    }
    function closeEditModal() { document.getElementById('edit-modal').classList.remove('open'); }
    document.getElementById('edit-modal').addEventListener('click', function(e) { if (e.target === this) closeEditModal(); });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\control_center\settings.blade.php ENDPATH**/ ?>
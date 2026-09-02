<?php $__env->startSection('title', __('public.adm_cc_mod_title')); ?>
<?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('breadcrumb_home', __('public.adm_cc_mod_breadcrumb_home')); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.admin')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.adm_cc_mod_breadcrumb_section')); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.adm_cc_mod_heading')); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.adm_cc_mod_subtitle')); ?></p>
    </div>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div><?php echo e(session('error')); ?></div></div>
<?php endif; ?>

<div class="panel">
    <?php if($modules->count() === 0): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="puzzle"></i></div>
            <h3><?php echo e(__('public.adm_cc_mod_empty_heading')); ?></h3>
            <p><?php echo e(__('public.adm_cc_mod_empty_body')); ?></p>
        </div>
    <?php else: ?>
    <div class="panel-body">
        <?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mod): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="toggle-row">
            <div class="toggle-row__body">
                <div class="toggle-row__title"><?php echo e($mod->module_label); ?></div>
                <div class="toggle-row__desc">
                    <span class="code-token"><?php echo e($mod->module_key); ?></span>
                    <span class="badge badge-neutral badge-sm"><?php echo e($mod->scope); ?></span>
                    <?php if($mod->scope_value): ?><span class="td-muted"><?php echo e($mod->scope_value); ?></span><?php endif; ?>
                    <?php if(!$mod->enabled && $mod->disable_reason): ?><span class="td-muted">· <?php echo e($mod->disable_reason); ?></span><?php endif; ?>
                    <?php if($mod->updated_by): ?><span class="td-muted">· <?php echo e(__('public.adm_cc_mod_updated_by')); ?> <?php echo e($mod->updated_by); ?></span><?php endif; ?>
                </div>
            </div>
            <span class="badge <?php echo e($mod->enabled ? 'badge-success' : 'badge-neutral'); ?> badge-sm">
                <?php echo e($mod->enabled ? __('public.adm_cc_mod_badge_enabled') : __('public.adm_cc_mod_badge_disabled')); ?>

            </span>
            <?php if($mod->enabled): ?>
                <label class="switch">
                    <input type="checkbox" checked
                           onclick="event.preventDefault(); openDisableModal('<?php echo e($mod->module_key); ?>', '<?php echo e(addslashes($mod->module_label)); ?>');"
                           aria-label="Disable <?php echo e($mod->module_label); ?>">
                    <span class="switch__track"></span>
                </label>
            <?php else: ?>
                <form method="POST" action="<?php echo e(route('portals.admin.cc.modules.toggle', urlencode($mod->module_key))); ?>" class="inline-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="enabled" value="1">
                    <input type="hidden" name="disable_reason" value="">
                    <label class="switch">
                        <input type="checkbox" onchange="this.form.submit()" aria-label="Enable <?php echo e($mod->module_label); ?>">
                        <span class="switch__track"></span>
                    </label>
                </form>
            <?php endif; ?>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>
</div>


<div id="disable-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--sm">
        <div class="modal-fixed__head">
            <h3 class="modal-fixed__title"><?php echo e(__('public.adm_cc_mod_disable_modal_heading')); ?></h3>
            <button type="button" class="icon-btn" aria-label="<?php echo e(__('public.aria_close')); ?>" onclick="closeDisableModal()"><i data-lucide="x"></i></button>
        </div>
        <p id="disable-module-name" class="td-muted text-sm mb-4"></p>
        <form id="disable-form" method="POST" action="">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="enabled" value="0">
            <div class="form-group mb-4">
                <label class="form-label"><?php echo e(__('public.adm_cc_mod_disable_modal_lbl_reason')); ?> <span class="td-muted"><?php echo e(__('public.adm_cc_mod_disable_modal_hint_optional')); ?></span></label>
                <textarea name="disable_reason" class="form-control" rows="2" maxlength="255" placeholder="<?php echo e(__('public.adm_cc_mod_disable_modal_ph_reason')); ?>"></textarea>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeDisableModal()"><?php echo e(__('public.adm_cc_mod_disable_modal_btn_cancel')); ?></button>
                <button type="submit" class="btn btn-danger btn-sm"><?php echo e(__('public.adm_cc_mod_disable_modal_btn_disable')); ?></button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    var _ag = {
        modulePrefix: <?php echo json_encode(__('public.adm_cc_mod_js_module_prefix'), 15, 512) ?>,
    };
    function openDisableModal(id, label) {
        document.getElementById('disable-module-name').textContent = _ag.modulePrefix + label;
        document.getElementById('disable-form').action = '/portals/admin/cc/modules/' + encodeURIComponent(id) + '/toggle';
        document.getElementById('disable-modal').classList.add('open');
    }
    function closeDisableModal() { document.getElementById('disable-modal').classList.remove('open'); }
    document.getElementById('disable-modal').addEventListener('click', function(e) { if (e.target === this) closeDisableModal(); });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\control_center\modules.blade.php ENDPATH**/ ?>
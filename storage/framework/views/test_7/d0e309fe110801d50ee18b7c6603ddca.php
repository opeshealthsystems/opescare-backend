<?php $__env->startSection('title', __('public.adm_cert_form_title')); ?>
<?php $__env->startSection('sidebar'); ?> <?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="breadcrumb">
    <a href="<?php echo e(route('portals.admin.certifications.index')); ?>"><?php echo e(__('public.adm_cert_form_breadcrumb_parent')); ?></a>
    <i data-lucide="chevron-right"></i>
    <span><?php echo e(__('public.adm_cert_form_breadcrumb_new')); ?></span>
</div>

<div class="page-head">
    <h2><?php echo e(__('public.adm_cert_form_heading')); ?></h2>
</div>

<div class="panel form-panel">
    <div class="panel-body">
        <form method="POST" action="<?php echo e(route('portals.admin.certifications.store')); ?>">
            <?php echo csrf_field(); ?>

            <div class="form-group mb-4">
                <label class="form-label form-label-required"><?php echo e(__('public.adm_cert_form_lbl_integration_name')); ?></label>
                <input type="text" name="integration_name" value="<?php echo e(old('integration_name')); ?>" required
                       class="form-control" placeholder="<?php echo e(__('public.adm_cert_form_ph_integration_name')); ?>">
                <?php $__errorArgs = ['integration_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-hint"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.adm_cert_form_lbl_integration_type')); ?></label>
                    <select name="integration_type" class="form-control" required>
                        <option value=""><?php echo e(__('public.adm_cert_form_opt_select_type')); ?></option>
                        <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($t); ?>" <?php echo e(old('integration_type') === $t ? 'selected' : ''); ?>><?php echo e(strtoupper($t)); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.adm_cert_form_lbl_version')); ?></label>
                    <input type="text" name="version" value="<?php echo e(old('version')); ?>" class="form-control" placeholder="<?php echo e(__('public.adm_cert_form_ph_version')); ?>">
                </div>
            </div>

            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.adm_cert_form_lbl_vendor_name')); ?></label>
                    <input type="text" name="vendor_name" value="<?php echo e(old('vendor_name')); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.adm_cert_form_lbl_vendor_contact')); ?></label>
                    <input type="text" name="vendor_contact" value="<?php echo e(old('vendor_contact')); ?>" class="form-control" placeholder="<?php echo e(__('public.adm_cert_form_ph_vendor_contact')); ?>">
                </div>
            </div>

            <div class="form-group mb-6">
                <label class="form-label"><?php echo e(__('public.adm_cert_form_lbl_scope')); ?></label>
                <textarea name="scope_description" rows="3" class="form-control"
                          placeholder="<?php echo e(__('public.adm_cert_form_ph_scope')); ?>"><?php echo e(old('scope_description')); ?></textarea>
            </div>

            <div class="row-actions-inline">
                <button type="submit" class="btn btn-primary"><?php echo e(__('public.adm_cert_form_btn_start')); ?></button>
                <a href="<?php echo e(route('portals.admin.certifications.index')); ?>" class="btn btn-secondary"><?php echo e(__('public.adm_cert_form_btn_cancel')); ?></a>
            </div>
        </form>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/admin/certifications/form.blade.php ENDPATH**/ ?>
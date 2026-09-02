<?php $__env->startSection('title', __('public.portal.add_dependent_title', [], app()->getLocale()) ?: 'Add Dependent'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.add_dependent_breadcrumb', [], app()->getLocale()) ?: 'Add Dependent'); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="user-plus"></i> <?php echo e(__('public.portal.panel_register_dependent', [], $l) ?: 'Register a Dependent'); ?></h2>
    </div>
    <div class="panel-body">
        <p class="text-sm text-muted mb-6">
            <?php echo e(__('public.portal.add_dependent_info', [], $l) ?: 'This creates a new patient record for your dependent. No login account is created — you manage their records.'); ?>

        </p>
        <?php if($errors->any()): ?>
        <div class="alert alert-danger mb-4">
            <i data-lucide="alert-circle"></i>
            <ul class="alert-list"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
        </div>
        <?php endif; ?>
        <form method="POST" action="<?php echo e(route('portals.patient.family.store')); ?>">
            <?php echo csrf_field(); ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.portal.field_first_name', [], $l) ?: 'First Name'); ?></label>
                    <input type="text" name="first_name" value="<?php echo e(old('first_name')); ?>" required class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.portal.field_last_name', [], $l) ?: 'Last Name'); ?></label>
                    <input type="text" name="last_name" value="<?php echo e(old('last_name')); ?>" required class="form-control">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.portal.field_dob', [], $l) ?: 'Date of Birth'); ?></label>
                    <input type="date" name="date_of_birth" value="<?php echo e(old('date_of_birth')); ?>" required class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.portal.field_sex', [], $l) ?: 'Sex'); ?></label>
                    <select name="sex" required class="form-control">
                        <option value=""><?php echo e(__('public.portal.opt_select', [], $l) ?: '— select —'); ?></option>
                        <option value="male" <?php echo e(old('sex') === 'male' ? 'selected' : ''); ?>><?php echo e(__('public.portal.opt_male', [], $l) ?: 'Male'); ?></option>
                        <option value="female" <?php echo e(old('sex') === 'female' ? 'selected' : ''); ?>><?php echo e(__('public.portal.opt_female', [], $l) ?: 'Female'); ?></option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.portal.field_relationship', [], $l) ?: 'Your Relationship'); ?></label>
                    <select name="relationship" required class="form-control">
                        <option value=""><?php echo e(__('public.portal.opt_select', [], $l) ?: '— select —'); ?></option>
                        <?php $__currentLoopData = ['parent','grandparent','spouse','sibling','caregiver','legal_guardian','other']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($r); ?>" <?php echo e(old('relationship') === $r ? 'selected' : ''); ?>><?php echo e(ucfirst(str_replace('_',' ',$r))); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.portal.field_access_level', [], $l) ?: 'Access Level'); ?></label>
                    <select name="access_level" required class="form-control">
                        <option value="full" <?php echo e(old('access_level','full') === 'full' ? 'selected' : ''); ?>><?php echo e(__('public.portal.opt_full_access', [], $l) ?: 'Full Access'); ?></option>
                        <option value="read_only" <?php echo e(old('access_level') === 'read_only' ? 'selected' : ''); ?>><?php echo e(__('public.portal.opt_read_only', [], $l) ?: 'Read Only'); ?></option>
                    </select>
                </div>
            </div>
            <div class="row-actions mt-6">
                <button type="submit" class="btn btn-primary"><?php echo e(__('public.portal.btn_register_dependent', [], $l) ?: 'Register Dependent'); ?></button>
                <a href="<?php echo e(route('portals.patient.family')); ?>" class="btn btn-secondary"><?php echo e(__('public.portal.btn_cancel', [], $l) ?: 'Cancel'); ?></a>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\family\add.blade.php ENDPATH**/ ?>
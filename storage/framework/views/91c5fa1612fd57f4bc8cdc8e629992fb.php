<?php $__env->startSection('title', __('public.portal.add_dependent_title', [], app()->getLocale()) ?: 'Add Dependent'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.add_dependent_breadcrumb', [], app()->getLocale()) ?: 'Add Dependent'); ?>

<?php $__env->startSection('head'); ?>
    <?php echo $__env->make('portals.patient._finder_head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>
<div class="pf pf--form">

    <div class="pf-head">
        <div>
            <h1><?php echo e(__('public.portal.panel_register_dependent', [], $l) ?: 'Register a Dependent'); ?></h1>
            <p class="pf-sub"><?php echo e(__('public.portal.add_dependent_info', [], $l) ?: 'This creates a new patient record for your dependent. No login account is created — you manage their records.'); ?></p>
        </div>
        <a href="<?php echo e(route('portals.patient.family')); ?>" class="f-btn">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('public.portal.family_breadcrumb', [], $l) ?: 'My Family'); ?>

        </a>
    </div>

    <?php if($errors->any()): ?>
    <div class="pf-alert pf-alert--danger" role="alert">
        <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p class="f-small" style="margin:0;"><?php echo e($e); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('portals.patient.family.store')); ?>" class="f-card">
        <?php echo csrf_field(); ?>
        <div class="f-field">
            <label class="f-label" for="first_name"><?php echo e(__('public.portal.field_first_name', [], $l) ?: 'First Name'); ?></label>
            <input type="text" id="first_name" name="first_name" value="<?php echo e(old('first_name')); ?>" required class="f-input">
        </div>
        <div class="f-field">
            <label class="f-label" for="last_name"><?php echo e(__('public.portal.field_last_name', [], $l) ?: 'Last Name'); ?></label>
            <input type="text" id="last_name" name="last_name" value="<?php echo e(old('last_name')); ?>" required class="f-input">
        </div>
        <div class="f-field">
            <label class="f-label" for="date_of_birth"><?php echo e(__('public.portal.field_dob', [], $l) ?: 'Date of Birth'); ?></label>
            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo e(old('date_of_birth')); ?>" required class="f-input">
        </div>
        <div class="f-field">
            <label class="f-label" for="sex"><?php echo e(__('public.portal.field_sex', [], $l) ?: 'Sex'); ?></label>
            <select id="sex" name="sex" required class="f-input">
                <option value=""><?php echo e(__('public.portal.opt_select', [], $l) ?: '— select —'); ?></option>
                <option value="male" <?php echo e(old('sex') === 'male' ? 'selected' : ''); ?>><?php echo e(__('public.portal.opt_male', [], $l) ?: 'Male'); ?></option>
                <option value="female" <?php echo e(old('sex') === 'female' ? 'selected' : ''); ?>><?php echo e(__('public.portal.opt_female', [], $l) ?: 'Female'); ?></option>
            </select>
        </div>
        <div class="f-field">
            <label class="f-label" for="relationship"><?php echo e(__('public.portal.field_relationship', [], $l) ?: 'Your Relationship'); ?></label>
            <select id="relationship" name="relationship" required class="f-input">
                <option value=""><?php echo e(__('public.portal.opt_select', [], $l) ?: '— select —'); ?></option>
                <?php $__currentLoopData = ['parent','grandparent','spouse','sibling','caregiver','legal_guardian','other']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($r); ?>" <?php echo e(old('relationship') === $r ? 'selected' : ''); ?>><?php echo e(\App\Support\Enums::label($r)); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="f-field">
            <label class="f-label" for="access_level"><?php echo e(__('public.portal.field_access_level', [], $l) ?: 'Access Level'); ?></label>
            <select id="access_level" name="access_level" required class="f-input">
                <option value="full" <?php echo e(old('access_level','full') === 'full' ? 'selected' : ''); ?>><?php echo e(__('public.portal.opt_full_access', [], $l) ?: 'Full Access'); ?></option>
                <option value="read_only" <?php echo e(old('access_level') === 'read_only' ? 'selected' : ''); ?>><?php echo e(__('public.portal.opt_read_only', [], $l) ?: 'Read Only'); ?></option>
            </select>
        </div>
        <div class="pf-actions" style="margin-top:8px;">
            <button type="submit" class="f-btn f-btn--primary" style="min-height:48px;"><?php echo e(__('public.portal.btn_register_dependent', [], $l) ?: 'Register Dependent'); ?></button>
            <a href="<?php echo e(route('portals.patient.family')); ?>" class="f-btn"><?php echo e(__('public.portal.btn_cancel', [], $l) ?: 'Cancel'); ?></a>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\family\add.blade.php ENDPATH**/ ?>
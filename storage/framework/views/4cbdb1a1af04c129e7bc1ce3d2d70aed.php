<?php $__env->startSection('title', __('public.portal.invite_title', [], app()->getLocale()) ?: 'Invite Member'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.invite_breadcrumb', [], app()->getLocale()) ?: 'Invite Member'); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="mail"></i> <?php echo e(__('public.portal.panel_invite_patient', [], $l) ?: 'Invite an Existing Patient'); ?></h2>
    </div>
    <div class="panel-body">
        <p class="text-sm text-muted mb-6">
            <?php echo e(__('public.portal.invite_info', [], $l) ?: 'Enter the Health ID or email of a patient who already has an OpesCare record. They will receive an invite link to approve the connection.'); ?>

        </p>
        <?php if($errors->any()): ?>
        <div class="alert alert-danger mb-4">
            <i data-lucide="alert-circle"></i>
            <ul class="alert-list"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
        </div>
        <?php endif; ?>
        <form method="POST" action="<?php echo e(route('portals.patient.family.invite.send')); ?>">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label class="form-label form-label-required"><?php echo e(__('public.portal.field_health_id_email', [], $l) ?: 'Health ID or Email'); ?></label>
                <input type="text" name="health_id_or_email" value="<?php echo e(old('health_id_or_email')); ?>" required class="form-control" placeholder="CM-HID-XXXX-XXXX-XXXX or email@example.com">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.portal.field_relationship', [], $l) ?: 'Relationship'); ?></label>
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
                        <option value="read_only" <?php echo e(old('access_level','read_only') === 'read_only' ? 'selected' : ''); ?>><?php echo e(__('public.portal.opt_read_only', [], $l) ?: 'Read Only'); ?></option>
                        <option value="full" <?php echo e(old('access_level') === 'full' ? 'selected' : ''); ?>><?php echo e(__('public.portal.opt_full_access', [], $l) ?: 'Full Access'); ?></option>
                    </select>
                </div>
            </div>
            <div class="row-actions mt-6">
                <button type="submit" class="btn btn-primary"><?php echo e(__('public.portal.btn_send_invite', [], $l) ?: 'Send Invite'); ?></button>
                <a href="<?php echo e(route('portals.patient.family')); ?>" class="btn btn-secondary"><?php echo e(__('public.portal.btn_cancel', [], $l) ?: 'Cancel'); ?></a>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\family\invite.blade.php ENDPATH**/ ?>
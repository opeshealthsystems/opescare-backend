<?php $__env->startSection('title', __('public.portal.invite_title', [], app()->getLocale()) ?: 'Invite Member'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.invite_breadcrumb', [], app()->getLocale()) ?: 'Invite Member'); ?>

<?php $__env->startSection('head'); ?>
    <?php echo $__env->make('portals.patient._finder_head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>
<div class="pf pf--form">

    <div class="pf-head">
        <div>
            <h1><?php echo e(__('public.portal.panel_invite_patient', [], $l) ?: 'Invite an Existing Patient'); ?></h1>
            <p class="pf-sub"><?php echo e(__('public.portal.invite_info', [], $l) ?: 'Enter the Health ID or email of a patient who already has an OpesCare record. They will receive an invite link to approve the connection.'); ?></p>
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

    <form method="POST" action="<?php echo e(route('portals.patient.family.invite.send')); ?>" class="f-card">
        <?php echo csrf_field(); ?>
        <div class="f-field">
            <label class="f-label" for="health_id_or_email"><?php echo e(__('public.portal.field_health_id_email', [], $l) ?: 'Health ID or Email'); ?></label>
            <input type="text" id="health_id_or_email" name="health_id_or_email" value="<?php echo e(old('health_id_or_email')); ?>" required class="f-input"
                   placeholder="CM-HID-XXXX-XXXX-XXXX or email@example.com">
        </div>
        <div class="f-field">
            <label class="f-label" for="relationship"><?php echo e(__('public.portal.field_relationship', [], $l) ?: 'Relationship'); ?></label>
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
                <option value="read_only" <?php echo e(old('access_level','read_only') === 'read_only' ? 'selected' : ''); ?>><?php echo e(__('public.portal.opt_read_only', [], $l) ?: 'Read Only'); ?></option>
                <option value="full" <?php echo e(old('access_level') === 'full' ? 'selected' : ''); ?>><?php echo e(__('public.portal.opt_full_access', [], $l) ?: 'Full Access'); ?></option>
            </select>
        </div>
        <div class="pf-actions" style="margin-top:8px;">
            <button type="submit" class="f-btn f-btn--primary" style="min-height:48px;">
                <?php echo e(__('public.portal.btn_send_invite', [], $l) ?: 'Send Invite'); ?>

                <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </button>
            <a href="<?php echo e(route('portals.patient.family')); ?>" class="f-btn"><?php echo e(__('public.portal.btn_cancel', [], $l) ?: 'Cancel'); ?></a>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\family\invite.blade.php ENDPATH**/ ?>
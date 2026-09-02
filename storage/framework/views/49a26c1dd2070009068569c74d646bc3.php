<?php $__env->startSection('title', __('public.portal.edit_family_link_title', [], app()->getLocale()) ?: 'Edit Family Link'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.edit_family_link_breadcrumb', [], app()->getLocale()) ?: 'Edit Family Link'); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">
            <i data-lucide="settings"></i>
            <?php echo e($link->dependentPatient->first_name); ?> <?php echo e($link->dependentPatient->last_name); ?>

        </h2>
    </div>
    <div class="panel-body">
        <form method="POST" action="<?php echo e(route('portals.patient.family.update', $link->id)); ?>">
            <?php echo csrf_field(); ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.portal.field_relationship', [], $l) ?: 'Relationship'); ?></label>
                    <select name="relationship" required class="form-control">
                        <?php $__currentLoopData = ['parent','grandparent','spouse','sibling','caregiver','legal_guardian','other']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($r); ?>" <?php echo e($link->relationship === $r ? 'selected' : ''); ?>><?php echo e(ucfirst(str_replace('_',' ',$r))); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.portal.field_access_level', [], $l) ?: 'Access Level'); ?></label>
                    <select name="access_level" required class="form-control">
                        <option value="full" <?php echo e($link->access_level === 'full' ? 'selected' : ''); ?>><?php echo e(__('public.portal.opt_full_access', [], $l) ?: 'Full Access'); ?></option>
                        <option value="read_only" <?php echo e($link->access_level === 'read_only' ? 'selected' : ''); ?>><?php echo e(__('public.portal.opt_read_only', [], $l) ?: 'Read Only'); ?></option>
                    </select>
                </div>
            </div>
            <h3 class="panel-title mb-3"><i data-lucide="bell"></i> <?php echo e(__('public.portal.notif_pref_heading', [], $l) ?: 'Notification Preferences'); ?></h3>
            <div class="table-wrapper mb-6">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?php echo e(__('public.portal.col_event', [], $l) ?: 'Event'); ?></th>
                            <th><?php echo e(__('public.portal.col_in_portal', [], $l) ?: 'In-Portal'); ?></th>
                            <th><?php echo e(__('public.portal.col_email', [], $l) ?: 'Email'); ?></th>
                            <th><?php echo e(__('public.portal.col_sms', [], $l) ?: 'SMS'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $__currentLoopData = [
                        'lab_result'      => __('public.portal.event_lab_result', [], $l) ?: 'New Lab Result',
                        'appointment'     => __('public.portal.event_appointment', [], $l) ?: 'Appointment',
                        'consent_request' => __('public.portal.event_consent_request', [], $l) ?: 'Consent Request',
                        'age_transition'  => __('public.portal.event_age_transition', [], $l) ?: 'Age Transition Alert',
                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td data-label="<?php echo e(__('public.portal.col_event', [], $l) ?: 'Event'); ?>"><?php echo e($label); ?></td>
                        <?php $__currentLoopData = ['portal','email','sms']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <td data-label="<?php echo e(ucfirst($ch)); ?>">
                            <input type="checkbox" name="notification_prefs[<?php echo e($key); ?>][<?php echo e($ch); ?>]" value="1"
                                <?php echo e($link->notificationPrefFor($key, $ch) ? 'checked' : ''); ?>>
                        </td>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            <div class="row-actions">
                <button type="submit" class="btn btn-primary"><?php echo e(__('public.portal.btn_save_changes', [], $l) ?: 'Save Changes'); ?></button>
                <a href="<?php echo e(route('portals.patient.family')); ?>" class="btn btn-secondary"><?php echo e(__('public.portal.btn_cancel', [], $l) ?: 'Cancel'); ?></a>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\family\edit.blade.php ENDPATH**/ ?>
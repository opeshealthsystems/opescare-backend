<?php $__env->startSection('title', __('public.portal.edit_family_link_title', [], app()->getLocale()) ?: 'Edit Family Link'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.edit_family_link_breadcrumb', [], app()->getLocale()) ?: 'Edit Family Link'); ?>

<?php $__env->startSection('head'); ?>
    <?php echo $__env->make('portals.patient._finder_head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>
<div class="pf pf--form">

    <div class="pf-head">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="pf-avatar"><?php echo e(strtoupper(substr($link->dependentPatient->first_name ?? 'D', 0, 1))); ?></div>
            <h1><?php echo e($link->dependentPatient->first_name); ?> <?php echo e($link->dependentPatient->last_name); ?></h1>
        </div>
        <a href="<?php echo e(route('portals.patient.family')); ?>" class="f-btn">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('public.portal.family_breadcrumb', [], $l) ?: 'My Family'); ?>

        </a>
    </div>

    <form method="POST" action="<?php echo e(route('portals.patient.family.update', $link->id)); ?>">
        <?php echo csrf_field(); ?>
        <div class="f-card" style="margin-bottom:12px;">
            <div class="f-field">
                <label class="f-label" for="relationship"><?php echo e(__('public.portal.field_relationship', [], $l) ?: 'Relationship'); ?></label>
                <select id="relationship" name="relationship" required class="f-input">
                    <?php $__currentLoopData = ['parent','grandparent','spouse','sibling','caregiver','legal_guardian','other']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($r); ?>" <?php echo e($link->relationship === $r ? 'selected' : ''); ?>><?php echo e(\App\Support\Enums::label($r)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="f-field" style="margin-bottom:0;">
                <label class="f-label" for="access_level"><?php echo e(__('public.portal.field_access_level', [], $l) ?: 'Access Level'); ?></label>
                <select id="access_level" name="access_level" required class="f-input">
                    <option value="full" <?php echo e($link->access_level === 'full' ? 'selected' : ''); ?>><?php echo e(__('public.portal.opt_full_access', [], $l) ?: 'Full Access'); ?></option>
                    <option value="read_only" <?php echo e($link->access_level === 'read_only' ? 'selected' : ''); ?>><?php echo e(__('public.portal.opt_read_only', [], $l) ?: 'Read Only'); ?></option>
                </select>
            </div>
        </div>

        <div class="f-card" style="margin-bottom:16px;">
            <h2 class="pf-card-head">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'filter', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('public.portal.notif_pref_heading', [], $l) ?: 'Notification Preferences'); ?>

            </h2>
            <div class="pf-table-wrap">
                <table class="pf-table">
                    <thead>
                        <tr>
                            <th><?php echo e(__('public.portal.col_event', [], $l) ?: 'Event'); ?></th>
                            <th style="text-align:center;"><?php echo e(__('public.portal.col_in_portal', [], $l) ?: 'In-Portal'); ?></th>
                            <th style="text-align:center;"><?php echo e(__('public.portal.col_email', [], $l) ?: 'Email'); ?></th>
                            <th style="text-align:center;"><?php echo e(__('public.portal.col_sms', [], $l) ?: 'SMS'); ?></th>
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
                        <td><?php echo e($label); ?></td>
                        <?php $__currentLoopData = ['portal','email','sms']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <td style="text-align:center;">
                            <input type="checkbox" name="notification_prefs[<?php echo e($key); ?>][<?php echo e($ch); ?>]" value="1"
                                aria-label="<?php echo e($label); ?> — <?php echo e(ucfirst($ch)); ?>"
                                <?php echo e($link->notificationPrefFor($key, $ch) ? 'checked' : ''); ?>>
                        </td>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pf-actions">
            <button type="submit" class="f-btn f-btn--primary" style="min-height:48px;"><?php echo e(__('public.portal.btn_save_changes', [], $l) ?: 'Save Changes'); ?></button>
            <a href="<?php echo e(route('portals.patient.family')); ?>" class="f-btn"><?php echo e(__('public.portal.btn_cancel', [], $l) ?: 'Cancel'); ?></a>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\family\edit.blade.php ENDPATH**/ ?>
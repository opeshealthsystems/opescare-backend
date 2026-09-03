<?php $__env->startSection('title', __('public.appt_reschedule_title') . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.appt_reschedule_title')); ?>

<?php $__env->startSection('head'); ?>
    <?php echo $__env->make('portals.patient._finder_head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="pf pf--form">

    <div class="pf-head">
        <div>
            <h1><?php echo e(__('public.appt_reschedule_title')); ?></h1>
            <p class="pf-sub"><?php echo e(__('public.appt_reschedule_subtitle')); ?></p>
        </div>
        <a href="<?php echo e(route('portals.patient.appointments')); ?>" class="f-btn">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments'); ?>

        </a>
    </div>

    <?php if($errors->any()): ?>
    <div class="pf-alert pf-alert--danger" role="alert">
        <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <p><?php echo e($errors->first()); ?></p>
    </div>
    <?php endif; ?>

    
    <div class="f-card" style="margin-bottom:12px;">
        <h2 class="pf-card-head">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'calendar', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('finder_patient.current_appointment')); ?>

        </h2>
        <dl class="pf-summary">
            <div>
                <dt><?php echo e(__('finder_patient.confirm_type')); ?></dt>
                <dd><?php echo e(\App\Support\Enums::label($appt->appointment_type ?? 'consultation')); ?></dd>
            </div>
            <div>
                <dt><?php echo e(__('public.appt_reschedule_current')); ?></dt>
                <dd><?php echo e($appt->scheduled_at?->isoFormat('LLLL') ?? '—'); ?></dd>
            </div>
            <?php if($appt->facility?->name): ?>
            <div>
                <dt><?php echo e(__('finder_patient.confirm_facility')); ?></dt>
                <dd><?php echo e($appt->facility->name); ?></dd>
            </div>
            <?php endif; ?>
        </dl>
    </div>

    
    <form method="POST" action="<?php echo e(route('portals.patient.appointments.reschedule.store', $appt->id)); ?>" class="f-card">
        <?php echo csrf_field(); ?>
        <h2 class="pf-card-head">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'clock', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('public.appt_reschedule_new')); ?>

        </h2>
        <div class="f-field">
            <label class="f-label" for="scheduled_at"><?php echo e(__('finder_patient.step_time')); ?></label>
            <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="f-input"
                   min="<?php echo e(now()->format('Y-m-d\TH:i')); ?>" required>
            <p class="f-hint"><?php echo e(__('finder_patient.time_help')); ?></p>
        </div>

        <div class="pf-alert pf-alert--warn">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'info', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <p><?php echo e(__('finder_patient.reschedule_note')); ?></p>
        </div>

        <button type="submit" class="f-btn f-btn--primary f-btn--block" style="min-height:48px;">
            <?php echo e(__('public.appt_reschedule_submit')); ?>

        </button>
    </form>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\appointment_reschedule.blade.php ENDPATH**/ ?>
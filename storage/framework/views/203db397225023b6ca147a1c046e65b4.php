<?php $__env->startSection('title', __('public.appt_reschedule_title') . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.appt_reschedule_title')); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.appt_reschedule_title')); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.appt_reschedule_subtitle')); ?></p>
    </div>
    <a href="<?php echo e(route('portals.patient.appointments')); ?>" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> <?php echo e(__('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments'); ?>

    </a>
</div>

<?php if($errors->any()): ?>
<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div><?php echo e($errors->first()); ?></div></div>
<?php endif; ?>

<div class="panel" style="max-width:640px;">
    <div class="panel-body">
        <p class="page-subtitle" style="margin:0 0 1rem;">
            <?php echo e(__('public.appt_reschedule_current')); ?>:
            <strong><?php echo e($appt->scheduled_at?->isoFormat('LLLL') ?? '—'); ?></strong>
            · <?php echo e(__('public.appt_type_' . $appt->appointment_type, [], app()->getLocale())); ?>

        </p>
        <form method="POST" action="<?php echo e(route('portals.patient.appointments.reschedule.store', $appt->id)); ?>">
            <?php echo csrf_field(); ?>
            <div style="margin-bottom:1rem;">
                <label class="form-label" for="scheduled_at"><?php echo e(__('public.appt_reschedule_new')); ?></label>
                <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="calendar-clock"></i> <?php echo e(__('public.appt_reschedule_submit')); ?>

            </button>
        </form>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\appointment_reschedule.blade.php ENDPATH**/ ?>
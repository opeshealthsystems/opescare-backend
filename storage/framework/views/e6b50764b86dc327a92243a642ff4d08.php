<?php $__env->startSection('title', __('public.appt_book_title') . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.appt_book_title')); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.appt_book_title')); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.appt_book_subtitle')); ?></p>
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
        <form method="POST" action="<?php echo e(route('portals.patient.appointments.book.store')); ?>">
            <?php echo csrf_field(); ?>

            <div style="margin-bottom:1rem;">
                <label class="form-label" for="facility_id"><?php echo e(__('public.appt_book_facility')); ?></label>
                <select id="facility_id" name="facility_id" class="form-control" required>
                    <option value="">—</option>
                    <?php $__currentLoopData = $facilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($f->id); ?>" <?php if(old('facility_id', $defaultFacility) === $f->id): echo 'selected'; endif; ?>><?php echo e($f->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div style="margin-bottom:1rem;">
                <label class="form-label" for="appointment_type"><?php echo e(__('public.appt_book_type')); ?></label>
                <select id="appointment_type" name="appointment_type" class="form-control" required>
                    <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($t); ?>" <?php if(old('appointment_type') === $t): echo 'selected'; endif; ?>><?php echo e(__('public.appt_type_' . $t)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div style="margin-bottom:1rem;">
                <label class="form-label" for="scheduled_at"><?php echo e(__('public.appt_book_when')); ?></label>
                <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="form-control"
                       value="<?php echo e(old('scheduled_at')); ?>" required>
            </div>

            <div style="margin-bottom:1rem;">
                <label class="form-label" for="reason"><?php echo e(__('public.appt_book_reason')); ?></label>
                <textarea id="reason" name="reason" class="form-control" rows="3" maxlength="500"
                          placeholder="<?php echo e(__('public.appt_book_reason_ph')); ?>"><?php echo e(old('reason')); ?></textarea>
            </div>

            <p class="page-subtitle" style="font-size:.82rem;margin:0 0 1rem;"><?php echo e(__('public.appt_book_note')); ?></p>

            <button type="submit" class="btn btn-primary">
                <i data-lucide="calendar-plus"></i> <?php echo e(__('public.appt_book_submit')); ?>

            </button>
        </form>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\appointment_book.blade.php ENDPATH**/ ?>
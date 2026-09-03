<?php $__env->startSection('title', __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.staff')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments'); ?>

<?php $__env->startSection('head'); ?>
<?php echo $__env->make('portals.pharmacy._supply_styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php
    // Desk view: today's list first (soonest first), everything else after,
    // in the order the controller delivered it. Presentation-only grouping.
    $todayAppointments = $appointments
        ->filter(fn ($a) => \Carbon\Carbon::parse($a->scheduled_at ?? $a->created_at)->isToday())
        ->sortBy(fn ($a) => \Carbon\Carbon::parse($a->scheduled_at ?? $a->created_at))
        ->values();
    $otherAppointments = $appointments
        ->reject(fn ($a) => \Carbon\Carbon::parse($a->scheduled_at ?? $a->created_at)->isToday())
        ->values();
?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments'); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.staff_portal.appointments_subtitle', [], app()->getLocale()) ?: 'View and manage scheduled patient appointments.'); ?></p>
    </div>
    <div>
        <a href="<?php echo e(route('portals.staff.appointments.create')); ?>" class="btn btn-primary fs-tap">
            <i data-lucide="calendar-plus"></i>
            <?php echo e(__('public.staff_portal.btn_book_appointment', [], app()->getLocale()) ?: 'Book Appointment'); ?>

        </a>
    </div>
</div>


<?php if(session('success')): ?>
    <div class="alert alert-success mb-4"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div>
<?php endif; ?>
<?php if($errors->any()): ?>
    <div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i><div><?php echo e($errors->first()); ?></div></div>
<?php endif; ?>


<form method="GET" action="<?php echo e(route('portals.staff.appointments')); ?>" class="filter-bar">
    <div class="form-search">
        <span class="search-icon">
            <i data-lucide="search"></i>
        </span>
        <input
            type="text"
            name="patient_id"
            class="form-control"
            placeholder="<?php echo e(__('public.staff_portal.filter_patient_id', [], app()->getLocale()) ?: 'Patient ID…'); ?>"
            value="<?php echo e(request('patient_id')); ?>"
        >
    </div>
    <input
        type="text"
        name="facility_id"
        class="form-control"
        placeholder="<?php echo e(__('public.staff_portal.filter_facility', [], app()->getLocale()) ?: 'Facility ID…'); ?>"
        value="<?php echo e(request('facility_id')); ?>"
    >
    <input
        type="text"
        name="provider_id"
        class="form-control"
        placeholder="<?php echo e(__('public.staff_portal.filter_provider', [], app()->getLocale()) ?: 'Provider ID…'); ?>"
        value="<?php echo e(request('provider_id')); ?>"
    >
    <input
        type="date"
        name="date"
        class="form-control"
        value="<?php echo e(request('date')); ?>"
    >
    <button type="submit" class="btn btn-primary btn-sm fs-tap">
        <i data-lucide="filter"></i>
        <?php echo e(__('public.staff_portal.filter_apply', [], app()->getLocale()) ?: 'Filter'); ?>

    </button>
    <a href="<?php echo e(route('portals.staff.appointments')); ?>" class="btn btn-ghost btn-sm fs-tap">
        <?php echo e(__('public.staff_portal.filter_clear', [], app()->getLocale()) ?: 'Clear'); ?>

    </a>
</form>

<?php if(count($appointments) === 0): ?>
    <div class="panel">
        <div class="panel-body panel-body--flush">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="calendar-x-2"></i>
                </div>
                <h3><?php echo e(__('public.staff_portal.no_appointments_title', [], app()->getLocale()) ?: 'No Appointments Found'); ?></h3>
                <p><?php echo e(__('public.staff_portal.no_appointments_desc', [], app()->getLocale()) ?: 'There are no appointments matching your current filters.'); ?></p>
            </div>
        </div>
    </div>
<?php else: ?>

    <div class="fs-day-head">
        <?php echo $__env->make('partials.finder.icon', ['name' => 'calendar', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo e(__('finder_supply.appt_today')); ?>

        <span class="fs-day-count"><?php echo e($todayAppointments->count()); ?></span>
    </div>

    <?php if($todayAppointments->isEmpty()): ?>
        <p class="page-subtitle mb-6"><?php echo e(__('finder_supply.appt_today_empty')); ?></p>
    <?php else: ?>
        <?php echo $__env->make('portals.staff._appointments_table', ['rows' => $todayAppointments, 'timeOnly' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>

    <?php if($otherAppointments->isNotEmpty()): ?>
        <div class="fs-day-head">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'clock', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('finder_supply.appt_other')); ?>

            <span class="fs-day-count"><?php echo e($otherAppointments->count()); ?></span>
        </div>
        <?php echo $__env->make('portals.staff._appointments_table', ['rows' => $otherAppointments, 'timeOnly' => false], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>

<?php endif; ?>


<div id="appt-cancel-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="appt-cancel-title">
        <h3 id="appt-cancel-title" class="modal__title">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'x', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('finder_supply.cancel_title')); ?>

        </h3>
        <form id="appt-cancel-form" method="POST" action="">
            <?php echo csrf_field(); ?>
            <div class="modal__body">
                <p id="appt-cancel-label" class="td-muted"></p>
                <div class="form-group">
                    <label class="form-label" for="appt-cancel-reason"><?php echo e(__('finder_supply.cancel_reason_label')); ?></label>
                    <textarea id="appt-cancel-reason" name="reason" class="form-control" rows="3"
                              required minlength="5" maxlength="500"
                              placeholder="<?php echo e(__('finder_supply.cancel_reason_ph')); ?>"></textarea>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm fs-tap" onclick="closeApptCancelModal()"><?php echo e(__('finder_supply.btn_keep')); ?></button>
                <button type="submit" class="fs-act-btn fs-act-btn--danger">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'x', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('finder_supply.btn_cancel_confirm')); ?>

                </button>
            </div>
        </form>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    var apptBase = '<?php echo e(url('/portals/staff/appointments')); ?>';

    function openApptCancelModal(id, label) {
        document.getElementById('appt-cancel-label').textContent = label;
        document.getElementById('appt-cancel-form').action = apptBase + '/' + id + '/cancel';
        document.getElementById('appt-cancel-modal').removeAttribute('hidden');
        document.getElementById('appt-cancel-reason').focus();
    }
    function closeApptCancelModal() { document.getElementById('appt-cancel-modal').setAttribute('hidden',''); }
    document.getElementById('appt-cancel-modal').addEventListener('click', function(e) { if (e.target === this) closeApptCancelModal(); });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\staff\appointments.blade.php ENDPATH**/ ?>
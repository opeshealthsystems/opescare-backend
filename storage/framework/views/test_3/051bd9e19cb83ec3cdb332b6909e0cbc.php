<?php $__env->startSection('title', __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.staff')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments'); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments'); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.staff_portal.appointments_subtitle', [], app()->getLocale()) ?: 'View and manage scheduled patient appointments.'); ?></p>
    </div>
    <div>
        <a href="<?php echo e(route('portals.staff.appointments.create')); ?>" class="btn btn-primary">
            <i data-lucide="calendar-plus"></i>
            <?php echo e(__('public.staff_portal.btn_book_appointment', [], app()->getLocale()) ?: 'Book Appointment'); ?>

        </a>
    </div>
</div>


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
    <button type="submit" class="btn btn-primary btn-sm">
        <i data-lucide="filter"></i>
        <?php echo e(__('public.staff_portal.filter_apply', [], app()->getLocale()) ?: 'Filter'); ?>

    </button>
    <a href="<?php echo e(route('portals.staff.appointments')); ?>" class="btn btn-ghost btn-sm">
        <?php echo e(__('public.staff_portal.filter_clear', [], app()->getLocale()) ?: 'Clear'); ?>

    </a>
</form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        <?php if(count($appointments) === 0): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="calendar-x-2"></i>
                </div>
                <h3><?php echo e(__('public.staff_portal.no_appointments_title', [], app()->getLocale()) ?: 'No Appointments Found'); ?></h3>
                <p><?php echo e(__('public.staff_portal.no_appointments_desc', [], app()->getLocale()) ?: 'There are no appointments matching your current filters.'); ?></p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?php echo e(__('public.staff_portal.col_datetime', [], app()->getLocale()) ?: 'Date / Time'); ?></th>
                            <th><?php echo e(__('public.staff_portal.col_patient_id', [], app()->getLocale()) ?: 'Patient ID'); ?></th>
                            <th><?php echo e(__('public.staff_portal.col_provider', [], app()->getLocale()) ?: 'Provider'); ?></th>
                            <th><?php echo e(__('public.staff_portal.col_type', [], app()->getLocale()) ?: 'Type'); ?></th>
                            <th><?php echo e(__('public.staff_portal.col_facility', [], app()->getLocale()) ?: 'Facility'); ?></th>
                            <th><?php echo e(__('public.staff_portal.col_status', [], app()->getLocale()) ?: 'Status'); ?></th>
                            <th><?php echo e(__('public.staff_portal.col_actions', [], app()->getLocale()) ?: 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $appointments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appointment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $statusBadge = match($appointment->status ?? '') {
                                'completed'  => 'badge-success',
                                'cancelled'  => 'badge-danger',
                                'no_show'    => 'badge-warning',
                                'checked_in' => 'badge-teal',
                                default      => 'badge-neutral',
                            };
                        ?>
                        <tr>
                            <td data-label="<?php echo e(__('public.staff_portal.col_datetime', [], app()->getLocale()) ?: 'Date / Time'); ?>">
                                <?php echo e(\Carbon\Carbon::parse($appointment->scheduled_at ?? $appointment->created_at)->format('M d, Y H:i')); ?>

                            </td>
                            <td data-label="<?php echo e(__('public.staff_portal.col_patient_id', [], app()->getLocale()) ?: 'Patient ID'); ?>">
                                <span class="mono"><?php echo e($appointment->patient?->health_id ?? ($appointment->patient_id ? '#'.$appointment->patient_id : '—')); ?></span>
                            </td>
                            <td data-label="<?php echo e(__('public.staff_portal.col_provider', [], app()->getLocale()) ?: 'Provider'); ?>">
                                <?php echo e($appointment->provider_name ?? $appointment->provider_id ?? '--'); ?>

                            </td>
                            <td data-label="<?php echo e(__('public.staff_portal.col_type', [], app()->getLocale()) ?: 'Type'); ?>">
                                <span class="badge badge-primary"><?php echo e(\App\Support\Enums::label($appointment->appointment_type ?? 'General')); ?></span>
                            </td>
                            <td data-label="<?php echo e(__('public.staff_portal.col_facility', [], app()->getLocale()) ?: 'Facility'); ?>">
                                <?php echo e($appointment->facility_name ?? $appointment->facility_id ?? '--'); ?>

                            </td>
                            <td data-label="<?php echo e(__('public.staff_portal.col_status', [], app()->getLocale()) ?: 'Status'); ?>">
                                <span class="badge <?php echo e($statusBadge); ?>"><?php echo e(\App\Support\Enums::label($appointment->status ?? 'Unknown')); ?></span>
                            </td>
                            <td data-label="<?php echo e(__('public.staff_portal.col_actions', [], app()->getLocale()) ?: 'Actions'); ?>">
                                <a href="<?php echo e(route('portals.staff.appointments')); ?>?view=<?php echo e($appointment->id ?? $appointment->uuid ?? ''); ?>" class="btn btn-ghost btn-sm">
                                    <i data-lucide="eye"></i>
                                    <?php echo e(__('public.staff_portal.action_view', [], app()->getLocale()) ?: 'View'); ?>

                                </a>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/staff/appointments.blade.php ENDPATH**/ ?>
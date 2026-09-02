<?php $__env->startSection('title', __('public.portal.nav_appointments', [], app()->getLocale()) . ' — OpesCare Patient Portal'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments'); ?>


<?php $__env->startSection('patient_banner'); ?>
    <?php echo $__env->make('partials.guardian-context-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.portal.nav_appointments', [], app()->getLocale()) ?: 'My Appointments'); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.portal.appointments_subtitle', [], app()->getLocale()) ?: 'View your upcoming and past appointments.'); ?></p>
    </div>
    <a href="<?php echo e(route('portals.patient.appointments.book')); ?>" class="btn btn-primary">
        <i data-lucide="calendar-plus"></i> <?php echo e(__('public.appt_book_btn')); ?>

    </a>
</div>

<?php if(session('success')): ?>
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>

<?php $apptCount = method_exists($appointments, 'total') ? $appointments->total() : $appointments->count(); ?>

<?php if($apptCount === 0): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="calendar-check-2"></i></div>
        <h3><?php echo e(__('public.portal.no_appointments_title', [], app()->getLocale()) ?: 'No Appointments'); ?></h3>
        <p><?php echo e(__('public.portal.no_appointments_desc', [], app()->getLocale()) ?: 'You don\'t have any appointments scheduled at this time.'); ?></p>
        <a href="<?php echo e(route('public.care-map')); ?>" class="btn btn-primary">
            <i data-lucide="map-pin"></i>
            <?php echo e(__('public.portal.nav_care_map', [], app()->getLocale()) ?: 'Find a Provider'); ?>

        </a>
    </div>
</div>
<?php else: ?>
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="calendar-check-2"></i> <?php echo e(__('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments'); ?></h2>
        <span class="badge badge-primary"><?php echo e($apptCount); ?></span>
    </div>
    <div class="table-wrapper">
        <table class="data-table" aria-label="<?php echo e(__('public.aria_appointments_list')); ?>">
            <thead>
                <tr>
                    <th><?php echo e(__('public.portal.date_time', [], app()->getLocale()) ?: 'Date & Time'); ?></th>
                    <th><?php echo e(__('public.portal.provider', [], app()->getLocale()) ?: 'Provider'); ?></th>
                    <th><?php echo e(__('public.portal.appointment_type', [], app()->getLocale()) ?: 'Type'); ?></th>
                    <th><?php echo e(__('public.portal.facility', [], app()->getLocale()) ?: 'Facility'); ?></th>
                    <th><?php echo e(__('public.portal.status', [], app()->getLocale()) ?: 'Status'); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $appointments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td data-label="<?php echo e(__('public.portal.date_time', [], app()->getLocale()) ?: 'Date & Time'); ?>">
                        <span class="td-strong">
                            <?php echo e($appt->scheduled_at?->format('d M Y') ?? '—'); ?>

                        </span>
                        <?php if($appt->scheduled_at): ?>
                        <div class="td-muted"><?php echo e($appt->scheduled_at->format('H:i')); ?></div>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?php echo e(__('public.portal.provider', [], app()->getLocale()) ?: 'Provider'); ?>">
                        <?php
                            $providerName = $appt->provider?->name
                                ?? (($appt->provider?->first_name ?? '') . ' ' . ($appt->provider?->last_name ?? ''))
                                ?: '—';
                        ?>
                        <span class="td-strong"><?php echo e(trim($providerName) ?: '—'); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.portal.appointment_type', [], app()->getLocale()) ?: 'Type'); ?>">
                        <span class="badge badge-primary"><?php echo e(\App\Support\Enums::label($appt->appointment_type ?? 'General')); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.portal.facility', [], app()->getLocale()) ?: 'Facility'); ?>">
                        <span class="td-muted"><?php echo e($appt->facility?->name ?? __('public.portal.unknown_facility', [], app()->getLocale()) ?: 'Unknown Facility'); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.portal.status', [], app()->getLocale()) ?: 'Status'); ?>">
                        <?php
                            $stCls = match($appt->status ?? 'scheduled') {
                                'completed'  => 'badge-success',
                                'cancelled'  => 'badge-danger',
                                'no_show'    => 'badge-warning',
                                'checked_in' => 'badge-teal',
                                default      => 'badge-primary',
                            };
                        ?>
                        <span class="badge <?php echo e($stCls); ?>"><?php echo e(\App\Support\Enums::label($appt->status ?? 'Scheduled')); ?></span>
                    </td>
                    <td class="row-actions">
                        <?php if(in_array($appt->status, ['requested', 'scheduled', 'confirmed'])): ?>
                        <a href="<?php echo e(route('portals.patient.appointments.reschedule', $appt->id)); ?>" class="btn btn-secondary btn-sm">
                            <i data-lucide="calendar-clock"></i> <?php echo e(__('public.appt_reschedule_btn')); ?>

                        </a>
                        <button type="button" class="btn btn-danger btn-sm" onclick="opOpenModal('cancel-appt-<?php echo e($appt->id); ?>')">
                            <i data-lucide="x-circle"></i> <?php echo e(__('public.portal.btn_cancel_appointment', [], app()->getLocale()) ?: 'Cancel'); ?>

                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <?php if(method_exists($appointments, 'links')): ?>
    <div class="panel-body">
        <?php echo e($appointments->links()); ?>

    </div>
    <?php endif; ?>
</div>

<?php $__currentLoopData = $appointments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php if(in_array($appt->status, ['requested', 'scheduled', 'confirmed'])): ?>
    <div id="cancel-appt-<?php echo e($appt->id); ?>" class="modal-backdrop mt-6" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="cancel-appt-<?php echo e($appt->id); ?>-title">
            <h3 class="modal__title" id="cancel-appt-<?php echo e($appt->id); ?>-title"><i data-lucide="x-circle"></i> <?php echo e(__('public.portal.modal_cancel_appt_title', [], app()->getLocale()) ?: 'Cancel appointment'); ?></h3>
            <form method="POST" action="<?php echo e(route('portals.patient.appointments.cancel', $appt->id)); ?>">
                <?php echo csrf_field(); ?>
                <div class="modal__body">
                    <p><?php echo e(__('public.portal.modal_cancel_appt_body', [], app()->getLocale()) ?: 'Cancel this appointment? This action cannot be undone.'); ?></p>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-ghost" onclick="opCloseModal('cancel-appt-<?php echo e($appt->id); ?>')"><?php echo e(__('public.portal.btn_keep_appointment', [], app()->getLocale()) ?: 'Keep appointment'); ?></button>
                    <button type="submit" class="btn btn-danger"><?php echo e(__('public.portal.btn_cancel_appointment', [], app()->getLocale()) ?: 'Cancel appointment'); ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/patient/appointments.blade.php ENDPATH**/ ?>
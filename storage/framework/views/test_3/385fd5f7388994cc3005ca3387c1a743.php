<?php $__env->startSection('title', __('public.portal.nav_appointments', [], app()->getLocale()) . ' — OpesCare Patient Portal'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments'); ?>

<?php $__env->startSection('head'); ?>
    <?php echo $__env->make('portals.patient._finder_head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('patient_banner'); ?>
    <?php echo $__env->make('partials.guardian-context-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php
    $l = app()->getLocale();
    $apptCount = method_exists($appointments, 'total') ? $appointments->total() : $appointments->count();
?>

<?php $__env->startSection('content'); ?>

<div class="pf">

    <div class="pf-head">
        <div>
            <h1><?php echo e(__('public.portal.nav_appointments', [], $l) ?: 'My Appointments'); ?></h1>
            <p class="pf-sub"><?php echo e(__('public.portal.appointments_subtitle', [], $l) ?: 'View your upcoming and past appointments.'); ?></p>
        </div>
        <a href="<?php echo e(route('portals.patient.appointments.book')); ?>" class="f-btn f-btn--primary">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'calendar-plus', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('public.appt_book_btn')); ?>

        </a>
    </div>

    <?php if($apptCount === 0): ?>
    <div class="f-card" style="text-align:center;padding:40px 20px;">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'calendar', 'size' => 24], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <h2 style="font-size:1.125rem;font-weight:750;margin:0 0 6px;"><?php echo e(__('public.portal.no_appointments_title', [], $l) ?: 'No Appointments'); ?></h2>
        <p class="f-muted" style="margin:0 0 18px;"><?php echo e(__('public.portal.no_appointments_desc', [], $l) ?: 'You don\'t have any appointments scheduled at this time.'); ?></p>
        <div class="pf-actions" style="justify-content:center;">
            <a href="<?php echo e(route('portals.patient.appointments.book')); ?>" class="f-btn f-btn--primary" style="min-height:48px;">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'calendar-plus', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('public.appt_book_btn')); ?>

            </a>
            <a href="<?php echo e(route('public.care-map')); ?>" class="f-btn">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'map-pin', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('public.portal.nav_care_map', [], $l) ?: 'Find a Provider'); ?>

            </a>
        </div>
    </div>
    <?php else: ?>

    <div class="pf-stack">
        <?php $__currentLoopData = $appointments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $status = $appt->status ?? 'scheduled';
            $badgeCls = match($status) {
                'requested'                             => 'f-badge--warn',
                'confirmed', 'scheduled', 'checked_in'  => 'f-badge--good',
                'cancelled'                             => 'pf-badge--muted',
                'no_show'                               => 'f-badge--danger',
                default                                 => '',
            };
            $providerName = trim($appt->provider?->name
                ?? (($appt->provider?->first_name ?? '') . ' ' . ($appt->provider?->last_name ?? '')));
            $isActionable = in_array($status, ['requested', 'scheduled', 'confirmed']);
        ?>
        <div class="f-card">
            <div class="pf-appt">
                <div class="pf-appt__date" aria-hidden="true">
                    <div class="d"><?php echo e($appt->scheduled_at?->format('d') ?? '—'); ?></div>
                    <div class="m"><?php echo e($appt->scheduled_at?->isoFormat('MMM') ?? ''); ?></div>
                </div>
                <div class="pf-appt__body">
                    <p class="pf-appt__title">
                        <?php echo e(\App\Support\Enums::label($appt->appointment_type ?? 'General')); ?>
                        <span class="f-badge <?php echo e($badgeCls); ?>"><?php echo e(\App\Support\Enums::label($status)); ?></span>
                    </p>
                    <div class="pf-appt__meta">
                        <?php if($appt->scheduled_at): ?>
                        <span>
                            <?php echo $__env->make('partials.finder.icon', ['name' => 'clock', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <?php echo e($appt->scheduled_at->isoFormat('ddd D MMM YYYY, HH:mm')); ?>

                        </span>
                        <?php endif; ?>
                        <span>
                            <?php echo $__env->make('partials.finder.icon', ['name' => 'map-pin', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <?php echo e($appt->facility?->name ?? (__('public.portal.unknown_facility', [], $l) ?: 'Unknown Facility')); ?>

                        </span>
                        <?php if($providerName !== ''): ?>
                        <span>
                            <?php echo $__env->make('partials.finder.icon', ['name' => 'users', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <?php echo e(__('finder_patient.with_provider', ['name' => $providerName])); ?>

                        </span>
                        <?php endif; ?>
                    </div>
                    <?php if($status === 'requested'): ?>
                    <p class="f-small" style="color:var(--f-warn);margin:8px 0 0;display:flex;align-items:center;gap:6px;">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'clock', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo e(__('finder_patient.requested_hint')); ?>

                    </p>
                    <?php endif; ?>
                    <?php if($isActionable): ?>
                    <div class="pf-appt__actions">
                        <a href="<?php echo e(route('portals.patient.appointments.reschedule', $appt->id)); ?>" class="f-btn">
                            <?php echo $__env->make('partials.finder.icon', ['name' => 'refresh-cw', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <?php echo e(__('public.appt_reschedule_btn')); ?>

                        </a>
                        <button type="button" class="f-btn pf-btn--danger-ghost" onclick="pfOpenModal('cancel-appt-<?php echo e($appt->id); ?>')">
                            <?php echo $__env->make('partials.finder.icon', ['name' => 'x', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <?php echo e(__('public.portal.btn_cancel_appointment', [], $l) ?: 'Cancel'); ?>

                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <?php if(method_exists($appointments, 'links')): ?>
    <div class="pf-pagination">
        <?php echo e($appointments->links()); ?>

    </div>
    <?php endif; ?>

    
    <?php $__currentLoopData = $appointments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if(in_array($appt->status, ['requested', 'scheduled', 'confirmed'])): ?>
        <div id="cancel-appt-<?php echo e($appt->id); ?>" class="pf-modal-backdrop" hidden>
            <div class="pf-modal" role="dialog" aria-modal="true" aria-labelledby="cancel-appt-<?php echo e($appt->id); ?>-title">
                <h3 id="cancel-appt-<?php echo e($appt->id); ?>-title">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('public.portal.modal_cancel_appt_title', [], $l) ?: 'Cancel appointment'); ?>

                </h3>
                <form method="POST" action="<?php echo e(route('portals.patient.appointments.cancel', $appt->id)); ?>">
                    <?php echo csrf_field(); ?>
                    <p><?php echo e(__('public.portal.modal_cancel_appt_body', [], $l) ?: 'Cancel this appointment? This action cannot be undone.'); ?></p>
                    <div class="pf-modal__actions">
                        <button type="button" class="f-btn" onclick="pfCloseModal('cancel-appt-<?php echo e($appt->id); ?>')"><?php echo e(__('public.portal.btn_keep_appointment', [], $l) ?: 'Keep appointment'); ?></button>
                        <button type="submit" class="f-btn pf-btn--danger"><?php echo e(__('public.portal.btn_cancel_appointment', [], $l) ?: 'Cancel appointment'); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
function pfOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function pfCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.pf-modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/patient/appointments.blade.php ENDPATH**/ ?>

<?php $lApp = app()->getLocale(); ?>
<div class="panel mb-6">
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php echo e(__('public.staff_portal.col_datetime', [], $lApp) ?: 'Date / Time'); ?></th>
                        <th><?php echo e(__('public.staff_portal.col_patient_id', [], $lApp) ?: 'Patient ID'); ?></th>
                        <th><?php echo e(__('public.staff_portal.col_provider', [], $lApp) ?: 'Provider'); ?></th>
                        <th><?php echo e(__('public.staff_portal.col_type', [], $lApp) ?: 'Type'); ?></th>
                        <th><?php echo e(__('public.staff_portal.col_facility', [], $lApp) ?: 'Facility'); ?></th>
                        <th><?php echo e(__('public.staff_portal.col_status', [], $lApp) ?: 'Status'); ?></th>
                        <th><?php echo e(__('public.staff_portal.col_actions', [], $lApp) ?: 'Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appointment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $when = \Carbon\Carbon::parse($appointment->scheduled_at ?? $appointment->created_at);
                        $status = $appointment->status ?? '';
                        $statusBadge = match($status) {
                            'completed'  => 'badge-success',
                            'confirmed'  => 'badge-success',
                            'cancelled'  => 'badge-danger',
                            'no_show'    => 'badge-warning',
                            'checked_in' => 'badge-teal',
                            default      => 'badge-neutral',
                        };
                        $patientLabel = $appointment->patient?->health_id
                            ?? ($appointment->patient_id ? '#'.$appointment->patient_id : '—');
                        $phone   = $appointment->patient?->phone_number;
                        $dial    = $phone ? preg_replace('/[^0-9+]/', '', $phone) : null;
                        $canConfirm = $status === 'scheduled';
                        $canNoShow  = in_array($status, ['scheduled', 'confirmed'], true);
                        $canCancel  = in_array($status, ['scheduled', 'confirmed'], true);
                    ?>
                    <tr>
                        <td data-label="<?php echo e(__('public.staff_portal.col_datetime', [], $lApp) ?: 'Date / Time'); ?>">
                            <span class="td-strong"><?php echo e(($timeOnly ?? false) ? $when->format('H:i') : $when->format('M d, Y H:i')); ?></span>
                        </td>
                        <td data-label="<?php echo e(__('public.staff_portal.col_patient_id', [], $lApp) ?: 'Patient ID'); ?>">
                            <span class="mono"><?php echo e($patientLabel); ?></span>
                            <?php if($dial): ?>
                                
                                <div>
                                    <a class="fs-tel" href="tel:<?php echo e($dial); ?>" aria-label="<?php echo e(__('finder_supply.call_patient')); ?>">
                                        <?php echo $__env->make('partials.finder.icon', ['name' => 'phone', 'size' => 14], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                        <?php echo e($phone); ?>

                                    </a>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?php echo e(__('public.staff_portal.col_provider', [], $lApp) ?: 'Provider'); ?>">
                            <?php echo e($appointment->provider_name ?? $appointment->provider_id ?? '--'); ?>

                        </td>
                        <td data-label="<?php echo e(__('public.staff_portal.col_type', [], $lApp) ?: 'Type'); ?>">
                            <span class="badge badge-primary"><?php echo e(\App\Support\Enums::label($appointment->appointment_type ?? 'General')); ?></span>
                        </td>
                        <td data-label="<?php echo e(__('public.staff_portal.col_facility', [], $lApp) ?: 'Facility'); ?>">
                            <?php echo e($appointment->facility_name ?? $appointment->facility_id ?? '--'); ?>

                        </td>
                        <td data-label="<?php echo e(__('public.staff_portal.col_status', [], $lApp) ?: 'Status'); ?>">
                            <span class="badge <?php echo e($statusBadge); ?>"><?php echo e(\App\Support\Enums::label($appointment->status ?? 'Unknown')); ?></span>
                        </td>
                        <td data-label="<?php echo e(__('public.staff_portal.col_actions', [], $lApp) ?: 'Actions'); ?>">
                            <?php if($canConfirm || $canNoShow || $canCancel): ?>
                            <div class="fs-act">
                                <?php if($canConfirm): ?>
                                <form method="POST" action="<?php echo e(route('portals.staff.appointments.confirm', $appointment->id)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="fs-act-btn fs-act-btn--good">
                                        <?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                        <?php echo e(__('finder_supply.btn_confirm')); ?>

                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php if($canNoShow): ?>
                                <form method="POST" action="<?php echo e(route('portals.staff.appointments.no-show', $appointment->id)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="fs-act-btn fs-act-btn--muted">
                                        <?php echo $__env->make('partials.finder.icon', ['name' => 'clock', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                        <?php echo e(__('finder_supply.btn_no_show')); ?>

                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php if($canCancel): ?>
                                <button type="button" class="fs-act-btn fs-act-btn--danger"
                                        onclick="openApptCancelModal('<?php echo e($appointment->id); ?>', '<?php echo e(addslashes($patientLabel)); ?> · <?php echo e($when->format('M d, H:i')); ?>')">
                                    <?php echo $__env->make('partials.finder.icon', ['name' => 'x', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo e(__('finder_supply.btn_cancel')); ?>

                                </button>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                                <span class="td-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\staff\_appointments_table.blade.php ENDPATH**/ ?>
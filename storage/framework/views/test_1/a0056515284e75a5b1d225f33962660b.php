<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('title', __('prescriber.counter_title', [], $l) ?: 'Dispense at the counter'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('prescriber.counter_title', [], $l) ?: 'Dispense at the counter'); ?></h1>
        <p class="page-subtitle"><?php echo e(__('prescriber.counter_sub', [], $l) ?: 'Enter the Health ID the patient presents, or the number printed on their prescription.'); ?></p>
    </div>
    <a href="<?php echo e(route('portals.pharmacy.prescriptions')); ?>" class="btn btn-secondary">
        <i data-lucide="list"></i>
        <span><?php echo e(__('prescriber.title', [], $l) ?: 'Prescriptions'); ?></span>
    </a>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
<?php endif; ?>


<div class="panel">
    <div class="panel-body">
        <form method="GET" action="<?php echo e(route('portals.pharmacy.prescriptions.lookup')); ?>" class="form-row">
            <div class="form-group" style="flex:1 1 300px">
                <label class="form-label" for="q"><?php echo e(__('prescriber.counter_query', [], $l) ?: 'Health ID or prescription number'); ?></label>
                <input type="text" id="q" name="q" class="form-control"
                       value="<?php echo e($query); ?>" autocomplete="off" autocapitalize="characters"
                       spellcheck="false" required
                       placeholder="CM-HID-XXXX-XXXX-XXXX  /  RX-XXXX-XXXX">
                <p class="form-hint"><?php echo e(__('prescriber.counter_query_hint', [], $l) ?: 'Enter the exact Health ID, or the prescription number from the printed script. Patient names are not searchable.'); ?></p>
            </div>
            <div class="form-group" style="align-self:flex-start">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="search"></i>
                    <span><?php echo e(__('prescriber.counter_lookup', [], $l) ?: 'Find prescriptions'); ?></span>
                </button>
            </div>
        </form>
    </div>
</div>


<?php if($searched && $results->isEmpty() && $mode === 'reference'): ?>
    <div class="alert alert-danger">
        <i data-lucide="file-question"></i>
        <div><?php echo e(__('prescriber.counter_norx', ['reference' => $reference], $l)); ?></div>
    </div>
<?php elseif($searched && ! $patient): ?>
    <div class="alert alert-danger">
        <i data-lucide="user-x"></i>
        <div><?php echo e(__('prescriber.counter_nopat', [], $l) ?: 'No patient matches that Health ID.'); ?></div>
    </div>
<?php endif; ?>

<?php if($searched && ($patient || $results->isNotEmpty())): ?>
    <?php
        /**
         * Identity, resolved defensively. On the reference path the patient row
         * can legitimately come back null — merged away, or filtered out by the
         * demo-isolation scope — and the script still has to be readable.
         */
        $patientName = $patient
            ? trim(((string) ($patient->first_name ?? '')) . ' ' . ((string) ($patient->last_name ?? '')))
            : '';

        /**
         * Status label from the prescriber namespace, falling back to the shared
         * enum labeller for values that have no prescriber.st_* key of their own.
         */
        $label = function (?string $value) use ($l): string {
            $value = (string) $value;
            if ($value === '') {
                return '—';
            }
            $key  = 'prescriber.st_' . $value;
            $text = __($key, [], $l);

            return $text !== $key ? $text : \App\Support\Enums::label($value);
        };
    ?>

    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">
                <i data-lucide="user"></i>
                <?php echo e($patientName !== '' ? $patientName : (__('prescriber.counter_nopatrow', [], $l))); ?>

            </div>
            <span class="badge badge-neutral mono"><?php echo e($patient?->health_id ?? '—'); ?></span>
        </div>

        <?php if($results->isEmpty()): ?>
            <div class="panel-body">
                <div class="empty-state">
                    <i data-lucide="file-x" style="width:32px;height:32px"></i>
                    <p><?php echo e(__('prescriber.counter_none', [], $l) ?: 'No active prescriptions on that Health ID.'); ?></p>
                </div>
            </div>
        <?php else: ?>
            <?php $__currentLoopData = $results; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $rxStatus = (string) ($rx->status ?? '');

                    /**
                     * Whether this script may still be filled — and, when it may
                     * not, why. `isDispensable()` is the model's own rule and the
                     * same one PrescriptionService re-checks on POST, so the
                     * button and the write path cannot disagree.
                     *
                     * Expiry is a date check independent of the stored status: a
                     * script can sit at 'active' and still be long lapsed.
                     */
                    $expired     = ($rx->expires_at && $rx->expires_at->isPast()) || $rxStatus === 'expired';
                    $terminal    = $rx->isTerminal();
                    $canDispense = $rx->isDispensable();

                    /**
                     * Driven off isDispensable() rather than off the status alone,
                     * so no refusable state can slip through un-explained: a stored
                     * 'expired' with no expiry date, or any status added later,
                     * still lands on a refusal instead of a silent missing button.
                     */
                    $refusal = match (true) {
                        $canDispense              => null,
                        $rxStatus === 'dispensed' => __('prescriber.counter_blocked_dispensed', [], $l),
                        $expired                  => __('prescriber.counter_blocked_expired', ['date' => $rx->expires_at?->format('d M Y') ?? '—'], $l),
                        default                   => __('prescriber.counter_blocked_terminal', ['status' => $label($rxStatus)], $l),
                    };

                    $rxBadge = match ($rxStatus) {
                        'active'              => 'badge-info',
                        'dispensed'           => 'badge-success',
                        'partially_dispensed' => 'badge-warning',
                        'voided',
                        'entered_in_error',
                        'cancelled'           => 'badge-danger',
                        'amended'             => 'badge-warning',
                        default               => 'badge-neutral',
                    };

                    $prescriberName = trim((string) ($rx->prescriber->name ?? ''));
                ?>

                <div class="panel-body" style="border-top:1px solid var(--p-border,#e5e7eb)">
                    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start">
                        <div style="min-width:0">
                            <div style="font-weight:600;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                <span class="mono"><?php echo e($rx->reference ?: '—'); ?></span>
                                <span class="badge <?php echo e($rxBadge); ?> badge-sm"><?php echo e($label($rxStatus)); ?></span>
                                
                                <?php if($expired && ! $terminal && $rxStatus !== 'expired'): ?>
                                    <span class="badge badge-danger badge-sm"><?php echo e(__('prescriber.st_expired', [], $l) ?: 'Expired'); ?></span>
                                <?php endif; ?>
                            </div>

                            <table class="kv-table" style="margin-top:8px">
                                <tr>
                                    <td><?php echo e(__('prescriber.counter_from', [], $l) ?: 'Issued at'); ?></td>
                                    <td class="kv-strong"><?php echo e($rx->facility->name ?? '—'); ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo e(__('prescriber.col_prescriber', [], $l) ?: 'Prescriber'); ?></td>
                                    <td class="kv-strong"><?php echo e($prescriberName !== '' ? $prescriberName : '—'); ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo e(__('prescriber.issued_at', [], $l) ?: 'Issued'); ?></td>
                                    <td class="kv-strong"><?php echo e($rx->prescribed_at?->format('d M Y H:i') ?? '—'); ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo e(__('prescriber.expires_at', [], $l) ?: 'Expires'); ?></td>
                                    <td class="kv-strong">
                                        <?php if($rx->expires_at): ?>
                                            <?php if($expired): ?>
                                                <span class="badge badge-danger badge-sm"><?php echo e($rx->expires_at->format('d M Y')); ?></span>
                                            <?php else: ?>
                                                <?php echo e($rx->expires_at->format('d M Y')); ?>

                                            <?php endif; ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if($rx->dispensed_at): ?>
                                <tr>
                                    <td><?php echo e(__('prescriber.dispensed_at', [], $l) ?: 'Dispensed'); ?></td>
                                    <td class="kv-strong"><?php echo e($rx->dispensed_at->format('d M Y H:i')); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>

                            <?php if(filled($rx->notes)): ?>
                                <div class="td-muted" style="font-size:.85rem;margin-top:8px"><?php echo e($rx->notes); ?></div>
                            <?php endif; ?>
                        </div>

                        <?php if($canDispense): ?>
                            <form method="POST" action="<?php echo e(route('portals.pharmacy.dispense', $rx->id)); ?>"
                                  style="display:flex;gap:8px;align-items:flex-start;flex-wrap:wrap">
                                <?php echo csrf_field(); ?>
                                <input type="text" name="dispense_notes" class="form-control" style="min-width:180px"
                                       placeholder="<?php echo e(__('prescriber.counter_notes', [], $l) ?: 'Dispense notes'); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i data-lucide="check"></i>
                                    <span><?php echo e(__('prescriber.counter_dispense', [], $l) ?: 'Dispense'); ?></span>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    
                    <?php if($refusal): ?>
                        <div class="alert <?php echo e($rxStatus === 'dispensed' ? 'alert-warning' : 'alert-danger'); ?>" style="margin-top:10px">
                            <i data-lucide="<?php echo e($rxStatus === 'dispensed' ? 'check-circle' : 'shield-alert'); ?>"></i>
                            <div>
                                <strong><?php echo e($rxStatus === 'dispensed'
                                    ? (__('prescriber.counter_blocked_title_done', [], $l))
                                    : (__('prescriber.counter_blocked_title', [], $l))); ?></strong>
                                <div><?php echo e($refusal); ?></div>
                                <?php if(filled($rx->void_reason)): ?>
                                    <div class="td-muted" style="font-size:.85rem">
                                        <?php echo e(__('prescriber.void_reason', [], $l) ?: 'Reason'); ?>: <?php echo e($rx->void_reason); ?>

                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="table-wrapper" style="margin-top:10px">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th><?php echo e(__('prescriber.drug_name', [], $l) ?: 'Medicine'); ?></th>
                                    <th><?php echo e(__('prescriber.dose', [], $l) ?: 'Dose'); ?></th>
                                    <th><?php echo e(__('prescriber.frequency', [], $l) ?: 'Frequency'); ?></th>
                                    <th><?php echo e(__('prescriber.route', [], $l) ?: 'Route'); ?></th>
                                    <th><?php echo e(__('prescriber.quantity', [], $l) ?: 'Quantity'); ?></th>
                                    <th><?php echo e(__('prescriber.item_status', [], $l) ?: 'Line status'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $rx->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php
                                        $itemStatus = (string) ($item->status ?? '');
                                        $itemBadge  = match ($itemStatus) {
                                            'dispensed' => 'badge-success',
                                            'pending'   => 'badge-info',
                                            default     => 'badge-neutral',
                                        };
                                    ?>
                                    <tr>
                                        <td data-label="<?php echo e(__('prescriber.drug_name', [], $l) ?: 'Medicine'); ?>" class="td-strong"><?php echo e($item->drug_name ?: '—'); ?></td>
                                        <td data-label="<?php echo e(__('prescriber.dose', [], $l) ?: 'Dose'); ?>"><?php echo e($item->dose ?: '—'); ?></td>
                                        <td data-label="<?php echo e(__('prescriber.frequency', [], $l) ?: 'Frequency'); ?>"><?php echo e($item->frequency ?: '—'); ?></td>
                                        <td data-label="<?php echo e(__('prescriber.route', [], $l) ?: 'Route'); ?>"><?php echo e($item->route ? \App\Support\Enums::label($item->route, 'route') : '—'); ?></td>
                                        <td data-label="<?php echo e(__('prescriber.quantity', [], $l) ?: 'Quantity'); ?>"><?php echo e($item->quantity !== null && $item->quantity !== '' ? $item->quantity : '—'); ?></td>
                                        <td data-label="<?php echo e(__('prescriber.item_status', [], $l) ?: 'Line status'); ?>">
                                            <span class="badge <?php echo e($itemBadge); ?> badge-sm"><?php echo e($label($itemStatus)); ?></span>
                                            <?php if($item->dispensed_at): ?>
                                                <div class="td-muted" style="font-size:.8rem"><?php echo e($item->dispensed_at->format('d M Y H:i')); ?></div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="6" class="td-muted empty-cell">—</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/pharmacy/prescription_lookup.blade.php ENDPATH**/ ?>
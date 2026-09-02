<?php $__env->startSection('title', __('public.portal.prescriptions_title', [], app()->getLocale()) ?: 'My Prescriptions'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.prescriptions_breadcrumb', [], app()->getLocale()) ?: 'Prescriptions'); ?>

<?php $__env->startSection('patient_banner'); ?>
    <?php echo $__env->make('partials.guardian-context-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.portal.prescriptions_title', [], $l) ?: 'My Prescriptions'); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.portal.prescriptions_subtitle', [], $l) ?: 'All medications prescribed to you across your care history.'); ?></p>
    </div>
</div>

<?php if(session('success')): ?>
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>

<?php if(!$patient): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="alert-circle"></i></div>
        <h3><?php echo e(__('public.portal.no_profile_found_title', [], $l) ?: 'No Patient Profile Found'); ?></h3>
        <p><?php echo e(__('public.portal.no_profile_found_desc', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.'); ?></p>
    </div>
</div>
<?php elseif($prescriptions->isEmpty()): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="pill"></i></div>
        <h3><?php echo e(__('public.portal.no_prescriptions_title', [], $l) ?: 'No Prescriptions'); ?></h3>
        <p><?php echo e(__('public.portal.no_prescriptions_desc', [], $l) ?: 'You have no recorded prescriptions at this time.'); ?></p>
    </div>
</div>
<?php else: ?>
<?php $__currentLoopData = $prescriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="panel mb-4">
    <div class="panel-header">
        <div>
            <h2 class="panel-title">
                <i data-lucide="pill"></i>
                <?php echo e(__('public.portal.rx_heading_prefix', [], $l) ?: 'Prescription —'); ?> <?php echo e($rx->prescribed_at?->format('d M Y') ?? (__('public.portal.rx_unknown_date', [], $l) ?: 'Unknown date')); ?>

            </h2>
            <?php if($rx->facility): ?>
            <p class="text-sm text-muted mt-1"><?php echo e($rx->facility->name); ?></p>
            <?php endif; ?>
        </div>
        <div class="row-actions">
            <?php
                $stCls = match($rx->statusColor()) {
                    'success' => 'badge-success', 'info' => 'badge-info', default => 'badge-neutral'
                };
            ?>
            <span class="badge <?php echo e($stCls); ?>"><?php echo e(\App\Support\Enums::label($rx->status)); ?></span>
            <?php if(in_array($rx->status, ['dispensed', 'active', 'partial'])): ?>
            <button type="button" class="btn btn-secondary btn-sm" onclick="opOpenModal('refill-rx-<?php echo e($rx->id); ?>')">
                <i data-lucide="refresh-cw"></i> <?php echo e(__('public.portal.btn_request_refill', [], $l) ?: 'Request Refill'); ?>

            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php if($rx->items->isNotEmpty()): ?>
    <div class="table-wrapper">
        <table class="data-table" aria-label="<?php echo e(__('public.portal.prescriptions_title', [], $l) ?: 'Prescription items'); ?>">
            <thead>
                <tr>
                    <th><?php echo e(__('public.portal.col_medication', [], $l) ?: 'Medication'); ?></th>
                    <th><?php echo e(__('public.portal.col_dose', [], $l) ?: 'Dose'); ?></th>
                    <th><?php echo e(__('public.portal.col_frequency', [], $l) ?: 'Frequency'); ?></th>
                    <th><?php echo e(__('public.portal.col_duration', [], $l) ?: 'Duration'); ?></th>
                    <th><?php echo e(__('public.portal.col_status', [], $l) ?: 'Status'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $rx->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td data-label="<?php echo e(__('public.portal.col_medication', [], $l) ?: 'Medication'); ?>"><span class="td-strong"><?php echo e($item->drug_name); ?></span></td>
                    <td data-label="<?php echo e(__('public.portal.col_dose', [], $l) ?: 'Dose'); ?>"><?php echo e($item->dose); ?> (<?php echo e($item->route); ?>)</td>
                    <td data-label="<?php echo e(__('public.portal.col_frequency', [], $l) ?: 'Frequency'); ?>"><?php echo e($item->frequency); ?></td>
                    <td data-label="<?php echo e(__('public.portal.col_duration', [], $l) ?: 'Duration'); ?>"><?php echo e($item->duration_days ? $item->duration_days . ' ' . (__('public.portal.lbl_days', [], $l) ?: 'days') : '—'); ?></td>
                    <td data-label="<?php echo e(__('public.portal.col_status', [], $l) ?: 'Status'); ?>">
                        <span class="badge <?php echo e($item->isDispensed() ? 'badge-success' : 'badge-neutral'); ?>"><?php echo e(\App\Support\Enums::label($item->status)); ?></span>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php if(method_exists($prescriptions, 'links') && $prescriptions->hasPages()): ?>
<div class="mt-3">
    <?php echo e($prescriptions->links()); ?>

</div>
<?php endif; ?>

<?php $__currentLoopData = $prescriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php if(in_array($rx->status, ['dispensed', 'active', 'partial'])): ?>
    <div id="refill-rx-<?php echo e($rx->id); ?>" class="modal-backdrop mt-6" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="refill-rx-<?php echo e($rx->id); ?>-title">
            <h3 class="modal__title" id="refill-rx-<?php echo e($rx->id); ?>-title"><i data-lucide="refresh-cw"></i> <?php echo e(__('public.portal.modal_refill_title', [], $l) ?: 'Request refill'); ?></h3>
            <form method="POST" action="<?php echo e(route('portals.patient.prescriptions.refill', $rx->id)); ?>">
                <?php echo csrf_field(); ?>
                <div class="modal__body">
                    <p><?php echo e(__('public.portal.modal_refill_body', [], $l) ?: 'Request a refill for this prescription?'); ?></p>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-ghost" onclick="opCloseModal('refill-rx-<?php echo e($rx->id); ?>')"><?php echo e(__('public.portal.btn_cancel', [], $l) ?: 'Cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo e(__('public.portal.btn_request_refill', [], $l) ?: 'Request Refill'); ?></button>
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

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/patient/prescriptions.blade.php ENDPATH**/ ?>
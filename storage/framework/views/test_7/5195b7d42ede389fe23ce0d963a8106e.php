<?php $__env->startSection('title', __('public.portal.prescriptions_title', [], app()->getLocale()) ?: 'My Prescriptions'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.prescriptions_breadcrumb', [], app()->getLocale()) ?: 'Prescriptions'); ?>

<?php $__env->startSection('head'); ?>
    <?php echo $__env->make('portals.patient._finder_head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('patient_banner'); ?>
    <?php echo $__env->make('partials.guardian-context-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

<div class="pf">

    <div class="pf-head">
        <div>
            <h1><?php echo e(__('public.portal.prescriptions_title', [], $l) ?: 'My Prescriptions'); ?></h1>
            <p class="pf-sub"><?php echo e(__('public.portal.prescriptions_subtitle', [], $l) ?: 'All medications prescribed to you across your care history.'); ?></p>
        </div>
    </div>

    <?php if(!$patient): ?>
    <div class="f-card" style="text-align:center;padding:40px 20px;">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 24], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <h2 style="font-size:1.125rem;font-weight:750;margin:0 0 6px;"><?php echo e(__('public.portal.no_profile_found_title', [], $l) ?: 'No Patient Profile Found'); ?></h2>
        <p class="f-muted" style="margin:0;"><?php echo e(__('public.portal.no_profile_found_desc', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.'); ?></p>
    </div>
    <?php elseif($prescriptions->isEmpty()): ?>
    <div class="f-card" style="text-align:center;padding:40px 20px;">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'pill', 'size' => 24], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <h2 style="font-size:1.125rem;font-weight:750;margin:0 0 6px;"><?php echo e(__('public.portal.no_prescriptions_title', [], $l) ?: 'No Prescriptions'); ?></h2>
        <p class="f-muted" style="margin:0;"><?php echo e(__('public.portal.no_prescriptions_desc', [], $l) ?: 'You have no recorded prescriptions at this time.'); ?></p>
    </div>
    <?php else: ?>

    <div class="pf-stack">
        <?php $__currentLoopData = $prescriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="f-card">
            <h2 class="pf-card-head" style="flex-wrap:wrap;">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'pill', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('public.portal.rx_heading_prefix', [], $l) ?: 'Prescription —'); ?> <?php echo e($rx->prescribed_at?->format('d M Y') ?? (__('public.portal.rx_unknown_date', [], $l) ?: 'Unknown date')); ?>

                <?php
                    $stCls = match($rx->statusColor()) {
                        'success' => 'f-badge--good', 'info' => '', default => 'pf-badge--muted'
                    };
                ?>
                <span class="f-badge <?php echo e($stCls); ?>" style="margin-left:auto;"><?php echo e(\App\Support\Enums::label($rx->status)); ?></span>
            </h2>
            <?php if($rx->facility): ?>
            <p class="f-small f-muted" style="margin:-6px 0 10px;display:flex;align-items:center;gap:5px;">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'map-pin', 'size' => 14], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e($rx->facility->name); ?>

            </p>
            <?php endif; ?>

            <?php if($rx->items->isNotEmpty()): ?>
            <div style="display:grid;gap:8px;">
                <?php $__currentLoopData = $rx->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="display:flex;align-items:baseline;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:10px 0;border-top:1px solid var(--f-line);">
                    <div style="min-width:0;">
                        <div style="font-weight:700;color:var(--f-ink);"><?php echo e($item->drug_name); ?></div>
                        <div class="f-small f-muted">
                            <?php echo e($item->dose); ?> (<?php echo e($item->route); ?>) &middot; <?php echo e($item->frequency); ?>

                            <?php if($item->duration_days): ?>
                                &middot; <?php echo e($item->duration_days); ?> <?php echo e(__('public.portal.lbl_days', [], $l) ?: 'days'); ?>

                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="f-badge <?php echo e($item->isDispensed() ? 'f-badge--good' : 'pf-badge--muted'); ?>"><?php echo e(\App\Support\Enums::label($item->status)); ?></span>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <?php endif; ?>

            <?php if(in_array($rx->status, ['dispensed', 'active', 'partial'])): ?>
            <div class="pf-appt__actions">
                <button type="button" class="f-btn" onclick="pfOpenModal('refill-rx-<?php echo e($rx->id); ?>')">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'refresh-cw', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('public.portal.btn_request_refill', [], $l) ?: 'Request Refill'); ?>

                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <?php if(method_exists($prescriptions, 'links') && $prescriptions->hasPages()): ?>
    <div class="pf-pagination">
        <?php echo e($prescriptions->links()); ?>

    </div>
    <?php endif; ?>

    <?php $__currentLoopData = $prescriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if(in_array($rx->status, ['dispensed', 'active', 'partial'])): ?>
        <div id="refill-rx-<?php echo e($rx->id); ?>" class="pf-modal-backdrop" hidden>
            <div class="pf-modal" role="dialog" aria-modal="true" aria-labelledby="refill-rx-<?php echo e($rx->id); ?>-title">
                <h3 id="refill-rx-<?php echo e($rx->id); ?>-title">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'refresh-cw', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('public.portal.modal_refill_title', [], $l) ?: 'Request refill'); ?>

                </h3>
                <form method="POST" action="<?php echo e(route('portals.patient.prescriptions.refill', $rx->id)); ?>">
                    <?php echo csrf_field(); ?>
                    <p><?php echo e(__('public.portal.modal_refill_body', [], $l) ?: 'Request a refill for this prescription?'); ?></p>
                    <div class="pf-modal__actions">
                        <button type="button" class="f-btn" onclick="pfCloseModal('refill-rx-<?php echo e($rx->id); ?>')"><?php echo e(__('public.portal.btn_cancel', [], $l) ?: 'Cancel'); ?></button>
                        <button type="submit" class="f-btn f-btn--primary"><?php echo e(__('public.portal.btn_request_refill', [], $l) ?: 'Request Refill'); ?></button>
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

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/patient/prescriptions.blade.php ENDPATH**/ ?>
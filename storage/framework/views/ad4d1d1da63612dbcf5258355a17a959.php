<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('title', __('public.pat_timeline_title', [], $l) . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], $l) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.pat_timeline_breadcrumb', [], $l)); ?>

<?php $__env->startSection('patient_banner'); ?>
    <?php echo $__env->make('partials.guardian-context-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.pat_timeline_title', [], $l)); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.pat_timeline_subtitle', [], $l)); ?></p>
    </div>
</div>

<?php if(!$patient): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="alert-circle"></i></div>
        <h3><?php echo e(__('public.portal.no_profile_found_title', [], $l) ?: 'No Patient Profile Found'); ?></h3>
        <p><?php echo e(__('public.portal.no_profile_found_desc', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.'); ?></p>
    </div>
</div>
<?php elseif($events->isEmpty()): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="history"></i></div>
        <h3><?php echo e(__('public.pat_timeline_empty_title', [], $l)); ?></h3>
        <p><?php echo e(__('public.pat_timeline_empty_desc', [], $l)); ?></p>
    </div>
</div>
<?php else: ?>
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="history"></i> <?php echo e(__('public.pat_timeline_title', [], $l)); ?></h2>
        <span class="badge badge-primary"><?php echo e($events->count()); ?></span>
    </div>
    <div class="panel-body">
        <ul style="list-style:none;margin:0;padding:0;display:grid;gap:.25rem;">
            <?php $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $meta = match($event->event_type) {
                        'lab_result'   => ['icon' => 'flask-conical', 'badge' => 'badge-teal',    'label' => __('public.pat_timeline_event_lab', [], $l)],
                        'prescription' => ['icon' => 'pill',          'badge' => 'badge-info',    'label' => __('public.pat_timeline_event_prescription', [], $l)],
                        default        => ['icon' => 'stethoscope',   'badge' => 'badge-primary', 'label' => __('public.pat_timeline_event_visit', [], $l)],
                    };
                    $summary = $event->event_type === 'prescription'
                        ? __('public.pat_timeline_rx_items', ['count' => $event->summary], $l)
                        : $event->summary;
                ?>
                <li style="display:flex;gap:1rem;padding:1rem 0;border-bottom:1px solid var(--p-border, #e5e7eb);">
                    <div style="flex:0 0 auto;width:2.5rem;height:2.5rem;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(15,76,129,.08);color:#0F4C81;">
                        <i data-lucide="<?php echo e($meta['icon']); ?>" style="width:1.1rem;height:1.1rem;"></i>
                    </div>
                    <div style="flex:1 1 auto;min-width:0;">
                        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;">
                            <span class="badge <?php echo e($meta['badge']); ?>"><?php echo e($meta['label']); ?></span>
                            <span class="td-muted"><?php echo e($event->occurred_at?->isoFormat('LL') ?? '—'); ?></span>
                        </div>
                        <div class="td-strong" style="margin-top:.35rem;"><?php echo e($summary ?: '—'); ?></div>
                        <div class="td-muted" style="margin-top:.15rem;">
                            <i data-lucide="building-2" style="width:.85rem;height:.85rem;display:inline;vertical-align:middle;"></i>
                            <?php echo e($event->facility_name ?: __('public.pat_timeline_no_facility', [], $l)); ?>

                        </div>
                    </div>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\timeline.blade.php ENDPATH**/ ?>
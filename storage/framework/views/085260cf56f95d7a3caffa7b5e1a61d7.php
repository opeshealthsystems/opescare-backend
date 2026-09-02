<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('title', __('public.pat_referral_title', [], $l) . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], $l) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.pat_referral_breadcrumb', [], $l)); ?>

<?php $__env->startSection('patient_banner'); ?>
    <?php echo $__env->make('partials.guardian-context-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.pat_referral_title', [], $l)); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.pat_referral_subtitle', [], $l)); ?></p>
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
<?php elseif($referrals->isEmpty()): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="share-2"></i></div>
        <h3><?php echo e(__('public.pat_referral_empty_title', [], $l)); ?></h3>
        <p><?php echo e(__('public.pat_referral_empty_desc', [], $l)); ?></p>
    </div>
</div>
<?php else: ?>
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="share-2"></i> <?php echo e(__('public.pat_referral_title', [], $l)); ?></h2>
        <span class="badge badge-primary"><?php echo e($referrals->count()); ?></span>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?php echo e(__('public.pat_referral_col_from', [], $l)); ?></th>
                    <th><?php echo e(__('public.pat_referral_col_to', [], $l)); ?></th>
                    <th><?php echo e(__('public.pat_referral_col_reason', [], $l)); ?></th>
                    <th><?php echo e(__('public.pat_referral_col_urgency', [], $l)); ?></th>
                    <th><?php echo e(__('public.pat_referral_col_status', [], $l)); ?></th>
                    <th><?php echo e(__('public.pat_referral_col_date', [], $l)); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $referrals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $referral): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $statusBadge = match($referral->status) {
                        'accepted', 'completed' => 'badge-success',
                        'rejected', 'cancelled' => 'badge-danger',
                        'pending'               => 'badge-warning',
                        default                 => 'badge-primary',
                    };
                    $urgencyBadge = match($referral->urgency) {
                        'emergency', 'urgent' => 'badge-danger',
                        'priority'            => 'badge-warning',
                        default               => 'badge-teal',
                    };
                ?>
                <tr>
                    <td data-label="<?php echo e(__('public.pat_referral_col_from', [], $l)); ?>">
                        <span class="td-strong"><?php echo e($referral->referringFacility?->name ?? __('public.pat_referral_unknown', [], $l)); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.pat_referral_col_to', [], $l)); ?>">
                        <span class="td-strong"><?php echo e($referral->receivingFacility?->name ?? $referral->receiving_specialty ?? __('public.pat_referral_unknown', [], $l)); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.pat_referral_col_reason', [], $l)); ?>">
                        <span class="td-muted"><?php echo e($referral->reason ?: '—'); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.pat_referral_col_urgency', [], $l)); ?>">
                        <span class="badge <?php echo e($urgencyBadge); ?>"><?php echo e(\App\Support\Enums::label($referral->urgency ?? 'routine', 'urgency')); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.pat_referral_col_status', [], $l)); ?>">
                        <span class="badge <?php echo e($statusBadge); ?>"><?php echo e(\App\Support\Enums::label($referral->status ?? 'pending')); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.pat_referral_col_date', [], $l)); ?>">
                        <span class="td-muted"><?php echo e($referral->created_at?->isoFormat('LL') ?? '—'); ?></span>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\referrals.blade.php ENDPATH**/ ?>
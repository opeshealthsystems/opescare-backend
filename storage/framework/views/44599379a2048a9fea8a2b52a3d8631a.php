<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('title', __('public.pat_survey_title', [], $l) . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], $l) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.pat_survey_breadcrumb', [], $l)); ?>

<?php $__env->startSection('patient_banner'); ?>
    <?php echo $__env->make('partials.guardian-context-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.pat_survey_title', [], $l)); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.pat_survey_subtitle', [], $l)); ?></p>
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
<?php elseif($surveys->isEmpty()): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="clipboard-check"></i></div>
        <h3><?php echo e(__('public.pat_survey_empty_title', [], $l)); ?></h3>
        <p><?php echo e(__('public.pat_survey_empty_desc', [], $l)); ?></p>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info mb-4">
    <i data-lucide="info"></i>
    <div><?php echo e(__('public.pat_survey_status_note', [], $l)); ?></div>
</div>
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="clipboard-check"></i> <?php echo e(__('public.pat_survey_title', [], $l)); ?></h2>
        <span class="badge badge-primary"><?php echo e($surveys->count()); ?></span>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?php echo e(__('public.pat_survey_col_name', [], $l)); ?></th>
                    <th><?php echo e(__('public.pat_survey_col_facility', [], $l)); ?></th>
                    <th><?php echo e(__('public.pat_survey_col_sent', [], $l)); ?></th>
                    <th><?php echo e(__('public.pat_survey_col_status', [], $l)); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $surveys; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $survey): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $statusBadge = match($survey->status) {
                        'completed' => 'badge-success',
                        'expired'   => 'badge-danger',
                        'sent'      => 'badge-warning',
                        default     => 'badge-primary',
                    };
                    $name = ucwords(str_replace(['_', '-'], ' ', (string) ($survey->template_key ?? '—')));
                ?>
                <tr>
                    <td data-label="<?php echo e(__('public.pat_survey_col_name', [], $l)); ?>"><span class="td-strong"><?php echo e($name); ?></span></td>
                    <td data-label="<?php echo e(__('public.pat_survey_col_facility', [], $l)); ?>"><span class="td-muted"><?php echo e($survey->facility?->name ?? '—'); ?></span></td>
                    <td data-label="<?php echo e(__('public.pat_survey_col_sent', [], $l)); ?>"><span class="td-muted"><?php echo e($survey->sent_at?->isoFormat('LL') ?? '—'); ?></span></td>
                    <td data-label="<?php echo e(__('public.pat_survey_col_status', [], $l)); ?>">
                        <span class="badge <?php echo e($statusBadge); ?>"><?php echo e(\App\Support\Enums::label($survey->status ?? 'sent')); ?></span>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\surveys.blade.php ENDPATH**/ ?>
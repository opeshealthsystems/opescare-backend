<?php $__env->startSection('title', __('public.portal.conditions_title', [], app()->getLocale()) ?: 'My Conditions'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.conditions_breadcrumb', [], app()->getLocale()) ?: 'Conditions'); ?>

<?php $__env->startSection('patient_banner'); ?>
    <?php echo $__env->make('partials.guardian-context-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.portal.conditions_title', [], $l) ?: 'My Conditions'); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.portal.conditions_subtitle', [], $l) ?: 'Diagnoses and clinical conditions recorded by your healthcare providers.'); ?></p>
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
<?php elseif($conditions->isEmpty()): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="check-circle"></i></div>
        <h3><?php echo e(__('public.portal.no_conditions_title', [], $l) ?: 'No Conditions on Record'); ?></h3>
        <p><?php echo e(__('public.portal.no_conditions_desc', [], $l) ?: 'No diagnoses or clinical conditions have been recorded for your profile.'); ?></p>
    </div>
</div>
<?php else: ?>

<?php
    $active  = $conditions->whereIn('status', ['active', 'chronic']);
    $resolved = $conditions->where('status', 'resolved');
?>

<?php if($active->isNotEmpty()): ?>
<div class="panel mb-6">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="stethoscope"></i> <?php echo e(__('public.portal.panel_active_conditions', [], $l) ?: 'Active & Chronic Conditions'); ?> (<?php echo e($active->count()); ?>)</h2>
    </div>
    <div class="table-wrapper">
        <table class="data-table" aria-label="<?php echo e(__('public.portal.panel_active_conditions', [], $l) ?: 'Active and chronic conditions'); ?>">
            <thead>
                <tr>
                    <th><?php echo e(__('public.portal.col_condition', [], $l) ?: 'Condition'); ?></th>
                    <th><?php echo e(__('public.portal.col_code', [], $l) ?: 'Code'); ?></th>
                    <th><?php echo e(__('public.portal.col_status', [], $l) ?: 'Status'); ?></th>
                    <th><?php echo e(__('public.portal.col_recorded', [], $l) ?: 'Recorded'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $active; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $condition): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td data-label="<?php echo e(__('public.portal.col_condition', [], $l) ?: 'Condition'); ?>"><span class="td-strong"><?php echo e($condition->display_name ?? $condition->code ?? '—'); ?></span></td>
                    <td data-label="<?php echo e(__('public.portal.col_code', [], $l) ?: 'Code'); ?>"><span class="td-mono"><?php echo e($condition->code ?? '—'); ?></span></td>
                    <td data-label="<?php echo e(__('public.portal.col_status', [], $l) ?: 'Status'); ?>">
                        <span class="badge <?php echo e($condition->status === 'chronic' ? 'badge-teal' : 'badge-primary'); ?>"><?php echo e(\App\Support\Enums::label($condition->status)); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.portal.col_recorded', [], $l) ?: 'Recorded'); ?>"><span class="td-muted"><?php echo e($condition->created_at?->format('d M Y') ?? '—'); ?></span></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if($resolved->isNotEmpty()): ?>
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="check-circle"></i> <?php echo e(__('public.portal.panel_resolved_conditions', [], $l) ?: 'Resolved Conditions'); ?> (<?php echo e($resolved->count()); ?>)</h2>
    </div>
    <div class="table-wrapper">
        <table class="data-table" aria-label="<?php echo e(__('public.portal.panel_resolved_conditions', [], $l) ?: 'Resolved conditions'); ?>">
            <thead>
                <tr>
                    <th><?php echo e(__('public.portal.col_condition', [], $l) ?: 'Condition'); ?></th>
                    <th><?php echo e(__('public.portal.col_code', [], $l) ?: 'Code'); ?></th>
                    <th><?php echo e(__('public.portal.col_recorded', [], $l) ?: 'Recorded'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $resolved; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $condition): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td data-label="<?php echo e(__('public.portal.col_condition', [], $l) ?: 'Condition'); ?>"><span class="td-muted"><?php echo e($condition->display_name ?? $condition->code ?? '—'); ?></span></td>
                    <td data-label="<?php echo e(__('public.portal.col_code', [], $l) ?: 'Code'); ?>"><span class="td-mono"><?php echo e($condition->code ?? '—'); ?></span></td>
                    <td data-label="<?php echo e(__('public.portal.col_recorded', [], $l) ?: 'Recorded'); ?>"><span class="td-muted"><?php echo e($condition->created_at?->format('d M Y') ?? '—'); ?></span></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\clinical.blade.php ENDPATH**/ ?>
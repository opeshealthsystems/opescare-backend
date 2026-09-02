<?php $__env->startSection('title', __('public.portal.immunizations_title', [], app()->getLocale()) ?: 'My Immunizations'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.immunizations_breadcrumb', [], app()->getLocale()) ?: 'Immunizations'); ?>

<?php $__env->startSection('patient_banner'); ?>
    <?php echo $__env->make('partials.guardian-context-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.portal.immunizations_title', [], $l) ?: 'My Immunizations'); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.portal.immunizations_subtitle', [], $l) ?: 'Vaccination history recorded by your healthcare providers.'); ?></p>
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
<?php elseif($immunizations->isEmpty()): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="syringe"></i></div>
        <h3><?php echo e(__('public.portal.no_immunizations_title', [], $l) ?: 'No Immunizations on Record'); ?></h3>
        <p><?php echo e(__('public.portal.no_immunizations_desc', [], $l) ?: 'No vaccinations have been recorded for your profile. Ask your healthcare provider to record your vaccination history.'); ?></p>
    </div>
</div>
<?php else: ?>

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="syringe"></i> <?php echo e(__('public.portal.panel_vaccination_history', [], $l) ?: 'Vaccination History'); ?> (<?php echo e($immunizations->count()); ?>)</h2>
    </div>
    <div class="table-wrapper">
        <table class="data-table" aria-label="<?php echo e(__('public.portal.panel_vaccination_history', [], $l) ?: 'Vaccination history'); ?>">
            <thead>
                <tr>
                    <th><?php echo e(__('public.portal.col_vaccine', [], $l) ?: 'Vaccine'); ?></th>
                    <th><?php echo e(__('public.portal.col_lot_number', [], $l) ?: 'Lot Number'); ?></th>
                    <th><?php echo e(__('public.portal.col_dose', [], $l) ?: 'Dose'); ?></th>
                    <th><?php echo e(__('public.portal.col_administered', [], $l) ?: 'Administered'); ?></th>
                    <th><?php echo e(__('public.portal.col_status', [], $l) ?: 'Status'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $immunizations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $imm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td data-label="<?php echo e(__('public.portal.col_vaccine', [], $l) ?: 'Vaccine'); ?>"><span class="td-strong"><?php echo e($imm->vaccine_name); ?></span></td>
                    <td data-label="<?php echo e(__('public.portal.col_lot_number', [], $l) ?: 'Lot Number'); ?>"><span class="td-mono"><?php echo e($imm->lot_number ?? '—'); ?></span></td>
                    <td data-label="<?php echo e(__('public.portal.col_dose', [], $l) ?: 'Dose'); ?>"><span class="td-muted"><?php echo e($imm->dose_number ?? '—'); ?></span></td>
                    <td data-label="<?php echo e(__('public.portal.col_administered', [], $l) ?: 'Administered'); ?>"><span class="td-muted"><?php echo e($imm->administered_at?->format('d M Y') ?? '—'); ?></span></td>
                    <td data-label="<?php echo e(__('public.portal.col_status', [], $l) ?: 'Status'); ?>">
                        <span class="badge badge-success"><?php echo e(\App\Support\Enums::label($imm->status ?? 'completed')); ?></span>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\immunizations.blade.php ENDPATH**/ ?>
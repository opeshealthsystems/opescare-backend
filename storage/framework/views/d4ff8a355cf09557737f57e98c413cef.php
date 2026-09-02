<?php $__env->startSection('title', __('public.portal.allergies_title', [], app()->getLocale()) ?: 'My Allergies'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.allergies_breadcrumb', [], app()->getLocale()) ?: 'Allergies'); ?>

<?php $__env->startSection('patient_banner'); ?>
    <?php echo $__env->make('partials.guardian-context-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.portal.allergies_title', [], $l) ?: 'My Allergies'); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.portal.allergies_subtitle', [], $l) ?: 'All known allergies and adverse reactions on your health record.'); ?></p>
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
<?php elseif($allergies->isEmpty()): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="check-circle"></i></div>
        <h3><?php echo e(__('public.portal.no_allergies_title', [], $l) ?: 'No Allergies on Record'); ?></h3>
        <p><?php echo e(__('public.portal.no_allergies_desc', [], $l) ?: 'No known allergies have been recorded for your profile. If you have allergies, please inform your healthcare provider.'); ?></p>
    </div>
</div>
<?php else: ?>

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="zap"></i> <?php echo e(__('public.portal.allergy_list_panel', [], $l) ?: 'Allergy List'); ?> (<?php echo e($allergies->count()); ?>)</h2>
    </div>
    <div class="table-wrapper">
        <table class="data-table" aria-label="<?php echo e(__('public.portal.allergy_list_panel', [], $l) ?: 'Allergy list'); ?>">
            <thead>
                <tr>
                    <th><?php echo e(__('public.portal.col_substance', [], $l) ?: 'Substance'); ?></th>
                    <th><?php echo e(__('public.portal.col_severity', [], $l) ?: 'Severity'); ?></th>
                    <th><?php echo e(__('public.portal.col_status', [], $l) ?: 'Status'); ?></th>
                    <th><?php echo e(__('public.portal.col_recorded', [], $l) ?: 'Recorded'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $allergies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allergy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $sev = strtolower($allergy->severity ?? '');
                    $sevCls = match($sev) {
                        'life-threatening', 'severe', 'high' => 'badge-danger',
                        'moderate', 'medium'                 => 'badge-warning',
                        default                              => 'badge-neutral',
                    };
                    $isCritical = in_array($sev, ['life-threatening', 'severe', 'high']);
                ?>
                <tr>
                    <td data-label="<?php echo e(__('public.portal.col_substance', [], $l) ?: 'Substance'); ?>">
                        <span class="cell-with-icon">
                            <?php if($isCritical): ?>
                                <i data-lucide="alert-triangle"></i>
                            <?php endif; ?>
                            <span class="td-strong"><?php echo e($allergy->substance); ?></span>
                        </span>
                    </td>
                    <td data-label="<?php echo e(__('public.portal.col_severity', [], $l) ?: 'Severity'); ?>">
                        <span class="badge <?php echo e($sevCls); ?>"><?php echo e(\App\Support\Enums::label($allergy->severity ?? 'unknown', 'severity')); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.portal.col_status', [], $l) ?: 'Status'); ?>">
                        <span class="badge <?php echo e($allergy->status === 'active' ? 'badge-success' : 'badge-neutral'); ?>"><?php echo e(\App\Support\Enums::label($allergy->status ?? 'active')); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.portal.col_recorded', [], $l) ?: 'Recorded'); ?>">
                        <span class="td-muted"><?php echo e($allergy->created_at?->format('d M Y') ?? '—'); ?></span>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>

<div class="alert alert-warning mt-6">
    <i data-lucide="info"></i>
    <div><?php echo e(__('public.portal.allergy_readonly_hint', [], $l) ?: 'Allergy records are maintained by your healthcare providers. To add or update an allergy, please contact the facility that manages your record.'); ?></div>
</div>

<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\allergies.blade.php ENDPATH**/ ?>
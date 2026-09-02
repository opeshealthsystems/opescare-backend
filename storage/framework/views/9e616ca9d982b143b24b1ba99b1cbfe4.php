<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('title', __('public.pat_settings_title', [], $l) . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], $l) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.pat_settings_breadcrumb', [], $l)); ?>

<?php $__env->startSection('patient_banner'); ?>
    <?php echo $__env->make('partials.guardian-context-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.pat_settings_title', [], $l)); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.pat_settings_subtitle', [], $l)); ?></p>
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
<?php else: ?>
<form method="POST" action="<?php echo e(route('portals.patient.settings.update')); ?>">
    <?php echo csrf_field(); ?>

    <?php
        $prefs = [
            'push_appointments'     => ['icon' => 'calendar-check-2', 'label' => __('public.pat_settings_appointments', [], $l)],
            'push_lab_results'      => ['icon' => 'flask-conical',    'label' => __('public.pat_settings_lab_results', [], $l)],
            'push_prescriptions'    => ['icon' => 'pill',             'label' => __('public.pat_settings_prescriptions', [], $l)],
            'push_billing'          => ['icon' => 'credit-card',      'label' => __('public.pat_settings_billing', [], $l)],
            'push_consent_requests' => ['icon' => 'shield-check',     'label' => __('public.pat_settings_consent', [], $l)],
        ];
    ?>

    <div class="panel mb-4">
        <div class="panel-header">
            <h2 class="panel-title"><i data-lucide="bell"></i> <?php echo e(__('public.pat_settings_notif_title', [], $l)); ?></h2>
        </div>
        <div class="panel-body">
            <ul style="list-style:none;margin:0;padding:0;display:grid;gap:.25rem;">
                <?php $__currentLoopData = $prefs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.85rem 0;border-bottom:1px solid var(--p-border, #e5e7eb);">
                    <span style="display:flex;align-items:center;gap:.6rem;">
                        <i data-lucide="<?php echo e($meta['icon']); ?>" style="width:1.1rem;height:1.1rem;color:#0F4C81;"></i>
                        <span class="td-strong"><?php echo e($meta['label']); ?></span>
                    </span>
                    <label class="switch">
                        <input type="checkbox" name="<?php echo e($field); ?>" value="1" <?php echo e($settings?->{$field} ? 'checked' : ''); ?> aria-label="<?php echo e($meta['label']); ?>">
                        <span class="switch__track"></span>
                    </label>
                </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    </div>

    <div class="panel mb-4">
        <div class="panel-header">
            <h2 class="panel-title"><i data-lucide="palette"></i> <?php echo e(__('public.pat_settings_appearance_title', [], $l)); ?></h2>
        </div>
        <div class="panel-body">
            <label class="td-strong" for="preferred_theme" style="display:block;margin-bottom:.4rem;"><?php echo e(__('public.pat_settings_theme', [], $l)); ?></label>
            <select id="preferred_theme" name="preferred_theme" class="form-control" style="max-width:260px;">
                <option value="system" <?php echo e(($settings?->preferred_theme ?? 'system') === 'system' ? 'selected' : ''); ?>><?php echo e(__('public.pat_settings_theme_system', [], $l)); ?></option>
                <option value="light"  <?php echo e($settings?->preferred_theme === 'light' ? 'selected' : ''); ?>><?php echo e(__('public.pat_settings_theme_light', [], $l)); ?></option>
                <option value="dark"   <?php echo e($settings?->preferred_theme === 'dark' ? 'selected' : ''); ?>><?php echo e(__('public.pat_settings_theme_dark', [], $l)); ?></option>
            </select>
        </div>
    </div>

    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:1rem;">
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i> <?php echo e(__('public.pat_settings_save', [], $l)); ?>

        </button>
        <span class="page-subtitle" style="margin:0;"><?php echo e(__('public.pat_settings_save_hint', [], $l)); ?></span>
    </div>
</form>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\settings.blade.php ENDPATH**/ ?>
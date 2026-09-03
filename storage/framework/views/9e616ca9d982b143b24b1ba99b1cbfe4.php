<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('title', __('public.pat_settings_title', [], $l) . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], $l) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.pat_settings_breadcrumb', [], $l)); ?>

<?php $__env->startSection('head'); ?>
    <?php echo $__env->make('portals.patient._finder_head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('patient_banner'); ?>
    <?php echo $__env->make('partials.guardian-context-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="pf pf--form">

    <div class="pf-head">
        <div>
            <h1><?php echo e(__('public.pat_settings_title', [], $l)); ?></h1>
            <p class="pf-sub"><?php echo e(__('public.pat_settings_subtitle', [], $l)); ?></p>
        </div>
    </div>

    <?php if(!$patient): ?>
    <div class="f-card" style="text-align:center;padding:40px 20px;">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-triangle', 'size' => 24], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <h2 style="font-size:1.125rem;font-weight:750;margin:0 0 6px;"><?php echo e(__('public.portal.no_profile_found_title', [], $l) ?: 'No Patient Profile Found'); ?></h2>
        <p class="f-muted" style="margin:0;"><?php echo e(__('public.portal.no_profile_found_desc', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.'); ?></p>
    </div>
    <?php else: ?>
    <form method="POST" action="<?php echo e(route('portals.patient.settings.update')); ?>">
        <?php echo csrf_field(); ?>

        <?php
            $prefs = [
                'push_appointments'     => ['icon' => 'calendar',     'label' => __('public.pat_settings_appointments', [], $l)],
                'push_lab_results'      => ['icon' => 'droplet',      'label' => __('public.pat_settings_lab_results', [], $l)],
                'push_prescriptions'    => ['icon' => 'pill',         'label' => __('public.pat_settings_prescriptions', [], $l)],
                'push_billing'          => ['icon' => 'info',         'label' => __('public.pat_settings_billing', [], $l)],
                'push_consent_requests' => ['icon' => 'shield-check', 'label' => __('public.pat_settings_consent', [], $l)],
            ];
        ?>

        <div class="f-card" style="margin-bottom:12px;">
            <h2 class="pf-card-head">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'bell', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('public.pat_settings_notif_title', [], $l)); ?>

            </h2>
            <div>
                <?php $__currentLoopData = $prefs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="pf-setting">
                    <span class="pf-setting__label">
                        <?php echo $__env->make('partials.finder.icon', ['name' => $meta['icon'], 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo e($meta['label']); ?>

                    </span>
                    <label class="pf-switch">
                        <input type="checkbox" name="<?php echo e($field); ?>" value="1" <?php echo e($settings?->{$field} ? 'checked' : ''); ?> aria-label="<?php echo e($meta['label']); ?>">
                        <i></i>
                    </label>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <div class="f-card" style="margin-bottom:16px;">
            <h2 class="pf-card-head"><?php echo e(__('public.pat_settings_appearance_title', [], $l)); ?></h2>
            <div class="f-field" style="margin-bottom:0;">
                <label class="f-label" for="preferred_theme"><?php echo e(__('public.pat_settings_theme', [], $l)); ?></label>
                <select id="preferred_theme" name="preferred_theme" class="f-input" style="max-width:280px;">
                    <option value="system" <?php echo e(($settings?->preferred_theme ?? 'system') === 'system' ? 'selected' : ''); ?>><?php echo e(__('public.pat_settings_theme_system', [], $l)); ?></option>
                    <option value="light"  <?php echo e($settings?->preferred_theme === 'light' ? 'selected' : ''); ?>><?php echo e(__('public.pat_settings_theme_light', [], $l)); ?></option>
                    <option value="dark"   <?php echo e($settings?->preferred_theme === 'dark' ? 'selected' : ''); ?>><?php echo e(__('public.pat_settings_theme_dark', [], $l)); ?></option>
                </select>
            </div>
        </div>

        <div class="pf-actions">
            <button type="submit" class="f-btn f-btn--primary" style="min-height:48px;">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('public.pat_settings_save', [], $l)); ?>

            </button>
            <span class="f-small f-muted"><?php echo e(__('public.pat_settings_save_hint', [], $l)); ?></span>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\settings.blade.php ENDPATH**/ ?>
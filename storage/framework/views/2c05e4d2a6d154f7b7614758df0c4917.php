<?php $__env->startSection('title', __('notifications.prefs_title') . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('notifications.prefs_title')); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('notifications.prefs_title')); ?></h1>
        <p class="page-subtitle"><?php echo e(__('notifications.prefs_subtitle')); ?></p>
    </div>
    <a href="<?php echo e(route('portals.patient.notifications')); ?>" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> <?php echo e(__('notifications.title')); ?>

    </a>
</div>

<?php if(session('success')): ?>
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('portals.patient.notifications.preferences.update')); ?>">
    <?php echo csrf_field(); ?>

    <div class="panel mb-4">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="sliders-horizontal"></i> <?php echo e(__('notifications.prefs_channels')); ?></h3></div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php echo e(__('notifications.category')); ?></th>
                        <?php $__currentLoopData = $channels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><th style="text-align:center;"><?php echo e(__('notifications.channel_' . $ch)); ?></th><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td data-label="<?php echo e(__('notifications.category')); ?>"><strong><?php echo e(__('notifications.cat_' . $cat)); ?></strong></td>
                        <?php $__currentLoopData = $channels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <td style="text-align:center;" data-label="<?php echo e(__('notifications.channel_' . $ch)); ?>">
                            <input type="checkbox" name="prefs[<?php echo e($cat); ?>][<?php echo e($ch); ?>]" value="1" <?php if($prefs[$cat][$ch] ?? false): echo 'checked'; endif; ?>>
                        </td>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel mb-4" style="max-width:420px;">
        <div class="panel-body">
            <label class="form-label" for="language"><?php echo e(__('notifications.language')); ?></label>
            <select id="language" name="language" class="form-control">
                <option value="en" <?php if($language === 'en'): echo 'selected'; endif; ?>>English</option>
                <option value="fr" <?php if($language === 'fr'): echo 'selected'; endif; ?>>Français</option>
            </select>
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> <?php echo e(__('notifications.prefs_save')); ?></button>
</form>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\notification_preferences.blade.php ENDPATH**/ ?>
<?php $__env->startSection('title', __('notifications.prefs_title') . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('notifications.prefs_title')); ?>

<?php $__env->startSection('head'); ?>
    <?php echo $__env->make('portals.patient._finder_head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="pf pf--form">

    <div class="pf-head">
        <div>
            <h1><?php echo e(__('notifications.prefs_title')); ?></h1>
            <p class="pf-sub"><?php echo e(__('notifications.prefs_subtitle')); ?></p>
        </div>
        <a href="<?php echo e(route('portals.patient.notifications')); ?>" class="f-btn">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('notifications.title')); ?>

        </a>
    </div>

    <form method="POST" action="<?php echo e(route('portals.patient.notifications.preferences.update')); ?>">
        <?php echo csrf_field(); ?>

        <div class="f-card" style="margin-bottom:12px;">
            <h2 class="pf-card-head">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'sliders-horizontal', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('notifications.prefs_channels')); ?>

            </h2>
            <div class="pf-table-wrap">
                <table class="pf-table">
                    <thead>
                        <tr>
                            <th><?php echo e(__('notifications.category')); ?></th>
                            <?php $__currentLoopData = $channels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><th style="text-align:center;"><?php echo e(__('notifications.channel_' . $ch)); ?></th><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><strong><?php echo e(__('notifications.cat_' . $cat)); ?></strong></td>
                            <?php $__currentLoopData = $channels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <td style="text-align:center;">
                                <input type="checkbox" name="prefs[<?php echo e($cat); ?>][<?php echo e($ch); ?>]" value="1"
                                       aria-label="<?php echo e(__('notifications.cat_' . $cat)); ?> — <?php echo e(__('notifications.channel_' . $ch)); ?>"
                                       <?php if($prefs[$cat][$ch] ?? false): echo 'checked'; endif; ?>>
                            </td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="f-card" style="margin-bottom:16px;">
            <div class="f-field" style="margin-bottom:0;">
                <label class="f-label" for="language">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'globe', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('notifications.language')); ?>

                </label>
                <select id="language" name="language" class="f-input" style="max-width:280px;">
                    <option value="en" <?php if($language === 'en'): echo 'selected'; endif; ?>>English</option>
                    <option value="fr" <?php if($language === 'fr'): echo 'selected'; endif; ?>>Français</option>
                </select>
            </div>
        </div>

        <button type="submit" class="f-btn f-btn--primary" style="min-height:48px;">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('notifications.prefs_save')); ?>

        </button>
    </form>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\notification_preferences.blade.php ENDPATH**/ ?>
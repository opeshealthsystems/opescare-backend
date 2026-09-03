<?php $__env->startSection('title', __('finder_story.redirect.titles.security')); ?>
<?php $__env->startSection('description', __('finder_story.redirect.meta_description')); ?>


<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('public.partials._platform_redirect', ['pageKey' => 'security', 'showPrivacy' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.finder', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/public/security.blade.php ENDPATH**/ ?>
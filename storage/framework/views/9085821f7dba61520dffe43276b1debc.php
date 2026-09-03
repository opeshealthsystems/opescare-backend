
<?php $fsPrimary = $primary ?? 'directory'; ?>
<div class="fs-cta-row">
    <a class="f-btn <?php echo e($fsPrimary === 'medicine' ? 'f-btn--primary' : ''); ?>" href="<?php echo e(route('public.network.medicine-finder')); ?>">
        <?php echo $__env->make('partials.finder.icon', ['name' => 'pill', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo e(__('finder_story.cta.medicine')); ?>

    </a>
    <a class="f-btn <?php echo e($fsPrimary === 'blood' ? 'f-btn--primary' : ''); ?>" href="<?php echo e(route('public.network.blood-finder')); ?>">
        <?php echo $__env->make('partials.finder.icon', ['name' => 'droplet', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo e(__('finder_story.cta.blood')); ?>

    </a>
    <a class="f-btn <?php echo e($fsPrimary === 'directory' ? 'f-btn--primary' : ''); ?>" href="<?php echo e(route('public.care-map')); ?>">
        <?php echo $__env->make('partials.finder.icon', ['name' => 'map-pin', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo e(__('finder_story.cta.directory')); ?>

    </a>
    <a class="f-btn f-btn--ghost" href="<?php echo e(route('public.how-it-works')); ?>">
        <?php echo e(__('finder_story.cta.how')); ?>

    </a>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\public\partials\_finder_ctas.blade.php ENDPATH**/ ?>
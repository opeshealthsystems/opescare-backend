<?php $__env->startSection('title', __('finder_story.patients.page_title')); ?>
<?php $__env->startSection('description', __('finder_story.patients.meta_description')); ?>

<?php $__env->startSection('content'); ?>

    
    <style>
        .fs-narrow { max-width: 720px; }
        .fs-cta-row { display: flex; flex-wrap: wrap; gap: 0.625rem; }
        .fs-note { display: flex; align-items: flex-start; gap: 0.625rem; }
        .fs-note > svg { flex-shrink: 0; margin-top: 0.2rem; color: var(--f-warn); }
    </style>

    <section class="f-section f-section--surface">
        <div class="f-container">
            <div class="fs-narrow">
                <p class="f-eyebrow"><?php echo e(__('finder_story.patients.eyebrow')); ?></p>
                <h1 class="f-h1"><?php echo e(__('finder_story.patients.title')); ?></h1>
                <p class="f-lead"><?php echo e(__('finder_story.patients.lead')); ?></p>
                <?php echo $__env->make('public.partials._finder_ctas', ['primary' => 'medicine'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
    </section>

    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <p class="fs-note">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'phone', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <span><?php echo e(__('finder_story.patients.honest_p')); ?></span>
                </p>
            </div>
        </div>
    </section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.finder', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\public\solutions\patients.blade.php ENDPATH**/ ?>
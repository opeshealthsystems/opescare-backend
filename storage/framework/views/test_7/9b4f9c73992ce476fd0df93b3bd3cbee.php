<?php $__env->startSection('title', __('finder_story.about.page_title')); ?>
<?php $__env->startSection('description', __('finder_story.about.meta_description')); ?>

<?php $__env->startSection('content'); ?>

    
    <style>
        .fs-narrow { max-width: 720px; }
        .fs-source { display: flex; align-items: flex-start; gap: 0.875rem; }
        .fs-source > svg { flex-shrink: 0; margin-top: 0.25rem; color: var(--f-brand); }
        .fs-cta-row { display: flex; flex-wrap: wrap; gap: 0.625rem; }
        .fs-note { display: flex; align-items: flex-start; gap: 0.625rem; }
        .fs-note > svg { flex-shrink: 0; margin-top: 0.2rem; }
    </style>

    
    <section class="f-section f-section--surface">
        <div class="f-container">
            <div class="fs-narrow">
                <p class="f-eyebrow"><?php echo e(__('finder_story.about.eyebrow')); ?></p>
                <h1 class="f-h1"><?php echo e(__('finder_story.about.title')); ?></h1>
                <p class="f-lead"><?php echo e(__('finder_story.about.lead')); ?></p>
            </div>
        </div>
    </section>

    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <h2 class="f-h2"><?php echo e(__('finder_story.about.what_title')); ?></h2>
                <p><?php echo e(__('finder_story.about.what_p1')); ?></p>
                <p><?php echo e(__('finder_story.about.what_p2')); ?></p>

                <h2 class="f-h2"><?php echo e(__('finder_story.about.free_title')); ?></h2>
                <p><?php echo e(__('finder_story.about.free_p')); ?></p>
            </div>
        </div>
    </section>

    
    <section class="f-section f-section--wash">
        <div class="f-container">
            <div class="fs-narrow">
                <h2 class="f-h2"><?php echo e(__('finder_story.about.data_title')); ?></h2>
                <p class="f-lead"><?php echo e(__('finder_story.about.data_lead')); ?></p>
            </div>

            <div class="f-grid">
                <div class="f-card fs-source">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'building-2', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div>
                        <h3><?php echo e(__('finder_story.about.data_registry_title')); ?></h3>
                        <p class="f-muted"><?php echo e(__('finder_story.about.data_registry_p')); ?></p>
                    </div>
                </div>
                <div class="f-card fs-source">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'map-pin', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div>
                        <h3><?php echo e(__('finder_story.about.data_osm_title')); ?></h3>
                        <p class="f-muted"><?php echo e(__('finder_story.about.data_osm_p')); ?></p>
                    </div>
                </div>
                <div class="f-card fs-source">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'refresh-cw', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div>
                        <h3><?php echo e(__('finder_story.about.data_self_title')); ?></h3>
                        <p class="f-muted"><?php echo e(__('finder_story.about.data_self_p')); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <h2 class="f-h2"><?php echo e(__('finder_story.about.honest_title')); ?></h2>
                <p class="fs-note">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'info', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <span><?php echo e(__('finder_story.about.honest_p1')); ?></span>
                </p>
                <p class="fs-note">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'phone', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <span><?php echo e(__('finder_story.about.honest_p2')); ?></span>
                </p>
            </div>
        </div>
    </section>

    
    <section class="f-section f-section--surface">
        <div class="f-container">
            <h2 class="f-h2"><?php echo e(__('finder_story.about.who_title')); ?></h2>
            <div class="f-grid">
                <div class="f-card">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <h3><?php echo e(__('finder_story.about.who_patients_title')); ?></h3>
                    <p class="f-muted"><?php echo e(__('finder_story.about.who_patients_p')); ?></p>
                </div>
                <div class="f-card">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'users', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <h3><?php echo e(__('finder_story.about.who_caregivers_title')); ?></h3>
                    <p class="f-muted"><?php echo e(__('finder_story.about.who_caregivers_p')); ?></p>
                </div>
                <div class="f-card">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'building-2', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <h3><?php echo e(__('finder_story.about.who_facilities_title')); ?></h3>
                    <p class="f-muted"><?php echo e(__('finder_story.about.who_facilities_p')); ?></p>
                    <a class="f-btn f-btn--ghost" href="<?php echo e(route('public.solutions.pharmacies')); ?>">
                        <?php echo e(__('finder_story.about.who_facilities_cta')); ?>

                        <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    
    <section class="f-section f-section--tight">
        <div class="f-container">
            <h2 class="f-h2"><?php echo e(__('finder_story.about.cta_title')); ?></h2>
            <?php echo $__env->make('public.partials._finder_ctas', ['primary' => 'medicine'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.finder', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/public/about.blade.php ENDPATH**/ ?>
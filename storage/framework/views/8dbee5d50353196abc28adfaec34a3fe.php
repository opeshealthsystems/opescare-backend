<?php $__env->startSection('title', __('finder_story.hospitals.page_title')); ?>
<?php $__env->startSection('description', __('finder_story.hospitals.meta_description')); ?>

<?php $__env->startSection('content'); ?>

    
    <style>
        .fs-narrow { max-width: 720px; }
        .fs-steps { list-style: none; padding: 0; margin: 0 0 0.75rem; counter-reset: fs-step; }
        .fs-steps li { display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.375rem 0; }
        .fs-steps li::before {
            counter-increment: fs-step;
            content: counter(fs-step);
            flex-shrink: 0;
            width: 1.75rem; height: 1.75rem;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 50%;
            background: var(--f-wash); color: var(--f-brand-ink);
            font-weight: 600; font-size: 0.875rem;
        }
        .fs-cta-row { display: flex; flex-wrap: wrap; gap: 0.625rem; }
        .fs-benefit > svg { color: var(--f-brand); }
        .fs-platform-cta { display: inline-flex; align-items: center; gap: 0.5rem; }
    </style>

    
    <section class="f-section f-section--surface">
        <div class="f-container">
            <div class="fs-narrow">
                <p class="f-eyebrow"><?php echo e(__('finder_story.hospitals.eyebrow')); ?></p>
                <h1 class="f-h1"><?php echo e(__('finder_story.hospitals.title')); ?></h1>
                <p class="f-lead"><?php echo e(__('finder_story.hospitals.lead')); ?></p>
                <div class="fs-cta-row">
                    <a class="f-btn f-btn--primary" href="<?php echo e(route('public.care-map')); ?>">
                        <?php echo e(__('finder_story.hospitals.cta_primary')); ?>

                        <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </a>
                    <a class="f-btn" href="<?php echo e(route('register')); ?>"><?php echo e(__('finder_story.hospitals.cta_secondary')); ?></a>
                </div>
            </div>
        </div>
    </section>

    
    <section class="f-section">
        <div class="f-container">
            <div class="f-grid">
                <div class="f-card fs-benefit">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'building-2', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <h2><?php echo e(__('finder_story.hospitals.listing_title')); ?></h2>
                    <p class="f-muted"><?php echo e(__('finder_story.hospitals.listing_p')); ?></p>
                </div>
                <div class="f-card fs-benefit">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'droplet', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <h2><?php echo e(__('finder_story.hospitals.blood_title')); ?></h2>
                    <p class="f-muted"><?php echo e(__('finder_story.hospitals.blood_p')); ?></p>
                </div>
                <div class="f-card fs-benefit">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'calendar', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <h2><?php echo e(__('finder_story.hospitals.booking_title')); ?></h2>
                    <p class="f-muted"><?php echo e(__('finder_story.hospitals.booking_p')); ?></p>
                </div>
            </div>
        </div>
    </section>

    
    <section class="f-section f-section--wash f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <h2 class="f-h2"><?php echo e(__('finder_story.hospitals.steps_title')); ?></h2>
                <ol class="fs-steps">
                    <li><span><?php echo e(__('finder_story.hospitals.step1')); ?></span></li>
                    <li><span><?php echo e(__('finder_story.hospitals.step2')); ?></span></li>
                    <li><span><?php echo e(__('finder_story.hospitals.step3')); ?></span></li>
                    <li><span><?php echo e(__('finder_story.hospitals.step4')); ?></span></li>
                </ol>
            </div>
        </div>
    </section>

    
    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <div class="f-card">
                    <h2><?php echo e(__('finder_story.hospitals.platform_title')); ?></h2>
                    <p class="f-muted"><?php echo e(__('finder_story.hospitals.platform_p')); ?></p>
                    <a class="f-btn fs-platform-cta" href="https://opescare.cloud" rel="noopener">
                        <?php echo e(__('finder_story.hospitals.platform_cta')); ?>

                        <?php echo $__env->make('partials.finder.icon', ['name' => 'external-link', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.finder', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\public\solutions\hospitals.blade.php ENDPATH**/ ?>
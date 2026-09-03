<?php $__env->startSection('title', __('finder_story.how.page_title')); ?>
<?php $__env->startSection('description', __('finder_story.how.meta_description')); ?>

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
        .fs-tool-head { display: flex; align-items: center; gap: 0.625rem; }
        .fs-tool-head > svg { color: var(--f-brand); flex-shrink: 0; }
        .fs-limit { display: flex; align-items: flex-start; gap: 0.625rem; padding: 0.375rem 0; }
        .fs-limit > svg { flex-shrink: 0; margin-top: 0.2rem; color: var(--f-warn); }
        .fs-cta-row { display: flex; flex-wrap: wrap; gap: 0.625rem; }
        .fs-note { display: flex; align-items: flex-start; gap: 0.625rem; }
        .fs-note > svg { flex-shrink: 0; margin-top: 0.2rem; }
    </style>

    
    <section class="f-section f-section--surface">
        <div class="f-container">
            <div class="fs-narrow">
                <p class="f-eyebrow"><?php echo e(__('finder_story.how.eyebrow')); ?></p>
                <h1 class="f-h1"><?php echo e(__('finder_story.how.title')); ?></h1>
                <p class="f-lead"><?php echo e(__('finder_story.how.lead')); ?></p>
            </div>
        </div>
    </section>

    
    <section class="f-section">
        <div class="f-container">
            <div class="f-grid">

                <div class="f-card">
                    <h2 class="fs-tool-head">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'pill', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo e(__('finder_story.how.medicine_title')); ?>

                    </h2>
                    <ol class="fs-steps">
                        <li><span><?php echo e(__('finder_story.how.medicine_step1')); ?></span></li>
                        <li><span><?php echo e(__('finder_story.how.medicine_step2')); ?></span></li>
                        <li><span><?php echo e(__('finder_story.how.medicine_step3')); ?></span></li>
                    </ol>
                    <p class="f-small f-muted fs-note">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'info', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <span><?php echo e(__('finder_story.how.medicine_note')); ?></span>
                    </p>
                    <a class="f-btn f-btn--ghost" href="<?php echo e(route('public.network.medicine-finder')); ?>">
                        <?php echo e(__('finder_story.cta.medicine')); ?>

                        <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </a>
                </div>

                <div class="f-card">
                    <h2 class="fs-tool-head">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'droplet', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo e(__('finder_story.how.blood_title')); ?>

                    </h2>
                    <ol class="fs-steps">
                        <li><span><?php echo e(__('finder_story.how.blood_step1')); ?></span></li>
                        <li><span><?php echo e(__('finder_story.how.blood_step2')); ?></span></li>
                        <li><span><?php echo e(__('finder_story.how.blood_step3')); ?></span></li>
                    </ol>
                    <a class="f-btn f-btn--ghost" href="<?php echo e(route('public.network.blood-finder')); ?>">
                        <?php echo e(__('finder_story.cta.blood')); ?>

                        <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </a>
                </div>

                <div class="f-card">
                    <h2 class="fs-tool-head">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'map-pin', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo e(__('finder_story.how.facility_title')); ?>

                    </h2>
                    <ol class="fs-steps">
                        <li><span><?php echo e(__('finder_story.how.facility_step1')); ?></span></li>
                        <li><span><?php echo e(__('finder_story.how.facility_step2')); ?></span></li>
                        <li><span><?php echo e(__('finder_story.how.facility_step3')); ?></span></li>
                    </ol>
                    <a class="f-btn f-btn--ghost" href="<?php echo e(route('public.care-map')); ?>">
                        <?php echo e(__('finder_story.cta.directory')); ?>

                        <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </a>
                </div>

                <div class="f-card">
                    <h2 class="fs-tool-head">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'calendar', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo e(__('finder_story.how.booking_title')); ?>

                    </h2>
                    <ol class="fs-steps">
                        <li><span><?php echo e(__('finder_story.how.booking_step1')); ?></span></li>
                        <li><span><?php echo e(__('finder_story.how.booking_step2')); ?></span></li>
                        <li><span><?php echo e(__('finder_story.how.booking_step3')); ?></span></li>
                    </ol>
                    <a class="f-btn f-btn--ghost" href="<?php echo e(route('public.care-map')); ?>">
                        <?php echo e(__('finder_shell.nav.book_appointment')); ?>

                        <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </a>
                </div>

            </div>
        </div>
    </section>

    
    <section class="f-section f-section--wash f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <h2 class="f-h2"><?php echo e(__('finder_story.how.limits_title')); ?></h2>
                <p class="f-lead"><?php echo e(__('finder_story.how.limits_lead')); ?></p>
                <div class="f-card">
                    <p class="fs-limit">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'phone', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <span><?php echo e(__('finder_story.how.limit_1')); ?></span>
                    </p>
                    <p class="fs-limit">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'clock', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <span><?php echo e(__('finder_story.how.limit_2')); ?></span>
                    </p>
                    <p class="fs-limit">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'info', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <span><?php echo e(__('finder_story.how.limit_3')); ?></span>
                    </p>
                    <p class="fs-limit">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <span><?php echo e(__('finder_story.how.limit_4')); ?></span>
                    </p>
                </div>
            </div>
        </div>
    </section>

    
    <section class="f-section f-section--tight">
        <div class="f-container">
            <h2 class="f-h2"><?php echo e(__('finder_story.how.cta_title')); ?></h2>
            <?php echo $__env->make('public.partials._finder_ctas', ['primary' => 'medicine'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.finder', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\public\how_it_works.blade.php ENDPATH**/ ?>
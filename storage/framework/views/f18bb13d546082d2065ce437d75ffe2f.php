<?php $__env->startSection('title', __('finder_home.page_title')); ?>
<?php $__env->startSection('description', __('finder_home.page_desc', ['count' => number_format($stats['facilities'])])); ?>

<?php $__env->startSection('content'); ?>

    

    <style>
        .fh-actions { display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1.25rem; }
        .fh-action { display: flex; align-items: center; gap: 0.875rem; text-decoration: none; }
        .fh-action-text { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 0.125rem; }
        .fh-action > svg:last-child, .fh-action > .f-icon:last-child { opacity: 0.6; flex-shrink: 0; }
        .fh-trust-list { list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: 0.625rem 1.5rem; }
        .fh-trust-list li { display: flex; align-items: flex-start; gap: 0.5rem; }
        .fh-trust-list li svg { flex-shrink: 0; margin-top: 0.125rem; }
        .fh-step-no { display: inline-flex; align-items: center; gap: 0.5rem; font-variant-numeric: tabular-nums; }
        .fh-limit { display: flex; align-items: flex-start; gap: 0.5rem; }
        .fh-limit svg { flex-shrink: 0; margin-top: 0.125rem; }
        .fh-card-cta { display: inline-flex; align-items: center; gap: 0.375rem; }
        .fh-chips { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .fh-claim { display: flex; flex-direction: column; gap: 0.75rem; align-items: flex-start; }
    </style>

    
    <section class="f-section">
        <div class="f-container">
            <p class="f-eyebrow"><?php echo e(__('finder_home.hero.eyebrow')); ?></p>
            <h1 class="f-h1"><?php echo e(__('finder_home.hero.title')); ?></h1>
            <p class="f-lead"><?php echo e(__('finder_home.hero.lead')); ?></p>

            <nav class="fh-actions" aria-label="<?php echo e(__('finder_home.hero.nav_label')); ?>">
                <a class="f-card fh-action" href="<?php echo e(route('public.network.medicine-finder')); ?>">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'pill', 'size' => 24], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <span class="fh-action-text">
                        <strong><?php echo e(__('finder_home.hero.medicine_label')); ?></strong>
                        <small class="f-muted"><?php echo e(__('finder_home.hero.medicine_sub')); ?></small>
                    </span>
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </a>

                <a class="f-card fh-action" href="<?php echo e(route('public.network.blood-finder')); ?>">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'droplet', 'size' => 24], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <span class="fh-action-text">
                        <strong><?php echo e(__('finder_home.hero.blood_label')); ?></strong>
                        <small class="f-muted"><?php echo e(__('finder_home.hero.blood_sub')); ?></small>
                    </span>
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </a>

                <a class="f-card fh-action" href="<?php echo e(route('public.care-map')); ?>">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'calendar', 'size' => 24], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <span class="fh-action-text">
                        <strong><?php echo e(__('finder_home.hero.appointment_label')); ?></strong>
                        <small class="f-muted"><?php echo e(__('finder_home.hero.appointment_sub')); ?></small>
                    </span>
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </a>
            </nav>
        </div>
    </section>

    
    <section class="f-section">
        <div class="f-container">
            <ul class="fh-trust-list">
                <li>
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'building-2', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <span><?php echo e(__('finder_home.trust.facilities', ['count' => number_format($stats['facilities'])])); ?></span>
                </li>
                <li>
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'map-pin', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <span><?php echo e(__('finder_home.trust.regions', ['count' => $stats['regions']])); ?></span>
                </li>
                <li>
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'globe', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <span><?php echo e(__('finder_home.trust.bilingual')); ?></span>
                </li>
                <li>
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <span><?php echo e(__('finder_home.trust.free')); ?></span>
                </li>
            </ul>
        </div>
    </section>

    
    <section class="f-section">
        <div class="f-container">
            <p class="f-eyebrow"><?php echo e(__('finder_home.how.eyebrow')); ?></p>
            <h2 class="f-h2"><?php echo e(__('finder_home.how.title')); ?></h2>

            <div class="f-grid">
                <div class="f-card">
                    <p class="fh-step-no">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'search', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <span>1</span>
                    </p>
                    <h3><?php echo e(__('finder_home.how.step1_title')); ?></h3>
                    <p><?php echo e(__('finder_home.how.step1_desc')); ?></p>
                </div>
                <div class="f-card">
                    <p class="fh-step-no">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'phone', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <span>2</span>
                    </p>
                    <h3><?php echo e(__('finder_home.how.step2_title')); ?></h3>
                    <p><?php echo e(__('finder_home.how.step2_desc')); ?></p>
                </div>
                <div class="f-card">
                    <p class="fh-step-no">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'navigation', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <span>3</span>
                    </p>
                    <h3><?php echo e(__('finder_home.how.step3_title')); ?></h3>
                    <p><?php echo e(__('finder_home.how.step3_desc')); ?></p>
                </div>
            </div>
        </div>
    </section>

    
    <section class="f-section">
        <div class="f-container">
            <p class="f-eyebrow"><?php echo e(__('finder_home.finders.eyebrow')); ?></p>
            <h2 class="f-h2"><?php echo e(__('finder_home.finders.title')); ?></h2>

            <div class="f-grid">
                <div class="f-card">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'pill', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <h3><?php echo e(__('finder_home.finders.medicine_title')); ?></h3>
                    <p><?php echo e(__('finder_home.finders.medicine_desc')); ?></p>
                    <p class="fh-limit f-muted f-small">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'clock', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <span><?php echo e(__('finder_home.finders.medicine_limit')); ?></span>
                    </p>
                    <a class="f-btn f-btn--ghost fh-card-cta" href="<?php echo e(route('public.network.medicine-finder')); ?>">
                        <?php echo e(__('finder_home.finders.medicine_cta')); ?>

                        <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </a>
                </div>

                <div class="f-card">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'droplet', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <h3><?php echo e(__('finder_home.finders.blood_title')); ?></h3>
                    <p><?php echo e(__('finder_home.finders.blood_desc')); ?></p>
                    <p class="fh-limit f-muted f-small">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'phone', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <span><?php echo e(__('finder_home.finders.blood_limit')); ?></span>
                    </p>
                    <a class="f-btn f-btn--ghost fh-card-cta" href="<?php echo e(route('public.network.blood-finder')); ?>">
                        <?php echo e(__('finder_home.finders.blood_cta')); ?>

                        <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </a>
                </div>

                <div class="f-card">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'calendar', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <h3><?php echo e(__('finder_home.finders.appointment_title')); ?></h3>
                    <p><?php echo e(__('finder_home.finders.appointment_desc')); ?></p>
                    <p class="fh-limit f-muted f-small">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'phone', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <span><?php echo e(__('finder_home.finders.appointment_limit')); ?></span>
                    </p>
                    <a class="f-btn f-btn--ghost fh-card-cta" href="<?php echo e(route('public.care-map')); ?>">
                        <?php echo e(__('finder_home.finders.appointment_cta')); ?>

                        <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    
    <section class="f-section">
        <div class="f-container">
            <div class="f-card fh-claim">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'building-2', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <h2 class="f-h2"><?php echo e(__('finder_home.claim.title')); ?></h2>
                <p><?php echo e(__('finder_home.claim.desc')); ?></p>
                <p class="fh-limit f-muted f-small">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <span><?php echo e(__('finder_home.claim.hint')); ?></span>
                </p>
                <a class="f-btn f-btn--primary fh-card-cta" href="<?php echo e(route('public.care-map')); ?>">
                    <?php echo e(__('finder_home.claim.cta')); ?>

                    <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </a>
            </div>
        </div>
    </section>

    
    <section class="f-section">
        <div class="f-container">
            <h2 class="f-h2"><?php echo e(__('finder_home.coverage.title', ['count' => $stats['regions']])); ?></h2>
            <p class="f-lead"><?php echo e(__('finder_home.coverage.desc')); ?></p>

            <div class="fh-chips">
                <?php $__currentLoopData = __('finder_home.coverage.regions'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $region): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a class="f-chip" href="<?php echo e(route('public.care-map')); ?>"><?php echo e($region); ?></a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.finder', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\public\landing.blade.php ENDPATH**/ ?>
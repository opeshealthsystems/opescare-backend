
<style>
    .fs-redirect { max-width: 640px; }
    .fs-redirect .f-card { margin-bottom: 1.25rem; }
    .fs-cta-row { display: flex; flex-wrap: wrap; gap: 0.625rem; }
    .fs-platform-cta { display: inline-flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem; }
</style>

<section class="f-section">
    <div class="f-container">
        <div class="fs-redirect">
            <p class="f-eyebrow"><?php echo e(__('finder_story.redirect.titles.' . $pageKey)); ?></p>
            <h1 class="f-h2"><?php echo e(__('finder_story.redirect.heading')); ?></h1>
            <p class="f-lead"><?php echo e(__('finder_story.redirect.lead')); ?></p>

            <div class="f-card">
                <h2><?php echo e(__('finder_story.redirect.platform_title')); ?></h2>
                <p class="f-muted"><?php echo e(__('finder_story.redirect.platform_p')); ?></p>
                <a class="f-btn f-btn--primary fs-platform-cta" href="https://opescare.cloud" rel="noopener">
                    <?php echo e(__('finder_story.redirect.platform_cta')); ?>

                    <?php echo $__env->make('partials.finder.icon', ['name' => 'external-link', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </a>
            </div>

            <div class="f-card">
                <h2><?php echo e(__('finder_story.redirect.consumer_title')); ?></h2>
                <p class="f-muted"><?php echo e(__('finder_story.redirect.consumer_p')); ?></p>
                <?php echo $__env->make('public.partials._finder_ctas', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>

            <?php if(!empty($showPrivacy)): ?>
                <p class="f-small f-muted">
                    <?php echo e(__('finder_story.redirect.privacy_note')); ?>

                    <a href="<?php echo e(route('public.privacy')); ?>"><?php echo e(__('finder_story.redirect.privacy_cta')); ?></a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\public\partials\_platform_redirect.blade.php ENDPATH**/ ?>
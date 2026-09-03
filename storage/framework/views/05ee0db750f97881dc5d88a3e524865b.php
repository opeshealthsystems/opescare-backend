<?php $__env->startSection('title', __('finder_story.faq.page_title')); ?>
<?php $__env->startSection('description', __('finder_story.faq.meta_description')); ?>


<?php $__env->startPush('schema'); ?>

<?php echo $__env->make('partials.finder.schema.faq', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

    
    <style>
        .fs-narrow { max-width: 720px; }
        .fs-qa { border-bottom: 1px solid var(--f-line); }
        .fs-qa summary {
            display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;
            padding: 1rem 0; cursor: pointer; list-style: none;
        }
        .fs-qa summary::-webkit-details-marker { display: none; }
        .fs-qa summary h3 { margin: 0; font-size: 1rem; }
        .fs-qa summary svg { flex-shrink: 0; transition: transform 0.15s ease; }
        .fs-qa[open] summary svg { transform: rotate(180deg); }
        .fs-qa > p { padding: 0 0 1rem; margin: 0; }
        .fs-cta-row { display: flex; flex-wrap: wrap; gap: 0.625rem; }
    </style>

    
    <section class="f-section f-section--surface f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <p class="f-eyebrow"><?php echo e(__('finder_story.faq.eyebrow')); ?></p>
                <h1 class="f-h1"><?php echo e(__('finder_story.faq.title')); ?></h1>
                <p class="f-lead"><?php echo e(__('finder_story.faq.lead')); ?></p>
            </div>
        </div>
    </section>

    
    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <?php $__currentLoopData = __('finder_story.faq.items'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fsItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <details class="fs-qa" <?php if($loop->first): ?> open <?php endif; ?>>
                        <summary>
                            <h3><?php echo e($fsItem['q']); ?></h3>
                            <?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-down', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </summary>
                        <p class="f-muted"><?php echo e($fsItem['a']); ?></p>
                    </details>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </section>

    
    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <h2 class="f-h2"><?php echo e(__('finder_story.faq.still_title')); ?></h2>
                <p class="f-muted"><?php echo e(__('finder_story.faq.still_p')); ?></p>
                <div class="fs-cta-row">
                    <a class="f-btn f-btn--primary" href="<?php echo e(route('public.contact')); ?>">
                        <?php echo e(__('finder_story.faq.still_cta')); ?>

                        <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </a>
                    <a class="f-btn f-btn--ghost" href="<?php echo e(route('public.how-it-works')); ?>"><?php echo e(__('finder_story.cta.how')); ?></a>
                </div>
            </div>
        </div>
    </section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.finder', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\public\faq.blade.php ENDPATH**/ ?>
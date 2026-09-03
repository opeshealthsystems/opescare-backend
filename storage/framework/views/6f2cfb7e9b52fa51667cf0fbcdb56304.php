<?php $__env->startSection('title', __('public.legal_page.page_title')); ?>
<?php $__env->startSection('description', __('public.legal_page.hero_subtitle')); ?>

<?php $__env->startSection('content'); ?>

    
    <style>
        .fs-narrow { max-width: 860px; }
        .fs-legal-card { display: flex; align-items: center; justify-content: space-between; gap: 1rem; text-decoration: none; margin-bottom: 0.75rem; }
        .fs-legal-card__title { font-weight: 700; }
        .fs-legal-card__meta { font-size: 0.8125rem; color: var(--f-muted); margin-top: 3px; }
        .fs-legal-card > svg { flex-shrink: 0; color: var(--f-brand); }
    </style>

    <section class="f-section f-section--surface f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <h1 class="f-h1"><?php echo e(__('public.legal_page.hero_title')); ?></h1>
                <p class="f-lead"><?php echo e(__('public.legal_page.hero_subtitle')); ?></p>
            </div>
        </div>
    </section>

    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <p class="f-small f-muted"><?php echo e(__('public.legal_page.intro')); ?></p>

                <?php $__empty_1 = true; $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $ver = $doc->versions->first(); ?>
                    <a href="<?php echo e(route('public.legal.show', $doc->slug)); ?>" class="f-card fs-legal-card">
                        <div>
                            <div class="fs-legal-card__title"><?php echo e($doc->title); ?></div>
                            <div class="fs-legal-card__meta">
                                <?php if($ver): ?>
                                    <?php echo e(__('public.legal_page.version_label')); ?> <?php echo e($ver->version); ?>

                                    <?php if($ver->effective_at): ?>
                                        · <?php echo e(__('public.legal_page.effective_label')); ?> <?php echo e($ver->effective_at->format('d F Y')); ?>

                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php echo e(__('public.legal_page.no_version')); ?>

                                <?php endif; ?>
                            </div>
                        </div>
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="f-card f-muted" style="text-align:center;">
                        <?php echo e(__('public.legal_page.no_docs')); ?>

                    </div>
                <?php endif; ?>

                <div class="f-card f-small" style="margin-top:1.5rem;">
                    <strong><?php echo e(__('public.legal_page.questions')); ?></strong> <?php echo e(__('public.legal_page.contact_privacy')); ?>

                    <a href="mailto:privacy@opescare.com">privacy@opescare.com</a>
                    <?php echo e(__('public.legal_page.or_visit')); ?> <a href="<?php echo e(route('public.help')); ?>"><?php echo e(__('public.legal_page.help_centre')); ?></a>.
                </div>
            </div>
        </div>
    </section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.finder', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\public\legal\index.blade.php ENDPATH**/ ?>
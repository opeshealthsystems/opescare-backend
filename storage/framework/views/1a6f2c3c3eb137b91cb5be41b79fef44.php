<?php $__env->startSection('title', $document->title); ?>
<?php $__env->startSection('description', $document->title); ?>

<?php $__env->startSection('content'); ?>

    
    <style>
        .fs-narrow { max-width: 800px; }
        .fs-legal-back { display: inline-flex; align-items: center; gap: 0.375rem; text-decoration: none; font-size: 0.875rem; }
        .fs-legal-meta { display: flex; flex-wrap: wrap; gap: 1rem 1.5rem; font-size: 0.8125rem; margin-bottom: 1.5rem; }
        .fs-legal-meta__item { color: var(--f-muted); }
        .fs-legal-meta__item strong { color: var(--f-ink); display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px; }
        .fs-legal-body { line-height: 1.7; }
        .fs-legal-body h1, .fs-legal-body h2, .fs-legal-body h3 { color: var(--f-brand-ink); }
        .fs-legal-body table { border-collapse: collapse; width: 100%; }
        .fs-legal-body th, .fs-legal-body td { border: 1px solid var(--f-line); padding: 0.5rem 0.75rem; text-align: left; }
        .fs-no-content { text-align: center; padding: 3rem 1rem; color: var(--f-muted); }
    </style>

    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <p>
                    <a class="fs-legal-back" href="<?php echo e(route('public.legal')); ?>">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-left', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        Legal Centre
                    </a>
                </p>

                <h1 class="f-h2"><?php echo e($document->title); ?></h1>

                <?php if($currentVersion): ?>
                    <div class="f-card fs-legal-meta">
                        <div class="fs-legal-meta__item"><strong>Version</strong><?php echo e($currentVersion->version); ?></div>
                        <?php if($currentVersion->effective_at): ?>
                            <div class="fs-legal-meta__item"><strong>Effective</strong><?php echo e($currentVersion->effective_at->format('d F Y')); ?></div>
                        <?php endif; ?>
                        <?php if($currentVersion->published_at): ?>
                            <div class="fs-legal-meta__item"><strong>Published</strong><?php echo e($currentVersion->published_at->format('d F Y')); ?></div>
                        <?php endif; ?>
                        <div class="fs-legal-meta__item"><strong>Language</strong><?php echo e(strtoupper($document->language)); ?></div>
                    </div>

                    <div class="f-card fs-legal-body">
                        <?php echo $currentVersion->content_html; ?>

                    </div>
                <?php else: ?>
                    <div class="f-card fs-no-content">
                        <p>This document has not been published yet.</p>
                        <a href="<?php echo e(route('public.legal')); ?>">Legal Centre</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.finder', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\public\legal\show.blade.php ENDPATH**/ ?>
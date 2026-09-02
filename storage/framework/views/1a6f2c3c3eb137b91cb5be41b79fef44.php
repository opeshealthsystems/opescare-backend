<!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale()); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($document->title); ?> — OpesCare Legal</title>
    <?php echo $__env->make('partials.favicons', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(asset('css/portal.css')); ?>">
    <style>
        body { background:#f8fafc; font-family:'Inter',sans-serif; margin:0; }
        .legal-topbar {
            background:#0F4C81; color:#fff;
            padding:14px 24px; display:flex; align-items:center; gap:16px;
        }
        .legal-topbar a { color:rgba(255,255,255,.75); text-decoration:none; font-size:0.88rem; }
        .legal-topbar a:hover { color:#fff; }
        .legal-container { max-width:800px; margin:0 auto; padding:32px 24px; }
        .legal-meta { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px 20px; margin-bottom:24px; display:flex; flex-wrap:wrap; gap:16px; font-size:0.83rem; }
        .legal-meta__item { color:#64748b; }
        .legal-meta__item strong { color:#1e293b; display:block; font-size:0.78rem; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px; }
        .legal-body { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:32px; line-height:1.7; font-size:0.92rem; color:#1e293b; }
        .legal-body h1, .legal-body h2, .legal-body h3 { color:#0F4C81; }
        .legal-body a { color:#7c3aed; }
        .legal-footer { text-align:center; padding:24px; font-size:0.8rem; color:#94a3b8; }
        .no-content { text-align:center; padding:60px; color:#94a3b8; }
    </style>

<?php echo $__env->make('partials.seo_head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

</head>
<body>
<div class="legal-topbar">
    <a href="<?php echo e(route('public.legal')); ?>">← Legal Centre</a>
    <span style="opacity:.4;">|</span>
    <span style="font-weight:600;"><?php echo e($document->title); ?></span>
</div>

<div class="legal-container">
    <h1 style="font-size:1.5rem;font-weight:800;color:#0F4C81;margin:0 0 16px;"><?php echo e($document->title); ?></h1>

    <?php if($currentVersion): ?>
    <div class="legal-meta">
        <div class="legal-meta__item"><strong>Version</strong><?php echo e($currentVersion->version); ?></div>
        <?php if($currentVersion->effective_at): ?>
            <div class="legal-meta__item"><strong>Effective</strong><?php echo e($currentVersion->effective_at->format('d F Y')); ?></div>
        <?php endif; ?>
        <?php if($currentVersion->published_at): ?>
            <div class="legal-meta__item"><strong>Published</strong><?php echo e($currentVersion->published_at->format('d F Y')); ?></div>
        <?php endif; ?>
        <div class="legal-meta__item"><strong>Language</strong><?php echo e(strtoupper($document->language)); ?></div>
    </div>

    <div class="legal-body">
        <?php echo $currentVersion->content_html; ?>

    </div>
    <?php else: ?>
    <div class="no-content">
        <p>This document has not been published yet.</p>
        <a href="<?php echo e(route('public.legal')); ?>" style="color:#7c3aed;">← Back to Legal Centre</a>
    </div>
    <?php endif; ?>
</div>

<div class="legal-footer">
    © <?php echo e(date('Y')); ?> OpesCare. All rights reserved.
    <a href="<?php echo e(route('public.legal')); ?>" style="color:#94a3b8;margin-left:12px;">Legal Centre</a>
    <a href="/" style="color:#94a3b8;margin-left:12px;">Portal</a>
</div>
</body>
</html>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\public\legal\show.blade.php ENDPATH**/ ?>
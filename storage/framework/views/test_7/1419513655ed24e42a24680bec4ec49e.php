<?php $__env->startSection('title', __('account_deletion.web.page_title')); ?>
<?php $__env->startSection('description', __('account_deletion.web.meta_description')); ?>



<?php $__env->startSection('content'); ?>

    <style>
        .ad-wrap { width: 100%; max-width: 860px; margin-inline: auto; padding-inline: 16px; }
        .ad-hero { padding: clamp(40px, 7vw, 72px) 0 1.5rem; background: var(--f-surface); text-align: center; }
        .ad-hero h1 { font-size: clamp(1.6rem, 4vw, 2.4rem); line-height: 1.15; margin: 0 0 .75rem; }
        .ad-badge {
            display: inline-block; font-size: .75rem; font-weight: 700; letter-spacing: .05em;
            text-transform: uppercase; padding: .35rem 1rem; border-radius: 999px;
            background: rgba(15, 76, 129, .12); color: #0F4C81; margin-bottom: 1rem;
        }
        .ad-lede { color: var(--f-muted); max-width: 640px; margin: 0 auto; }
        .ad-body { padding: 2.5rem 0 4rem; }
        .ad-section { margin-bottom: 2.75rem; }
        .ad-section h2 { font-size: 1.25rem; margin: 0 0 .85rem; color: #0F4C81; }
        .ad-steps { list-style: none; margin: 0; padding: 0; counter-reset: adstep; }
        .ad-steps li {
            position: relative; padding: 0 0 1rem 2.75rem; counter-increment: adstep;
            line-height: 1.6;
        }
        .ad-steps li::before {
            content: counter(adstep);
            position: absolute; left: 0; top: -2px;
            width: 1.9rem; height: 1.9rem; border-radius: 999px;
            display: grid; place-items: center;
            background: #0F4C81; color: #fff; font-size: .8125rem; font-weight: 700;
        }
        .ad-card {
            border: 1px solid var(--f-line); border-radius: 12px;
            padding: 1.25rem 1.35rem; background: var(--f-surface);
        }
        .ad-card + .ad-card { margin-top: .85rem; }
        .ad-card h3 { margin: 0 0 .5rem; font-size: 1rem; }
        .ad-card p { margin: 0; color: var(--f-muted); line-height: 1.6; }
        .ad-list { margin: .5rem 0 0; padding-left: 1.15rem; color: var(--f-muted); line-height: 1.7; }
        .ad-kept { border-left: 3px solid #0F4C81; padding-left: 1rem; }
        .ad-cannot { border-left: 3px solid #B45309; padding-left: 1rem; }
        .ad-meta { font-size: .8125rem; color: var(--f-muted); border-top: 1px solid var(--f-line); padding-top: 1rem; }
        .ad-contact a { color: #0F4C81; font-weight: 600; }
    </style>

    <header class="ad-hero">
        <div class="ad-wrap">
            <div class="ad-badge"><?php echo e(__('account_deletion.web.badge')); ?></div>
            <h1><?php echo e(__('account_deletion.web.hero_title')); ?></h1>
            <p class="ad-lede"><?php echo e(__('account_deletion.web.hero_subtitle')); ?></p>
        </div>
    </header>

    <div class="ad-body">
        <div class="ad-wrap">

            
            <section class="ad-section">
                <h2><?php echo e(__('account_deletion.web.how_title')); ?></h2>
                <p style="color:var(--f-muted);margin-top:0;"><?php echo e(__('account_deletion.web.how_intro')); ?></p>
                <ol class="ad-steps">
                    <li><?php echo e(__('account_deletion.web.step_app')); ?></li>
                    <li><?php echo e(__('account_deletion.web.step_confirm')); ?></li>
                    <li><?php echo e(__('account_deletion.web.step_grace', ['days' => $graceDays])); ?></li>
                </ol>
            </section>

            
            <section class="ad-section">
                <h2><?php echo e(__('account_deletion.erased.title')); ?></h2>
                <p style="color:var(--f-muted);margin-top:0;"><?php echo e(__('account_deletion.erased.note')); ?></p>
                <ul class="ad-list">
                    <?php $__currentLoopData = ['sign_in', 'password', 'pin', 'sessions', 'devices', 'phone', 'email', 'address', 'emergency_contact', 'national_id', 'referral_code']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e(__('account_deletion.erased.items.' . $item)); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </section>

            
            <section class="ad-section">
                <h2><?php echo e(__('account_deletion.deidentified.title')); ?></h2>
                <p style="color:var(--f-muted);margin-top:0;"><?php echo e(__('account_deletion.deidentified.note')); ?></p>
                <ul class="ad-list">
                    <?php $__currentLoopData = ['name', 'record_marked']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e(__('account_deletion.deidentified.items.' . $item)); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </section>

            
            <section class="ad-section ad-kept">
                <h2><?php echo e(__('account_deletion.retained.title')); ?></h2>
                <p style="color:var(--f-muted);margin-top:0;"><?php echo e(__('account_deletion.retained.note')); ?></p>
                <p style="color:var(--f-muted);"><?php echo e(__('account_deletion.retained.legal_basis', ['years' => $retentionYears])); ?></p>
            </section>

            
            <section class="ad-section ad-cannot">
                <h2><?php echo e(__('account_deletion.cannot.title')); ?></h2>
                <ul class="ad-list">
                    <?php $__currentLoopData = ['clinical_events', 'access_logs', 'facility_copies', 'fiscal']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e(__('account_deletion.cannot.items.' . $item)); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </section>

            
            <section class="ad-section">
                <h2><?php echo e(__('account_deletion.web.timing_title')); ?></h2>
                <div class="ad-card">
                    <p><?php echo e(__('account_deletion.web.timing_body', ['days' => $graceDays, 'years' => $retentionYears])); ?></p>
                </div>
            </section>

            
            <section class="ad-section">
                <h2><?php echo e(__('account_deletion.web.no_app_title')); ?></h2>
                <div class="ad-card ad-contact">
                    <p><?php echo e(__('account_deletion.web.no_app_body', ['days' => $graceDays])); ?></p>
                    <p style="margin-top:.75rem;">
                        <strong><?php echo e(__('account_deletion.web.contact_label')); ?>:</strong>
                        <a href="mailto:<?php echo e($contactEmail); ?>"><?php echo e($contactEmail); ?></a>
                    </p>
                </div>
            </section>

            <p class="ad-meta">
                <?php echo e(__('account_deletion.web.last_updated')); ?>: <?php echo e($lastUpdated); ?>

            </p>

        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.finder', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/public/account-deletion.blade.php ENDPATH**/ ?>
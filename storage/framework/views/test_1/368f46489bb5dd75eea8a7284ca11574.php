<?php $__env->startSection('title', __('public.consent_page.page_title')); ?>
<?php $__env->startSection('description', __('public.consent_page.meta_description')); ?>

<?php $__env->startSection('content'); ?>

    
    <style>
        .fc-narrow { max-width: 720px; }
        .fc-cta-row { display: flex; flex-wrap: wrap; gap: 0.625rem; }
        .fc-card-icon { color: var(--f-brand); }
        .fc-step-no {
            width: 2rem; height: 2rem; border-radius: 50%;
            background: var(--f-brand); color: var(--f-surface);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 0.875rem; margin-bottom: 0.75rem;
        }
        .fc-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 0.75rem; }
        .fc-list li { display: flex; gap: 0.75rem; align-items: flex-start; }
        .fc-list svg { flex-shrink: 0; margin-top: 0.2rem; color: var(--f-danger); }
        .fc-two-col { display: grid; grid-template-columns: 1fr; gap: 2rem; align-items: start; }
        @media (min-width: 960px) { .fc-two-col { grid-template-columns: 1fr 1fr; } }
        .fc-demo { background: var(--f-brand-ink); border-radius: var(--f-r); padding: 2rem; color: var(--f-surface); }
        .fc-demo__head { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;
            font-weight: 700; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .fc-demo__row { background: rgba(255,255,255,0.07); border-radius: 8px; padding: 1rem; }
        .fc-demo__label { font-size: 0.75rem; opacity: 0.7; margin-bottom: 0.25rem; }
        .fc-demo__grid { display: grid; gap: 0.75rem; font-size: 0.875rem; }
        .fc-demo__note { background: rgba(255,255,255,0.07); border: 1px solid var(--f-line);
            border-radius: 8px; padding: 0.75rem; font-size: 0.75rem; display: flex; gap: 0.5rem; align-items: flex-start; }
        .fc-rights-item { display: flex; gap: 1rem; align-items: flex-start; }
        .fc-rights-item > svg { flex-shrink: 0; margin-top: 0.2rem; color: var(--f-brand); }
    </style>

    
    <section class="f-section f-section--surface">
        <div class="f-container">
            <div class="fc-narrow">
                <p class="f-eyebrow"><?php echo e(__('public.consent_page.badge')); ?></p>
                <h1 class="f-h1"><?php echo e(__('public.consent_page.hero_title')); ?></h1>
                <p class="f-lead"><?php echo e(__('public.consent_page.hero_subtitle')); ?></p>
                <div class="fc-cta-row">
                    <a href="<?php echo e(route('register.patient')); ?>" class="f-btn f-btn--primary"><?php echo e(__('public.consent_page.btn_get_hid')); ?></a>
                    <a href="<?php echo e(route('public.security')); ?>" class="f-btn"><?php echo e(__('public.consent_page.btn_security')); ?></a>
                </div>
            </div>
        </div>
    </section>

    
    <section class="f-section f-section--tight">
        <div class="f-container">
            <h2 class="f-h2"><?php echo e(__('public.consent_page.pillars_title')); ?></h2>
            <p class="f-lead"><?php echo e(__('public.consent_page.pillars_subtitle')); ?></p>
            <div class="f-grid">
                <div class="f-card">
                    <span class="fc-card-icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                    <h3><?php echo e(__('public.consent_page.pillar_consent_title')); ?></h3>
                    <p class="f-muted"><?php echo e(__('public.consent_page.pillar_consent_desc')); ?></p>
                </div>
                <div class="f-card">
                    <span class="fc-card-icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'sliders-horizontal', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                    <h3><?php echo e(__('public.consent_page.pillar_scoped_title')); ?></h3>
                    <p class="f-muted"><?php echo e(__('public.consent_page.pillar_scoped_desc')); ?></p>
                </div>
                <div class="f-card">
                    <span class="fc-card-icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'clock', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                    <h3><?php echo e(__('public.consent_page.pillar_logs_title')); ?></h3>
                    <p class="f-muted"><?php echo e(__('public.consent_page.pillar_logs_desc')); ?></p>
                </div>
                <div class="f-card">
                    <span class="fc-card-icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'x', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                    <h3><?php echo e(__('public.consent_page.pillar_revoke_title')); ?></h3>
                    <p class="f-muted"><?php echo e(__('public.consent_page.pillar_revoke_desc')); ?></p>
                </div>
            </div>
        </div>
    </section>

    
    <section class="f-section f-section--wash">
        <div class="f-container">
            <h2 class="f-h2"><?php echo e(__('public.consent_page.flow_title')); ?></h2>
            <p class="f-lead"><?php echo e(__('public.consent_page.flow_subtitle')); ?></p>
            <?php
            $steps = [
                ['icon'=>'send','title'=>__('public.consent_page.step_submit_title'),'desc'=>__('public.consent_page.step_submit_desc')],
                ['icon'=>'bell','title'=>__('public.consent_page.step_notify_title'),'desc'=>__('public.consent_page.step_notify_desc')],
                ['icon'=>'check','title'=>__('public.consent_page.step_approve_title'),'desc'=>__('public.consent_page.step_approve_desc')],
                ['icon'=>'log-in','title'=>__('public.consent_page.step_access_title'),'desc'=>__('public.consent_page.step_access_desc')],
                ['icon'=>'info','title'=>__('public.consent_page.step_log_title'),'desc'=>__('public.consent_page.step_log_desc')],
                ['icon'=>'x','title'=>__('public.consent_page.step_revoke_title'),'desc'=>__('public.consent_page.step_revoke_desc')],
            ];
            ?>
            <div class="f-grid">
                <?php $__currentLoopData = $steps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="f-card">
                    <div class="fc-step-no"><?php echo e($i + 1); ?></div>
                    <span class="fc-card-icon"><?php echo $__env->make('partials.finder.icon', ['name' => $step['icon'], 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                    <h3><?php echo e($step['title']); ?></h3>
                    <p class="f-muted f-small"><?php echo e($step['desc']); ?></p>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </section>

    
    <section class="f-section">
        <div class="f-container">
            <div class="fc-two-col">
                <div>
                    <span class="f-badge f-badge--danger"><?php echo e(__('public.consent_page.emergency_badge')); ?></span>
                    <h2 class="f-h2" style="margin-top: 0.75rem;"><?php echo e(__('public.consent_page.emergency_title')); ?></h2>
                    <p class="f-lead"><?php echo e(__('public.consent_page.emergency_body')); ?></p>
                    <ul class="fc-list">
                        <li>
                            <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-triangle', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <span><?php echo e(__('public.consent_page.emergency_audit')); ?></span>
                        </li>
                        <li>
                            <?php echo $__env->make('partials.finder.icon', ['name' => 'info', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <span><?php echo e(__('public.consent_page.emergency_reason')); ?></span>
                        </li>
                        <li>
                            <?php echo $__env->make('partials.finder.icon', ['name' => 'users', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <span><?php echo e(__('public.consent_page.emergency_notify')); ?></span>
                        </li>
                        <li>
                            <?php echo $__env->make('partials.finder.icon', ['name' => 'filter', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <span><?php echo e(__('public.consent_page.emergency_limited')); ?></span>
                        </li>
                    </ul>
                </div>
                <div class="fc-demo" aria-hidden="true">
                    <div class="fc-demo__head">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <span><?php echo e(__('public.consent_page.emergency_profile')); ?></span>
                    </div>
                    <div class="fc-demo__grid">
                        <div class="fc-demo__row">
                            <div class="fc-demo__label"><?php echo e(__('public.consent_page.emerg_identity')); ?></div>
                            <div style="font-weight:600;"><?php echo e(__('public.consent_page.emerg_sample_identity')); ?></div>
                        </div>
                        <div class="fc-demo__row">
                            <div class="fc-demo__label"><?php echo e(__('public.consent_page.emerg_allergies')); ?></div>
                            <div style="font-weight:600;"><?php echo e(__('public.consent_page.emerg_sample_allergy')); ?></div>
                        </div>
                        <div class="fc-demo__row">
                            <div class="fc-demo__label"><?php echo e(__('public.consent_page.emerg_conditions')); ?></div>
                            <div style="font-weight:600;"><?php echo e(__('public.consent_page.emerg_sample_conditions')); ?></div>
                        </div>
                        <div class="fc-demo__note">
                            <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 14], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <span><?php echo e(__('public.consent_page.emergency_logged')); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <section class="f-section f-section--wash">
        <div class="f-container">
            <h2 class="f-h2"><?php echo e(__('public.consent_page.rights_title')); ?></h2>
            <p class="f-lead"><?php echo e(__('public.consent_page.rights_subtitle')); ?></p>
            <?php
            $rights = [
                ['icon'=>'search','title'=>__('public.consent_page.right_know_title'),'desc'=>__('public.consent_page.right_know_desc')],
                ['icon'=>'x','title'=>__('public.consent_page.right_refuse_title'),'desc'=>__('public.consent_page.right_refuse_desc')],
                ['icon'=>'refresh-cw','title'=>__('public.consent_page.right_revoke_title'),'desc'=>__('public.consent_page.right_revoke_desc')],
                ['icon'=>'clock','title'=>__('public.consent_page.right_review_title'),'desc'=>__('public.consent_page.right_review_desc')],
                ['icon'=>'check','title'=>__('public.consent_page.right_correct_title'),'desc'=>__('public.consent_page.right_correct_desc')],
                ['icon'=>'phone','title'=>__('public.consent_page.right_support_title'),'desc'=>__('public.consent_page.right_support_desc')],
            ];
            ?>
            <div class="f-grid">
                <?php $__currentLoopData = $rights; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $right): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="f-card fc-rights-item">
                    <?php echo $__env->make('partials.finder.icon', ['name' => $right['icon'], 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div>
                        <h3><?php echo e($right['title']); ?></h3>
                        <p class="f-muted f-small"><?php echo e($right['desc']); ?></p>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </section>

    
    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fc-narrow">
                <h2 class="f-h2"><?php echo e(__('public.consent_page.cta_title')); ?></h2>
                <p class="f-lead"><?php echo e(__('public.consent_page.cta_body')); ?></p>
                <div class="fc-cta-row">
                    <a href="<?php echo e(route('register.patient')); ?>" class="f-btn f-btn--primary"><?php echo e(__('public.consent_page.btn_register')); ?></a>
                    <a href="<?php echo e(route('public.privacy')); ?>" class="f-btn"><?php echo e(__('public.consent_page.btn_privacy')); ?></a>
                </div>
            </div>
        </div>
    </section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.finder', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/public/consent.blade.php ENDPATH**/ ?>
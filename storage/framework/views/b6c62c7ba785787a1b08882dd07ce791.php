<?php $__env->startSection('title', __('public.help_page.page_title')); ?>
<?php $__env->startSection('description', __('public.help_page.meta_description')); ?>

<?php $__env->startSection('content'); ?>

    
    <style>
        .fh-narrow { max-width: 720px; }
        .fh-search { display: flex; gap: 0.5rem; max-width: 560px; margin-top: 1.5rem; }
        .fh-search .f-input { flex: 1; }
        .fh-links { list-style: none; margin: 1rem 0 0; padding: 0; display: grid; gap: 0.5rem; font-size: 0.875rem; }
        .fh-topic { display: flex; align-items: flex-start; gap: 0.875rem; text-decoration: none; color: inherit; }
        .fh-topic > svg { flex-shrink: 0; margin-top: 0.2rem; color: var(--f-brand); }
        .fh-cta-row { display: flex; flex-wrap: wrap; gap: 0.625rem; }
        .fh-card-icon { color: var(--f-brand); }
    </style>

    
    <section class="f-section f-section--surface">
        <div class="f-container">
            <div class="fh-narrow">
                <p class="f-eyebrow"><?php echo e(__('public.help_page.badge')); ?></p>
                <h1 class="f-h1"><?php echo e(__('public.help_page.hero_title')); ?></h1>
                <p class="f-lead"><?php echo e(__('public.help_page.hero_subtitle')); ?></p>
                <form action="<?php echo e(route('public.contact')); ?>" method="GET" class="fh-search">
                    <input type="text" name="q" class="f-input" placeholder="<?php echo e(__('public.help_page.search_placeholder')); ?>" aria-label="<?php echo e(__('public.help_page.search_placeholder')); ?>">
                    <button type="submit" class="f-btn f-btn--primary"><?php echo e(__('public.help_page.search_btn')); ?></button>
                </form>
            </div>
        </div>
    </section>

    
    <section class="f-section f-section--tight">
        <div class="f-container">
            <h2 class="f-h2"><?php echo e(__('public.help_page.browse_title')); ?></h2>
            <p class="f-lead"><?php echo e(__('public.help_page.browse_subtitle')); ?></p>
            <div class="f-grid">
                <div class="f-card">
                    <span class="fh-card-icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'user', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                    <h3><?php echo e(__('public.help_page.for_patients')); ?></h3>
                    <p class="f-muted"><?php echo e(__('public.help_page.for_patients_desc')); ?></p>
                    <ul class="fh-links">
                        <li><a href="<?php echo e(route('public.solutions.patients')); ?>"><?php echo e(__('public.help_page.lnk_what_is_hid')); ?></a></li>
                        <li><a href="<?php echo e(route('register.patient')); ?>"><?php echo e(__('public.help_page.lnk_how_register')); ?></a></li>
                        <li><a href="<?php echo e(route('public.consent')); ?>"><?php echo e(__('public.help_page.lnk_manage_consent')); ?></a></li>
                        <li><a href="<?php echo e(route('public.privacy')); ?>"><?php echo e(__('public.help_page.lnk_data_privacy')); ?></a></li>
                    </ul>
                </div>
                <div class="f-card">
                    <span class="fh-card-icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'building-2', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                    <h3><?php echo e(__('public.help_page.for_institutions')); ?></h3>
                    <p class="f-muted"><?php echo e(__('public.help_page.for_institutions_desc')); ?></p>
                    <ul class="fh-links">
                        <li><a href="<?php echo e(route('public.solutions.hospitals')); ?>"><?php echo e(__('public.help_page.lnk_hospital_guide')); ?></a></li>
                        <li><a href="<?php echo e(route('public.solutions.laboratories')); ?>"><?php echo e(__('public.help_page.lnk_lab_guide')); ?></a></li>
                        <li><a href="<?php echo e(route('public.solutions.pharmacies')); ?>"><?php echo e(__('public.help_page.lnk_pharmacy_guide')); ?></a></li>
                        <li><a href="<?php echo e(route('register.organization')); ?>"><?php echo e(__('public.help_page.lnk_register_facility')); ?></a></li>
                    </ul>
                </div>
                <div class="f-card">
                    <span class="fh-card-icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'globe', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                    <h3><?php echo e(__('public.help_page.for_developers')); ?></h3>
                    <p class="f-muted"><?php echo e(__('public.help_page.for_developers_desc')); ?></p>
                    <ul class="fh-links">
                        <li><a href="<?php echo e(route('public.developers')); ?>"><?php echo e(__('public.help_page.lnk_dev_portal')); ?></a></li>
                        <li><a href="<?php echo e(route('public.developers')); ?>#sdk"><?php echo e(__('public.help_page.lnk_sdk_docs')); ?></a></li>
                        <li><a href="<?php echo e(route('public.interoperability')); ?>"><?php echo e(__('public.help_page.lnk_interop')); ?></a></li>
                        <li><a href="<?php echo e(route('public.status')); ?>"><?php echo e(__('public.help_page.lnk_api_status')); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    
    <section class="f-section f-section--wash">
        <div class="f-container">
            <h2 class="f-h2"><?php echo e(__('public.help_page.popular_title')); ?></h2>
            <p class="f-lead"><?php echo e(__('public.help_page.popular_subtitle')); ?></p>
            <?php
            $topics = [
                ['icon'=>'user','title'=>__('public.help_page.topic_health_id_title'),'desc'=>__('public.help_page.topic_health_id_desc'),'link'=>route('register.patient')],
                ['icon'=>'qr-code','title'=>__('public.help_page.topic_qr_title'),'desc'=>__('public.help_page.topic_qr_desc'),'link'=>route('portals.patient')],
                ['icon'=>'shield-check','title'=>__('public.help_page.topic_consent_title'),'desc'=>__('public.help_page.topic_consent_desc'),'link'=>route('public.consent')],
                ['icon'=>'clock','title'=>__('public.help_page.topic_timeline_title'),'desc'=>__('public.help_page.topic_timeline_desc'),'link'=>route('portals.patient')],
                ['icon'=>'building-2','title'=>__('public.help_page.topic_facility_title'),'desc'=>__('public.help_page.topic_facility_desc'),'link'=>route('register.organization')],
                ['icon'=>'refresh-cw','title'=>__('public.help_page.topic_password_title'),'desc'=>__('public.help_page.topic_password_desc'),'link'=>route('password.request')],
                ['icon'=>'send','title'=>__('public.help_page.topic_webhooks_title'),'desc'=>__('public.help_page.topic_webhooks_desc'),'link'=>route('public.developers').'#webhooks'],
                ['icon'=>'phone','title'=>__('public.help_page.topic_contact_title'),'desc'=>__('public.help_page.topic_contact_desc'),'link'=>route('public.contact')],
            ];
            ?>
            <div class="f-grid">
                <?php $__currentLoopData = $topics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $topic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e($topic['link']); ?>" class="f-card fh-topic">
                    <?php echo $__env->make('partials.finder.icon', ['name' => $topic['icon'], 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <span>
                        <strong><?php echo e($topic['title']); ?></strong><br>
                        <span class="f-muted f-small"><?php echo e($topic['desc']); ?></span>
                    </span>
                </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </section>

    
    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fh-narrow">
                <h2 class="f-h2"><?php echo e(__('public.help_page.still_help_title')); ?></h2>
                <p class="f-lead"><?php echo e(__('public.help_page.still_help_body')); ?></p>
                <div class="fh-cta-row">
                    <a href="<?php echo e(route('public.contact')); ?>" class="f-btn f-btn--primary"><?php echo e(__('public.help_page.btn_contact')); ?></a>
                    <a href="<?php echo e(route('public.faq')); ?>" class="f-btn"><?php echo e(__('public.help_page.btn_faq')); ?></a>
                </div>
            </div>
        </div>
    </section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.finder', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\public\help.blade.php ENDPATH**/ ?>
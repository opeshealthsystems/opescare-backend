<?php $__env->startSection('title', __('landing.page_title_home')); ?>
<?php $__env->startSection('meta_description', __('landing.page_desc_home')); ?>

<?php $__env->startSection('content'); ?>

    

    
    <section class="lp-hero">
        <div class="container lp-hero-inner">
            <div class="lp-hero-copy">
                <div class="lp-badge"><i data-lucide="id-card"></i> <?php echo e(__('landing.hero.badge')); ?></div>
                <h1><?php echo e(__('landing.hero.title')); ?></h1>
                <p class="lp-hero-sub"><?php echo e(__('landing.hero.subtitle')); ?></p>

                
                <p class="lp-positioning">
                    <i data-lucide="quote"></i>
                    <span><?php echo e(__('landing.hero.positioning')); ?></span>
                </p>

                <div class="lp-hero-actions">
                    <a href="<?php echo e(route('public.request-demo')); ?>" class="btn btn-primary btn-lg">
                        <i data-lucide="cable"></i> <?php echo e(__('landing.hero.cta_primary')); ?>

                    </a>
                    <a href="<?php echo e(route('register.patient')); ?>" class="btn btn-secondary btn-lg">
                        <i data-lucide="id-card"></i> <?php echo e(__('landing.hero.cta_secondary')); ?>

                    </a>
                </div>

                <div class="lp-hero-facts">
                    <span><i data-lucide="languages"></i> <?php echo e(__('landing.hero.fact_bilingual')); ?></span>
                    <span><i data-lucide="git-merge"></i> <?php echo e(__('landing.hero.fact_standards')); ?></span>
                    <span><i data-lucide="shield-check"></i> <?php echo e(__('landing.hero.fact_consent')); ?></span>
                </div>
            </div>

            
            <div class="lp-hero-visual" aria-hidden="true">
                <div class="lp-identity">
                    <div class="lp-identity-core">
                        <div class="lp-identity-pulse"></div>
                        <i data-lucide="id-card"></i>
                        <strong><?php echo e(__('landing.hero_card.label_health_id')); ?></strong>
                        <span class="lp-identity-id"><?php echo e(__('landing.hero_card.demo_id')); ?></span>
                        <span class="lp-identity-verified"><i data-lucide="badge-check"></i> <?php echo e(__('landing.hero_card.label_verified')); ?></span>
                    </div>
                    <div class="lp-identity-ring">
                        <span class="lp-node n1"><i data-lucide="hospital"></i><em><?php echo e(__('landing.ecosystem.chip_hospitals')); ?></em></span>
                        <span class="lp-node n2"><i data-lucide="flask-conical"></i><em><?php echo e(__('landing.ecosystem.chip_labs')); ?></em></span>
                        <span class="lp-node n3"><i data-lucide="pill"></i><em><?php echo e(__('landing.ecosystem.chip_pharmacies')); ?></em></span>
                        <span class="lp-node n4"><i data-lucide="heart-handshake"></i><em><?php echo e(__('landing.ecosystem.chip_insurers')); ?></em></span>
                        <span class="lp-node n5"><i data-lucide="landmark"></i><em><?php echo e(__('landing.ecosystem.chip_public_health')); ?></em></span>
                        <span class="lp-node n6"><i data-lucide="stethoscope"></i><em><?php echo e(__('landing.ecosystem.chip_providers')); ?></em></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <section class="section section-muted">
        <div class="container text-center mb-12">
            <h2><?php echo e(__('landing.problem.title')); ?></h2>
            <p class="section-subtitle"><?php echo e(__('landing.problem.subtitle')); ?></p>
        </div>
        <div class="container lp-grid-4">
            <div class="card card-problem">
                <div class="problem-header"><i data-lucide="users"></i><h3><?php echo e(__('landing.problem.identity_title')); ?></h3></div>
                <p><?php echo e(__('landing.problem.identity_desc')); ?></p>
            </div>
            <div class="card card-problem">
                <div class="problem-header"><i data-lucide="unlink"></i><h3><?php echo e(__('landing.problem.records_title')); ?></h3></div>
                <p><?php echo e(__('landing.problem.records_desc')); ?></p>
            </div>
            <div class="card card-problem">
                <div class="problem-header"><i data-lucide="clock-alert"></i><h3><?php echo e(__('landing.problem.delay_title')); ?></h3></div>
                <p><?php echo e(__('landing.problem.delay_desc')); ?></p>
            </div>
            <div class="card card-problem">
                <div class="problem-header"><i data-lucide="eye-off"></i><h3><?php echo e(__('landing.problem.visibility_title')); ?></h3></div>
                <p><?php echo e(__('landing.problem.visibility_desc')); ?></p>
            </div>
        </div>
    </section>

    
    <section class="section">
        <div class="container text-center mb-12">
            <h2><?php echo e(__('landing.answer.title')); ?></h2>
            <p class="section-subtitle"><?php echo e(__('landing.answer.subtitle')); ?></p>
        </div>
        <div class="container">
            <ol class="lp-chain">
                <?php $__currentLoopData = ['identity' => 'id-card', 'index' => 'git-merge', 'trust' => 'shield-check', 'interop' => 'cable', 'care' => 'heart-pulse']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $icon): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="lp-chain-step">
                        <div class="lp-chain-icon"><i data-lucide="<?php echo e($icon); ?>"></i></div>
                        <h3><?php echo e(__("landing.answer.{$key}_title")); ?></h3>
                        <p><?php echo e(__("landing.answer.{$key}_desc")); ?></p>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ol>
        </div>
    </section>

    
    <section class="section section-muted" id="how-it-works">
        <div class="container text-center mb-12">
            <h2><?php echo e(__('landing.exchange.title')); ?></h2>
            <p class="section-subtitle"><?php echo e(__('landing.exchange.subtitle')); ?></p>
        </div>

        <div class="container">
            
            <div class="lp-exchange" aria-hidden="true">
                <div class="lp-exchange-end">
                    <i data-lucide="hospital"></i>
                    <strong><?php echo e(__('landing.exchange.source_title')); ?></strong>
                    <span><?php echo e(__('landing.exchange.source_desc')); ?></span>
                </div>
                <div class="lp-exchange-flow"><i data-lucide="arrow-left-right"></i></div>
                <div class="lp-exchange-core">
                    <i data-lucide="network"></i>
                    <strong><?php echo e(__('landing.exchange.core_title')); ?></strong>
                    <span><?php echo e(__('landing.exchange.core_desc')); ?></span>
                </div>
                <div class="lp-exchange-flow"><i data-lucide="arrow-left-right"></i></div>
                <div class="lp-exchange-end">
                    <i data-lucide="building-2"></i>
                    <strong><?php echo e(__('landing.exchange.target_title')); ?></strong>
                    <span><?php echo e(__('landing.exchange.target_desc')); ?></span>
                </div>
            </div>

            
            <ol class="lp-steps">
                <?php $__currentLoopData = ['identify' => 'user-search', 'match' => 'git-merge', 'authorize' => 'shield-check', 'exchange' => 'refresh-cw', 'record' => 'history']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $icon): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="lp-step">
                        <span class="lp-step-num"><?php echo e($loop->iteration); ?></span>
                        <i data-lucide="<?php echo e($icon); ?>"></i>
                        <h4><?php echo e(__("landing.exchange.step_{$key}_title")); ?></h4>
                        <p><?php echo e(__("landing.exchange.step_{$key}_desc")); ?></p>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ol>

            <div class="lp-transports">
                <span class="lp-transports-label"><?php echo e(__('landing.exchange.transports_label')); ?></span>
                <div class="lp-transport-chips">
                    <span>FHIR R4</span><span>Connect API</span><span>SDK</span>
                    <span>Widget</span><span>Bridge Agent</span><span>OpesCare Lite</span><span>Webhooks</span>
                </div>
                <a href="<?php echo e(route('public.interoperability')); ?>" class="lp-arrow-link">
                    <?php echo e(__('landing.exchange.cta')); ?> <i data-lucide="arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    
    <section class="section">
        <div class="container text-center mb-12">
            <h2><?php echo e(__('landing.pillars.title')); ?></h2>
            <p class="section-subtitle"><?php echo e(__('landing.pillars.subtitle')); ?></p>
        </div>
        <div class="container lp-pillars">
            
            <?php $__currentLoopData = [
                'identity' => ['icon' => 'id-card',       'href' => route('public.solutions.patients')],
                'index'    => ['icon' => 'git-merge',     'href' => '#how-it-works'],
                'record'   => ['icon' => 'history',       'href' => route('public.solutions.patients') . '#timeline'],
                'trust'    => ['icon' => 'shield-check',  'href' => route('public.consent')],
                'interop'  => ['icon' => 'cable',         'href' => route('public.interoperability')],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e($meta['href']); ?>" class="card lp-pillar">
                    <div class="lp-pillar-icon"><i data-lucide="<?php echo e($meta['icon']); ?>"></i></div>
                    <h3><?php echo e(__("landing.pillars.{$key}_title")); ?></h3>
                    <p><?php echo e(__("landing.pillars.{$key}_desc")); ?></p>
                    <span class="lp-pillar-more"><i data-lucide="arrow-right"></i></span>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <div class="container lp-also">
            <span class="lp-also-label"><?php echo e(__('landing.pillars.also_label')); ?></span>
            <ul>
                <li><i data-lucide="send"></i> <?php echo e(__('landing.pillars.also_referrals')); ?> <a href="<?php echo e(route('public.interoperability')); ?>"><?php echo e(__('landing.pillars.also_more')); ?></a></li>
                <li><i data-lucide="flask-conical"></i> <?php echo e(__('landing.pillars.also_labs')); ?> <a href="<?php echo e(route('public.solutions.laboratories')); ?>"><?php echo e(__('landing.pillars.also_more')); ?></a></li>
                <li><i data-lucide="pill"></i> <?php echo e(__('landing.pillars.also_pharmacy')); ?> <a href="<?php echo e(route('public.solutions.pharmacies')); ?>"><?php echo e(__('landing.pillars.also_more')); ?></a></li>
                <li><i data-lucide="heart-handshake"></i> <?php echo e(__('landing.pillars.also_insurance')); ?> <a href="<?php echo e(route('public.solutions.insurers')); ?>"><?php echo e(__('landing.pillars.also_more')); ?></a></li>
            </ul>
        </div>
    </section>

    
    <section class="section section-muted" id="network">
        <div class="container text-center mb-12">
            <h2><?php echo e(__('landing.network.title')); ?></h2>
            <p class="section-subtitle"><?php echo e(__('landing.network.subtitle')); ?></p>
        </div>
        <div class="container grid-2">
            <a href="<?php echo e(route('public.network.medicine-finder')); ?>" class="card lp-service lp-service-med">
                <div class="lp-service-icon"><i data-lucide="map-pin"></i></div>
                <h3><?php echo e(__('landing.network.medicine_title')); ?></h3>
                <p><?php echo e(__('landing.network.medicine_desc')); ?></p>
                <span class="lp-arrow-link"><?php echo e(__('landing.network.medicine_cta')); ?> <i data-lucide="arrow-right"></i></span>
            </a>
            <a href="<?php echo e(route('public.network.blood-finder')); ?>" class="card lp-service lp-service-blood">
                <div class="lp-service-icon"><i data-lucide="droplet"></i></div>
                <h3><?php echo e(__('landing.network.blood_title')); ?></h3>
                <p><?php echo e(__('landing.network.blood_desc')); ?></p>
                <span class="lp-arrow-link"><?php echo e(__('landing.network.blood_cta')); ?> <i data-lucide="arrow-right"></i></span>
            </a>
        </div>
        <div class="container text-center" style="margin-top:1.75rem;">
            <p class="lp-note"><i data-lucide="info"></i> <?php echo e(__('landing.network.note')); ?></p>
        </div>
    </section>

    
    <section class="section" id="ecosystem">
        <div class="container text-center mb-12">
            <h2><?php echo e(__('landing.ecosystem.title')); ?></h2>
            <p class="section-subtitle"><?php echo e(__('landing.ecosystem.subtitle')); ?></p>
        </div>

        <div class="container">
            <div class="lp-chips">
                <a href="<?php echo e(route('public.solutions.patients')); ?>"><i data-lucide="user"></i> <?php echo e(__('landing.ecosystem.chip_patients')); ?></a>
                <a href="<?php echo e(route('public.solutions.hospitals')); ?>"><i data-lucide="stethoscope"></i> <?php echo e(__('landing.ecosystem.chip_providers')); ?></a>
                <a href="<?php echo e(route('public.solutions.hospitals')); ?>"><i data-lucide="hospital"></i> <?php echo e(__('landing.ecosystem.chip_hospitals')); ?></a>
                <a href="<?php echo e(route('public.solutions.laboratories')); ?>"><i data-lucide="flask-conical"></i> <?php echo e(__('landing.ecosystem.chip_labs')); ?></a>
                <a href="<?php echo e(route('public.solutions.pharmacies')); ?>"><i data-lucide="pill"></i> <?php echo e(__('landing.ecosystem.chip_pharmacies')); ?></a>
                <a href="<?php echo e(route('public.solutions.insurers')); ?>"><i data-lucide="heart-handshake"></i> <?php echo e(__('landing.ecosystem.chip_insurers')); ?></a>
                <a href="<?php echo e(route('public.solutions.public-health')); ?>"><i data-lucide="landmark"></i> <?php echo e(__('landing.ecosystem.chip_public_health')); ?></a>
                <a href="<?php echo e(route('public.developers')); ?>"><i data-lucide="code-2"></i> <?php echo e(__('landing.ecosystem.chip_developers')); ?></a>
            </div>

            <div class="lp-destinations">
                <a href="<?php echo e(route('public.solutions.patients')); ?>" class="card lp-destination">
                    <i data-lucide="user"></i>
                    <h3><?php echo e(__('landing.ecosystem.card_patients_title')); ?></h3>
                    <p><?php echo e(__('landing.ecosystem.card_patients_desc')); ?></p>
                </a>
                <a href="<?php echo e(route('public.solutions.hospitals')); ?>" class="card lp-destination">
                    <i data-lucide="hospital"></i>
                    <h3><?php echo e(__('landing.ecosystem.card_facilities_title')); ?></h3>
                    <p><?php echo e(__('landing.ecosystem.card_facilities_desc')); ?></p>
                </a>
                <a href="<?php echo e(route('public.solutions.insurers')); ?>" class="card lp-destination">
                    <i data-lucide="landmark"></i>
                    <h3><?php echo e(__('landing.ecosystem.card_orgs_title')); ?></h3>
                    <p><?php echo e(__('landing.ecosystem.card_orgs_desc')); ?></p>
                </a>
                <a href="<?php echo e(route('public.developers')); ?>" class="card lp-destination">
                    <i data-lucide="code-2"></i>
                    <h3><?php echo e(__('landing.ecosystem.card_devs_title')); ?></h3>
                    <p><?php echo e(__('landing.ecosystem.card_devs_desc')); ?></p>
                </a>
            </div>
        </div>
    </section>

    
    <section class="section section-dark" id="trust">
        <div class="container grid-2 items-center">
            <div>
                <h2 class="text-white"><?php echo e(__('landing.trust.title')); ?></h2>
                <p class="text-lg text-muted-light mb-8"><?php echo e(__('landing.trust.desc')); ?></p>

                
                <ul class="lp-consent-questions">
                    <li><i data-lucide="user-search"></i> <?php echo e(__('landing.trust.q_who')); ?></li>
                    <li><i data-lucide="help-circle"></i> <?php echo e(__('landing.trust.q_why')); ?></li>
                    <li><i data-lucide="layers"></i> <?php echo e(__('landing.trust.q_what')); ?></li>
                    <li><i data-lucide="clock"></i> <?php echo e(__('landing.trust.q_how_long')); ?></li>
                    <li><i data-lucide="toggle-right"></i> <?php echo e(__('landing.trust.q_control')); ?></li>
                </ul>

                <a href="<?php echo e(route('public.consent')); ?>" class="lp-arrow-link lp-arrow-light">
                    <?php echo e(__('landing.trust.consent_cta')); ?> <i data-lucide="arrow-right"></i>
                </a>
            </div>

            <div class="lp-trust-side">
                
                <div class="lp-breakglass">
                    <div class="lp-breakglass-head">
                        <i data-lucide="siren"></i>
                        <strong><?php echo e(__('landing.trust.emergency_title')); ?></strong>
                    </div>
                    <p><?php echo e(__('landing.trust.emergency_desc')); ?></p>
                    <a href="<?php echo e(route('public.care-map.emergency')); ?>" class="lp-arrow-link lp-arrow-light">
                        <?php echo e(__('landing.trust.emergency_cta')); ?> <i data-lucide="arrow-right"></i>
                    </a>
                </div>

                <div class="lp-trust-pillars">
                    <div><i data-lucide="lock-keyhole"></i><strong><?php echo e(__('landing.trust.pillar_private_title')); ?></strong><span><?php echo e(__('landing.trust.pillar_private_desc')); ?></span></div>
                    <div><i data-lucide="clipboard-check"></i><strong><?php echo e(__('landing.trust.pillar_audit_title')); ?></strong><span><?php echo e(__('landing.trust.pillar_audit_desc')); ?></span></div>
                    <div><i data-lucide="git-merge"></i><strong><?php echo e(__('landing.trust.pillar_standards_title')); ?></strong><span><?php echo e(__('landing.trust.pillar_standards_desc')); ?></span></div>
                    <div><i data-lucide="languages"></i><strong><?php echo e(__('landing.trust.pillar_local_title')); ?></strong><span><?php echo e(__('landing.trust.pillar_local_desc')); ?></span></div>
                </div>

                <a href="<?php echo e(route('public.security')); ?>" class="btn btn-secondary lp-trust-cta">
                    <?php echo e(__('landing.trust.security_cta')); ?> <i data-lucide="arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    
    <section class="section lp-final">
        <div class="container text-center">
            <h2><?php echo e(__('landing.footer_cta.title')); ?></h2>
            <p class="section-subtitle" style="margin-bottom:2.5rem;"><?php echo e(__('landing.footer_cta.subtitle')); ?></p>
            <div class="lp-final-actions">
                <a href="<?php echo e(route('public.request-demo')); ?>" class="btn btn-primary btn-lg">
                    <i data-lucide="cable"></i> <?php echo e(__('landing.footer_cta.cta_primary')); ?>

                </a>
                <a href="<?php echo e(route('register.patient')); ?>" class="btn btn-secondary btn-lg">
                    <i data-lucide="id-card"></i> <?php echo e(__('landing.footer_cta.cta_secondary')); ?>

                </a>
            </div>
            <p class="lp-faq-prompt">
                <?php echo e(__('landing.footer_cta.faq_prompt')); ?>

                <a href="<?php echo e(route('public.faq')); ?>"><?php echo e(__('landing.footer_cta.faq_cta')); ?> <i data-lucide="arrow-right"></i></a>
            </p>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/public/landing.blade.php ENDPATH**/ ?>
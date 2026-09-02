<?php $__env->startSection('title', __('public.interop_page.page_title')); ?>
<?php $__env->startSection('meta_description', __('public.interop_page.meta_description')); ?>

<?php $__env->startSection('head_scripts'); ?>
<style>
.interop-diagram { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 1rem; }
.interop-nodes   { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; margin-top: 1.25rem; }
@media (max-width: 540px) {
    .interop-diagram { grid-template-columns: 1fr; text-align: center; }
    .interop-nodes   { grid-template-columns: 1fr; }
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    
    <header class="content-header">
        <div class="container">
            <div class="badge" style="background:rgba(20,184,166,.15);color:#0F766E;margin-bottom:1rem;"><?php echo e(__('public.interop_page.badge')); ?></div>
            <h1><?php echo e(__('public.interop_page.hero_title')); ?></h1>
            <p class="text-muted" style="max-width:760px;margin:0 auto;font-size:1.2rem;">
                <?php echo e(__('public.interop_page.hero_subtitle')); ?>

            </p>
            <div style="margin-top:2.5rem;display:flex;justify-content:center;flex-wrap:wrap;gap:1rem;">
                <a href="<?php echo e(route('public.developers')); ?>" class="btn btn-primary"><?php echo e(__('public.interop_page.btn_dev_docs')); ?></a>
                <a href="<?php echo e(route('public.contact')); ?>" class="btn btn-secondary"><?php echo e(__('public.interop_page.btn_contact')); ?></a>
            </div>
        </div>
    </header>

    
    <section class="content-body">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <h2><?php echo e(__('public.interop_page.push_pull_title')); ?></h2>
                    <p class="text-muted" style="margin-bottom:2rem;">
                        <?php echo e(__('public.interop_page.push_pull_desc')); ?>

                    </p>
                    <div style="display:grid;gap:1rem;">
                        <div style="padding:1.5rem;background:#F0F9FF;border-left:4px solid #0F4C81;border-radius:.75rem;">
                            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem;">
                                <i data-lucide="upload" style="width:1.1rem;height:1.1rem;color:#0F4C81;"></i>
                                <h4 style="margin:0;color:#0F4C81;"><?php echo e(__('public.interop_page.push_title')); ?></h4>
                            </div>
                            <p style="margin:0;font-size:.9375rem;color:#1e293b;"><?php echo e(__('public.interop_page.push_desc')); ?></p>
                        </div>
                        <div style="padding:1.5rem;background:#F0FDF4;border-left:4px solid #14B8A6;border-radius:.75rem;">
                            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem;">
                                <i data-lucide="download" style="width:1.1rem;height:1.1rem;color:#0F766E;"></i>
                                <h4 style="margin:0;color:#0F766E;"><?php echo e(__('public.interop_page.pull_title')); ?></h4>
                            </div>
                            <p style="margin:0;font-size:.9375rem;color:#1e293b;"><?php echo e(__('public.interop_page.pull_desc')); ?></p>
                        </div>
                    </div>
                </div>
                <div class="hero-visual">
                    
                    <div style="background:#0F2744;border-radius:1.5rem;padding:2rem;color:#fff;">
                        <div style="text-align:center;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:1.5rem;"><?php echo e(__('public.interop_page.interop_flow')); ?></div>
                        <div class="interop-diagram">
                            
                            <div style="background:rgba(255,255,255,.07);border-radius:1rem;padding:1.25rem;text-align:center;">
                                <i data-lucide="hospital" style="width:2rem;height:2rem;color:#93c5fd;margin-bottom:.5rem;display:block;margin:0 auto .5rem;"></i>
                                <div style="font-size:.8125rem;font-weight:600;"><?php echo e(__('public.interop_page.facility_label')); ?></div>
                                <div style="font-size:.6875rem;color:#94a3b8;margin-top:.25rem;"><?php echo e(__('public.interop_page.facility_role')); ?></div>
                            </div>
                            
                            <div style="text-align:center;">
                                <div style="font-size:.75rem;color:#14B8A6;margin-bottom:.5rem;">Push →</div>
                                <div style="width:2rem;height:2rem;background:#0F4C81;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                    <i data-lucide="shield-check" style="width:1rem;height:1rem;color:#fff;"></i>
                                </div>
                                <div style="font-size:.75rem;color:#93c5fd;margin-top:.5rem;">← Pull</div>
                            </div>
                            
                            <div style="background:rgba(15,76,129,.4);border:1px solid rgba(99,179,237,.3);border-radius:1rem;padding:1.25rem;text-align:center;">
                                <img src="<?php echo e(asset('brand/opescare-favicon.png')); ?>" width="28" height="28" style="display:block;margin:0 auto .5rem;" alt="">
                                <div style="font-size:.8125rem;font-weight:600;">OpesCare</div>
                                <div style="font-size:.6875rem;color:#94a3b8;margin-top:.25rem;"><?php echo e(__('public.interop_page.platform_role')); ?></div>
                            </div>
                        </div>
                        <div class="interop-nodes">
                            <?php $__currentLoopData = ['Laboratory','Pharmacy','Insurer']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $node): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div style="background:rgba(255,255,255,.07);border-radius:.75rem;padding:.75rem;text-align:center;font-size:.75rem;color:#cbd5e1;"><?php echo e($node); ?></div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <section class="section" style="background:#F8FAFC;">
        <div class="container">
            <div class="section-header">
                <h2><?php echo e(__('public.interop_page.methods_title')); ?></h2>
                <p class="text-muted"><?php echo e(__('public.interop_page.methods_subtitle')); ?></p>
            </div>
            <div class="card-grid">
                <?php
                $methods = [
                    ['icon'=>'braces','title'=>'Connect API','badge'=>__('public.interop_page.badge_enterprise'),'color'=>'#0F4C81','desc'=>__('public.interop_page.method_api_desc'),'link'=>route('public.developers').'#api'],
                    ['icon'=>'code-2','title'=>'Connect SDK','badge'=>__('public.interop_page.badge_developer'),'color'=>'#6D28D9','desc'=>__('public.interop_page.method_sdk_desc'),'link'=>route('public.developers').'#sdk'],
                    ['icon'=>'panel-top','title'=>'Connect Widget','badge'=>__('public.interop_page.badge_web_embed'),'color'=>'#0F766E','desc'=>__('public.interop_page.method_widget_desc'),'link'=>route('public.developers').'#widget'],
                    ['icon'=>'cpu','title'=>'Bridge Agent','badge'=>__('public.interop_page.badge_legacy'),'color'=>'#C2410C','desc'=>__('public.interop_page.method_bridge_desc'),'link'=>route('public.developers').'#bridge'],
                    ['icon'=>'layout-dashboard','title'=>'OpesCare Lite','badge'=>__('public.interop_page.badge_no_code'),'color'=>'#065F46','desc'=>__('public.interop_page.method_lite_desc'),'link'=>route('public.developers').'#lite'],
                ];
                ?>
                <?php $__currentLoopData = $methods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $method): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="card">
                    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
                        <div style="width:2.25rem;height:2.25rem;background:<?php echo e($method['color']); ?>1A;color:<?php echo e($method['color']); ?>;border-radius:.75rem;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="<?php echo e($method['icon']); ?>" style="width:1rem;height:1rem;"></i>
                        </div>
                        <h3 style="margin:0;font-size:1rem;"><?php echo e($method['title']); ?></h3>
                        <span style="margin-left:auto;font-size:.6875rem;font-weight:700;padding:.2rem .6rem;background:<?php echo e($method['color']); ?>1A;color:<?php echo e($method['color']); ?>;border-radius:999px;"><?php echo e($method['badge']); ?></span>
                    </div>
                    <p class="text-muted" style="font-size:.875rem;margin-bottom:1.25rem;"><?php echo e($method['desc']); ?></p>
                    <a href="<?php echo e($method['link']); ?>" style="font-size:.875rem;font-weight:600;color:<?php echo e($method['color']); ?>;text-decoration:none;"><?php echo e(__('public.interop_page.learn_more')); ?></a>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </section>

    
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2><?php echo e(__('public.interop_page.standards_title')); ?></h2>
                <p class="text-muted"><?php echo e(__('public.interop_page.standards_subtitle')); ?></p>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;max-width:960px;margin:0 auto;">
                <?php $__currentLoopData = [
                    ['title'=>'HL7 FHIR R4','desc'=>'Patient, Encounter, Observation, MedicationRequest, DiagnosticReport resources mapped to the FHIR R4 standard.'],
                    ['title'=>'RESTful JSON','desc'=>'All API endpoints return structured JSON. OpenAPI 3.0 specification available to registered partners.'],
                    ['title'=>'ISO 8583 for Billing','desc'=>'Financial transaction messages for insurance claims and billing reconciliation follow ISO messaging standards.'],
                    ['title'=>'HL7 v2 Import','desc'=>'The Bridge Agent can parse and translate legacy HL7 v2.x message files from older EMR systems into FHIR-compatible records.'],
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $std): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="background:#F8FAFC;border:1px solid #e2e8f0;border-radius:1.25rem;padding:1.75rem;">
                    <h4 style="margin:0 0 .5rem;color:#0F4C81;"><?php echo e($std['title']); ?></h4>
                    <p class="text-muted" style="font-size:.875rem;margin:0;"><?php echo e($std['desc']); ?></p>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </section>

    
    <section class="section" style="background:#0F2744;color:#fff;text-align:center;">
        <div class="container" style="max-width:640px;">
            <h2 style="color:#fff;margin-bottom:1rem;"><?php echo e(__('public.interop_page.cta_title')); ?></h2>
            <p style="color:rgba(255,255,255,.75);margin-bottom:2rem;"><?php echo e(__('public.interop_page.cta_body')); ?></p>
            <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:1rem;">
                <a href="<?php echo e(route('register.organization')); ?>" class="btn btn-primary"><?php echo e(__('public.interop_page.btn_register')); ?></a>
                <a href="<?php echo e(route('public.developers')); ?>" class="btn" style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.25);"><?php echo e(__('public.interop_page.btn_dev')); ?></a>
            </div>
        </div>
    </section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\public\interoperability.blade.php ENDPATH**/ ?>
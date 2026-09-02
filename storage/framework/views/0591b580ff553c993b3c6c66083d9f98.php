<?php $__env->startSection('title', __('pricing.page_title')); ?>
<?php $__env->startSection('meta_description', __('pricing.meta_description')); ?>

<?php $__env->startSection('content'); ?>

<section class="content-header" style="background:linear-gradient(135deg,#0F2744 0%,#0F4C81 100%);padding:4rem 0 3rem;color:#fff;">
    <div class="container" style="text-align:center;">
        <div style="display:inline-flex;align-items:center;gap:0.5rem;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:2rem;padding:0.35rem 1rem;font-size:0.75rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#BAE6FD;margin-bottom:1.5rem;">
            <i data-lucide="tag" style="width:0.875rem;height:0.875rem;"></i>
            <?php echo e(__('pricing.badge')); ?>

        </div>
        <h1 style="font-size:clamp(2rem,4vw,3rem);font-weight:900;margin:0 0 1rem;"><?php echo e(__('pricing.heading')); ?></h1>
        <p style="font-size:1.125rem;color:#BAE6FD;max-width:620px;margin:0 auto;"><?php echo e(__('pricing.subheading')); ?></p>
    </div>
</section>


<section style="padding:4rem 0 2rem;">
    <div class="container" style="max-width:960px;">
        <div style="text-align:center;margin-bottom:2.5rem;">
            <h2 style="font-size:1.5rem;font-weight:800;color:var(--color-text-primary);margin:0 0 .5rem;"><?php echo e(__('pricing.patients_title')); ?></h2>
            <p style="color:var(--color-text-secondary);max-width:560px;margin:0 auto;"><?php echo e(__('pricing.patients_subtitle')); ?></p>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;align-items:stretch;">
            <?php $__currentLoopData = $patientPlans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php $isPremium = ! $plan->isFree(); ?>
                <div style="position:relative;display:flex;flex-direction:column;border:1px solid <?php echo e($isPremium ? '#0F4C81' : 'var(--color-border)'); ?>;border-radius:1rem;padding:2rem;background:#fff;<?php echo e($isPremium ? 'box-shadow:0 10px 30px rgba(15,76,129,.12);' : ''); ?>">
                    <?php if($isPremium): ?>
                        <div style="position:absolute;top:-0.75rem;left:50%;transform:translateX(-50%);background:#0F4C81;color:#fff;font-size:0.7rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;padding:0.3rem 0.85rem;border-radius:2rem;"><?php echo e(__('pricing.most_popular')); ?></div>
                    <?php endif; ?>

                    <h3 style="font-size:1.25rem;font-weight:800;color:var(--color-text-primary);margin:0 0 .5rem;"><?php echo e($plan->name); ?></h3>
                    <p style="color:var(--color-text-secondary);font-size:.9rem;min-height:2.5rem;margin:0 0 1rem;"><?php echo e($plan->description); ?></p>

                    <div style="margin-bottom:1.25rem;">
                        <?php if($plan->isFree()): ?>
                            <span style="font-size:2.25rem;font-weight:900;color:#0F4C81;"><?php echo e(__('pricing.free_label')); ?></span>
                        <?php else: ?>
                            <span style="font-size:2.25rem;font-weight:900;color:#0F4C81;"><?php echo e($plan->priceFormatted()); ?></span>
                            <span style="color:var(--color-text-secondary);"><?php echo e(__('pricing.per_month')); ?></span>
                            <?php if($plan->annualPriceFormatted()): ?>
                                <div style="font-size:.85rem;color:var(--color-text-secondary);margin-top:.35rem;">
                                    <i data-lucide="check-circle" style="width:.85rem;height:.85rem;color:#0F4C81;vertical-align:middle;"></i>
                                    <?php echo e($plan->annualPriceFormatted()); ?><?php echo e(__('pricing.per_year')); ?> — <?php echo e(__('pricing.save_annual')); ?>

                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div style="font-size:.75rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--color-text-secondary);margin-bottom:.6rem;"><?php echo e(__('pricing.whats_included')); ?></div>
                    <ul style="list-style:none;padding:0;margin:0 0 1.5rem;display:grid;gap:.55rem;flex:1;">
                        <?php $__currentLoopData = $plan->planFeatures; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $feature): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li style="display:flex;align-items:flex-start;gap:.5rem;font-size:.9rem;color:var(--color-text-primary);">
                                <i data-lucide="check" style="width:1rem;height:1rem;color:#0F4C81;flex-shrink:0;margin-top:.15rem;"></i>
                                <?php echo e($feature->feature_label); ?>

                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>

                    <a href="<?php echo e(route('register.patient')); ?>" class="btn <?php echo e($isPremium ? 'btn-primary' : 'btn-secondary'); ?>" style="width:100%;text-align:center;justify-content:center;">
                        <?php echo e($isPremium ? __('pricing.cta_premium') : __('pricing.cta_free')); ?>

                    </a>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>


<section style="padding:2rem 0 4rem;background:var(--color-surface,#F8FAFC);">
    <div class="container" style="max-width:1040px;">
        <div style="text-align:center;margin:2rem 0 2.5rem;">
            <h2 style="font-size:1.5rem;font-weight:800;color:var(--color-text-primary);margin:0 0 .5rem;"><?php echo e(__('pricing.orgs_title')); ?></h2>
            <p style="color:var(--color-text-secondary);max-width:600px;margin:0 auto;"><?php echo e(__('pricing.orgs_subtitle')); ?></p>
        </div>

        <?php
            $orgs = [
                ['icon' => 'hospital',      'type' => 'facility',  'title' => __('pricing.org_facilities_title'),  'desc' => __('pricing.org_facilities_desc')],
                ['icon' => 'shield',        'type' => 'insurer',   'title' => __('pricing.org_insurers_title'),    'desc' => __('pricing.org_insurers_desc')],
                ['icon' => 'flask-conical', 'type' => 'lab',       'title' => __('pricing.org_labs_title'),        'desc' => __('pricing.org_labs_desc')],
                ['icon' => 'pill',          'type' => 'pharmacy',  'title' => __('pricing.org_pharmacies_title'),  'desc' => __('pricing.org_pharmacies_desc')],
                ['icon' => 'code-2',        'type' => 'developer', 'title' => __('pricing.org_developers_title'),  'desc' => __('pricing.org_developers_desc')],
            ];
        ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.25rem;">
            <?php $__currentLoopData = $orgs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $org): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="display:flex;flex-direction:column;background:#fff;border:1px solid var(--color-border);border-radius:1rem;padding:1.75rem;">
                    <div style="width:2.75rem;height:2.75rem;border-radius:.75rem;background:rgba(15,76,129,.08);display:flex;align-items:center;justify-content:center;margin-bottom:1rem;">
                        <i data-lucide="<?php echo e($org['icon']); ?>" style="width:1.35rem;height:1.35rem;color:#0F4C81;"></i>
                    </div>
                    <h3 style="font-size:1.05rem;font-weight:700;color:var(--color-text-primary);margin:0 0 .4rem;"><?php echo e($org['title']); ?></h3>
                    <p style="color:var(--color-text-secondary);font-size:.88rem;flex:1;margin:0 0 1.25rem;"><?php echo e($org['desc']); ?></p>
                    <div style="font-weight:700;color:#0F4C81;font-size:.9rem;margin-bottom:.75rem;"><?php echo e(__('pricing.custom_pricing')); ?></div>
                    <a href="<?php echo e(route('public.request-demo', ['type' => $org['type'], 'source' => 'pricing'])); ?>" class="btn btn-ghost" style="justify-content:center;">
                        <i data-lucide="calendar" style="width:1rem;height:1rem;"></i> <?php echo e(__('pricing.request_demo')); ?>

                    </a>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <p style="text-align:center;color:var(--color-text-secondary);font-size:.85rem;margin-top:2rem;">
            <i data-lucide="shield-check" style="width:.9rem;height:.9rem;color:#0F4C81;vertical-align:middle;"></i>
            <?php echo e(__('pricing.trust_line')); ?>

        </p>
    </div>
</section>


<section style="padding:4rem 0;background:linear-gradient(135deg,#0F2744 0%,#0F4C81 100%);color:#fff;">
    <div class="container" style="text-align:center;max-width:680px;">
        <h2 style="font-size:1.75rem;font-weight:900;margin:0 0 .75rem;"><?php echo e(__('pricing.cta_title')); ?></h2>
        <p style="color:#BAE6FD;margin:0 0 1.75rem;"><?php echo e(__('pricing.cta_subtitle')); ?></p>
        <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
            <a href="<?php echo e(route('register.patient')); ?>" class="btn btn-primary"><?php echo e(__('pricing.cta_primary')); ?></a>
            <a href="<?php echo e(route('public.contact')); ?>" class="btn btn-ghost" style="color:#fff;border-color:rgba(255,255,255,.4);"><?php echo e(__('pricing.cta_secondary')); ?></a>
        </div>
    </div>
</section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\public\pricing.blade.php ENDPATH**/ ?>
<?php $__env->startSection('title', __('public.network_medicine.page_title')); ?>
<?php $__env->startSection('meta_description', __('public.network_medicine.meta_description')); ?>


<?php $__env->startPush('schema'); ?>
<script type="application/ld+json">
<?php echo json_encode([
    '<?php $__contextArgs = [];
if (context()->has($__contextArgs[0])) :
if (isset($value)) { $__contextPrevious[] = $value; }
$value = context()->get($__contextArgs[0]); ?>'      => 'https://schema.org',
    '@type'         => 'Service',
    '@id'           => rtrim(url('/'), '/') . '/#MedicineFinder',
    'name'          => __('public.network_medicine.hero_title'),
    'description'   => __('public.network_medicine.meta_description'),
    'serviceType'   => 'Health information network service',
    'provider'      => ['@id' => rtrim(url('/'), '/') . '/#organization'],
    'areaServed'    => ['@type' => 'Country', 'name' => 'Cameroon'],
    'availableChannel' => [
        '@type'          => 'ServiceChannel',
        'serviceUrl'     => url()->current(),
        'availableLanguage' => ['en', 'fr'],
    ],
    'inLanguage'    => app()->getLocale(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>

</script>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <header class="content-header">
        <div class="container">
            <div class="badge" style="background-color: var(--color-primary-light); color: var(--color-primary); margin-bottom: 1rem;"><?php echo e(__('public.network_medicine.badge')); ?></div>
            <h1><?php echo e(__('public.network_medicine.hero_title')); ?></h1>
            <p class="text-muted" style="max-width: 760px; margin: 0 auto; font-size: 1.25rem;">
                <?php echo e(__('public.network_medicine.hero_subtitle')); ?>

            </p>
        </div>
    </header>

    <section class="content-body">
        <div class="container">
            <div class="card-grid">
                <div class="card">
                    <div class="card-icon"><i data-lucide="badge-check"></i></div>
                    <h3><?php echo e(__('public.network_medicine.b1_title')); ?></h3>
                    <p><?php echo e(__('public.network_medicine.b1_desc')); ?></p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="clock"></i></div>
                    <h3><?php echo e(__('public.network_medicine.b2_title')); ?></h3>
                    <p><?php echo e(__('public.network_medicine.b2_desc')); ?></p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="file-text"></i></div>
                    <h3><?php echo e(__('public.network_medicine.b3_title')); ?></h3>
                    <p><?php echo e(__('public.network_medicine.b3_desc')); ?></p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="bookmark-check"></i></div>
                    <h3><?php echo e(__('public.network_medicine.b4_title')); ?></h3>
                    <p><?php echo e(__('public.network_medicine.b4_desc')); ?></p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="map-pin"></i></div>
                    <h3><?php echo e(__('public.network_medicine.b5_title')); ?></h3>
                    <p><?php echo e(__('public.network_medicine.b5_desc')); ?></p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="shield-alert"></i></div>
                    <h3><?php echo e(__('public.network_medicine.b6_title')); ?></h3>
                    <p><?php echo e(__('public.network_medicine.b6_desc')); ?></p>
                </div>
            </div>

            
            <div style="margin-top: 3.5rem; padding: 1.5rem 2rem; background-color: #FEF3C7; border:1px solid #FDE68A; border-radius: 1rem; display:flex; align-items:flex-start; gap:1rem;">
                <i data-lucide="alert-triangle" style="width:1.5rem;height:1.5rem;color:#B45309;flex-shrink:0;margin-top:.1rem;"></i>
                <div>
                    <p class="text-sm font-bold uppercase tracking-widest" style="color:#B45309;margin-bottom: 0.5rem;"><?php echo e(__('public.network_medicine.safety_title')); ?></p>
                    <p style="margin:0;"><?php echo e(__('public.network_medicine.safety_desc')); ?></p>
                </div>
            </div>

            <div style="margin-top: 2rem; padding: 1.5rem 2rem; background-color: var(--color-primary-light); border-radius: 1rem; display:flex; align-items:flex-start; gap:1rem;">
                <i data-lucide="plug-zap" style="width:1.5rem;height:1.5rem;color:#0F4C81;flex-shrink:0;margin-top:.1rem;"></i>
                <div>
                    <p class="text-sm font-bold uppercase tracking-widest text-primary" style="margin-bottom: 0.5rem;"><?php echo e(__('public.network_medicine.publish_label')); ?></p>
                    <p style="margin:0;"><?php echo e(__('public.network_medicine.publish_body')); ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="background:#0F2744;color:#fff;text-align:center;">
        <div class="container" style="max-width:680px;">
            <h2 style="color:#fff;margin-bottom:1rem;"><?php echo e(__('public.network_medicine.cta_title')); ?></h2>
            <p style="color:rgba(255,255,255,.75);margin-bottom:2rem;"><?php echo e(__('public.network_medicine.cta_body')); ?></p>
            <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:1rem;">
                <a href="<?php echo e(route('public.care-map')); ?>" class="btn btn-primary"><?php echo e(__('public.network_medicine.btn_care_map')); ?></a>
                <a href="<?php echo e(route('public.solutions.pharmacies')); ?>" class="btn" style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.25);"><?php echo e(__('public.network_medicine.btn_pharmacies')); ?></a>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\public\network\medicine_finder.blade.php ENDPATH**/ ?>
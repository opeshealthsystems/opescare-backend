<?php $__env->startSection('title', __('public.sol_pharmacies.page_title')); ?>
<?php $__env->startSection('meta_description', __('seo.meta.sol_pharmacies')); ?>

<?php $__env->startSection('content'); ?>
    <header class="content-header">
        <div class="container">
            <div class="badge" style="background-color: var(--color-primary-light); color: var(--color-primary); margin-bottom: 1rem;"><?php echo e(__('public.sol_pharmacies.badge')); ?></div>
            <h1><?php echo e(__('public.sol_pharmacies.hero_title')); ?></h1>
            <p class="text-muted" style="max-width: 800px; margin: 0 auto; font-size: 1.25rem;">
                <?php echo e(__('public.sol_pharmacies.hero_subtitle')); ?>

            </p>
        </div>
    </header>

    <section class="content-body">
        <div class="container">
            <div class="card-grid">
                <div class="card">
                    <div class="card-icon"><i data-lucide="file-plus-2"></i></div>
                    <h3><?php echo e(__('public.sol_pharmacies.card_rx_title')); ?></h3>
                    <p><?php echo e(__('public.sol_pharmacies.card_rx_desc')); ?></p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="store"></i></div>
                    <h3><?php echo e(__('public.sol_pharmacies.card_avail_title')); ?></h3>
                    <p><?php echo e(__('public.sol_pharmacies.card_avail_desc')); ?></p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="bookmark-check"></i></div>
                    <h3><?php echo e(__('public.sol_pharmacies.card_reserve_title')); ?></h3>
                    <p><?php echo e(__('public.sol_pharmacies.card_reserve_desc')); ?></p>
                </div>
                <div class="card">
                    <div class="card-icon"><i data-lucide="shield-alert"></i></div>
                    <h3><?php echo e(__('public.sol_pharmacies.card_safety_title')); ?></h3>
                    <p><?php echo e(__('public.sol_pharmacies.card_safety_desc')); ?></p>
                </div>
            </div>
            
            <div style="margin-top: 4rem; padding: 1.5rem 2rem; background-color: var(--color-primary-light); border-radius: 1rem; display:flex; align-items:flex-start; gap:1rem;">
                <i data-lucide="plug-zap" style="width:1.5rem;height:1.5rem;color:#0F4C81;flex-shrink:0;margin-top:.1rem;"></i>
                <div>
                    <p class="text-sm font-bold uppercase tracking-widest text-primary" style="margin-bottom: 0.5rem;"><?php echo e(__('public.sol_pharmacies.integration_label')); ?></p>
                    <p style="margin:0;"><?php echo e(__('public.sol_pharmacies.integration_body')); ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="background:#0F2744;color:#fff;text-align:center;">
        <div class="container" style="max-width:640px;">
            <h2 style="color:#fff;margin-bottom:1rem;"><?php echo e(__('public.sol_pharmacies.cta_title')); ?></h2>
            <p style="color:rgba(255,255,255,.75);margin-bottom:2rem;"><?php echo e(__('public.sol_pharmacies.cta_body')); ?></p>
            <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:1rem;">
                <a href="<?php echo e(route('register.organization')); ?>" class="btn btn-primary"><?php echo e(__('public.sol_pharmacies.btn_register')); ?></a>
                <a href="<?php echo e(route('public.contact')); ?>" class="btn" style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.25);"><?php echo e(__('public.sol_pharmacies.btn_contact')); ?></a>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/public/solutions/pharmacies.blade.php ENDPATH**/ ?>
<?php $__env->startSection('title', __('caremap_claim.edit_title')); ?>
<?php $__env->startSection('breadcrumb_section', __('caremap_claim.edit_title')); ?>

<?php $__env->startSection('content'); ?>

<div class="page-head">
    <h2><?php echo e(__('caremap_claim.edit_title')); ?></h2>
    <div class="page-head__spacer"></div>
</div>

<div class="panel">
    <div class="panel-body">
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="map-pin"></i></div>
            <p><?php echo e(__('caremap_claim.none_title')); ?></p>
            <p class="td-muted"><?php echo e(__('caremap_claim.none_body')); ?></p>
            <div style="margin-top:1rem;display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap">
                <a href="<?php echo e(route('public.care-map')); ?>" class="btn btn-primary btn-sm">
                    <i data-lucide="map"></i> <?php echo e(__('caremap_claim.btn_find_listing')); ?>

                </a>
                <?php if($claims->isNotEmpty()): ?>
                    <a href="<?php echo e(route('portals.listing.claims')); ?>" class="btn btn-secondary btn-sm">
                        <i data-lucide="list-checks"></i> <?php echo e(__('caremap_claim.my_claims_title')); ?>

                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\care_map\listing\none.blade.php ENDPATH**/ ?>
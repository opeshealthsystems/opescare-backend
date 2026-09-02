<?php $__env->startSection('title', __('caremap_claim.my_claims_title')); ?>
<?php $__env->startSection('breadcrumb_section', __('caremap_claim.my_claims_title')); ?>

<?php $__env->startSection('content'); ?>

<div class="page-head">
    <h2><?php echo e(__('caremap_claim.my_claims_title')); ?></h2>
    <div class="page-head__spacer"></div>
    <?php if($listing): ?>
        <a href="<?php echo e(route('portals.listing.edit')); ?>" class="btn btn-primary btn-sm">
            <i data-lucide="pencil"></i> <?php echo e(__('caremap_claim.btn_manage_listing')); ?>

        </a>
    <?php else: ?>
        <a href="<?php echo e(route('public.care-map')); ?>" class="btn btn-secondary btn-sm">
            <i data-lucide="map"></i> <?php echo e(__('caremap_claim.btn_find_listing')); ?>

        </a>
    <?php endif; ?>
</div>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if(session('error')): ?><div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>

<?php if($claims->isEmpty()): ?>
    <div class="panel">
        <div class="panel-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="map-pin"></i></div>
                <p><?php echo e(__('caremap_claim.empty_claims')); ?></p>
                <p class="td-muted"><?php echo e(__('caremap_claim.empty_claims_hint')); ?></p>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="list-checks"></i> <?php echo e(__('caremap_claim.my_claims_subtitle')); ?></h3>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php echo e(__('caremap_claim.col_facility')); ?></th>
                        <th><?php echo e(__('caremap_claim.col_status')); ?></th>
                        <th><?php echo e(__('caremap_claim.col_submitted')); ?></th>
                        <th><?php echo e(__('caremap_claim.col_reviewed')); ?></th>
                        <th><?php echo e(__('caremap_claim.col_notes')); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php $__currentLoopData = $claims; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $claim): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $badge = match($claim->claim_status) {
                            \App\Enums\FacilityClaimStatus::Approved => 'badge-success',
                            \App\Enums\FacilityClaimStatus::Rejected => 'badge-danger',
                            \App\Enums\FacilityClaimStatus::Revoked  => 'badge-neutral',
                            default                                  => 'badge-warning',
                        };
                    ?>
                    <tr>
                        <td data-label="<?php echo e(__('caremap_claim.col_facility')); ?>">
                            <span class="td-strong"><?php echo e($claim->careFacility?->facility_name ?? '—'); ?></span>
                            <?php if($claim->careFacility): ?>
                                <div class="td-muted"><?php echo e($claim->careFacility->city); ?></div>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?php echo e(__('caremap_claim.col_status')); ?>">
                            <span class="badge <?php echo e($badge); ?>"><?php echo e(__('caremap_claim.status_' . $claim->claim_status->value)); ?></span>
                        </td>
                        <td data-label="<?php echo e(__('caremap_claim.col_submitted')); ?>" class="td-muted">
                            <?php echo e(optional($claim->submitted_at ?? $claim->created_at)->format('Y-m-d') ?? '—'); ?>

                        </td>
                        <td data-label="<?php echo e(__('caremap_claim.col_reviewed')); ?>" class="td-muted">
                            <?php echo e(optional($claim->reviewed_at)->format('Y-m-d') ?? '—'); ?>

                        </td>
                        <td data-label="<?php echo e(__('caremap_claim.col_notes')); ?>" class="td-muted">
                            <?php echo e($claim->review_notes ?: '—'); ?>

                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\care_map\listing\claims.blade.php ENDPATH**/ ?>
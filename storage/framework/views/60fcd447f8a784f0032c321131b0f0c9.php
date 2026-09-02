<?php $__env->startSection('title', __('public.developer_portal.page_prod_requests', [], app()->getLocale()) ?: 'Production Access Requests'); ?>
<?php $__env->startSection('sidebar_nav'); ?> <?php echo $__env->make('portals.developer._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php $__env->stopSection(); ?>
<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

    <div class="breadcrumb">
        <a href="<?php echo e(route('portals.developer.dashboard')); ?>"><?php echo e(__('public.developer_portal.page_heading', [], $l) ?: 'Developer Portal'); ?></a>
        <i data-lucide="chevron-right"></i>
        <span><?php echo e(__('public.developer_portal.nav_prod_access', [], $l) ?: 'Production Access'); ?></span>
    </div>

    <div class="page-head">
        <h2><?php echo e(__('public.developer_portal.page_prod_requests', [], $l) ?: 'Production Access Requests'); ?></h2>
        <div class="page-head__spacer"></div>
        <a href="<?php echo e(route('portals.developer.production_requests.create')); ?>" class="btn btn-primary"><i data-lucide="plus"></i> <?php echo e(__('public.developer_portal.btn_new_request', [], $l) ?: 'New request'); ?></a>
    </div>

    <?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>

    <div class="alert alert-info mb-6">
        <i data-lucide="info"></i>
        <div><?php echo e(__('public.developer_portal.prod_review_info', [], $l) ?: 'Production access requests are reviewed by the OpesCare team within 3–5 business days. All production integrations must pass the'); ?>

        <a href="<?php echo e(route('portals.admin.certifications.index')); ?>"><?php echo e(__('public.developer_portal.lnk_integration_cert', [], $l) ?: 'Integration Certification'); ?></a> <?php echo e(__('public.developer_portal.prod_review_suffix', [], $l) ?: 'checklist before going live.'); ?></div>
    </div>

    <?php if($requests->isEmpty()): ?>
    <div class="panel">
        <div class="empty-state">
            <i data-lucide="rocket" class="empty-state-icon"></i>
            <p><?php echo e(__('public.developer_portal.no_prod_requests', [], $l) ?: 'No production access requests yet.'); ?></p>
            <a href="<?php echo e(route('portals.developer.production_requests.create')); ?>" class="btn btn-primary btn-sm"><i data-lucide="rocket"></i> <?php echo e(__('public.developer_portal.btn_request_prod', [], $l) ?: 'Request production access'); ?></a>
        </div>
    </div>
    <?php else: ?>
    <div class="panel">
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th><?php echo e(__('public.developer_portal.col_use_case', [], $l) ?: 'Use case'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_scopes', [], $l) ?: 'Scopes requested'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_patient_data', [], $l) ?: 'Patient data'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_status', [], $l) ?: 'Status'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_submitted', [], $l) ?: 'Submitted'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_reviewed', [], $l) ?: 'Reviewed'); ?></th>
                </tr></thead>
                <tbody>
                <?php $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td data-label="<?php echo e(__('public.developer_portal.col_use_case', [], $l) ?: 'Use case'); ?>">
                        <span class="td-strong"><?php echo e(Str::limit($req->use_case, 50)); ?></span>
                        <?php if($req->integration_client_id): ?>
                        <div class="mono"><?php echo e(Str::limit($req->integration_client_id, 24)); ?></div>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?php echo e(__('public.developer_portal.col_scopes', [], $l) ?: 'Scopes'); ?>" class="td-muted"><?php echo e(count((array)$req->requested_scopes)); ?> <?php echo e(__('dev_extra.scopes_suffix', [], $l) ?: 'scopes'); ?></td>
                    <td data-label="<?php echo e(__('public.developer_portal.col_patient_data', [], $l) ?: 'Patient data'); ?>">
                        <?php if($req->handles_patient_data): ?>
                        <span class="badge badge-warning"><?php echo e(__('public.developer_portal.lbl_yes', [], $l) ?: 'Yes'); ?></span>
                        <?php else: ?>
                        <span class="badge badge-neutral"><?php echo e(__('public.developer_portal.lbl_no', [], $l) ?: 'No'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?php echo e(__('public.developer_portal.col_status', [], $l) ?: 'Status'); ?>"><span class="<?php echo e($req->statusBadgeClass()); ?>"><?php echo e(\App\Support\Enums::label($req->status)); ?></span></td>
                    <td data-label="<?php echo e(__('public.developer_portal.col_submitted', [], $l) ?: 'Submitted'); ?>" class="td-muted"><?php echo e($req->created_at->format('d M Y')); ?></td>
                    <td data-label="<?php echo e(__('public.developer_portal.col_reviewed', [], $l) ?: 'Reviewed'); ?>" class="td-muted">
                        <?php echo e($req->reviewed_at?->format('d M Y') ?? '—'); ?>

                        <?php if($req->review_notes): ?>
                        <div class="td-muted"><?php echo e(Str::limit($req->review_notes, 40)); ?></div>
                        <?php endif; ?>
                        <?php if($req->rejected_reason): ?>
                        <div class="badge badge-danger"><?php echo e(Str::limit($req->rejected_reason, 40)); ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\developer\production_requests.blade.php ENDPATH**/ ?>
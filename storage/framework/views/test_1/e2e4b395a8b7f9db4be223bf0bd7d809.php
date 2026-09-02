<?php $__env->startSection('title', __('public.adm_cert_idx_title')); ?>
<?php $__env->startSection('sidebar'); ?> <?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="breadcrumb">
    <a href="<?php echo e(route('portals.admin.certifications.index')); ?>"><?php echo e(__('public.adm_cert_idx_breadcrumb')); ?></a>
    <i data-lucide="chevron-right"></i>
    <span><?php echo e(__('public.adm_cert_idx_breadcrumb_dir')); ?></span>
</div>

<div class="page-head">
    <h2><?php echo e(__('public.adm_cert_idx_heading')); ?></h2>
    <div class="page-head__spacer"></div>
    <form method="POST" action="<?php echo e(route('portals.admin.certifications.seed')); ?>" class="inline-form">
        <?php echo csrf_field(); ?>
        <button type="submit" class="btn btn-secondary btn-sm">
            <i data-lucide="list-checks"></i> <?php echo e(__('public.adm_cert_idx_btn_seed')); ?>

        </button>
    </form>
    <a href="<?php echo e(route('portals.admin.certifications.create')); ?>" class="btn btn-primary btn-sm">
        <i data-lucide="plus"></i> <?php echo e(__('public.adm_cert_idx_btn_new')); ?>

    </a>
</div>

<p class="td-muted mb-6"><?php echo e(__('public.adm_cert_idx_desc')); ?></p>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if(session('error')): ?><div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>


<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="layers"></i><span class="stat-card__label"><?php echo e(__('public.adm_cert_idx_stat_total')); ?></span></div>
        <div class="stat-card__value"><?php echo e($stats['total']); ?></div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__head"><i data-lucide="check-circle-2"></i><span class="stat-card__label"><?php echo e(__('public.adm_cert_idx_stat_certified')); ?></span></div>
        <div class="stat-card__value"><?php echo e($stats['passed']); ?></div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__head"><i data-lucide="clock"></i><span class="stat-card__label"><?php echo e(__('public.adm_cert_idx_stat_in_progress')); ?></span></div>
        <div class="stat-card__value"><?php echo e($stats['in_progress']); ?></div>
    </div>
    <div class="stat-card stat-card--teal">
        <div class="stat-card__head"><i data-lucide="award"></i><span class="stat-card__label"><?php echo e(__('public.adm_cert_idx_stat_badges')); ?></span></div>
        <div class="stat-card__value"><?php echo e($stats['badges']); ?></div>
    </div>
</div>


<form method="GET" class="filter-bar">
    <select name="status" class="filter-select" aria-label="<?php echo e(__('public.aria_status')); ?>" onchange="this.form.submit()">
        <option value=""><?php echo e(__('public.adm_cert_idx_filter_all_statuses')); ?></option>
        <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($s); ?>" <?php echo e($status === $s ? 'selected' : ''); ?>><?php echo e(\App\Support\Enums::label($s)); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <select name="type" class="filter-select" aria-label="<?php echo e(__('admin_extra.aria_type', [], app()->getLocale()) ?: 'Type'); ?>" onchange="this.form.submit()">
        <option value=""><?php echo e(__('public.adm_cert_idx_filter_all_types')); ?></option>
        <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($t); ?>" <?php echo e($type === $t ? 'selected' : ''); ?>><?php echo e(strtoupper($t)); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <?php if($status || $type): ?>
    <a href="<?php echo e(route('portals.admin.certifications.index')); ?>" class="btn btn-ghost btn-sm"><?php echo e(__('public.adm_cert_idx_filter_clear')); ?></a>
    <?php endif; ?>
</form>


<div class="panel">
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?php echo e(__('public.adm_cert_idx_col_integration')); ?></th>
                    <th><?php echo e(__('public.adm_cert_idx_col_type')); ?></th>
                    <th><?php echo e(__('public.adm_cert_idx_col_vendor')); ?></th>
                    <th><?php echo e(__('public.adm_cert_idx_col_status')); ?></th>
                    <th><?php echo e(__('public.adm_cert_idx_col_level')); ?></th>
                    <th><?php echo e(__('public.adm_cert_idx_col_last_run')); ?></th>
                    <th><?php echo e(__('public.adm_cert_idx_col_badge')); ?></th>
                    <th class="row-actions"></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $certifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td data-label="<?php echo e(__('public.adm_cert_idx_col_integration')); ?>" class="td-strong">
                        <?php echo e($cert->integration_name); ?>

                        <?php if($cert->version): ?>
                        <span class="td-muted">v<?php echo e($cert->version); ?></span>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?php echo e(__('public.adm_cert_idx_col_type')); ?>">
                        <span class="badge badge--info badge-sm"><?php echo e(strtoupper($cert->integration_type)); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.adm_cert_idx_col_vendor')); ?>" class="td-muted"><?php echo e($cert->vendor_name ?? '—'); ?></td>
                    <td data-label="<?php echo e(__('public.adm_cert_idx_col_status')); ?>">
                        <span class="badge <?php echo e($cert->statusBadgeClass()); ?> badge-sm">
                            <?php echo e(\App\Support\Enums::label($cert->status)); ?>
                        </span>
                    </td>
                    <td data-label="<?php echo e(__('public.adm_cert_idx_col_level')); ?>">
                        <?php if($cert->certification_level): ?>
                        <span class="badge <?php echo e($cert->levelBadgeClass()); ?> badge-sm"><?php echo e(ucfirst($cert->certification_level)); ?></span>
                        <?php else: ?>
                        <span class="td-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?php echo e(__('public.adm_cert_idx_col_last_run')); ?>">
                        <?php if($cert->latestTestRun): ?>
                            <span class="badge <?php echo e($cert->latestTestRun->isPassed() ? 'badge-success' : 'badge-danger'); ?> badge-sm">
                                <?php echo e($cert->latestTestRun->passRate()); ?>%
                            </span>
                            <span class="td-muted code-muted">(<?php echo e($cert->latestTestRun->started_at?->format('d M Y')); ?>)</span>
                        <?php else: ?>
                            <span class="td-muted"><?php echo e(__('public.adm_cert_idx_no_runs')); ?></span>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?php echo e(__('public.adm_cert_idx_col_badge')); ?>">
                        <?php if($cert->badge): ?>
                            <span class="mono code-token"><?php echo e($cert->badge->badge_code); ?></span>
                        <?php else: ?>
                            <span class="td-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="row-actions">
                        <a href="<?php echo e(route('portals.admin.certifications.show', $cert)); ?>" class="btn btn-secondary btn-sm"><?php echo e(__('public.adm_cert_idx_btn_manage')); ?></a>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="8" class="td-muted empty-cell">
                        <?php echo e(__('public.adm_cert_idx_empty')); ?> <a href="<?php echo e(route('portals.admin.certifications.create')); ?>"><?php echo e(__('public.adm_cert_idx_empty_link')); ?></a>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
        <?php if($certifications->hasPages()): ?>
        <div class="panel-footer"><?php echo e($certifications->links()); ?></div>
        <?php endif; ?>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/admin/certifications/index.blade.php ENDPATH**/ ?>
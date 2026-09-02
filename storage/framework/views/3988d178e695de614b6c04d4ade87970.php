<?php $__env->startSection('title', __('public.adm_fac_title')); ?>
<?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('breadcrumb_home', __('admin_extra.breadcrumb_admin', [], app()->getLocale()) ?: 'Admin'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.admin')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.adm_fac_breadcrumb')); ?>
<?php $__env->startSection('content'); ?>

<div class="breadcrumb">
    <a href="<?php echo e(route('admin.facilities.index')); ?>"><?php echo e(__('public.adm_fac_breadcrumb')); ?></a>
    <i data-lucide="chevron-right"></i>
    <span><?php echo e(__('public.adm_fac_breadcrumb_dir')); ?></span>
</div>

<div class="page-head">
    <h2><?php echo e(__('public.adm_fac_heading')); ?></h2>
    <div class="page-head__spacer"></div>
    <a href="<?php echo e(route('admin.facilities.index')); ?>#add" class="btn btn-primary">
        <i data-lucide="plus"></i> <?php echo e(__('public.adm_fac_btn_add')); ?>

    </a>
</div>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if(session('error')): ?><div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>

<form method="GET" action="<?php echo e(route('admin.facilities.index')); ?>" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="<?php echo e(__('public.adm_fac_ph_search')); ?>" aria-label="<?php echo e(__('public.aria_search_facilities')); ?>">
    </label>
    <select name="type" class="filter-select" aria-label="<?php echo e(__('admin_extra.aria_type', [], app()->getLocale()) ?: 'Type'); ?>" onchange="this.form.submit()">
        <option value=""><?php echo e(__('public.adm_fac_opt_all_types')); ?></option>
        <?php $__currentLoopData = ['hospital','clinic','laboratory','pharmacy','radiology','specialist']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($t); ?>" <?php echo e(request('type')===$t?'selected':''); ?>><?php echo e(ucfirst($t)); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <select name="status" class="filter-select" aria-label="<?php echo e(__('public.aria_status')); ?>" onchange="this.form.submit()">
        <option value=""><?php echo e(__('public.adm_fac_opt_all_statuses')); ?></option>
        <option value="active" <?php echo e(request('status')==='active'?'selected':''); ?>><?php echo e(__('public.adm_fac_opt_active')); ?></option>
        <option value="pending_approval" <?php echo e(request('status')==='pending_approval'?'selected':''); ?>><?php echo e(__('public.adm_fac_opt_pending')); ?></option>
        <option value="suspended" <?php echo e(request('status')==='suspended'?'selected':''); ?>><?php echo e(__('public.adm_fac_opt_suspended')); ?></option>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="filter"></i> <?php echo e(__('public.adm_fac_btn_filter')); ?></button>
    <a href="<?php echo e(route('admin.facilities.index')); ?>" class="btn btn-ghost btn-sm"><?php echo e(__('public.adm_fac_btn_reset')); ?></a>
</form>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="building-2"></i> <?php echo e(__('admin_extra.count_facilities', ['n' => $facilities->total()], app()->getLocale()) ?: $facilities->total().' facilities'); ?></h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?php echo e(__('public.adm_fac_col_facility')); ?></th>
                    <th><?php echo e(__('public.adm_fac_col_type')); ?></th>
                    <th><?php echo e(__('public.adm_fac_col_region')); ?></th>
                    <th><?php echo e(__('public.adm_fac_col_status')); ?></th>
                    <th class="row-actions"><?php echo e(__('public.adm_fac_col_actions')); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $facilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td data-label="<?php echo e(__('public.adm_fac_col_facility')); ?>">
                        <?php $typeIcon = ['hospital'=>'building-2','laboratory'=>'flask-conical','pharmacy'=>'pill','radiology'=>'scan'][$f->type] ?? 'building'; ?>
                        <span class="cell-with-icon">
                            <i data-lucide="<?php echo e($typeIcon); ?>"></i>
                            <span class="td-strong"><?php echo e($f->name); ?></span>
                        </span>
                    </td>
                    <td data-label="<?php echo e(__('public.adm_fac_col_type')); ?>"><?php echo e(ucfirst($f->type ?? '—')); ?></td>
                    <td data-label="<?php echo e(__('public.adm_fac_col_region')); ?>"><?php echo e($f->region ?? '—'); ?></td>
                    <td data-label="<?php echo e(__('public.adm_fac_col_status')); ?>">
                        <?php if($f->status==='active'): ?><span class="badge badge-success"><?php echo e(__('public.adm_fac_badge_active')); ?></span>
                        <?php elseif($f->status==='suspended'): ?><span class="badge badge-danger"><?php echo e(__('public.adm_fac_badge_suspended')); ?></span>
                        <?php elseif($f->status==='pending_approval'): ?><span class="badge badge-warning"><?php echo e(__('public.adm_fac_badge_pending')); ?></span>
                        <?php else: ?><span class="badge badge-neutral"><?php echo e(\App\Support\Enums::label($f->status ?? 'pending')); ?></span><?php endif; ?>
                    </td>
                    <td class="row-actions" data-label="<?php echo e(__('public.adm_fac_col_actions')); ?>">
                        <a href="<?php echo e(route('admin.facilities.show', $f->id)); ?>" class="icon-btn" aria-label="<?php echo e(__('public.aria_view_facility')); ?>" title="<?php echo e(__('admin_extra.title_view', [], app()->getLocale()) ?: 'View'); ?>">
                            <i data-lucide="eye"></i>
                        </a>
                        <?php if(($f->status ?? '') !== 'active'): ?>
                        <form method="POST" action="<?php echo e(route('admin.facilities.approve', $f->id)); ?>" class="inline-form"><?php echo csrf_field(); ?>
                            <button type="submit" class="icon-btn" aria-label="<?php echo e(__('public.aria_approve_facility')); ?>" title="<?php echo e(__('public.aria_approve')); ?>"><i data-lucide="check-circle"></i></button>
                        </form>
                        <?php endif; ?>
                        <?php if(($f->status ?? '') !== 'suspended'): ?>
                        <form method="POST" action="<?php echo e(route('admin.facilities.suspend', $f->id)); ?>" class="inline-form"><?php echo csrf_field(); ?>
                            <button type="submit" class="icon-btn" aria-label="<?php echo e(__('public.aria_suspend_facility')); ?>" title="<?php echo e(__('public.aria_suspend')); ?>"><i data-lucide="pause-circle"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="5" class="td-muted empty-cell"><?php echo e(__('public.adm_fac_empty')); ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="panel-body"><?php echo e($facilities->links()); ?></div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\facilities\index.blade.php ENDPATH**/ ?>
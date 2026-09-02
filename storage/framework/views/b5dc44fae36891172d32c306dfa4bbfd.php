<?php $__env->startSection('title', __('public.adm_roles_title') . ' — ' . $role->name); ?>
<?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('breadcrumb_home', __('public.adm_roles_breadcrumb_home')); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.admin')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.adm_roles_breadcrumb_section')); ?>
<?php $__env->startSection('content'); ?>

<div class="breadcrumb">
    <a href="<?php echo e(route('portals.admin.roles.index')); ?>"><?php echo e(__('public.adm_roles_breadcrumb_section')); ?></a>
    <i data-lucide="chevron-right"></i>
    <span><?php echo e($role->name); ?></span>
</div>

<div class="entity-head">
    <div class="entity-head__icon"><i data-lucide="shield"></i></div>
    <h2 class="entity-head__title"><?php echo e($role->name); ?></h2>
    <span class="badge badge-primary"><?php echo e(ucfirst(str_replace('_',' ',$role->portal??'—'))); ?></span>
    <?php if($role->is_protected): ?><span class="badge badge-warning"><?php echo e(__('public.adm_roles_badge_protected')); ?></span><?php endif; ?>
    <div class="entity-head__spacer"></div>
</div>

<div class="stat-grid mb-6">
    <div class="stat-card">
        <div class="stat-card__label"><?php echo e(__('public.adm_roles_stat_total_users')); ?></div>
        <div class="stat-card__value"><?php echo e($users->total()); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label"><?php echo e(__('public.adm_roles_stat_portal')); ?></div>
        <div class="stat-card__value"><?php echo e(ucfirst(str_replace('_',' ',$role->portal??'—'))); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label"><?php echo e(__('public.adm_roles_stat_protected')); ?></div>
        <div class="stat-card__value"><?php echo e($role->is_protected ? __('public.adm_roles_yes') : __('public.adm_roles_no')); ?></div>
    </div>
</div>

<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="users"></i> <?php echo e(__('public.adm_roles_panel_users_title')); ?></h3></div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?php echo e(__('public.adm_roles_col_name')); ?></th>
                    <th><?php echo e(__('public.adm_roles_col_email')); ?></th>
                    <th><?php echo e(__('public.adm_roles_col_facility')); ?></th>
                    <th><?php echo e(__('public.adm_roles_col_status')); ?></th>
                    <th><?php echo e(__('public.adm_roles_col_joined')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td data-label="<?php echo e(__('public.adm_roles_col_name')); ?>"><span class="td-strong"><?php echo e($user->name ?? '—'); ?></span></td>
                    <td data-label="<?php echo e(__('public.adm_roles_col_email')); ?>"><?php echo e($user->email); ?></td>
                    <td data-label="<?php echo e(__('public.adm_roles_col_facility')); ?>" class="td-muted"><?php echo e($user->facility?->name ?? '—'); ?></td>
                    <td data-label="<?php echo e(__('public.adm_roles_col_status')); ?>">
                        <?php if($user->is_active ?? true): ?><span class="badge badge-success"><?php echo e(__('public.adm_roles_status_active')); ?></span>
                        <?php else: ?><span class="badge badge-neutral"><?php echo e(__('public.adm_roles_status_inactive')); ?></span><?php endif; ?>
                    </td>
                    <td data-label="<?php echo e(__('public.adm_roles_col_joined')); ?>" class="td-muted"><?php echo e($user->created_at?->format('d M Y') ?? '—'); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="5" class="td-muted empty-cell"><?php echo e(__('public.adm_roles_empty')); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="panel-body"><?php echo e($users->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\roles\users.blade.php ENDPATH**/ ?>
<?php $__env->startSection('title', __('public.adm_cc_idx_title')); ?>
<?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('breadcrumb_home', __('public.adm_cc_idx_breadcrumb_home')); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.admin')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.adm_cc_idx_breadcrumb_section')); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.adm_cc_idx_heading')); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.adm_cc_idx_subtitle')); ?></p>
    </div>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>


<?php
    $anyError = collect($health)->whereIn('status', ['error'])->count() > 0;
    $anyWarn  = collect($health)->whereIn('status', ['warning'])->count() > 0 || ($health['failed_jobs']['count'] ?? 0) > 0;
?>
<?php if($anyError): ?>
<div class="banner banner--danger">
    <i data-lucide="alert-triangle"></i>
    <div><strong><?php echo e(__('public.adm_cc_idx_banner_error_msg')); ?></strong> <?php echo e(__('public.adm_cc_idx_banner_error_detail')); ?></div>
    <div class="banner__spacer"></div>
    <a href="<?php echo e(route('portals.admin.cc.health')); ?>" class="btn btn-danger btn-sm"><?php echo e(__('public.adm_cc_idx_banner_error_btn')); ?></a>
</div>
<?php elseif($anyWarn): ?>
<div class="banner banner--warning">
    <i data-lucide="alert-circle"></i>
    <div><strong><?php echo e(__('public.adm_cc_idx_banner_warn_label')); ?></strong> <?php echo e(__('public.adm_cc_idx_banner_warn_detail')); ?></div>
    <div class="banner__spacer"></div>
    <a href="<?php echo e(route('portals.admin.cc.health')); ?>" class="btn btn-secondary btn-sm"><?php echo e(__('public.adm_cc_idx_banner_warn_btn')); ?></a>
</div>
<?php endif; ?>


<div class="mb-6">
    <div class="section-head section-head--danger">
        <i data-lucide="zap"></i>
        <h2><?php echo e(__('public.adm_cc_idx_god_mode_heading')); ?></h2>
    </div>
    <div class="card-grid">
        <?php $godCards = [
            [__('public.adm_cc_idx_god_users'),        'users',        '/portals/admin/users',         __('public.adm_cc_idx_god_users_desc')],
            [__('public.adm_cc_idx_god_facilities'),   'building',     '/portals/admin/facilities',     __('public.adm_cc_idx_god_facilities_desc')],
            [__('public.adm_cc_idx_god_patients'),     'heart-pulse',  '/portals/admin/patients',       __('public.adm_cc_idx_god_patients_desc')],
            [__('public.adm_cc_idx_god_staff'),        'user-check',   '/portals/admin/staff',          __('public.adm_cc_idx_god_staff_desc')],
            [__('public.adm_cc_idx_god_financial'),    'banknote',     '/portals/admin/financial',      __('public.adm_cc_idx_god_financial_desc')],
            [__('public.adm_cc_idx_god_appointments'), 'calendar',     '/portals/admin/appointments',   __('public.adm_cc_idx_god_appointments_desc')],
            [__('public.adm_cc_idx_god_support'),      'headphones',   '/portals/admin/support',        __('public.adm_cc_idx_god_support_desc')],
            [__('public.adm_cc_idx_god_cdss'),         'activity',     '/portals/admin/cdss',           __('public.adm_cc_idx_god_cdss_desc')],
            [__('public.adm_cc_idx_god_roles'),        'shield',       '/portals/admin/roles',          __('public.adm_cc_idx_god_roles_desc')],
            [__('public.adm_cc_idx_god_orgs'),         'landmark',     '/portals/admin/organizations',  __('public.adm_cc_idx_god_orgs_desc')],
        ]; ?>
        <?php $__currentLoopData = $godCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$title, $icon, $url, $desc]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e($url); ?>" class="nav-card nav-card--danger">
            <div class="nav-card__head">
                <i data-lucide="<?php echo e($icon); ?>"></i>
                <span class="nav-card__title"><?php echo e($title); ?></span>
            </div>
            <p class="nav-card__desc"><?php echo e($desc); ?></p>
        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>


<div class="card-grid mb-6">
    <?php $cards = [
        [__('public.adm_cc_idx_nav_settings'),    'sliders-horizontal', route('portals.admin.cc.settings'),      __('public.adm_cc_idx_nav_settings_desc')],
        [__('public.adm_cc_idx_nav_flags'),       'toggle-right',       route('portals.admin.cc.feature_flags'), __('public.adm_cc_idx_nav_flags_desc')],
        [__('public.adm_cc_idx_nav_modules'),     'puzzle',             route('portals.admin.cc.modules'),       __('public.adm_cc_idx_nav_modules_desc')],
        [__('public.adm_cc_idx_nav_maintenance'), 'wrench',             route('portals.admin.cc.maintenance'),   __('public.adm_cc_idx_nav_maintenance_desc')],
        [__('public.adm_cc_idx_nav_health'),      'activity',           route('portals.admin.cc.health'),        __('public.adm_cc_idx_nav_health_desc')],
        [__('public.adm_cc_idx_nav_audit'),       'scroll-text',        route('portals.admin.cc.audit'),         __('public.adm_cc_idx_nav_audit_desc')],
    ]; ?>
    <?php $__currentLoopData = $cards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$title, $icon, $url, $desc]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <a href="<?php echo e($url); ?>" class="nav-card">
        <div class="nav-card__head">
            <i data-lucide="<?php echo e($icon); ?>"></i>
            <span class="nav-card__title"><?php echo e($title); ?></span>
        </div>
        <p class="nav-card__desc"><?php echo e($desc); ?></p>
    </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="scroll-text"></i> <?php echo e(__('public.adm_cc_idx_recent_heading')); ?></h3>
        <a href="<?php echo e(route('portals.admin.cc.audit')); ?>" class="btn btn-ghost btn-sm"><?php echo e(__('public.adm_cc_idx_recent_view_all')); ?></a>
    </div>
    <div class="panel-body panel-body--flush">
        <?php if($actions->count() === 0): ?>
            <div class="td-muted empty-cell"><?php echo e(__('public.adm_cc_idx_recent_empty')); ?></div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th><?php echo e(__('public.adm_cc_idx_col_action')); ?></th><th><?php echo e(__('public.adm_cc_idx_col_resource')); ?></th><th><?php echo e(__('public.adm_cc_idx_col_actor')); ?></th><th><?php echo e(__('public.adm_cc_idx_col_when')); ?></th>
                </tr></thead>
                <tbody>
                    <?php $__currentLoopData = $actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td data-label="Action"><span class="code-token"><?php echo e($a->action); ?></span></td>
                        <td data-label="Resource"><span class="badge badge-neutral badge-sm"><?php echo e($a->resource_type ?? '—'); ?></span></td>
                        <td data-label="Actor"><?php echo e($a->actor_id); ?></td>
                        <td data-label="When" class="td-muted"><?php echo e(\Carbon\Carbon::parse($a->occurred_at)->diffForHumans()); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\control_center\index.blade.php ENDPATH**/ ?>
<?php $__env->startSection('title', __('public.adm_cc_hlth_title')); ?>
<?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('breadcrumb_home', __('public.adm_cc_hlth_breadcrumb_home')); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.admin')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.adm_cc_hlth_breadcrumb_section')); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.adm_cc_hlth_heading')); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.adm_cc_hlth_subtitle')); ?></p>
    </div>
    <div class="page-head__spacer"></div>
    <a href="<?php echo e(route('portals.admin.cc.health')); ?>" class="btn btn-ghost btn-sm">
        <i data-lucide="refresh-cw"></i> <?php echo e(__('public.adm_cc_hlth_btn_refresh')); ?>

    </a>
</div>

<?php
    $statusVariant = fn($s) => match($s) {
        'ok'      => 'stat-card--success',
        'warning' => 'stat-card--warning',
        'error'   => 'stat-card--danger',
        default   => '',
    };
    $statusBadge = fn($s) => match($s) {
        'ok'      => 'badge-success',
        'warning' => 'badge-warning',
        'error'   => 'badge-danger',
        default   => 'badge-neutral',
    };
    $statusIcon = fn($s) => match($s) {
        'ok'      => 'check-circle',
        'warning' => 'alert-circle',
        'error'   => 'x-circle',
        default   => 'minus-circle',
    };
    $checks = [
        [__('public.adm_cc_hlth_check_database'),    $health['database']    ?? ['status'=>'unknown'], __('public.adm_cc_hlth_check_database_msg')],
        [__('public.adm_cc_hlth_check_storage'),     $health['storage']     ?? ['status'=>'unknown'], __('public.adm_cc_hlth_check_storage_msg')],
        [__('public.adm_cc_hlth_check_queue'),       $health['queue']       ?? ['status'=>'unknown'], __('public.adm_cc_hlth_check_queue_msg')],
        [__('public.adm_cc_hlth_check_maintenance'), $health['maintenance'] ?? ['status'=>'ok'],      __('public.adm_cc_hlth_check_maintenance_msg')],
    ];
    $fj = $health['failed_jobs'] ?? ['status'=>'ok','count'=>0,'message'=>''];
    $fjStatus = ($fj['count'] ?? 0) > 0 ? 'warning' : 'ok';
?>

<div class="card-grid mb-6">

    <?php $__currentLoopData = $checks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $data, $defaultMsg]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="stat-card <?php echo e($statusVariant($data['status'])); ?>">
        <div class="stat-card__head">
            <i data-lucide="<?php echo e($statusIcon($data['status'])); ?>"></i>
            <span class="stat-card__label"><?php echo e($label); ?></span>
            <span class="badge <?php echo e($statusBadge($data['status'])); ?>  badge-sm ml-auto"><?php echo e(strtoupper($data['status'])); ?></span>
        </div>
        <p class="nav-card__desc"><?php echo e($data['message'] ?? $defaultMsg); ?></p>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    
    <div class="stat-card <?php echo e($statusVariant($fjStatus)); ?>">
        <div class="stat-card__head">
            <i data-lucide="<?php echo e($statusIcon($fjStatus)); ?>"></i>
            <span class="stat-card__label"><?php echo e(__('public.adm_cc_hlth_check_failed_jobs')); ?></span>
            <span class="badge <?php echo e($fjStatus === 'warning' ? 'badge-warning' : 'badge-success'); ?>  badge-sm ml-auto"><?php echo e($fj['count'] ?? 0); ?></span>
        </div>
        <p class="nav-card__desc"><?php echo e($fj['message'] ?? __('public.adm_cc_hlth_check_failed_jobs_msg')); ?></p>
    </div>

</div>


<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="stethoscope"></i> <?php echo e(__('public.adm_cc_hlth_summary_heading')); ?></h3>
    </div>
    <div class="panel-body">
        <?php
            $errors   = collect($health)->where('status', 'error')->count();
            $warnings = collect($health)->where('status', 'warning')->count();
            $oks      = collect($health)->where('status', 'ok')->count();
        ?>
        <div class="stat-grid mb-4">
            <div class="stat-card stat-card--success">
                <div class="stat-card__value"><?php echo e($oks); ?></div>
                <div class="stat-card__label"><?php echo e(__('public.adm_cc_hlth_stat_healthy')); ?></div>
            </div>
            <div class="stat-card stat-card--warning">
                <div class="stat-card__value"><?php echo e($warnings); ?></div>
                <div class="stat-card__label"><?php echo e(__('public.adm_cc_hlth_stat_warnings')); ?></div>
            </div>
            <div class="stat-card stat-card--danger">
                <div class="stat-card__value"><?php echo e($errors); ?></div>
                <div class="stat-card__label"><?php echo e(__('public.adm_cc_hlth_stat_errors')); ?></div>
            </div>
        </div>
        <?php if($errors > 0): ?>
            <div class="alert alert-danger">
                <i data-lucide="alert-triangle"></i>
                <div><?php echo e(__('public.adm_cc_hlth_alert_critical')); ?></div>
            </div>
        <?php elseif($warnings > 0): ?>
            <div class="alert alert-warning">
                <i data-lucide="alert-circle"></i>
                <div><?php echo e(__('public.adm_cc_hlth_alert_warning')); ?></div>
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                <i data-lucide="check-circle"></i>
                <div><?php echo e(__('public.adm_cc_hlth_alert_ok')); ?></div>
            </div>
        <?php endif; ?>
        <div class="td-muted text-sm mt-6">
            <?php echo e(__('public.adm_cc_hlth_last_checked')); ?> <?php echo e(now()->format('M d, Y H:i:s')); ?> <?php echo e(__('public.adm_cc_hlth_server_time')); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\control_center\system_health.blade.php ENDPATH**/ ?>
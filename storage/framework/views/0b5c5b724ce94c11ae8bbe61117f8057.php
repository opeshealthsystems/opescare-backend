<?php $__env->startSection('title', __('public.medical_id.access_logs', [], app()->getLocale()) . ' — OpesCare Patient Portal'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.medical_id.access_logs', [], app()->getLocale()) ?: 'Access Logs'); ?>

<?php $__env->startSection('patient_banner'); ?>
    <?php echo $__env->make('partials.guardian-context-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.medical_id.access_logs', [], app()->getLocale()) ?: 'Access Logs'); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.portal.access_logs_subtitle', [], app()->getLocale()) ?: 'Review who has accessed your Health ID and the status of those requests.'); ?></p>
    </div>
</div>

<!-- Info Banner -->
<div class="alert alert-info mb-6">
    <i data-lucide="shield-check"></i>
    <div>
        <?php echo e(__('public.portal.access_logs_info', [], app()->getLocale()) ?: 'Every access to your Health ID is recorded here for your review. If you see an access you don\'t recognise, contact support immediately.'); ?>

    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">
            <i data-lucide="history"></i>
            <?php echo e(__('public.portal.access_history', [], app()->getLocale()) ?: 'Access History'); ?>

        </h2>
        <?php $logCount = method_exists($logs, 'total') ? $logs->total() : $logs->count(); ?>
        <?php if($logCount > 0): ?>
        <span class="badge badge-primary"><?php echo e($logCount); ?></span>
        <?php endif; ?>
    </div>

    <?php if($logCount === 0): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="clipboard-list"></i></div>
            <h3><?php echo e(__('public.portal.no_logs_title', [], app()->getLocale()) ?: 'No Access Logs'); ?></h3>
            <p><?php echo e(__('public.portal.no_logs_desc', [], app()->getLocale()) ?: 'Your Medical ID has not been accessed recently.'); ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table" aria-label="<?php echo e(__('public.aria_access_logs')); ?>">
                <thead>
                    <tr>
                        <th><?php echo e(__('public.portal.date_time', [], app()->getLocale()) ?: 'Date & Time'); ?></th>
                        <th><?php echo e(__('public.portal.access_type', [], app()->getLocale()) ?: 'Access Type'); ?></th>
                        <th><?php echo e(__('public.portal.purpose', [], app()->getLocale()) ?: 'Purpose'); ?></th>
                        <th><?php echo e(__('public.portal.result', [], app()->getLocale()) ?: 'Result'); ?></th>
                        <th><?php echo e(__('public.portal.details', [], app()->getLocale()) ?: 'Details'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td data-label="<?php echo e(__('public.portal.date_time', [], app()->getLocale()) ?: 'Date & Time'); ?>">
                            <span class="td-strong"><?php echo e(\Carbon\Carbon::parse($log->created_at)->format('d M Y')); ?></span>
                            <div class="td-muted"><?php echo e(\Carbon\Carbon::parse($log->created_at)->format('H:i')); ?></div>
                        </td>
                        <td data-label="<?php echo e(__('public.portal.access_type', [], app()->getLocale()) ?: 'Access Type'); ?>">
                            <span class="cell-with-icon">
                                <?php if(str_contains($log->access_type ?? '', 'qr')): ?>
                                    <i data-lucide="qr-code"></i>
                                    <span><?php echo e(__('public.portal.qr_scan', [], app()->getLocale()) ?: 'QR Scan'); ?></span>
                                <?php else: ?>
                                    <i data-lucide="search"></i>
                                    <span><?php echo e(__('public.portal.id_lookup', [], app()->getLocale()) ?: 'ID Lookup'); ?></span>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td data-label="<?php echo e(__('public.portal.purpose', [], app()->getLocale()) ?: 'Purpose'); ?>">
                            <span class="td-muted"><?php echo e(\App\Support\Enums::label($log->purpose ?? '—')); ?></span>
                        </td>
                        <td data-label="<?php echo e(__('public.portal.result', [], app()->getLocale()) ?: 'Result'); ?>">
                            <?php if(($log->result ?? '') === 'success'): ?>
                                <span class="badge badge-success"><?php echo e(__('public.portal.granted', [], app()->getLocale()) ?: 'Granted'); ?></span>
                            <?php else: ?>
                                <span class="badge badge-danger"><?php echo e(__('public.portal.denied', [], app()->getLocale()) ?: 'Denied'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?php echo e(__('public.portal.details', [], app()->getLocale()) ?: 'Details'); ?>">
                            <span class="td-mono"><?php echo e($log->ip_address ?? '—'); ?></span>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php if(method_exists($logs, 'links') && $logs->hasPages()): ?>
        <div class="panel-body">
            <?php echo e($logs->links()); ?>

        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\logs.blade.php ENDPATH**/ ?>
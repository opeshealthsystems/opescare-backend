<?php $__env->startSection('title', __('public.developer_portal.page_webhooks', [], app()->getLocale()) ?: 'Webhook Delivery Logs'); ?>
<?php $__env->startSection('sidebar_nav'); ?> <?php echo $__env->make('portals.developer._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php $__env->stopSection(); ?>
<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

    <div class="breadcrumb">
        <a href="<?php echo e(route('portals.developer.apps.show', $client->id)); ?>"><?php echo e($client->name ?? __('public.developer_portal.lbl_unnamed_app', [], $l) ?: 'App'); ?></a>
        <i data-lucide="chevron-right"></i>
        <span><?php echo e(__('public.developer_portal.lnk_webhooks', [], $l) ?: 'Webhook delivery logs'); ?></span>
    </div>

    <div class="page-head">
        <h2><?php echo e(__('public.developer_portal.page_webhooks', [], $l) ?: 'Webhook Delivery Logs'); ?></h2>
    </div>

    <div class="panel">
        <?php if($deliveries->isEmpty()): ?>
        <div class="empty-state">
            <i data-lucide="webhook" class="empty-state-icon"></i>
            <p><?php echo e(__('public.developer_portal.no_webhook_deliveries', [], $l) ?: 'No webhook deliveries recorded yet.'); ?></p>
        </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th><?php echo e(__('public.developer_portal.col_event', [], $l) ?: 'Event'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_type', [], $l) ?: 'Type'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_attempts', [], $l) ?: 'Attempts'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_http', [], $l) ?: 'HTTP'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_status', [], $l) ?: 'Status'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_delivered_at', [], $l) ?: 'Delivered at'); ?></th>
                </tr></thead>
                <tbody>
                <?php $__currentLoopData = $deliveries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td data-label="<?php echo e(__('dev_extra.dl_event', [], $l) ?: 'Event'); ?>" class="mono"><?php echo e(Str::limit($log->event_id, 20)); ?></td>
                    <td data-label="<?php echo e(__('dev_extra.dl_type', [], $l) ?: 'Type'); ?>" class="mono"><?php echo e($log->event_type); ?></td>
                    <td data-label="<?php echo e(__('dev_extra.dl_attempts', [], $l) ?: 'Attempts'); ?>"><?php echo e($log->attempts ?? $log->retry_count ?? 0); ?></td>
                    <td data-label="<?php echo e(__('dev_extra.dl_http', [], $l) ?: 'HTTP'); ?>">
                        <?php if($log->http_status_code): ?>
                        <span class="badge <?php echo e($log->http_status_code >= 200 && $log->http_status_code < 300 ? 'badge-success' : 'badge-danger'); ?>"><?php echo e($log->http_status_code); ?></span>
                        <?php else: ?>
                        <span class="td-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?php echo e(__('dev_extra.dl_status', [], $l) ?: 'Status'); ?>"><span class="<?php echo e($log->statusBadgeClass()); ?>"><?php echo e(\App\Support\Enums::label($log->status)); ?></span></td>
                    <td data-label="<?php echo e(__('dev_extra.dl_delivered_at', [], $l) ?: 'Delivered at'); ?>" class="td-muted"><?php echo e($log->delivered_at?->format('d M Y H:i') ?? '—'); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <div class="panel-body"><?php echo e($deliveries->links()); ?></div>
        <?php endif; ?>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\developer\webhook_deliveries.blade.php ENDPATH**/ ?>
<?php $__env->startSection('title', __('public.developer_portal.page_analytics', [], app()->getLocale()) ?: 'API Usage Analytics'); ?>
<?php $__env->startSection('sidebar_nav'); ?> <?php echo $__env->make('portals.developer._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php $__env->stopSection(); ?>
<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-head">
    <h2><?php echo e(__('public.developer_portal.nav_analytics', [], $l) ?: 'API Analytics'); ?></h2>
    <p class="portal-page-subtitle"><?php echo e(__('public.developer_portal.analytics_subtitle', [], $l) ?: 'Request volume, error rates, and response times across your apps — last 30 days.'); ?></p>
    <div class="page-head__spacer"></div>
    <form method="GET" class="filter-bar filter-bar--flush">
        <select name="period" class="filter-select" aria-label="<?php echo e(__('public.developer_portal.period_aria', [], $l) ?: 'Period'); ?>" onchange="this.form.submit()">
            <option value="daily" <?php echo e($period === 'daily' ? 'selected' : ''); ?>><?php echo e(__('public.developer_portal.period_daily', [], $l) ?: 'Daily'); ?></option>
            <option value="hourly" <?php echo e($period === 'hourly' ? 'selected' : ''); ?>><?php echo e(__('public.developer_portal.period_hourly', [], $l) ?: 'Hourly'); ?></option>
            <option value="monthly" <?php echo e($period === 'monthly' ? 'selected' : ''); ?>><?php echo e(__('public.developer_portal.period_monthly', [], $l) ?: 'Monthly'); ?></option>
        </select>
    </form>
</div>


<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__label"><?php echo e(__('public.developer_portal.stat_total_requests', [], $l) ?: 'Total requests'); ?></div>
        <div class="stat-card__value"><?php echo e(number_format($totals['requests'])); ?></div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__label"><?php echo e(__('public.developer_portal.stat_total_errors', [], $l) ?: 'Total errors'); ?></div>
        <div class="stat-card__value"><?php echo e(number_format($totals['errors'])); ?></div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__label"><?php echo e(__('public.developer_portal.stat_error_rate', [], $l) ?: 'Error rate'); ?></div>
        <div class="stat-card__value"><?php echo e($totals['requests'] > 0 ? round(($totals['errors'] / $totals['requests']) * 100, 1) : 0); ?>%</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__label"><?php echo e(__('public.developer_portal.stat_avg_response', [], $l) ?: 'Avg response'); ?></div>
        <div class="stat-card__value"><?php echo e(round($totals['avg_ms'])); ?>ms</div>
    </div>
    <div class="stat-card stat-card--teal">
        <div class="stat-card__label"><?php echo e(__('public.developer_portal.stat_active_apps', [], $l) ?: 'Active apps'); ?></div>
        <div class="stat-card__value"><?php echo e($clients->count()); ?></div>
    </div>
</div>

<?php if($metrics->isEmpty()): ?>
<div class="alert alert-info mb-6">
    <i data-lucide="info"></i>
    <div><?php echo e(__('public.developer_portal.analytics_no_data', [], $l) ?: 'No usage data yet for the selected period. Make your first API call to see metrics here.'); ?></div>
</div>
<?php endif; ?>

<div class="field-grid mb-6">

    
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="list"></i> <?php echo e(__('public.developer_portal.analytics_top_endpoints', [], $l) ?: 'Top endpoints'); ?></h3></div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php echo e(__('public.developer_portal.col_endpoint', [], $l) ?: 'Endpoint'); ?></th>
                        <th><?php echo e(__('public.developer_portal.col_requests', [], $l) ?: 'Requests'); ?></th>
                        <th><?php echo e(__('public.developer_portal.col_errors', [], $l) ?: 'Errors'); ?></th>
                        <th><?php echo e(__('public.developer_portal.col_avg_ms', [], $l) ?: 'Avg ms'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $byEndpoint; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td data-label="<?php echo e(__('public.developer_portal.col_endpoint', [], $l) ?: 'Endpoint'); ?>" class="mono"><?php echo e($row['endpoint']); ?></td>
                        <td data-label="<?php echo e(__('public.developer_portal.col_requests', [], $l) ?: 'Requests'); ?>" class="td-strong"><?php echo e(number_format($row['requests'])); ?></td>
                        <td data-label="<?php echo e(__('public.developer_portal.col_errors', [], $l) ?: 'Errors'); ?>">
                            <?php if($row['errors'] > 0): ?><span class="badge badge-danger"><?php echo e(number_format($row['errors'])); ?></span>
                            <?php else: ?><span class="td-muted">0</span><?php endif; ?>
                        </td>
                        <td data-label="<?php echo e(__('public.developer_portal.col_avg_ms', [], $l) ?: 'Avg ms'); ?>" class="td-muted"><?php echo e($row['avg_ms']); ?>ms</td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="4" class="td-muted empty-cell"><?php echo e(__('public.developer_portal.analytics_no_data_cell', [], $l) ?: 'No data yet.'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="cpu"></i> <?php echo e(__('public.developer_portal.analytics_your_apps', [], $l) ?: 'Your apps'); ?></h3>
            <a href="<?php echo e(route('portals.developer.apps')); ?>" class="btn btn-secondary btn-sm"><?php echo e(__('public.developer_portal.btn_manage', [], $l) ?: 'Manage'); ?></a>
        </div>
        <?php if($clients->isEmpty()): ?>
        <div class="panel-body empty-state">
            <p><?php echo e(__('public.developer_portal.analytics_no_apps', [], $l) ?: 'No apps yet.'); ?> <a href="<?php echo e(route('portals.developer.apps.create')); ?>"><?php echo e(__('public.developer_portal.lnk_create_first_app', [], $l) ?: 'Create your first app'); ?></a>.</p>
        </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th><?php echo e(__('public.developer_portal.col_app', [], $l) ?: 'App'); ?></th>
                    <th><?php echo e(__('public.portal.col_status', [], $l) ?: 'Status'); ?></th>
                </tr></thead>
                <tbody>
                <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td data-label="<?php echo e(__('public.developer_portal.col_app', [], $l) ?: 'App'); ?>">
                        <span class="td-strong"><?php echo e($client->name); ?></span>
                        <div class="mono"><?php echo e($client->client_id); ?></div>
                    </td>
                    <td data-label="<?php echo e(__('public.portal.col_status', [], $l) ?: 'Status'); ?>">
                        <span class="badge badge-<?php echo e($client->status === 'active' ? 'success' : ($client->status === 'pending' ? 'warning' : 'neutral')); ?>"><?php echo e(\App\Support\Enums::label($client->status)); ?></span>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>


<?php if($metrics->isNotEmpty()): ?>
<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="history"></i> <?php echo e(__('public.developer_portal.analytics_recent_metrics', [], $l) ?: 'Recent metric records'); ?></h3></div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?php echo e(__('public.developer_portal.col_endpoint', [], $l) ?: 'Endpoint'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_method', [], $l) ?: 'Method'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_period_start', [], $l) ?: 'Period start'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_requests', [], $l) ?: 'Requests'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_errors', [], $l) ?: 'Errors'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_avg_ms', [], $l) ?: 'Avg ms'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $metrics->take(20); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td data-label="<?php echo e(__('public.developer_portal.col_endpoint', [], $l) ?: 'Endpoint'); ?>" class="mono"><?php echo e($m->endpoint); ?></td>
                    <td data-label="<?php echo e(__('public.developer_portal.col_method', [], $l) ?: 'Method'); ?>"><span class="badge badge-neutral"><?php echo e($m->method); ?></span></td>
                    <td data-label="<?php echo e(__('public.developer_portal.col_period_start', [], $l) ?: 'Period start'); ?>" class="td-muted"><?php echo e($m->period_start?->format('d M Y H:i')); ?></td>
                    <td data-label="<?php echo e(__('public.developer_portal.col_requests', [], $l) ?: 'Requests'); ?>"><?php echo e(number_format($m->request_count)); ?></td>
                    <td data-label="<?php echo e(__('public.developer_portal.col_errors', [], $l) ?: 'Errors'); ?>">
                        <?php if($m->error_count > 0): ?><span class="badge badge-danger"><?php echo e($m->error_count); ?></span>
                        <?php else: ?><span class="td-muted">0</span><?php endif; ?>
                    </td>
                    <td data-label="<?php echo e(__('public.developer_portal.col_avg_ms', [], $l) ?: 'Avg ms'); ?>" class="td-muted"><?php echo e(round($m->avg_response_ms)); ?>ms</td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\developer\analytics.blade.php ENDPATH**/ ?>
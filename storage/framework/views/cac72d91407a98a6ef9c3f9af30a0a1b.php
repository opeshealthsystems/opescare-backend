<?php $__env->startSection('title', ($client->name ?? __('public.developer_portal.lbl_unnamed_app', [], app()->getLocale())) . ' — ' . __('public.developer_portal.lbl_view', [], app()->getLocale())); ?>
<?php $__env->startSection('sidebar_nav'); ?> <?php echo $__env->make('portals.developer._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php $__env->stopSection(); ?>
<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

    <div class="breadcrumb">
        <a href="<?php echo e(route('portals.developer.apps')); ?>"><?php echo e(__('public.developer_portal.lnk_my_apps', [], $l) ?: 'My apps'); ?></a>
        <i data-lucide="chevron-right"></i>
        <span><?php echo e($client->name ?? __('public.developer_portal.lbl_unnamed_app', [], $l) ?: 'Unnamed App'); ?></span>
    </div>

    <div class="entity-head">
        <div class="entity-head__icon"><i data-lucide="plug"></i></div>
        <div>
            <h2 class="entity-head__title"><?php echo e($client->name ?? __('public.developer_portal.lbl_unnamed_app', [], $l) ?: 'Unnamed App'); ?></h2>
            <div class="entity-head__sub">
                <span class="badge <?php echo e(($client->environment ?? 'sandbox') === 'production' ? 'badge-success' : 'badge-info'); ?>"><?php echo e(\App\Support\Enums::label($client->environment ?? 'sandbox', 'environment')); ?></span>
                <span class="badge <?php echo e(($client->status ?? 'active') === 'active' ? 'badge-success' : 'badge-neutral'); ?>"><?php echo e(\App\Support\Enums::label($client->status ?? 'active')); ?></span>
            </div>
        </div>
    </div>

    <?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>

    <?php if(session('new_client_secret')): ?>
    <div class="panel mb-6">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="key"></i> <?php echo e(__('public.developer_portal.panel_save_credentials', [], $l) ?: 'Save your credentials — shown only once'); ?></h3></div>
        <div class="panel-body">
            <?php if(session('new_client_id')): ?><div class="code-block mb-6"><strong><?php echo e(__('public.developer_portal.lbl_client_id', [], $l) ?: 'Client ID'); ?>:</strong> <span class="code-token" data-copy="<?php echo e(session('new_client_id')); ?>"><?php echo e(session('new_client_id')); ?></span></div><?php endif; ?>
            <div class="code-block"><strong><?php echo e(__('public.developer_portal.lbl_client_secret', [], $l) ?: 'Client Secret'); ?>:</strong> <span class="code-token" data-copy="<?php echo e(session('new_client_secret')); ?>"><?php echo e(session('new_client_secret')); ?></span></div>
        </div>
    </div>
    <?php endif; ?>

    
    <div class="panel mb-6">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="settings"></i> <?php echo e(__('public.developer_portal.panel_manage', [], $l) ?: 'Manage app'); ?></h3></div>
        <div class="panel-body" style="display:flex;gap:.6rem;flex-wrap:wrap;">
            <form method="POST" action="<?php echo e(route('portals.developer.apps.rotate', $client->id)); ?>"
                  onsubmit="return confirm('<?php echo e(__('public.developer_portal.rotate_confirm', [], $l) ?: 'Rotate the secret? The old secret stops working immediately.'); ?>');">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-secondary"><i data-lucide="refresh-cw"></i> <?php echo e(__('public.developer_portal.btn_rotate', [], $l) ?: 'Rotate secret'); ?></button>
            </form>
            <form method="POST" action="<?php echo e(route('portals.developer.apps.toggle', $client->id)); ?>"
                  onsubmit="return confirm('<?php echo e(__('public.developer_portal.toggle_confirm', [], $l) ?: 'Change this app’s status?'); ?>');">
                <?php echo csrf_field(); ?>
                <?php if(($client->status ?? 'active') === 'active'): ?>
                <button type="submit" class="btn btn-ghost btn-danger"><i data-lucide="power"></i> <?php echo e(__('public.developer_portal.btn_disable', [], $l) ?: 'Disable app'); ?></button>
                <?php else: ?>
                <button type="submit" class="btn btn-primary"><i data-lucide="power"></i> <?php echo e(__('public.developer_portal.btn_enable', [], $l) ?: 'Enable app'); ?></button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    
    <?php
        $quota = $plan?->monthly_request_quota;
        $pct = $quota ? min(100, (int) round($monthlyUsage / max(1, $quota) * 100)) : null;
        $barColor = $pct === null ? '#0F4C81' : ($pct >= 90 ? '#ef4444' : ($pct >= 70 ? '#f59e0b' : '#10b981'));
    ?>
    <div class="panel mb-6">
        <div class="panel-header" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
            <h3 class="panel-title"><i data-lucide="gauge"></i> <?php echo e(__('public.developer_portal.panel_plan_usage', [], $l) ?: 'Plan & usage'); ?></h3>
            <a href="<?php echo e(route('public.developers.pricing')); ?>" class="btn btn-ghost" style="font-size:.8125rem;"><i data-lucide="arrow-up-right"></i> <?php echo e(__('public.developer_portal.btn_view_plans', [], $l) ?: 'View plans'); ?></a>
        </div>
        <div class="panel-body">
            <div style="display:flex;align-items:baseline;gap:.6rem;flex-wrap:wrap;margin-bottom:1rem;">
                <span style="font-size:1.25rem;font-weight:800;color:#0F4C81;"><?php echo e($plan?->name ?? 'Sandbox'); ?></span>
                <span class="td-muted" style="font-size:.8125rem;">· <?php echo e(number_format($plan?->rate_limit_per_min ?? 60, 0, ',', ' ')); ?> <?php echo e(__('public.developer_portal.lbl_req_min', [], $l) ?: 'req/min'); ?> · <?php echo e(__('public.developer_portal.lbl_quota_reset', [], $l) ?: 'Resets on the 1st'); ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:.8125rem;color:#475569;margin-bottom:.4rem;">
                <span><?php echo e(__('public.developer_portal.lbl_this_month_usage', [], $l) ?: 'Requests this month'); ?>: <strong style="color:#0F2744;"><?php echo e(number_format($monthlyUsage, 0, ',', ' ')); ?></strong></span>
                <span><?php echo e(__('public.developer_portal.lbl_quota', [], $l) ?: 'Monthly quota'); ?>: <strong style="color:#0F2744;"><?php echo e($quota ? number_format($quota, 0, ',', ' ') : (__('public.developer_portal.lbl_unlimited', [], $l) ?: 'Unlimited')); ?></strong></span>
            </div>
            <?php if($pct !== null): ?>
            <div style="height:.5rem;background:#f1f5f9;border-radius:999px;overflow:hidden;">
                <div style="height:100%;width:<?php echo e($pct); ?>%;background:<?php echo e($barColor); ?>;border-radius:999px;"></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    
    <?php
        $unpaid = ($invoices ?? collect())->first(fn ($inv) => ! in_array($inv->status, ['paid', 'void'], true));
        $invStatusStyle = ['issued' => ['#b45309', '#fffbeb'], 'paid' => ['#047857', '#ecfdf5'], 'overdue' => ['#b91c1c', '#fef2f2'], 'void' => ['#64748b', '#f1f5f9']];
    ?>
    <div class="panel mb-6">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="receipt"></i> <?php echo e(__('public.developer_portal.panel_billing', [], $l) ?: 'Billing & invoices'); ?></h3></div>
        <div class="panel-body">
            <?php if(($invoices ?? collect())->isEmpty()): ?>
                <p class="td-muted" style="margin:0;"><?php echo e(__('public.developer_portal.lbl_no_invoices', [], $l) ?: 'No invoices yet — usage is metered and billed monthly.'); ?></p>
            <?php else: ?>
                <table class="kv-table" style="width:100%;">
                    <thead><tr>
                        <th><?php echo e(__('public.developer_portal.lbl_invoice', [], $l) ?: 'Invoice'); ?></th>
                        <th><?php echo e(__('public.developer_portal.lbl_period', [], $l) ?: 'Period'); ?></th>
                        <th><?php echo e(__('public.developer_portal.lbl_amount', [], $l) ?: 'Amount'); ?></th>
                        <th><?php echo e(__('public.developer_portal.lbl_status', [], $l) ?: 'Status'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $iss = $invStatusStyle[$inv->status] ?? ['#64748b', '#f1f5f9']; ?>
                        <tr>
                            <td class="mono"><?php echo e($inv->number()); ?></td>
                            <td><?php echo e(optional($inv->period_start)->format('M Y')); ?></td>
                            <td><strong><?php echo e(number_format($inv->total_xaf, 0, ',', ' ')); ?> FCFA</strong></td>
                            <td><span class="badge" style="color:<?php echo e($iss[0]); ?>;background:<?php echo e($iss[1]); ?>;"><?php echo e(__('public.developer_portal.st_' . $inv->status, [], $l) ?: ucfirst($inv->status)); ?></span></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>

                <?php if($unpaid): ?>
                <form method="POST" action="<?php echo e(route('portals.developer.apps.pay', $client->id)); ?>" style="margin-top:1.25rem;border-top:1px solid #f1f5f9;padding-top:1.25rem;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="invoice_id" value="<?php echo e($unpaid->id); ?>">
                    <label style="display:block;font-size:.75rem;color:#64748b;margin-bottom:.4rem;"><?php echo e(__('public.developer_portal.lbl_pay_invoice', [], $l) ?: 'Pay'); ?> <?php echo e($unpaid->number()); ?> · <?php echo e(number_format($unpaid->total_xaf, 0, ',', ' ')); ?> FCFA</label>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
                        <select name="provider" class="form-control" style="max-width:170px;">
                            <option value="mtn_momo"><?php echo e(__('public.developer_portal.opt_mtn', [], $l) ?: 'MTN MoMo'); ?></option>
                            <option value="orange_money"><?php echo e(__('public.developer_portal.opt_orange', [], $l) ?: 'Orange Money'); ?></option>
                        </select>
                        <input type="text" name="phone" class="form-control" placeholder="6XXXXXXXX" style="max-width:170px;" required>
                        <button type="submit" class="btn btn-primary"><i data-lucide="smartphone"></i> <?php echo e(__('public.developer_portal.btn_pay', [], $l) ?: 'Pay now'); ?></button>
                    </div>
                </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="field-grid mb-6">

        
        <div>
            <div class="panel mb-6">
                <div class="panel-header"><h3 class="panel-title"><i data-lucide="key"></i> <?php echo e(__('public.developer_portal.panel_credentials', [], $l) ?: 'Credentials'); ?></h3></div>
                <div class="panel-body">
                    <table class="kv-table">
                        <tr><th><?php echo e(__('public.developer_portal.lbl_client_id', [], $l) ?: 'Client ID'); ?></th><td><span class="code-token" data-copy="<?php echo e($client->client_id); ?>"><?php echo e($client->client_id); ?></span></td></tr>
                        <tr><th><?php echo e(__('public.developer_portal.lbl_secret', [], $l) ?: 'Secret'); ?></th><td class="td-muted mono"><?php echo e(__('public.developer_portal.lbl_secret_masked', [], $l) ?: '•••••••••••••••• (shown once at creation)'); ?></td></tr>
                        <tr><th><?php echo e(__('public.developer_portal.lbl_environment', [], $l) ?: 'Environment'); ?></th><td><?php echo e(\App\Support\Enums::label($client->environment ?? 'sandbox', 'environment')); ?></td></tr>
                        <tr><th><?php echo e(__('public.developer_portal.lbl_scopes', [], $l) ?: 'Scopes'); ?></th><td>
                            <?php $__currentLoopData = json_decode($client->scopes ?? '[]', true) ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $scope): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <span class="code-token"><?php echo e($scope); ?></span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </td></tr>
                        <tr><th><?php echo e(__('public.developer_portal.lbl_created', [], $l) ?: 'Created'); ?></th><td class="td-muted"><?php echo e($client->created_at->format('d M Y H:i')); ?></td></tr>
                    </table>
                </div>
            </div>

            
            <?php if($certification): ?>
            <div class="panel">
                <div class="panel-header"><h3 class="panel-title"><i data-lucide="award"></i> <?php echo e(__('public.developer_portal.panel_integration_cert', [], $l) ?: 'Integration certification'); ?></h3></div>
                <div class="panel-body">
                    <div class="entity-head">
                        <?php if($certification->badge): ?>
                        <div class="entity-head__icon"><i data-lucide="<?php echo e($certification->badge->levelIcon()); ?>" style="color:<?php echo e($certification->badge->levelColor()); ?>;"></i></div>
                        <div>
                            <div class="td-strong"><?php echo e(\App\Support\Enums::label($certification->badge->certification_level, 'level')); ?> certified</div>
                            <div class="mono"><?php echo e($certification->badge->badge_code); ?></div>
                        </div>
                        <?php else: ?>
                        <div>
                            <span class="<?php echo e($certification->statusBadgeClass()); ?>"><?php echo e(\App\Support\Enums::label($certification->status)); ?></span>
                            <div class="td-muted"><?php echo e(__('public.developer_portal.lbl_cert_in_progress', [], $l) ?: 'Certification in progress'); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        
        <div>

            
            <div class="panel mb-6">
                <div class="panel-header"><h3 class="panel-title"><i data-lucide="bar-chart-3"></i> <?php echo e(__('public.developer_portal.panel_api_usage', [], $l) ?: 'API usage (30 days)'); ?></h3></div>
                <?php if(empty($usageSummary)): ?>
                <div class="panel-body empty-state"><p><?php echo e(__('public.developer_portal.no_usage_yet', [], $l) ?: 'No usage recorded yet in the last 30 days.'); ?></p></div>
                <?php else: ?>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead><tr>
                            <th><?php echo e(__('public.developer_portal.col_endpoint_group', [], $l) ?: 'Endpoint group'); ?></th>
                            <th><?php echo e(__('public.developer_portal.col_requests', [], $l) ?: 'Requests'); ?></th>
                            <th><?php echo e(__('public.developer_portal.col_errors', [], $l) ?: 'Errors'); ?></th>
                        </tr></thead>
                        <tbody>
                        <?php $__currentLoopData = $usageSummary; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group => $stats): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td data-label="<?php echo e(__('public.developer_portal.col_endpoint_group', [], $l) ?: 'Endpoint group'); ?>" class="mono"><?php echo e($group); ?></td>
                            <td data-label="<?php echo e(__('public.developer_portal.col_requests', [], $l) ?: 'Requests'); ?>"><?php echo e(number_format($stats['total_requests'])); ?></td>
                            <td data-label="<?php echo e(__('public.developer_portal.col_errors', [], $l) ?: 'Errors'); ?>">
                                <?php if($stats['total_errors'] > 0): ?><span class="badge badge-danger"><?php echo e(number_format($stats['total_errors'])); ?></span>
                                <?php else: ?><span class="badge badge-success">0</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title"><i data-lucide="webhook"></i> <?php echo e(__('public.developer_portal.panel_webhook_subs', [], $l) ?: 'Webhook subscriptions'); ?></h3>
                    <a href="<?php echo e(route('portals.developer.webhook_deliveries', $client->id)); ?>" class="btn btn-ghost btn-sm"><?php echo e(__('public.developer_portal.btn_delivery_logs', [], $l) ?: 'Delivery logs'); ?></a>
                </div>
                <?php if($webhooks->isEmpty()): ?>
                <div class="panel-body empty-state"><p><?php echo e(__('public.developer_portal.no_webhook_subs', [], $l) ?: 'No webhook subscriptions. Use the API to create subscriptions.'); ?></p></div>
                <?php else: ?>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead><tr>
                            <th><?php echo e(__('public.developer_portal.col_endpoint', [], $l) ?: 'Endpoint'); ?></th>
                            <th><?php echo e(__('public.developer_portal.col_events', [], $l) ?: 'Events'); ?></th>
                            <th><?php echo e(__('public.portal.col_status', [], $l) ?: 'Status'); ?></th>
                        </tr></thead>
                        <tbody>
                        <?php $__currentLoopData = $webhooks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td data-label="<?php echo e(__('public.developer_portal.col_endpoint', [], $l) ?: 'Endpoint'); ?>" class="mono"><?php echo e($wh->callback_url); ?></td>
                            <td data-label="<?php echo e(__('public.developer_portal.col_events', [], $l) ?: 'Events'); ?>" class="td-muted"><?php echo e(count((array)$wh->subscribed_events)); ?> <?php echo e(__('public.developer_portal.col_events', [], $l) ?: 'events'); ?></td>
                            <td data-label="<?php echo e(__('public.portal.col_status', [], $l) ?: 'Status'); ?>"><span class="badge <?php echo e($wh->status === 'active' ? 'badge-success' : 'badge-neutral'); ?>"><?php echo e($wh->status); ?></span></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
<script>
document.addEventListener('click', function(e){
    var t = e.target.closest('.code-token[data-copy]');
    if(!t) return;
    navigator.clipboard && navigator.clipboard.writeText(t.getAttribute('data-copy'));
    var prev = t.textContent; t.textContent = <?php echo json_encode(__('dev_extra.copied', [], $l) ?: 'Copied') ?>; setTimeout(function(){ t.textContent = prev; }, 1200);
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\developer\app_detail.blade.php ENDPATH**/ ?>
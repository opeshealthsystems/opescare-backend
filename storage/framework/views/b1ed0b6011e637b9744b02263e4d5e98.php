<?php $__env->startSection('title', __('public.developer_portal.page_title', [], app()->getLocale()) ?: 'Developer Portal'); ?>
<?php $__env->startSection('sidebar_nav'); ?> <?php echo $__env->make('portals.developer._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <div class="page-head">
        <h2><?php echo e(__('public.developer_portal.page_heading', [], app()->getLocale()) ?: 'Developer portal'); ?></h2>
        <p class="portal-page-subtitle"><?php echo e(__('public.developer_portal.welcome_back', [], app()->getLocale()) ?: 'Welcome back,'); ?> <?php echo e($developer->display_name); ?></p>
        <div class="page-head__spacer"></div>
        <?php if($developer->isSandboxOnly()): ?>
            <span class="badge badge-warning"><?php echo e(__('public.developer_portal.badge_sandbox', [], app()->getLocale()) ?: 'Sandbox only'); ?></span>
            <a href="<?php echo e(route('portals.developer.production_requests.create')); ?>" class="btn btn-primary btn-sm">
                <i data-lucide="rocket"></i> <?php echo e(__('public.developer_portal.btn_request_prod', [], app()->getLocale()) ?: 'Request production access'); ?>

            </a>
        <?php else: ?>
            <span class="badge badge-success"><?php echo e(__('public.developer_portal.badge_production', [], app()->getLocale()) ?: 'Production access'); ?></span>
        <?php endif; ?>
    </div>

    <?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
    <?php if(session('error')): ?><div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>

    <?php if(!$developer->isEmailVerified()): ?>
    <div class="alert alert-warning mb-6">
        <i data-lucide="alert-triangle"></i>
        <div><?php echo e(__('public.developer_portal.email_unverified', [], app()->getLocale()) ?: 'Your email address has not been verified. Some features are restricted until verification is complete.'); ?></div>
    </div>
    <?php endif; ?>

    
    <div class="stat-grid mb-6">
        <div class="stat-card stat-card--primary">
            <div class="stat-card__label"><?php echo e(__('public.developer_portal.stat_apps', [], app()->getLocale()) ?: 'Apps'); ?></div>
            <div class="stat-card__value"><?php echo e($clients->count()); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-card__label"><?php echo e(__('public.developer_portal.stat_api_requests', [], app()->getLocale()) ?: 'API requests (30d)'); ?></div>
            <div class="stat-card__value"><?php echo e(number_format($totalRequests)); ?></div>
        </div>
        <div class="stat-card <?php echo e($totalErrors > 0 ? 'stat-card--danger' : 'stat-card--success'); ?>">
            <div class="stat-card__label"><?php echo e(__('public.developer_portal.stat_errors', [], app()->getLocale()) ?: 'Errors (30d)'); ?></div>
            <div class="stat-card__value"><?php echo e(number_format($totalErrors)); ?></div>
        </div>
        <div class="stat-card stat-card--warning">
            <div class="stat-card__label"><?php echo e(__('public.developer_portal.stat_pending_requests', [], app()->getLocale()) ?: 'Pending requests'); ?></div>
            <div class="stat-card__value"><?php echo e($productionRequests->where('status','pending')->count()); ?></div>
        </div>
    </div>

    <div class="field-grid mb-6">

        
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title"><i data-lucide="plug"></i> <?php echo e(__('public.developer_portal.panel_apps', [], app()->getLocale()) ?: 'Your apps'); ?></h3>
                <a href="<?php echo e(route('portals.developer.apps.create')); ?>" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> <?php echo e(__('public.developer_portal.btn_new_app', [], app()->getLocale()) ?: 'New app'); ?></a>
            </div>
            <?php if($clients->isEmpty()): ?>
            <div class="panel-body empty-state">
                <p><?php echo e(__('public.developer_portal.no_apps', [], app()->getLocale()) ?: 'No apps yet.'); ?>

                   <a href="<?php echo e(route('portals.developer.apps.create')); ?>"><?php echo e(__('public.developer_portal.lnk_create_first_app', [], app()->getLocale()) ?: 'Create your first app'); ?></a>
                   <?php echo e(__('public.developer_portal.lnk_create_first_app_suffix', [], app()->getLocale()) ?: 'to get sandbox API credentials.'); ?></p>
            </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th><?php echo e(__('public.developer_portal.col_app', [], app()->getLocale()) ?: 'App'); ?></th>
                        <th><?php echo e(__('public.developer_portal.col_environment', [], app()->getLocale()) ?: 'Environment'); ?></th>
                        <th><?php echo e(__('public.portal.col_status', [], app()->getLocale()) ?: 'Status'); ?></th>
                        <th class="row-actions"></th>
                    </tr></thead>
                    <tbody>
                    <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td data-label="<?php echo e(__('public.developer_portal.col_app', [], app()->getLocale()) ?: 'App'); ?>">
                            <span class="td-strong"><?php echo e($client->name ?? (__('public.developer_portal.lbl_unnamed_app', [], app()->getLocale()) ?: 'Unnamed App')); ?></span>
                            <div class="mono"><?php echo e(Str::limit($client->client_id, 20)); ?></div>
                        </td>
                        <td data-label="<?php echo e(__('public.developer_portal.col_environment', [], app()->getLocale()) ?: 'Environment'); ?>">
                            <span class="badge <?php echo e($client->environment === 'production' ? 'badge-success' : 'badge-info'); ?>"><?php echo e(\App\Support\Enums::label($client->environment ?? 'sandbox', 'environment')); ?></span>
                        </td>
                        <td data-label="<?php echo e(__('public.portal.col_status', [], app()->getLocale()) ?: 'Status'); ?>">
                            <span class="badge <?php echo e(($client->status ?? 'active') === 'active' ? 'badge-success' : 'badge-neutral'); ?>"><?php echo e(\App\Support\Enums::label($client->status ?? 'active')); ?></span>
                        </td>
                        <td class="row-actions" data-label="">
                            <a href="<?php echo e(route('portals.developer.apps.show', $client->id)); ?>" class="icon-btn" aria-label="<?php echo e(__('public.developer_portal.lbl_view_app', [], app()->getLocale()) ?: 'View app'); ?>" title="<?php echo e(__('public.developer_portal.lbl_view', [], app()->getLocale()) ?: 'View'); ?>"><i data-lucide="eye"></i></a>
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
                <h3 class="panel-title"><i data-lucide="rocket"></i> <?php echo e(__('public.developer_portal.panel_prod_requests', [], app()->getLocale()) ?: 'Production requests'); ?></h3>
                <a href="<?php echo e(route('portals.developer.production_requests.create')); ?>" class="btn btn-secondary btn-sm"><?php echo e(__('public.developer_portal.btn_request_access', [], app()->getLocale()) ?: 'Request access'); ?></a>
            </div>
            <?php if($productionRequests->isEmpty()): ?>
            <div class="panel-body empty-state">
                <p><?php echo e(__('public.developer_portal.no_prod_requests', [], app()->getLocale()) ?: 'No production access requests yet.'); ?></p>
            </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th><?php echo e(__('public.developer_portal.col_use_case', [], app()->getLocale()) ?: 'Use case'); ?></th>
                        <th><?php echo e(__('public.portal.col_status', [], app()->getLocale()) ?: 'Status'); ?></th>
                        <th><?php echo e(__('public.portal.col_date', [], app()->getLocale()) ?: 'Date'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php $__currentLoopData = $productionRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td data-label="<?php echo e(__('public.developer_portal.col_use_case', [], app()->getLocale()) ?: 'Use case'); ?>"><?php echo e(Str::limit($req->use_case, 40)); ?></td>
                        <td data-label="<?php echo e(__('public.portal.col_status', [], app()->getLocale()) ?: 'Status'); ?>"><span class="<?php echo e($req->statusBadgeClass()); ?>"><?php echo e(\App\Support\Enums::label($req->status)); ?></span></td>
                        <td data-label="<?php echo e(__('public.portal.col_date', [], app()->getLocale()) ?: 'Date'); ?>" class="td-muted"><?php echo e($req->created_at->format('d M Y')); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </div>

    
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="link"></i> <?php echo e(__('public.developer_portal.panel_quick_links', [], app()->getLocale()) ?: 'Quick links'); ?></h3></div>
        <div class="panel-body stat-grid">
            <a href="<?php echo e(route('portals.developer.apps')); ?>" class="stat-card">
                <i data-lucide="key"></i>
                <div class="stat-card__label"><?php echo e(__('public.developer_portal.lnk_api_keys', [], app()->getLocale()) ?: 'API keys'); ?></div>
            </a>
            <a href="<?php echo e(route('portals.developer.production_requests')); ?>" class="stat-card">
                <i data-lucide="rocket"></i>
                <div class="stat-card__label"><?php echo e(__('public.developer_portal.lnk_prod_access', [], app()->getLocale()) ?: 'Production access'); ?></div>
            </a>
            <a href="<?php echo e(route('portals.developer.analytics')); ?>" class="stat-card">
                <i data-lucide="bar-chart-3"></i>
                <div class="stat-card__label"><?php echo e(__('public.developer_portal.lnk_analytics', [], app()->getLocale()) ?: 'API Analytics'); ?></div>
            </a>
            <a href="<?php echo e(route('portals.developer.apps')); ?>" class="stat-card">
                <i data-lucide="webhook"></i>
                <div class="stat-card__label"><?php echo e(__('public.developer_portal.lnk_webhooks', [], app()->getLocale()) ?: 'Webhooks'); ?></div>
            </a>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\developer\dashboard.blade.php ENDPATH**/ ?>
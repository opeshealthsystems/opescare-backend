<?php $__env->startSection('title', __('public.developer_portal.page_apps', [], app()->getLocale()) ?: 'My Apps'); ?>
<?php $__env->startSection('sidebar_nav'); ?> <?php echo $__env->make('portals.developer._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php $__env->stopSection(); ?>
<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

    <div class="breadcrumb">
        <a href="<?php echo e(route('portals.developer.dashboard')); ?>"><?php echo e(__('public.developer_portal.page_heading', [], $l) ?: 'Developer Portal'); ?></a>
        <i data-lucide="chevron-right"></i>
        <span><?php echo e(__('public.developer_portal.nav_apps', [], $l) ?: 'My Apps'); ?></span>
    </div>

    <div class="page-head">
        <h2><?php echo e(__('public.developer_portal.nav_apps', [], $l) ?: 'My Apps'); ?></h2>
        <div class="page-head__spacer"></div>
        <a href="<?php echo e(route('portals.developer.apps.create')); ?>" class="btn btn-primary"><i data-lucide="plus"></i> <?php echo e(__('public.developer_portal.btn_new_app', [], $l) ?: 'New App'); ?></a>
    </div>

    <?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>

    <?php if(session('new_client_secret')): ?>
    <div class="panel mb-6">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="key"></i> <?php echo e(__('public.developer_portal.lbl_credentials_created', [], $l) ?: 'App created — save your credentials now'); ?></h3></div>
        <div class="panel-body">
            <p class="mb-6"><?php echo e(__('public.developer_portal.lbl_secret_once', [], $l) ?: 'Your client secret is shown only once. Store it securely — it cannot be retrieved again.'); ?></p>
            <div class="code-block mb-6"><strong><?php echo e(__('public.developer_portal.lbl_client_id', [], $l) ?: 'Client ID'); ?>:</strong> <span class="code-token" data-copy="<?php echo e(session('new_client_id')); ?>"><?php echo e(session('new_client_id')); ?></span></div>
            <div class="code-block"><strong><?php echo e(__('public.developer_portal.lbl_client_secret', [], $l) ?: 'Client Secret'); ?>:</strong> <span class="code-token" data-copy="<?php echo e(session('new_client_secret')); ?>"><?php echo e(session('new_client_secret')); ?></span></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if($clients->isEmpty()): ?>
    <div class="panel">
        <div class="empty-state">
            <i data-lucide="plug" class="empty-state-icon"></i>
            <p><?php echo e(__('public.developer_portal.no_apps', [], $l) ?: 'No apps yet.'); ?></p>
            <a href="<?php echo e(route('portals.developer.apps.create')); ?>" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> <?php echo e(__('public.developer_portal.btn_create_app', [], $l) ?: 'Create app'); ?></a>
        </div>
    </div>
    <?php else: ?>
    <div class="panel">
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th><?php echo e(__('public.developer_portal.col_app_name', [], $l) ?: 'App name'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_client_id', [], $l) ?: 'Client ID'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_environment', [], $l) ?: 'Environment'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_status', [], $l) ?: 'Status'); ?></th>
                    <th><?php echo e(__('public.developer_portal.col_created', [], $l) ?: 'Created'); ?></th>
                    <th class="row-actions"></th>
                </tr></thead>
                <tbody>
                <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td data-label="<?php echo e(__('public.developer_portal.col_app_name', [], $l) ?: 'App name'); ?>">
                        <span class="td-strong"><?php echo e($client->name ?? __('public.developer_portal.lbl_unnamed_app', [], $l) ?: 'Unnamed App'); ?></span>
                        <?php if($client->description): ?>
                        <div class="td-muted"><?php echo e(Str::limit($client->description, 60)); ?></div>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?php echo e(__('public.developer_portal.col_client_id', [], $l) ?: 'Client ID'); ?>"><span class="code-token" data-copy="<?php echo e($client->client_id); ?>"><?php echo e(Str::limit($client->client_id, 28)); ?></span></td>
                    <td data-label="<?php echo e(__('public.developer_portal.col_environment', [], $l) ?: 'Environment'); ?>">
                        <span class="badge <?php echo e(($client->environment ?? 'sandbox') === 'production' ? 'badge-success' : 'badge-info'); ?>"><?php echo e(\App\Support\Enums::label($client->environment ?? 'sandbox', 'environment')); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.developer_portal.col_status', [], $l) ?: 'Status'); ?>">
                        <span class="badge <?php echo e(($client->status ?? 'active') === 'active' ? 'badge-success' : 'badge-neutral'); ?>"><?php echo e(\App\Support\Enums::label($client->status ?? 'active')); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.developer_portal.col_created', [], $l) ?: 'Created'); ?>" class="td-muted"><?php echo e($client->created_at->format('d M Y')); ?></td>
                    <td class="row-actions" data-label=""><a href="<?php echo e(route('portals.developer.apps.show', $client->id)); ?>" class="btn btn-secondary btn-sm"><?php echo e(__('public.developer_portal.lbl_view', [], $l) ?: 'Details'); ?></a></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

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

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\developer\apps.blade.php ENDPATH**/ ?>
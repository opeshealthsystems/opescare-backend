<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge">
    <i data-lucide="plug-zap"></i>
    <?php echo e(__('portal.api_partner_admin_role', [], $l) ?: 'API Partner Admin'); ?>

</div>
<div class="sidebar-spacer"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Overview'); ?></div>
    <a href="<?php echo e(route('portals.admin')); ?>" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Dashboard'); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_connect', [], $l) ?: 'Connect Portal'); ?></div>
    <a href="<?php echo e(route('portals.developer.apps')); ?>" class="sidebar-link">
        <i data-lucide="terminal"></i>
        <span><?php echo e(__('public.portal.nav_apps', [], $l) ?: 'My Apps'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.developer.apps')); ?>" class="sidebar-link">
        <i data-lucide="webhook"></i>
        <span><?php echo e(__('public.portal.nav_webhooks', [], $l) ?: 'Webhooks'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.developer.production_requests')); ?>" class="sidebar-link">
        <i data-lucide="send"></i>
        <span><?php echo e(__('public.portal.nav_production_req', [], $l) ?: 'Production Requests'); ?></span>
    </a>
    <?php if (\Illuminate\Support\Facades\Blade::check('platformadmin')): ?>
        <?php endif; ?>
    <a href="<?php echo e(route('public.help')); ?>" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span><?php echo e(__('public.portal.nav_help', [], $l) ?: 'Help'); ?></span>
    </a>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\sidebars\api_partner_admin.blade.php ENDPATH**/ ?>
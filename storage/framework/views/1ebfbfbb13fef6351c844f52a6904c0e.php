<?php $l = app()->getLocale(); ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.developer_portal.nav_section', [], $l) ?: 'Developer Portal'); ?></div>
    <a href="<?php echo e(route('portals.developer.dashboard')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.developer.dashboard') ? 'active' : ''); ?>">
        <i data-lucide="layout-dashboard"></i><span><?php echo e(__('public.developer_portal.nav_dashboard', [], $l) ?: 'Dashboard'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.developer.apps')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.developer.apps*') ? 'active' : ''); ?>">
        <i data-lucide="plug"></i><span><?php echo e(__('public.developer_portal.nav_apps', [], $l) ?: 'My Apps'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.developer.production_requests')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.developer.production_requests*') ? 'active' : ''); ?>">
        <i data-lucide="rocket"></i><span><?php echo e(__('public.developer_portal.nav_prod_access', [], $l) ?: 'Production Access'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.developer.analytics')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.developer.analytics') ? 'active' : ''); ?>">
        <i data-lucide="bar-chart-3"></i><span><?php echo e(__('public.developer_portal.nav_analytics', [], $l) ?: 'API Analytics'); ?></span>
    </a>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\developer\_sidebar.blade.php ENDPATH**/ ?>
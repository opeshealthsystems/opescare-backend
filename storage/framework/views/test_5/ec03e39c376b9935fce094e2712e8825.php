<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge">
    <i data-lucide="code-2"></i>
    <?php echo e(__('portal.developer_role', [], $l) ?: 'Developer'); ?>

</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="<?php echo e(route('portals.developer.dashboard')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.developer.dashboard') ? 'active' : ''); ?>">
        <i data-lucide="layout-dashboard"></i><span><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Dashboard'); ?></span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_developer', [], $l) ?: 'Developer'); ?></div>
    <a href="<?php echo e(route('portals.developer.apps')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.developer.apps*') ? 'active' : ''); ?>">
        <i data-lucide="app-window"></i><span><?php echo e(__('public.portal.nav_apps', [], $l) ?: 'My Apps'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.developer.analytics')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.developer.analytics*') ? 'active' : ''); ?>">
        <i data-lucide="bar-chart-3"></i><span><?php echo e(__('public.portal.nav_analytics', [], $l) ?: 'Analytics'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.developer.production_requests')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.developer.production_requests*') ? 'active' : ''); ?>">
        <i data-lucide="rocket"></i><span><?php echo e(__('public.portal.nav_production_requests', [], $l) ?: 'Production Requests'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.developer.onboard')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.developer.onboard*') ? 'active' : ''); ?>">
        <i data-lucide="graduation-cap"></i><span><?php echo e(__('public.portal.nav_onboarding', [], $l) ?: 'Onboarding'); ?></span>
    </a>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/partials/sidebars/developer.blade.php ENDPATH**/ ?>
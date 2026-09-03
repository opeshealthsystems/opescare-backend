<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge">
    <i data-lucide="pill"></i>
    <?php echo e(__('portal.pharmacy_manager_role', [], $l) ?: 'Pharmacy Manager'); ?>

</div>
<div class="sidebar-spacer"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_pharmacy_portal', [], $l) ?: 'Pharmacy Portal'); ?></div>
    <a href="<?php echo e(route('portals.pharmacy.dashboard')); ?>" class="sidebar-link">
        <i data-lucide="pill"></i>
        <span><?php echo e(__('public.portal.nav_pharmacy_portal_my', [], $l) ?: 'My Pharmacy Portal'); ?></span>
    </a>
</div>
<?php if (\Illuminate\Support\Facades\Blade::check('feature', 'inventory_ops')): ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Overview'); ?></div>
    </div>
<?php endif; ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_pharmacy', [], $l) ?: 'Pharmacy'); ?></div>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'inventory_ops')): ?>
            <?php endif; ?>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'billing')): ?>
        <?php endif; ?>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'analytics_dashboards')): ?>
    <?php endif; ?>
    <a href="<?php echo e(route('portals.listing.edit')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.listing.*') ? 'active' : ''); ?>">
        <i data-lucide="map-pin"></i>
        <span><?php echo e(__('caremap_claim.nav_my_listing', [], $l)); ?></span>
    </a>
    </div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\sidebars\pharmacy_manager.blade.php ENDPATH**/ ?>
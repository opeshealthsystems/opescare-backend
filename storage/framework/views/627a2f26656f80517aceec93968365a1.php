<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge" style="background:rgba(37,99,235,.2);border-color:rgba(37,99,235,.4);color:#93C5FD;">
    <i data-lucide="flask-conical" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    <?php echo e(__('portal.lab_manager_role', [], $l) ?: 'Lab Manager'); ?>

</div>
<div style="margin-bottom:var(--p-space-3);"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_lab_portal', [], $l) ?: 'Lab Portal'); ?></div>
    <a href="<?php echo e(route('portals.lab.dashboard')); ?>" class="sidebar-link" style="background:rgba(14,165,233,.08);border-left:3px solid #0ea5e9;">
        <i data-lucide="microscope" style="color:#0ea5e9;"></i>
        <span style="font-weight:700;"><?php echo e(__('public.portal.nav_lab_portal_my', [], $l) ?: 'My Lab Portal'); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Overview'); ?></div>
    <a href="<?php echo e(route('portals.staff')); ?>" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Dashboard'); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_lab', [], $l) ?: 'Laboratory'); ?></div>
    <a href="<?php echo e(route('portals.staff.queue')); ?>" class="sidebar-link">
        <i data-lucide="list-ordered"></i>
        <span><?php echo e(__('public.portal.nav_queue', [], $l) ?: 'Sample Queue'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.staff.immunizations')); ?>" class="sidebar-link">
        <i data-lucide="syringe"></i>
        <span><?php echo e(__('public.portal.nav_immunizations', [], $l) ?: 'Immunizations'); ?></span>
    </a>
    
    <a href="<?php echo e(route('portals.staff.inventory.blood')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.staff.inventory.blood*') ? 'active' : ''); ?>">
        <i data-lucide="droplets"></i>
        <span><?php echo e(__('public.portal.nav_inventory_blood', [], $l) ?: 'Blood Bank'); ?></span>
    </a>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'inventory_ops')): ?>
    <a href="<?php echo e(route('portals.staff.supply')); ?>" class="sidebar-link">
        <i data-lucide="package"></i>
        <span><?php echo e(__('public.portal.nav_supply', [], $l) ?: 'Supply Chain'); ?></span>
    </a>
    <?php endif; ?>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'analytics_dashboards')): ?>
    <?php endif; ?>
    <a href="<?php echo e(route('portals.listing.edit')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.listing.*') ? 'active' : ''); ?>">
        <i data-lucide="map-pin"></i>
        <span><?php echo e(__('caremap_claim.nav_my_listing', [], $l)); ?></span>
    </a>
    <a href="<?php echo e(route('portals.staff.support')); ?>" class="sidebar-link">
        <i data-lucide="headset"></i>
        <span><?php echo e(__('public.portal.nav_support', [], $l) ?: 'Support'); ?></span>
    </a>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/partials/sidebars/lab_manager.blade.php ENDPATH**/ ?>
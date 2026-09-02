<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge" style="background:rgba(37,99,235,.2);border-color:rgba(37,99,235,.4);color:#93C5FD;">
    <i data-lucide="flask-conical" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    <?php echo e(__('portal.labtech_role', [], $l) ?: 'Lab Technician'); ?>

</div>
<div style="margin-bottom:var(--p-space-3);"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_lab_portal', [], $l) ?: 'Lab Portal'); ?></div>
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
            
    <a href="<?php echo e(route('portals.staff.inventory.blood')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.staff.inventory.blood*') ? 'active' : ''); ?>">
        <i data-lucide="droplets"></i>
        <span><?php echo e(__('public.portal.nav_inventory_blood', [], $l) ?: 'Blood Bank'); ?></span>
    </a>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'inventory_ops')): ?>
        <?php endif; ?>
    </div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\sidebars\labtech.blade.php ENDPATH**/ ?>
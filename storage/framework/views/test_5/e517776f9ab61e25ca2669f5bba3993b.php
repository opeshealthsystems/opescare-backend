<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge">
    <i data-lucide="flask-conical"></i>
    <?php echo e(__('portal.labtech_role', [], $l) ?: 'Lab Technician'); ?>

</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="<?php echo e(route('portals.staff')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.staff') ? 'active' : ''); ?>">
        <i data-lucide="layout-dashboard"></i><span><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Dashboard'); ?></span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_lab', [], $l) ?: 'Laboratory'); ?></div>
            
    <a href="<?php echo e(route('portals.staff.inventory.blood')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.staff.inventory.blood*') ? 'active' : ''); ?>">
        <i data-lucide="droplets"></i>
        <span><?php echo e(__('public.portal.nav_inventory_blood', [], $l) ?: 'Blood Bank'); ?></span>
    </a>
</div>



<?php echo $__env->make('partials.sidebars._switch_facility', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/partials/sidebars/labtech.blade.php ENDPATH**/ ?>
<?php $l = app()->getLocale(); ?>

<div class="sidebar-role-badge">
    <i data-lucide="building-2"></i>
    <?php echo e(__('portal.facility_admin_role', [], $l) ?: 'Facility Admin'); ?>

</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="<?php echo e(route('portals.admin')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin') && !request()->routeIs('portals.admin.*') ? 'active' : ''); ?>">
        <i data-lucide="layout-dashboard"></i><span><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Dashboard'); ?></span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_facility', [], $l) ?: 'Facility'); ?></div>
    <a href="<?php echo e(route('portals.facility.team')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.facility.team*') ? 'active' : ''); ?>">
        <i data-lucide="users"></i><span><?php echo e(__('public.portal.nav_team', [], $l) ?: 'Team'); ?></span>
    </a>
</div>



<?php echo $__env->make('partials.sidebars._switch_facility', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/partials/sidebars/facility_admin.blade.php ENDPATH**/ ?>
<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge">
    <i data-lucide="heart-pulse"></i>
    <?php echo e(__('portal.nurse_role', [], $l) ?: 'Nurse'); ?>

</div>
<div class="sidebar-spacer"></div>
<div class="sidebar-nav-section">
    <a href="<?php echo e(route('portals.staff')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.staff') ? 'active' : ''); ?>">
        <i data-lucide="layout-dashboard"></i><span><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Dashboard'); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_clinical', [], $l) ?: 'Clinical'); ?></div>
    <a href="<?php echo e(route('portals.staff.appointments')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.staff.appointments*') ? 'active' : ''); ?>">
        <i data-lucide="calendar-check-2"></i><span><?php echo e(__('public.portal.nav_appointments', [], $l) ?: 'Appointments'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.prescriber.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.prescriber.*') ? 'active' : ''); ?>">
        <i data-lucide="file-text"></i><span><?php echo e(__('prescriber.nav', [], $l) ?: 'Prescriptions'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.staff.messages')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.staff.messages*') ? 'active' : ''); ?>">
        <i data-lucide="messages-square"></i><span><?php echo e(__('messaging.nav', [], $l) ?: 'Messages'); ?></span>
    </a>
</div>



<?php echo $__env->make('partials.sidebars._switch_facility', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/partials/sidebars/nurse.blade.php ENDPATH**/ ?>
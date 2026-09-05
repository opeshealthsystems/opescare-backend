<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge">
    <i data-lucide="pill"></i>
    <?php echo e(__('portal.pharmacist_role', [], $l) ?: 'Pharmacist'); ?>

</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="<?php echo e(route('portals.pharmacy.dashboard')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.pharmacy.dashboard') ? 'active' : ''); ?>">
        <i data-lucide="layout-dashboard"></i><span><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Dashboard'); ?></span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_pharmacy', [], $l) ?: 'Pharmacy'); ?></div>
    <a href="<?php echo e(route('portals.pharmacy.stock')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.pharmacy.stock*') ? 'active' : ''); ?>">
        <i data-lucide="package"></i><span><?php echo e(__('public.portal.nav_stock', [], $l) ?: 'Stock'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.pharmacy.prescriptions')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.pharmacy.prescriptions*') ? 'active' : ''); ?>">
        <i data-lucide="file-text"></i><span><?php echo e(__('public.portal.nav_prescriptions', [], $l) ?: 'Prescriptions'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.pharmacy.prescriptions.lookup')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.pharmacy.prescriptions.lookup') ? 'active' : ''); ?>">
        <i data-lucide="scan-line"></i><span><?php echo e(__('prescriber.counter_link', [], $l) ?: 'Counter lookup'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.pharmacy.controlled')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.pharmacy.controlled*') ? 'active' : ''); ?>">
        <i data-lucide="shield-alert"></i><span><?php echo e(__('public.portal.nav_controlled', [], $l) ?: 'Controlled Substances'); ?></span>
    </a>
</div>



<?php echo $__env->make('partials.sidebars._switch_facility', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/partials/sidebars/pharmacist.blade.php ENDPATH**/ ?>
<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge">
    <i data-lucide="bed"></i>
    <?php echo e(__('portal.ward_nurse_role', [], $l) ?: 'Ward Nurse'); ?>

</div>
<div class="sidebar-spacer"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Overview'); ?></div>
    <a href="<?php echo e(route('portals.staff')); ?>" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Dashboard'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.staff.messages')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.staff.messages*') ? 'active' : ''); ?>">
        <i data-lucide="messages-square"></i>
        <span><?php echo e(__('messaging.nav', [], $l) ?: 'Messages'); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_clinical', [], $l) ?: 'Clinical'); ?></div>
                </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_operations', [], $l) ?: 'Operations'); ?></div>
    </div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\sidebars\ward_nurse.blade.php ENDPATH**/ ?>
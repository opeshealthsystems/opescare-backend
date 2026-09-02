<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge" style="background:rgba(37,99,235,.2);border-color:rgba(37,99,235,.4);color:#93C5FD;">
    <i data-lucide="stethoscope" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    <?php echo e(__('portal.resident_doctor_role', [], $l) ?: 'Resident Doctor'); ?>

</div>
<div style="margin-bottom:var(--p-space-3);"></div>
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
        <a href="<?php echo e(route('portals.staff.appointments')); ?>" class="sidebar-link">
        <i data-lucide="calendar-check-2"></i>
        <span><?php echo e(__('public.portal.nav_appointments', [], $l) ?: 'Appointments'); ?></span>
    </a>
    </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_operations', [], $l) ?: 'Operations'); ?></div>
    </div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\sidebars\resident_doctor.blade.php ENDPATH**/ ?>
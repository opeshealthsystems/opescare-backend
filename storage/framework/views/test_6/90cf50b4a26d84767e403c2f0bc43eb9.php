<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge">
    <i data-lucide="user"></i>
    <?php echo e(__('public.portal.patient_role', [], $l) ?: 'Patient'); ?>

</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="<?php echo e(route('portals.patient')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient') ? 'active' : ''); ?>">
        <i data-lucide="layout-dashboard"></i><span><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Dashboard'); ?></span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_care', [], $l) ?: 'Care'); ?></div>
    <a href="<?php echo e(route('portals.patient.appointments')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.appointments*') ? 'active' : ''); ?>">
        <i data-lucide="calendar-check-2"></i><span><?php echo e(__('public.portal.nav_appointments', [], $l) ?: 'Appointments'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.prescriptions')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.prescriptions*') ? 'active' : ''); ?>">
        <i data-lucide="pill"></i><span><?php echo e(__('public.portal.nav_prescriptions', [], $l) ?: 'Prescriptions'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.family')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.family*') ? 'active' : ''); ?>">
        <i data-lucide="users"></i><span><?php echo e(__('public.portal.nav_family', [], $l) ?: 'Family'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.messages')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.messages*') ? 'active' : ''); ?>">
        <i data-lucide="messages-square"></i><span><?php echo e(__('public.portal.nav_messages', [], $l) ?: 'Messages'); ?></span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_account', [], $l) ?: 'Account'); ?></div>
    <a href="<?php echo e(route('portals.patient.settings')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.settings') ? 'active' : ''); ?>">
        <i data-lucide="settings"></i><span><?php echo e(__('public.portal.nav_settings', [], $l) ?: 'Settings'); ?></span>
    </a>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/partials/sidebars/patient.blade.php ENDPATH**/ ?>
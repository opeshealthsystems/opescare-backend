<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge">
    <i data-lucide="heart-handshake"></i>
    <?php echo e(__('portal.caregiver_role', [], $l) ?: 'Caregiver'); ?>

</div>
<div class="sidebar-spacer"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_my_health', [], $l) ?: 'My Health'); ?></div>
    <a href="<?php echo e(route('portals.patient')); ?>" class="sidebar-link">
        <i data-lucide="id-card"></i>
        <span><?php echo e(__('public.medical_id.health_id', [], $l) ?: 'Health ID'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.appointments')); ?>" class="sidebar-link">
        <i data-lucide="calendar-check-2"></i>
        <span><?php echo e(__('public.portal.nav_appointments', [], $l) ?: 'Appointments'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.family')); ?>" class="sidebar-link">
        <i data-lucide="users"></i>
        <span><?php echo e(__('public.portal.nav_family', [], $l) ?: 'Family'); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_privacy', [], $l) ?: 'Privacy & Access'); ?></div>
        </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_resources', [], $l) ?: 'Resources'); ?></div>
    <a href="<?php echo e(route('public.care-map')); ?>" class="sidebar-link">
        <i data-lucide="map-pin"></i>
        <span><?php echo e(__('public.portal.nav_care_map', [], $l) ?: 'Care Map'); ?></span>
    </a>
    <a href="<?php echo e(route('public.help')); ?>" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span><?php echo e(__('public.portal.nav_help', [], $l) ?: 'Help'); ?></span>
    </a>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\sidebars\caregiver.blade.php ENDPATH**/ ?>
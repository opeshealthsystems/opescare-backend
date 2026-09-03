<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge">
    <i data-lucide="map-pin"></i>
    <?php echo e(__('portal.outreach_mobile_role', [], $l) ?: 'Outreach / Mobile'); ?>

</div>
<div class="sidebar-spacer"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_healthorg_portal', [], $l) ?: 'Health Org Portal'); ?></div>
    </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Overview'); ?></div>
    </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_clinical', [], $l) ?: 'Outreach'); ?></div>
                <a href="<?php echo e(route('public.care-map')); ?>" class="sidebar-link" target="_blank">
        <i data-lucide="map"></i>
        <span><?php echo e(__('public.portal.nav_care_map', [], $l) ?: 'Care Map'); ?></span>
    </a>
    <a href="<?php echo e(route('public.help')); ?>" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span><?php echo e(__('public.portal.nav_help', [], $l) ?: 'Help'); ?></span>
    </a>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\sidebars\outreach_mobile.blade.php ENDPATH**/ ?>
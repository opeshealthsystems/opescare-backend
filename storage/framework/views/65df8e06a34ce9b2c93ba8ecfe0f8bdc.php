<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge" style="background:rgba(109,40,217,.3);border-color:rgba(109,40,217,.5);color:#C4B5FD;">
    <i data-lucide="heart-handshake" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    <?php echo e(__('portal.ngo_health_org_role', [], $l) ?: 'NGO Health Org'); ?>

</div>
<div style="margin-bottom:var(--p-space-3);"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_healthorg_portal', [], $l) ?: 'Health Org Portal'); ?></div>
    </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_administration', [], $l) ?: 'Administration'); ?></div>
                </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_resources', [], $l) ?: 'Resources'); ?></div>
    <a href="<?php echo e(route('public.care-map')); ?>" class="sidebar-link" target="_blank">
        <i data-lucide="map"></i>
        <span><?php echo e(__('public.portal.nav_care_map', [], $l) ?: 'Care Map'); ?></span>
    </a>
    <a href="<?php echo e(route('public.help')); ?>" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span><?php echo e(__('public.portal.nav_help', [], $l) ?: 'Help'); ?></span>
    </a>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\sidebars\ngo_health_org.blade.php ENDPATH**/ ?>
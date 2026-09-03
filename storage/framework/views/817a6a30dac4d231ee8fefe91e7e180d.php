<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge">
    <i data-lucide="alert-triangle"></i>
    <?php echo e(__('portal.emergency_access_reviewer_role', [], $l) ?: 'Emergency Access Reviewer'); ?>

</div>
<div class="sidebar-spacer"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_administration', [], $l) ?: 'Administration'); ?></div>
    <a href="<?php echo e(route('portals.admin')); ?>" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Dashboard'); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_compliance', [], $l) ?: 'Compliance'); ?></div>
                    <a href="<?php echo e(route('public.help')); ?>" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span><?php echo e(__('public.portal.nav_help', [], $l) ?: 'Help'); ?></span>
    </a>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\sidebars\emergency_access_reviewer.blade.php ENDPATH**/ ?>
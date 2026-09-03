<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge">
    <i data-lucide="crown"></i>
    <?php echo e(__('portal.facility_executive_role', [], $l) ?: 'Facility Executive'); ?>

</div>
<div class="sidebar-spacer"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_administration', [], $l) ?: 'Administration'); ?></div>
    <a href="<?php echo e(route('portals.admin')); ?>" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Dashboard'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.facility.team')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.facility.team*') ? 'active' : ''); ?>">
        <i data-lucide="users"></i>
        <span><?php echo e(__('team.page_title', [], $l) ?: 'My Team'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.listing.edit')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.listing.*') ? 'active' : ''); ?>">
        <i data-lucide="map-pin"></i>
        <span><?php echo e(__('caremap_claim.nav_my_listing', [], $l)); ?></span>
    </a>
    <?php if (\Illuminate\Support\Facades\Blade::check('platformadmin')): ?>
        <?php endif; ?>
    <?php if (\Illuminate\Support\Facades\Blade::check('platformadmin')): ?>
        <?php endif; ?>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_compliance', [], $l) ?: 'Compliance'); ?></div>
    <?php if (\Illuminate\Support\Facades\Blade::check('platformadmin')): ?>
        <?php endif; ?>
    <?php if (\Illuminate\Support\Facades\Blade::check('platformadmin')): ?>
        <?php endif; ?>
    <a href="<?php echo e(route('public.help')); ?>" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span><?php echo e(__('public.portal.nav_help', [], $l) ?: 'Help'); ?></span>
    </a>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\sidebars\facility_executive.blade.php ENDPATH**/ ?>
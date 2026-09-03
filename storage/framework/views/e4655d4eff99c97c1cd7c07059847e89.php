<?php $l = app()->getLocale(); ?>


<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_platform', [], $l) ?: 'Platform'); ?></div>
    <a href="<?php echo e(route('portals.admin')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin') && !request()->routeIs('portals.admin.*') ? 'active' : ''); ?>">
        <i data-lucide="layout-dashboard"></i><span><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Dashboard'); ?></span>
    </a>
</div>


<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_organizations', [], $l) ?: 'Organizations'); ?></div>
    <a href="<?php echo e(route('portals.admin.facilities.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.facilities.*') ? 'active' : ''); ?>">
        <i data-lucide="hospital"></i><span><?php echo e(__('public.portal.nav_facilities', [], $l) ?: 'Facilities'); ?></span>
    </a>
    <?php if (\Illuminate\Support\Facades\Blade::check('platformadmin')): ?>
    <a href="<?php echo e(route('admin.care-map.review')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.care-map.review*') ? 'active' : ''); ?>">
        <i data-lucide="clipboard-check"></i><span><?php echo e(__('caremap_claim.nav_directory_review', [], $l)); ?></span>
    </a>
    <?php endif; ?>
</div>


<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_people', [], $l) ?: 'People'); ?></div>
    <a href="<?php echo e(route('portals.admin.users.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.users.*') ? 'active' : ''); ?>">
        <i data-lucide="users"></i><span><?php echo e(__('public.portal.nav_users', [], $l) ?: 'Users'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.roles.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.roles.*') ? 'active' : ''); ?>">
        <i data-lucide="shield-check"></i><span><?php echo e(__('public.portal.nav_roles', [], $l) ?: 'Roles'); ?></span>
    </a>
</div>


<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_clinical', [], $l) ?: 'Clinical'); ?></div>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'clinical_decision_support')): ?>
    <?php endif; ?>
    <a href="<?php echo e(route('portals.admin.certifications.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.certifications.*') ? 'active' : ''); ?>">
        <i data-lucide="award"></i><span><?php echo e(__('public.portal.nav_certifications', [], $l) ?: 'Certifications'); ?></span>
    </a>
</div>


<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_integration', [], $l) ?: 'Integration'); ?></div>
    <a href="<?php echo e(route('portals.admin.developer.accounts')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.developer.*') ? 'active' : ''); ?>">
        <i data-lucide="code-2"></i><span><?php echo e(__('public.portal.nav_developers', [], $l) ?: 'Developers'); ?></span>
    </a>
</div>


<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_compliance', [], $l) ?: 'Compliance'); ?></div>
</div>


<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_system', [], $l) ?: 'System'); ?></div>
    <a href="<?php echo e(route('portals.admin.cc')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.cc*') ? 'active' : ''); ?>">
        <i data-lucide="server"></i><span><?php echo e(__('public.portal.nav_control_center', [], $l) ?: 'Control Center'); ?></span>
    </a>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\sidebars\super_admin.blade.php ENDPATH**/ ?>
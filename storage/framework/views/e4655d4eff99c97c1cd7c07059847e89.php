<?php $__env->startPush('head'); ?>
<style>
.nav-section-label{font-size:.65rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--p-text-muted,rgba(255,255,255,.45));padding:.75rem 1rem .25rem;margin-top:.25rem}
.nav-section-label:first-child{margin-top:0}
</style>
<?php $__env->stopPush(); ?>
<?php $l = app()->getLocale(); ?>

<nav class="sidebar-nav">

  
  <div class="nav-section-label"><?php echo e(__('public.portal.nav_platform', [], $l) ?: 'Platform'); ?></div>
  <a href="<?php echo e(route('portals.admin')); ?>" class="nav-item <?php echo e(request()->routeIs('portals.admin') && !request()->routeIs('portals.admin.*') ? 'active' : ''); ?>">
    <i data-lucide="layout-dashboard"></i><span><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Dashboard'); ?></span>
  </a>
          
  
  <div class="nav-section-label"><?php echo e(__('public.portal.nav_organizations', [], $l) ?: 'Organizations'); ?></div>
    <a href="<?php echo e(route('portals.admin.facilities.index')); ?>" class="nav-item <?php echo e(request()->routeIs('portals.admin.facilities.*') ? 'active' : ''); ?>">
    <i data-lucide="hospital"></i><span><?php echo e(__('public.portal.nav_facilities', [], $l) ?: 'Facilities'); ?></span>
  </a>
  <?php if (\Illuminate\Support\Facades\Blade::check('platformadmin')): ?>
  <a href="<?php echo e(route('admin.care-map.review')); ?>" class="nav-item <?php echo e(request()->routeIs('admin.care-map.review*') ? 'active' : ''); ?>">
    <i data-lucide="clipboard-check"></i><span><?php echo e(__('caremap_claim.nav_directory_review', [], $l)); ?></span>
  </a>
  <?php endif; ?>
  
  
  <div class="nav-section-label"><?php echo e(__('public.portal.nav_people', [], $l) ?: 'People'); ?></div>
  <a href="<?php echo e(route('portals.admin.users.index')); ?>" class="nav-item <?php echo e(request()->routeIs('portals.admin.users.*') ? 'active' : ''); ?>">
    <i data-lucide="users"></i><span><?php echo e(__('public.portal.nav_users', [], $l) ?: 'Users'); ?></span>
  </a>
  <a href="<?php echo e(route('portals.admin.roles.index')); ?>" class="nav-item <?php echo e(request()->routeIs('portals.admin.roles.*') ? 'active' : ''); ?>">
    <i data-lucide="shield-check"></i><span><?php echo e(__('public.portal.nav_roles', [], $l) ?: 'Roles'); ?></span>
  </a>
    
  
  <div class="nav-section-label"><?php echo e(__('public.portal.nav_clinical', [], $l) ?: 'Clinical'); ?></div>
  <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'clinical_decision_support')): ?>
  <?php endif; ?>
    <a href="<?php echo e(route('portals.admin.certifications.index')); ?>" class="nav-item <?php echo e(request()->routeIs('portals.admin.certifications.*') ? 'active' : ''); ?>">
    <i data-lucide="award"></i><span><?php echo e(__('public.portal.nav_certifications', [], $l) ?: 'Certifications'); ?></span>
  </a>

  
  <div class="nav-section-label"><?php echo e(__('public.portal.nav_integration', [], $l) ?: 'Integration'); ?></div>
      <a href="<?php echo e(route('portals.admin.developer.accounts')); ?>" class="nav-item <?php echo e(request()->routeIs('portals.admin.developer.*') ? 'active' : ''); ?>">
    <i data-lucide="code-2"></i><span><?php echo e(__('public.portal.nav_developers', [], $l) ?: 'Developers'); ?></span>
  </a>

  
  <div class="nav-section-label"><?php echo e(__('public.portal.nav_compliance', [], $l) ?: 'Compliance'); ?></div>
      
  
  <div class="nav-section-label"><?php echo e(__('public.portal.nav_system', [], $l) ?: 'System'); ?></div>
  <a href="<?php echo e(route('portals.admin.cc')); ?>" class="nav-item <?php echo e(request()->routeIs('portals.admin.cc*') ? 'active' : ''); ?>">
    <i data-lucide="server"></i><span><?php echo e(__('public.portal.nav_control_center', [], $l) ?: 'Control Center'); ?></span>
  </a>
  
</nav>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\sidebars\super_admin.blade.php ENDPATH**/ ?>
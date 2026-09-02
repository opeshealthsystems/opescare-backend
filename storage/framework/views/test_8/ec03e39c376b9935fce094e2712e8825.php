<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge" style="background:rgba(245,158,11,.2);border-color:rgba(245,158,11,.4);color:#FCD34D;">
    <i data-lucide="code-2" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    <?php echo e(__('portal.developer_role', [], $l) ?: 'Developer'); ?>

</div>
<div style="margin-bottom:var(--p-space-3);"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Overview'); ?></div>
    <a href="<?php echo e(route('portals.admin')); ?>" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Dashboard'); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_developer_accounts', [], $l) ?: 'Developer'); ?></div>
    <?php if (\Illuminate\Support\Facades\Blade::check('platformadmin')): ?>
        <?php endif; ?>
    <?php if (\Illuminate\Support\Facades\Blade::check('platformadmin')): ?>
        <?php endif; ?>
    <?php if (\Illuminate\Support\Facades\Blade::check('platformadmin')): ?>
        <?php endif; ?>
    <?php if (\Illuminate\Support\Facades\Blade::check('platformadmin')): ?>
        <?php endif; ?>
    <a href="<?php echo e(route('public.help')); ?>" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span><?php echo e(__('public.portal.nav_help', [], $l) ?: 'Help'); ?></span>
    </a>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/partials/sidebars/developer.blade.php ENDPATH**/ ?>
<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge">
    <i data-lucide="code-2"></i>
    <?php echo e(__('portal.developer_role', [], $l) ?: 'Developer'); ?>

</div>
<div class="sidebar-spacer"></div>
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
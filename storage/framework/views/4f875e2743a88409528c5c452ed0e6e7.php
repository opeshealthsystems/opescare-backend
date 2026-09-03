<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge">
    <i data-lucide="shield-plus"></i>
    <?php echo e(__('public.portal.insurance_admin_role', [], $l) ?: 'Insurance Admin'); ?>

</div>
<div class="sidebar-spacer"></div>

<?php if (\Illuminate\Support\Facades\Blade::check('feature', 'insurance')): ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Overview'); ?></div>
    </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_operations', [], $l) ?: 'Insurance'); ?></div>
                </div>
<?php endif; ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_help', [], $l) ?: 'Help'); ?></div>
    <a href="<?php echo e(route('public.help')); ?>" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span><?php echo e(__('public.portal.nav_help', [], $l) ?: 'Help'); ?></span>
    </a>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\sidebars\insurance_admin.blade.php ENDPATH**/ ?>
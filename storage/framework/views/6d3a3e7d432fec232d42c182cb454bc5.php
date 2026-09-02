<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge" style="background:rgba(55,48,163,.3);border-color:rgba(55,48,163,.5);color:#A5B4FC;">
    <i data-lucide="shield-check" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    <?php echo e(__('public.portal.insurance_reviewer_role', [], $l) ?: 'Insurance Reviewer'); ?>

</div>
<div style="margin-bottom:var(--p-space-3);"></div>

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
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\sidebars\insurance_reviewer.blade.php ENDPATH**/ ?>
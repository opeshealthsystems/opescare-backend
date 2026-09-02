<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge" style="background:rgba(37,99,235,.2);border-color:rgba(37,99,235,.4);color:#93C5FD;">
    <i data-lucide="package-2" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    <?php echo e(__('portal.medicine_stock_role', [], $l) ?: 'Medicine Stock'); ?>

</div>
<div style="margin-bottom:var(--p-space-3);"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_pharmacy_portal', [], $l) ?: 'Pharmacy Portal'); ?></div>
    <a href="<?php echo e(route('portals.pharmacy.dashboard')); ?>" class="sidebar-link" style="background:rgba(16,185,129,.08);border-left:3px solid #10b981;">
        <i data-lucide="pill" style="color:#10b981;"></i>
        <span style="font-weight:700;"><?php echo e(__('public.portal.nav_pharmacy_portal_my', [], $l) ?: 'My Pharmacy Portal'); ?></span>
    </a>
</div>
<?php if (\Illuminate\Support\Facades\Blade::check('feature', 'inventory_ops')): ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Overview'); ?></div>
    </div>
<?php endif; ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_pharmacy', [], $l) ?: 'Pharmacy'); ?></div>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'inventory_ops')): ?>
            <?php endif; ?>
    </div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\sidebars\medicine_stock.blade.php ENDPATH**/ ?>
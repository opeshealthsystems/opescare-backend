<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge" style="background:rgba(15,76,129,.3);border-color:rgba(15,76,129,.5);color:#9DC3E6;">
    <i data-lucide="building-2" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    <?php echo e(__('portal.facility_admin_role', [], $l) ?: 'Facility Admin'); ?>

</div>
<div style="margin-bottom:var(--p-space-3);"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_administration', [], $l) ?: 'My Facility'); ?></div>
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
    <a href="<?php echo e(route('select-facility')); ?>" class="sidebar-link">
        <i data-lucide="repeat"></i>
        <span><?php echo e(__('public.portal.nav_switch_facility', [], $l) ?: 'Switch Facility'); ?></span>
    </a>
</div>

<?php if (\Illuminate\Support\Facades\Blade::check('platformadmin')): ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_people', [], $l) ?: 'People'); ?></div>
        </div>
<?php endif; ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_operations', [], $l) ?: 'Operations'); ?></div>
    <a href="<?php echo e(route('portals.staff.appointments')); ?>" class="sidebar-link">
        <i data-lucide="calendar-check-2"></i>
        <span><?php echo e(__('public.portal.nav_appointments', [], $l) ?: 'Appointments'); ?></span>
    </a>
        </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_support', [], $l) ?: 'Support'); ?></div>
    <a href="<?php echo e(route('public.help')); ?>" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span><?php echo e(__('public.portal.nav_help', [], $l) ?: 'Help'); ?></span>
    </a>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/partials/sidebars/facility_admin.blade.php ENDPATH**/ ?>
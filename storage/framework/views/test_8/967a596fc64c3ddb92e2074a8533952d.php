
<?php $l = app()->getLocale(); ?>


<div class="sidebar-nav-section">
    <a href="<?php echo e(route('portals.admin')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin') && ! request()->routeIs('portals.admin.*') ? 'active' : ''); ?>">
        <i data-lucide="layout-dashboard"></i><span><?php echo e(__('public.portal.nav_dashboard', [], $l) ?: 'Dashboard'); ?></span>
    </a>
</div>


<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_directory', [], $l) ?: 'Directory'); ?></div>
    <a href="<?php echo e(route('portals.admin.facilities.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.facilities.*') || request()->routeIs('admin.facilities.*') ? 'active' : ''); ?>">
        <i data-lucide="hospital"></i><span><?php echo e(__('public.portal.nav_facilities', [], $l) ?: 'Facilities'); ?></span>
    </a>
    <a href="<?php echo e(route('admin.care-map.review')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.care-map.review*') ? 'active' : ''); ?>">
        <i data-lucide="clipboard-check"></i><span><?php echo e(__('caremap_claim.nav_directory_review', [], $l) ?: 'Directory Review'); ?></span>
        <?php $directoryPending = rescue(fn () => \App\Models\FacilityClaim::query()->open()->count()
            + \App\Models\FacilityImportReview::where('status', \App\Enums\FacilityImportReviewStatus::Pending->value)->count(), 0, false); ?>
        <?php if($directoryPending > 0): ?><span class="sidebar-badge sidebar-badge--warning"><?php echo e($directoryPending); ?></span><?php endif; ?>
    </a>
</div>


<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_people', [], $l) ?: 'People'); ?></div>
    <a href="<?php echo e(route('portals.admin.users.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.users.*') || request()->routeIs('admin.users.*') ? 'active' : ''); ?>">
        <i data-lucide="users"></i><span><?php echo e(__('public.portal.nav_users', [], $l) ?: 'Users'); ?></span>
        <?php $pendingUsers = rescue(fn () => \App\Models\User::where('status', 'pending')->count(), 0, false); ?>
        <?php if($pendingUsers > 0): ?><span class="sidebar-badge sidebar-badge--warning"><?php echo e($pendingUsers); ?></span><?php endif; ?>
    </a>
    <a href="<?php echo e(route('portals.admin.roles.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.roles.*') || request()->routeIs('admin.roles.*') ? 'active' : ''); ?>">
        <i data-lucide="shield-check"></i><span><?php echo e(__('public.portal.nav_roles', [], $l) ?: 'Roles'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.verifications.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.verifications.*') ? 'active' : ''); ?>">
        <i data-lucide="badge-check"></i><span><?php echo e(__('professional.nav_verifications', [], $l) ?: 'Verifications'); ?></span>
        <?php $pendingCreds = rescue(fn () => \App\Models\ProviderCredential::whereNull('verified_at')->where('status', '!=', 'suspended')->count(), 0, false); ?>
        <?php if($pendingCreds > 0): ?><span class="sidebar-badge sidebar-badge--warning"><?php echo e($pendingCreds); ?></span><?php endif; ?>
    </a>
</div>


<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_partners', [], $l) ?: 'Partners'); ?></div>
    <a href="<?php echo e(route('portals.admin.developer.accounts')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.developer.accounts*') ? 'active' : ''); ?>">
        <i data-lucide="code-2"></i><span><?php echo e(__('public.adm_cc_sidebar_developer_accounts', [], $l) ?: 'Developer Accounts'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.developer.production_requests')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.developer.production_requests*') ? 'active' : ''); ?>">
        <i data-lucide="rocket"></i><span><?php echo e(__('public.adm_cc_sidebar_production_requests', [], $l) ?: 'Production Requests'); ?></span>
        <?php $pendingProd = rescue(fn () => \Illuminate\Support\Facades\DB::table('production_access_requests')->where('status', 'pending')->count(), 0, false); ?>
        <?php if($pendingProd > 0): ?><span class="sidebar-badge sidebar-badge--warning"><?php echo e($pendingProd); ?></span><?php endif; ?>
    </a>
    <a href="<?php echo e(route('portals.admin.certifications.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.certifications.*') ? 'active' : ''); ?>">
        <i data-lucide="badge-check"></i><span><?php echo e(__('public.adm_cc_sidebar_certifications', [], $l) ?: 'Certifications'); ?></span>
    </a>
</div>


<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_operations', [], $l) ?: 'Operations'); ?></div>
    <a href="<?php echo e(route('portals.admin.appointments.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.appointments.*') ? 'active' : ''); ?>">
        <i data-lucide="calendar"></i><span><?php echo e(__('public.adm_cc_sidebar_appointments', [], $l) ?: 'Appointments'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.leads')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.leads*') ? 'active' : ''); ?>">
        <i data-lucide="user-plus"></i><span><?php echo e(__('leads.admin.page_title', [], $l) ?: 'Leads'); ?></span>
    </a>
</div>


<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_control_center', [], $l) ?: 'Control Center'); ?></div>
    <a href="<?php echo e(route('portals.admin.cc')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.cc') && ! request()->routeIs('portals.admin.cc.*') ? 'active' : ''); ?>">
        <i data-lucide="layout-grid"></i><span><?php echo e(__('public.adm_cc_sidebar_section_overview', [], $l) ?: 'Overview'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.cc.settings')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.cc.settings*') ? 'active' : ''); ?>">
        <i data-lucide="sliders-horizontal"></i><span><?php echo e(__('public.adm_cc_sidebar_platform_settings', [], $l) ?: 'Platform Settings'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.cc.feature_flags')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.cc.feature_flags*') ? 'active' : ''); ?>">
        <i data-lucide="toggle-right"></i><span><?php echo e(__('public.adm_cc_sidebar_feature_flags', [], $l) ?: 'Feature Flags'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.cc.modules')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.cc.modules*') ? 'active' : ''); ?>">
        <i data-lucide="puzzle"></i><span><?php echo e(__('public.adm_cc_sidebar_module_toggles', [], $l) ?: 'Module Toggles'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.cc.maintenance')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.cc.maintenance*') ? 'active' : ''); ?>">
        <i data-lucide="wrench"></i><span><?php echo e(__('public.adm_cc_sidebar_maintenance', [], $l) ?: 'Maintenance'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.cc.health')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.cc.health*') ? 'active' : ''); ?>">
        <i data-lucide="activity"></i><span><?php echo e(__('public.adm_cc_sidebar_system_health', [], $l) ?: 'System Health'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.cc.audit')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.cc.audit*') ? 'active' : ''); ?>">
        <i data-lucide="scroll-text"></i><span><?php echo e(__('public.adm_cc_sidebar_admin_action_log', [], $l) ?: 'Audit Log'); ?></span>
    </a>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/admin/_admin_nav.blade.php ENDPATH**/ ?>
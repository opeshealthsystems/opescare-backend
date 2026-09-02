<?php $__env->startSection('sidebar_role_badge'); ?>
<div class="sidebar-role-badge sidebar-role-badge--primary">
    <i data-lucide="shield-check"></i>
    <?php echo e(__('public.adm_cc_sidebar_super_admin')); ?>

</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('sidebar_user_role', __('public.adm_cc_sidebar_super_admin')); ?>

<?php $__env->startSection('sidebar_nav'); ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.adm_cc_sidebar_section_overview')); ?></div>
    <a href="<?php echo e(route('portals.admin')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin') && !request()->routeIs('portals.admin.cc*') ? 'active' : ''); ?>">
        <i data-lucide="layout-dashboard"></i><span><?php echo e(__('public.adm_cc_sidebar_governance')); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.cc')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.cc') ? 'active' : ''); ?>">
        <i data-lucide="settings-2"></i><span><?php echo e(__('public.adm_cc_sidebar_control_center')); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.cc.health')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.cc.health') ? 'active' : ''); ?>">
        <i data-lucide="activity"></i><span><?php echo e(__('public.adm_cc_sidebar_system_health')); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.adm_cc_sidebar_section_platform')); ?></div>
    <a href="<?php echo e(route('portals.admin.cc.settings')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.cc.settings') ? 'active' : ''); ?>">
        <i data-lucide="sliders-horizontal"></i><span><?php echo e(__('public.adm_cc_sidebar_platform_settings')); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.cc.feature_flags')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.cc.feature_flags') ? 'active' : ''); ?>">
        <i data-lucide="toggle-right"></i><span><?php echo e(__('public.adm_cc_sidebar_feature_flags')); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.cc.modules')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.cc.modules') ? 'active' : ''); ?>">
        <i data-lucide="puzzle"></i><span><?php echo e(__('public.adm_cc_sidebar_module_toggles')); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.cc.maintenance')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.cc.maintenance') ? 'active' : ''); ?>">
        <i data-lucide="wrench"></i><span><?php echo e(__('public.adm_cc_sidebar_maintenance')); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.adm_cc_sidebar_section_audit')); ?></div>
    <a href="<?php echo e(route('portals.admin.cc.audit')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.cc.audit') ? 'active' : ''); ?>">
        <i data-lucide="scroll-text"></i><span><?php echo e(__('public.adm_cc_sidebar_admin_action_log')); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.go-live')); ?>" class="sidebar-link">
        <i data-lucide="rocket"></i><span><?php echo e(__('public.adm_cc_sidebar_facility_golive')); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.security')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.security*') ? 'active' : ''); ?>">
        <i data-lucide="shield-alert"></i><span><?php echo e(__('public.adm_cc_sidebar_security_ops')); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.adm_cc_sidebar_section_billing')); ?></div>
    <a href="<?php echo e(route('portals.admin.subscription')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.subscription') || request()->routeIs('portals.admin.subscription.detail') ? 'active' : ''); ?>">
        <i data-lucide="credit-card"></i><span><?php echo e(__('public.adm_cc_sidebar_subscriptions')); ?></span>
        <?php $pastDue = \App\Models\OrganizationSubscription::where('status','past_due')->count(); ?>
        <?php if($pastDue > 0): ?>
            <span class="sidebar-badge sidebar-badge--warning"><?php echo e($pastDue); ?></span>
        <?php endif; ?>
    </a>
    <a href="<?php echo e(route('portals.admin.subscription.plans')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.subscription.plans*') ? 'active' : ''); ?>">
        <i data-lucide="layers"></i><span><?php echo e(__('public.adm_cc_sidebar_plans')); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.subscription.invoices')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.subscription.invoices*') ? 'active' : ''); ?>">
        <i data-lucide="file-text"></i><span><?php echo e(__('public.adm_cc_sidebar_invoices')); ?></span>
        <?php $overdueInv = \App\Models\SubscriptionInvoice::where('status','sent')->where('due_date','<',now())->count(); ?>
        <?php if($overdueInv > 0): ?>
            <span class="sidebar-badge sidebar-badge--danger"><?php echo e($overdueInv); ?></span>
        <?php endif; ?>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.adm_cc_sidebar_section_integrations')); ?></div>
    <a href="<?php echo e(route('portals.admin.connect')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.connect*') ? 'active' : ''); ?>">
        <i data-lucide="plug-zap"></i><span><?php echo e(__('public.adm_cc_sidebar_connect_suite')); ?></span>
        <?php $pendingConnect = \App\Models\IntegrationClient::where('status','pending')->count(); ?>
        <?php if($pendingConnect > 0): ?>
            <span class="sidebar-badge sidebar-badge--warning"><?php echo e($pendingConnect); ?></span>
        <?php endif; ?>
    </a>
    <a href="<?php echo e(route('portals.admin.bridge')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.bridge*') ? 'active' : ''); ?>">
        <i data-lucide="cable"></i><span><?php echo e(__('public.adm_cc_sidebar_bridge_agents')); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.adm_cc_sidebar_section_god_mode')); ?></div>
    <a href="<?php echo e(route('admin.users.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.users*') ? 'active' : ''); ?>">
        <i data-lucide="users"></i><span><?php echo e(__('public.adm_cc_sidebar_all_users')); ?></span>
        <?php $pendingUsers = \App\Models\User::where('status','pending')->count(); ?>
        <?php if($pendingUsers > 0): ?><span class="sidebar-badge sidebar-badge--warning"><?php echo e($pendingUsers); ?></span><?php endif; ?>
    </a>
    <a href="<?php echo e(route('admin.facilities.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.facilities*') ? 'active' : ''); ?>">
        <i data-lucide="building-2"></i><span><?php echo e(__('public.adm_cc_sidebar_all_facilities')); ?></span>
    </a>
    <a href="<?php echo e(route('admin.care-map.review')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.care-map.review*') ? 'active' : ''); ?>">
        <i data-lucide="clipboard-check"></i><span><?php echo e(__('caremap_claim.nav_directory_review')); ?></span>
        <?php
            $directoryPending = \App\Models\FacilityClaim::query()->open()->count()
                + \App\Models\FacilityImportReview::where('status', \App\Enums\FacilityImportReviewStatus::Pending->value)->count();
        ?>
        <?php if($directoryPending > 0): ?><span class="sidebar-badge sidebar-badge--warning"><?php echo e($directoryPending); ?></span><?php endif; ?>
    </a>
    <a href="<?php echo e(route('admin.patients.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.patients*') ? 'active' : ''); ?>">
        <i data-lucide="heart-pulse"></i><span><?php echo e(__('public.adm_cc_sidebar_all_patients')); ?></span>
    </a>
    <a href="<?php echo e(route('admin.staff.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.staff*') ? 'active' : ''); ?>">
        <i data-lucide="user-check"></i><span><?php echo e(__('public.adm_cc_sidebar_all_staff')); ?></span>
    </a>
    <a href="<?php echo e(route('admin.organizations.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.organizations*') ? 'active' : ''); ?>">
        <i data-lucide="network"></i><span><?php echo e(__('public.adm_cc_sidebar_organizations')); ?></span>
    </a>
    <a href="<?php echo e(route('admin.roles.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.roles*') ? 'active' : ''); ?>">
        <i data-lucide="shield"></i><span><?php echo e(__('public.adm_cc_sidebar_roles_rbac')); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.appointments.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.appointments*') ? 'active' : ''); ?>">
        <i data-lucide="calendar"></i><span><?php echo e(__('public.adm_cc_sidebar_appointments')); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.support.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.support*') ? 'active' : ''); ?>">
        <i data-lucide="headphones"></i><span><?php echo e(__('public.adm_cc_sidebar_support_tickets')); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.leads')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.leads*') ? 'active' : ''); ?>">
        <i data-lucide="user-plus"></i><span><?php echo e(__('leads.admin.page_title')); ?></span>
    </a>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'clinical_decision_support')): ?>
    <?php endif; ?>
    <a href="<?php echo e(route('portals.admin.reports.minsante-monthly')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.reports*') ? 'active' : ''); ?>">
        <i data-lucide="file-bar-chart"></i><span><?php echo e(__('public.adm_cc_sidebar_reports')); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.adm_cc_sidebar_section_analytics')); ?></div>
    <a href="<?php echo e(route('portals.admin.kpi.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.kpi*') ? 'active' : ''); ?>">
        <i data-lucide="bar-chart-3"></i><span><?php echo e(__('public.admin_governance.nav_kpi_dashboard', [], app()->getLocale()) ?: 'KPI Dashboard'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.kpi.trend')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.kpi.trend') ? 'active' : ''); ?>">
        <i data-lucide="trending-up"></i><span><?php echo e(__('public.admin_governance.nav_kpi_trends', [], app()->getLocale()) ?: 'KPI Trends'); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.adm_cc_sidebar_section_compliance')); ?></div>
    <a href="<?php echo e(route('portals.admin.legal')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.legal') ? 'active' : ''); ?>">
        <i data-lucide="scale"></i><span><?php echo e(__('public.adm_cc_sidebar_legal_docs')); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.legal.closures')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.legal.closures*') ? 'active' : ''); ?>">
        <i data-lucide="file-x-2"></i><span><?php echo e(__('public.adm_cc_sidebar_patient_rights')); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.legal.complaints')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.legal.complaints*') ? 'active' : ''); ?>">
        <i data-lucide="message-circle-warning"></i><span><?php echo e(__('public.adm_cc_sidebar_privacy_complaints')); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.legal.minor_transitions')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.legal.minor_transitions') ? 'active' : ''); ?>">
        <i data-lucide="user-cog"></i><span><?php echo e(__('public.adm_cc_sidebar_minor_transitions')); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.certifications.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.certifications*') ? 'active' : ''); ?>">
        <i data-lucide="badge-check"></i><span><?php echo e(__('public.adm_cc_sidebar_certifications')); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.code_mappings.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.code_mappings*') ? 'active' : ''); ?>">
        <i data-lucide="code-2"></i><span><?php echo e(__('public.adm_cc_sidebar_code_mappings')); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.adm_cc_sidebar_section_onboarding')); ?></div>
    <a href="<?php echo e(route('portals.admin.onboarding')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.onboarding*') ? 'active' : ''); ?>">
        <i data-lucide="clipboard-list"></i><span><?php echo e(__('public.adm_cc_sidebar_facility_onboarding')); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.developer.accounts')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.developer.accounts*') ? 'active' : ''); ?>">
        <i data-lucide="code"></i><span><?php echo e(__('public.adm_cc_sidebar_developer_accounts')); ?></span>
    </a>
    <a href="<?php echo e(route('portals.admin.developer.production_requests')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.admin.developer.production_requests*') ? 'active' : ''); ?>">
        <i data-lucide="rocket"></i><span><?php echo e(__('public.adm_cc_sidebar_production_requests')); ?></span>
        <?php $pendingProd = \Illuminate\Support\Facades\DB::table('production_access_requests')->where('status','pending')->count(); ?>
        <?php if($pendingProd > 0): ?><span class="sidebar-badge sidebar-badge--warning"><?php echo e($pendingProd); ?></span><?php endif; ?>
    </a>
</div>
<?php $__env->stopSection(); ?>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/admin/control_center/_sidebar.blade.php ENDPATH**/ ?>
<?php $l = app()->getLocale(); ?>
<div class="sidebar-role-badge" style="background:rgba(15,118,110,.3);border-color:rgba(15,118,110,.5);color:#5EEAD4;">
    <i data-lucide="user" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    <?php echo e(__('public.portal.patient_role', [], $l) ?: 'Patient'); ?>

</div>
<div style="margin-bottom:var(--p-space-3);"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_my_health', [], $l) ?: 'My Health'); ?></div>
    <a href="<?php echo e(route('portals.patient')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient') ? 'active' : ''); ?>">
        <i data-lucide="id-card"></i>
        <span><?php echo e(__('public.medical_id.health_id', [], $l) ?: 'My Health ID'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.appointments')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.appointments') ? 'active' : ''); ?>">
        <i data-lucide="calendar-check-2"></i>
        <span><?php echo e(__('public.portal.nav_appointments', [], $l) ?: 'Appointments'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.labs')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.labs') ? 'active' : ''); ?>">
        <i data-lucide="flask-conical"></i>
        <span><?php echo e(__('public.portal.nav_labs', [], $l) ?: 'Lab Results'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.prescriptions')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.prescriptions') ? 'active' : ''); ?>">
        <i data-lucide="pill"></i>
        <span><?php echo e(__('public.portal.nav_prescriptions', [], $l) ?: 'Prescriptions'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.documents')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.documents') ? 'active' : ''); ?>">
        <i data-lucide="file-text"></i>
        <span><?php echo e(__('public.portal.nav_documents', [], $l) ?: 'Documents'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.timeline')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.timeline') ? 'active' : ''); ?>">
        <i data-lucide="history"></i>
        <span><?php echo e(__('public.pat_nav_timeline', [], $l) ?: 'Health Timeline'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.surveys')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.surveys') ? 'active' : ''); ?>">
        <i data-lucide="clipboard-check"></i>
        <span><?php echo e(__('public.pat_nav_surveys', [], $l) ?: 'Surveys'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.messages')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.messages*') ? 'active' : ''); ?>">
        <i data-lucide="messages-square"></i>
        <span><?php echo e(__('messaging.inbox_title', [], $l) ?: 'Messages'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.notifications')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.notifications*') ? 'active' : ''); ?>">
        <i data-lucide="bell"></i>
        <span><?php echo e(__('notifications.nav', [], $l) ?: 'Notifications'); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_clinical', [], $l) ?: 'Clinical'); ?></div>
    <a href="<?php echo e(route('portals.patient.allergies')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.allergies') ? 'active' : ''); ?>">
        <i data-lucide="zap"></i>
        <span><?php echo e(__('public.portal.nav_allergies', [], $l) ?: 'Allergies'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.clinical')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.clinical') ? 'active' : ''); ?>">
        <i data-lucide="stethoscope"></i>
        <span><?php echo e(__('public.portal.nav_conditions', [], $l) ?: 'Conditions'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.immunizations')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.immunizations') ? 'active' : ''); ?>">
        <i data-lucide="syringe"></i>
        <span><?php echo e(__('public.portal.nav_immunizations', [], $l) ?: 'Immunizations'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.care-plans')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.care-plans') ? 'active' : ''); ?>">
        <i data-lucide="clipboard-list"></i>
        <span><?php echo e(__('public.pat_nav_care_plans', [], $l) ?: 'Care Plans'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.referrals')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.referrals') ? 'active' : ''); ?>">
        <i data-lucide="share-2"></i>
        <span><?php echo e(__('public.pat_nav_referrals', [], $l) ?: 'Referrals'); ?></span>
    </a>
</div>
<?php if (\Illuminate\Support\Facades\Blade::check('feature', 'insurance_marketplace')): ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_insurance_section', [], $l) ?: 'Insurance'); ?></div>
    <a href="<?php echo e(route('portals.patient.insurance')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.insurance*') ? 'active' : ''); ?>">
        <i data-lucide="shield-check"></i>
        <span><?php echo e(__('public.portal.nav_health_insurance', [], $l) ?: 'Health Insurance'); ?></span>
    </a>
</div>
<?php endif; ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_billing', [], $l) ?: 'Billing'); ?></div>
    <a href="<?php echo e(route('portals.patient.subscription')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.subscription') ? 'active' : ''); ?>">
        <i data-lucide="credit-card"></i>
        <span><?php echo e(__('public.portal.nav_subscription', [], $l) ?: 'Subscription'); ?></span>
    </a>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'billing')): ?>
    <a href="<?php echo e(route('portals.patient.billing')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.billing*') ? 'active' : ''); ?>">
        <i data-lucide="receipt"></i>
        <span><?php echo e(__('billing.nav', [], $l) ?: 'Billing'); ?></span>
    </a>
    <?php endif; ?>
    <a href="<?php echo e(route('portals.patient.refer')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.refer') ? 'active' : ''); ?>">
        <i data-lucide="gift"></i>
        <span><?php echo e(__('referral.nav_label', [], $l) ?: 'Refer & Earn'); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_privacy', [], $l) ?: 'Privacy & Access'); ?></div>
    <a href="<?php echo e(route('portals.patient.consent')); ?>" class="sidebar-link">
        <i data-lucide="shield-check"></i>
        <span><?php echo e(__('public.portal.nav_consent', [], $l) ?: 'Consent Requests'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.logs')); ?>" class="sidebar-link">
        <i data-lucide="history"></i>
        <span><?php echo e(__('public.portal.nav_access_logs', [], $l) ?: 'Access Logs'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.profile')); ?>" class="sidebar-link">
        <i data-lucide="user-cog"></i>
        <span><?php echo e(__('public.portal.nav_profile', [], $l) ?: 'My Profile'); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.pat_nav_account', [], $l) ?: 'Account'); ?></div>
    <a href="<?php echo e(route('portals.patient.settings')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portals.patient.settings') ? 'active' : ''); ?>">
        <i data-lucide="settings"></i>
        <span><?php echo e(__('public.pat_nav_settings', [], $l) ?: 'Settings'); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_resources', [], $l) ?: 'Resources'); ?></div>
    <a href="<?php echo e(route('public.care-map')); ?>" class="sidebar-link">
        <i data-lucide="map-pin"></i>
        <span><?php echo e(__('public.portal.nav_care_map', [], $l) ?: 'Care Map'); ?></span>
    </a>
    <a href="<?php echo e(route('public.help')); ?>" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span><?php echo e(__('public.portal.nav_help', [], $l) ?: 'Help'); ?></span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.portal.nav_family', [], $l) ?: 'My Family'); ?></div>
    <a href="<?php echo e(route('portals.patient.family')); ?>"
       class="sidebar-link <?php echo e(request()->routeIs('portals.patient.family*') ? 'active' : ''); ?>">
        <i data-lucide="users"></i>
        <span><?php echo e(__('public.portal.nav_family_dashboard', [], $l) ?: 'Family Dashboard'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.family.add')); ?>"
       class="sidebar-link <?php echo e(request()->routeIs('portals.patient.family.add') ? 'active' : ''); ?>">
        <i data-lucide="user-plus"></i>
        <span><?php echo e(__('public.portal.nav_family_add', [], $l) ?: 'Add Dependent'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.family.invite')); ?>"
       class="sidebar-link <?php echo e(request()->routeIs('portals.patient.family.invite') ? 'active' : ''); ?>">
        <i data-lucide="mail"></i>
        <span><?php echo e(__('public.portal.nav_family_invite', [], $l) ?: 'Invite Member'); ?></span>
    </a>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/partials/sidebars/patient.blade.php ENDPATH**/ ?>
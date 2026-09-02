<?php $__env->startSection('title', __('public.staff_portal.dashboard_title', [], app()->getLocale()) ?: 'Staff Clinical Portal'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.staff')); ?>

<?php $__env->startSection('content'); ?>

<div class="page-head">
    <h2><?php echo e(__('public.staff_portal.dashboard_title', [], app()->getLocale()) ?: 'Staff Clinical Portal'); ?></h2>
</div>
<p class="page-subtitle mb-6"><?php echo e(__('public.staff_portal.dashboard_subtitle', [], app()->getLocale()) ?: 'Manage appointments, queues, and patient care from one place.'); ?></p>


<div class="stat-grid">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="calendar-check-2"></i></div>
        <div class="stat-card__label"><?php echo e(__('public.staff_portal.kpi_todays_appointments', [], app()->getLocale()) ?: "Today's Appointments"); ?></div>
        <div class="stat-card__value"><?php echo e($kpis['todays_appointments'] ?? 0); ?></div>
        <div class="stat-card__hint"><?php echo e(__('public.staff_portal.kpi_scheduled_today', [], app()->getLocale()) ?: 'Scheduled today'); ?></div>
    </div>
    <div class="stat-card stat-card--teal">
        <div class="stat-card__head"><i data-lucide="list-ordered"></i></div>
        <div class="stat-card__label"><?php echo e(__('public.staff_portal.kpi_patient_queue', [], app()->getLocale()) ?: 'Patient Queue'); ?></div>
        <div class="stat-card__value"><?php echo e($kpis['in_queue'] ?? 0); ?></div>
        <div class="stat-card__hint"><?php echo e(__('public.staff_portal.kpi_currently_waiting', [], app()->getLocale()) ?: 'Currently waiting'); ?></div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__head"><i data-lucide="send"></i></div>
        <div class="stat-card__label"><?php echo e(__('public.staff_portal.kpi_pending_referrals', [], app()->getLocale()) ?: 'Pending Referrals'); ?></div>
        <div class="stat-card__value"><?php echo e($kpis['pending_referrals'] ?? 0); ?></div>
        <div class="stat-card__hint"><?php echo e(__('public.staff_portal.kpi_awaiting_action', [], app()->getLocale()) ?: 'Awaiting action'); ?></div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="receipt"></i></div>
        <div class="stat-card__label"><?php echo e(__('public.staff_portal.kpi_open_invoices', [], app()->getLocale()) ?: 'Open Invoices'); ?></div>
        <div class="stat-card__value"><?php echo e($kpis['open_invoices'] ?? 0); ?></div>
        <div class="stat-card__hint"><?php echo e(__('public.staff_portal.kpi_unpaid_balance', [], app()->getLocale()) ?: 'Unpaid balance'); ?></div>
    </div>
</div>

<div class="grid-main-side mt-6">

    
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">
                <i data-lucide="shield-check"></i>
                <?php echo e(__('public.staff_portal.patient_verification', [], app()->getLocale()) ?: 'Patient Verification'); ?>

            </h2>
        </div>
        <div class="panel-body">
            <p class="form-hint mb-4">
                <?php echo e(__('public.staff_portal.verification_desc', [], app()->getLocale()) ?: 'Enter a patient Health ID to verify their identity before providing care.'); ?>

            </p>
            <form method="GET" action="<?php echo e(route('portals.staff')); ?>" autocomplete="off">
                <div class="form-group">
                    <label class="form-label form-label-required" for="health_id">
                        <?php echo e(__('public.medical_id.health_id', [], app()->getLocale()) ?: 'Health ID'); ?>

                    </label>
                    <div class="form-search">
                        <span class="search-icon">
                            <i data-lucide="search"></i>
                        </span>
                        <input
                            type="text"
                            id="health_id"
                            name="health_id"
                            class="form-control mono"
                            placeholder="e.g. CM-HID-7KQ9-MP42-X8D1"
                            value="<?php echo e(request('health_id')); ?>"
                        >
                    </div>
                    <span class="form-hint"><?php echo e(__('public.staff_portal.health_id_hint', [], app()->getLocale()) ?: 'Enter the full Health ID as printed on the patient card.'); ?></span>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label form-label-required" for="purpose">
                            <?php echo e(__('public.staff_portal.purpose', [], app()->getLocale()) ?: 'Access Purpose'); ?>

                        </label>
                        <select id="purpose" name="purpose" class="form-control">
                            <option value="treatment" <?php if(request('purpose') === 'treatment'): echo 'selected'; endif; ?>><?php echo e(__('public.staff_portal.purpose_treatment', [], app()->getLocale()) ?: 'Treatment'); ?></option>
                            <option value="pharmacy_dispense" <?php if(request('purpose') === 'pharmacy_dispense'): echo 'selected'; endif; ?>><?php echo e(__('public.staff_portal.purpose_pharmacy', [], app()->getLocale()) ?: 'Pharmacy Dispense'); ?></option>
                            <option value="lab_order" <?php if(request('purpose') === 'lab_order'): echo 'selected'; endif; ?>><?php echo e(__('public.staff_portal.purpose_lab', [], app()->getLocale()) ?: 'Lab Order'); ?></option>
                            <option value="insurance_claim" <?php if(request('purpose') === 'insurance_claim'): echo 'selected'; endif; ?>><?php echo e(__('public.staff_portal.purpose_insurance', [], app()->getLocale()) ?: 'Insurance Claim'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="facility_id">
                            <?php echo e(__('public.staff_portal.facility_id', [], app()->getLocale()) ?: 'Facility ID'); ?>

                        </label>
                        <input
                            type="text"
                            id="facility_id"
                            name="facility_id"
                            class="form-control"
                            placeholder="<?php echo e(__('public.staff_portal.facility_id_placeholder', [], app()->getLocale()) ?: 'FAC-XXXXX'); ?>"
                            value="<?php echo e(request('facility_id')); ?>"
                        >
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="search"></i>
                        <?php echo e(__('public.medical_id.verify_health_id', [], app()->getLocale()) ?: 'Verify Patient'); ?>

                    </button>
                </div>
            </form>

            <?php if(isset($verification)): ?>
                <?php if($verification['patient']): ?>
                    <?php
                        $vp = $verification['patient'];
                        $vStatus = $vp->verification_status instanceof \BackedEnum ? $vp->verification_status->value : $vp->verification_status;
                    ?>
                    <div class="alert alert-success mt-4" role="status">
                        <i data-lucide="user-check"></i>
                        <div>
                            <strong><?php echo e($vp->first_name); ?> <?php echo e($vp->last_name); ?></strong>
                            <span class="badge badge-info"><?php echo e(\App\Support\Enums::label($vStatus ?? 'active', 'verification')); ?></span>
                            <div class="td-muted mt-1">
                                <span class="mono"><?php echo e($vp->health_id); ?></span>
                                <?php if($vp->date_of_birth): ?> · <?php echo e(\Carbon\Carbon::parse($vp->date_of_birth)->format('d M Y')); ?><?php endif; ?>
                                <?php if($vp->sex): ?> · <?php echo e(\App\Support\Enums::label($vp->sex)); ?><?php endif; ?>
                            </div>
                            <div class="form-hint mt-1"><?php echo e(__('public.staff_portal.verification_logged', [], app()->getLocale()) ?: 'Identity confirmed — this access has been logged and audited.'); ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger mt-4" role="alert">
                        <i data-lucide="user-x"></i>
                        <div><?php echo e(__('public.staff_portal.verification_not_found', [], app()->getLocale()) ?: 'No patient found for that Health ID. Check the ID and try again.'); ?> <span class="mono"><?php echo e($verification['health_id']); ?></span></div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">
                <i data-lucide="zap"></i>
                <?php echo e(__('public.staff_portal.quick_links', [], app()->getLocale()) ?: 'Quick Links'); ?>

            </h2>
        </div>
        <div class="panel-body">
            <?php $roleSlug = auth()->user()->role?->name ?? ''; ?>
            <div class="grid-2">
                
                <a href="<?php echo e(route('portals.staff.appointments')); ?>" class="btn btn-secondary">
                    <i data-lucide="calendar-check-2"></i>
                    <?php echo e(__('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments'); ?>

                </a>
                                                                                <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'clinical_decision_support')): ?>
                <?php endif; ?>
                                                
                
                <?php if(in_array($roleSlug, ['receptionist', 'front_desk', 'cashier', 'billing_officer', 'finance_manager', 'hospital_admin', 'clinic_admin', 'doctor'])): ?>
                <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'billing')): ?>
                                <?php endif; ?>
                <?php endif; ?>

                
                <?php if(in_array($roleSlug, ['doctor', 'hospital_admin', 'clinic_admin'])): ?>
                                <?php endif; ?>

                
                <?php if(in_array($roleSlug, ['hospital_admin', 'clinic_admin', 'doctor'])): ?>
                <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'analytics_dashboards')): ?>
                <?php endif; ?>
                <?php endif; ?>

                
                <?php if($roleSlug === 'doctor'): ?>
                <?php endif; ?>

                
                <?php if(in_array($roleSlug, ['hospital_admin', 'clinic_admin'])): ?>
                                <?php endif; ?>

                
                <?php if(in_array($roleSlug, ['pharmacist', 'hospital_admin', 'clinic_admin'])): ?>
                <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'inventory_ops')): ?>
                                <?php endif; ?>
                <?php endif; ?>

                
                <?php if(in_array($roleSlug, ['hospital_admin', 'clinic_admin', 'pharmacist'])): ?>
                <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'inventory_ops')): ?>
                                <?php endif; ?>
                <?php endif; ?>

                
                <?php if(in_array($roleSlug, ['hospital_admin', 'clinic_admin'])): ?>
                                <?php endif; ?>

                
                <?php if(in_array($roleSlug, ['doctor', 'nurse', 'pharmacist'])): ?>
                                <?php endif; ?>

                
                <?php if(in_array($roleSlug, ['doctor', 'nurse', 'labtech', 'lab_scientist', 'lab_manager', 'lab_validator', 'sample_collection'])): ?>
                                <?php endif; ?>
            </div>
        </div>
    </div>

</div>


<div class="alert alert-warning mt-6">
    <i data-lucide="triangle-alert"></i>
    <div>
        <strong><?php echo e(__('public.staff_portal.disclaimer_title', [], app()->getLocale()) ?: 'Clinical Safety Disclaimer'); ?></strong>
        <?php echo e(__('public.staff_portal.disclaimer_text', [], app()->getLocale()) ?: 'This portal is for authorised clinical staff only. All access is logged and audited. Do not share login credentials. Patient data must be handled in accordance with applicable data protection regulations and facility privacy policies. In an emergency, use the break-glass access procedure and document the clinical justification.'); ?>

    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\staff\index.blade.php ENDPATH**/ ?>
<?php $__env->startSection('title', __('public.staff_portal.btn_book_appointment', [], app()->getLocale()) ?: 'Book Appointment'); ?>

<?php $__env->startSection('sidebar_role_badge'); ?>
<div class="sidebar-role-badge"><?php echo e(__('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff'); ?></div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('sidebar_user_role', __('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff'); ?>

<?php $__env->startSection('sidebar_nav'); ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.stf_nav_overview')); ?></div>
    <a href="<?php echo e(route('portals.staff')); ?>" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span><?php echo e(__('public.portal.nav_dashboard', [], app()->getLocale()) ?: 'Dashboard'); ?></span>
    </a>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'analytics_dashboards')): ?>
    <?php endif; ?>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.stf_nav_clinical')); ?></div>
    <a href="<?php echo e(route('portals.staff.appointments')); ?>" class="sidebar-link active">
        <i data-lucide="calendar-check-2"></i>
        <span><?php echo e(__('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments'); ?></span>
    </a>
            <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'clinical_decision_support')): ?>
    <?php endif; ?>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.stf_nav_hr_staff')); ?></div>
                </div>
<?php if (\Illuminate\Support\Facades\Blade::check('feature', 'inventory_ops')): ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.stf_nav_inventory')); ?></div>
        <a href="<?php echo e(route('portals.staff.inventory.blood')); ?>" class="sidebar-link">
        <i data-lucide="droplets"></i>
        <span><?php echo e(__('public.portal.nav_inventory_blood', [], app()->getLocale()) ?: 'Blood Bank'); ?></span>
    </a>
</div>
<?php endif; ?>
<?php if (\Illuminate\Support\Facades\Blade::check('feature', 'inventory_ops')): ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.stf_nav_supply_chain')); ?></div>
    </div>
<?php endif; ?>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label"><?php echo e(__('public.stf_nav_operations')); ?></div>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'billing')): ?>
        <?php endif; ?>
        <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'insurance')): ?>
        <?php endif; ?>
                </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.staff')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.staff_portal.btn_book_appointment', [], app()->getLocale()) ?: 'Book Appointment'); ?>

<?php $__env->startSection('head'); ?>
<?php echo $__env->make('portals.pharmacy._supply_styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.staff_portal.btn_book_appointment', [], app()->getLocale()) ?: 'Book Appointment'); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.staff_portal.appointments_subtitle', [], app()->getLocale()) ?: 'Schedule a new patient appointment.'); ?></p>
    </div>
    <a href="<?php echo e(route('portals.staff.appointments')); ?>" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i>
        <?php echo e(__('public.stf_back')); ?>

    </a>
</div>

<?php if(session('error')): ?>
    <div class="auth-alert auth-alert-danger mb-4">
        <i data-lucide="triangle-alert"></i><div><?php echo e(session('error')); ?></div>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-body">
        <form method="POST" action="<?php echo e(route('portals.staff.appointments.store')); ?>">
            <?php echo csrf_field(); ?>

            <div class="form-group mb-4">
                <label class="form-label"><?php echo e(__('public.stf_appt_patient_id')); ?></label>
                <?php if(count($patients) > 0): ?>
                    <select name="patient_id" class="form-control" required>
                        <option value=""><?php echo e(__('public.stf_select_patient')); ?></option>
                        <?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($p->id); ?>" <?php echo e(old('patient_id') == $p->id ? 'selected' : ''); ?>>
                                <?php echo e($p->health_id ?? $p->id); ?> (<?php echo e($p->first_name ?? ''); ?> <?php echo e($p->last_name ?? ''); ?>)
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                <?php else: ?>
                    <input type="text" name="patient_id" class="form-control" required
                        placeholder="<?php echo e(__('public.stf_appt_enter_patient_id')); ?>" value="<?php echo e(old('patient_id')); ?>">
                <?php endif; ?>
                <?php $__errorArgs = ['patient_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="form-group mb-4">
                <label class="form-label"><?php echo e(__('public.stf_appt_facility')); ?></label>
                <input type="text" class="form-control" disabled
                       value="<?php echo e($activeFacilityName ?? __('public.stf_appt_facility_session')); ?>">
            </div>

            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.stf_appt_type')); ?></label>
                    <select name="appointment_type" class="form-control" required>
                        <option value="general" <?php echo e(old('appointment_type') === 'general' ? 'selected' : ''); ?>>
                            <?php echo e(__('public.staff_portal.type_general', [], app()->getLocale()) ?: 'General'); ?>

                        </option>
                        <option value="follow_up" <?php echo e(old('appointment_type') === 'follow_up' ? 'selected' : ''); ?>>
                            <?php echo e(__('public.staff_portal.type_followup', [], app()->getLocale()) ?: 'Follow-up'); ?>

                        </option>
                        <option value="specialist" <?php echo e(old('appointment_type') === 'specialist' ? 'selected' : ''); ?>>
                            <?php echo e(__('public.staff_portal.type_specialist', [], app()->getLocale()) ?: 'Specialist'); ?>

                        </option>
                        <option value="lab" <?php echo e(old('appointment_type') === 'lab' ? 'selected' : ''); ?>>
                            <?php echo e(__('public.staff_portal.type_lab', [], app()->getLocale()) ?: 'Lab / Diagnostics'); ?>

                        </option>
                        <option value="pharmacy" <?php echo e(old('appointment_type') === 'pharmacy' ? 'selected' : ''); ?>>
                            <?php echo e(__('public.staff_portal.type_pharmacy', [], app()->getLocale()) ?: 'Pharmacy'); ?>

                        </option>
                        <option value="emergency" <?php echo e(old('appointment_type') === 'emergency' ? 'selected' : ''); ?>>
                            <?php echo e(__('public.staff_portal.type_emergency', [], app()->getLocale()) ?: 'Emergency'); ?>

                        </option>
                    </select>
                    <?php $__errorArgs = ['appointment_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.stf_appt_datetime')); ?></label>
                    <input type="datetime-local" name="scheduled_at" class="form-control" required
                        value="<?php echo e(old('scheduled_at', now()->addDay()->format('Y-m-d\TH:i'))); ?>">
                    <?php $__errorArgs = ['scheduled_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="form-group mb-6">
                <label class="form-label"><?php echo e(__('public.stf_appt_reason')); ?></label>
                <textarea name="reason" class="form-control" rows="3" maxlength="500"
                    placeholder="<?php echo e(__('public.stf_appt_reason_placeholder')); ?>"><?php echo e(old('reason')); ?></textarea>
            </div>

            <div class="row-actions-inline">
                <button type="submit" class="btn btn-primary fs-tap">
                    <i data-lucide="calendar-plus"></i>
                    <?php echo e(__('public.staff_portal.btn_book_appointment', [], app()->getLocale()) ?: 'Book Appointment'); ?>

                </button>
                <a href="<?php echo e(route('portals.staff.appointments')); ?>" class="btn btn-ghost"><?php echo e(__('public.stf_cancel')); ?></a>
            </div>
        </form>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\staff\appointments_create.blade.php ENDPATH**/ ?>
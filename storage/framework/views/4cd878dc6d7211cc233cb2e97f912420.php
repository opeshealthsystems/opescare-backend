<?php $__env->startSection('title', __('public.portal.profile_page_title', [], app()->getLocale()) ?: 'My Profile — OpesCare Patient Portal'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.profile_breadcrumb', [], app()->getLocale()) ?: 'Profile'); ?>

<?php $__env->startSection('patient_banner'); ?>
    <?php echo $__env->make('partials.guardian-context-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.portal.profile_page_heading', [], $l) ?: 'My Profile'); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.portal.profile_page_subtitle', [], $l) ?: 'Manage your contact details, clinical information, and privacy preferences.'); ?></p>
    </div>
</div>

<?php if(!$patient): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="alert-circle"></i></div>
        <h3><?php echo e(__('public.portal.profile_no_profile_h3', [], $l) ?: 'No Patient Profile Found'); ?></h3>
        <p><?php echo e(__('public.portal.profile_no_profile_p', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.'); ?></p>
    </div>
</div>
<?php else: ?>

<?php if(session('success')): ?>
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('portals.patient.profile.update')); ?>">
<?php echo csrf_field(); ?>

<div class="grid-main-side">

    
    <div style="display:flex;flex-direction:column;gap:var(--p-space-5);">

        
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title"><i data-lucide="user"></i> <?php echo e(__('public.portal.panel_contact_details', [], $l) ?: 'Contact Details'); ?></h2></div>
            <div class="panel-body">

                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.portal.field_phone_number', [], $l) ?: 'Phone Number'); ?></label>
                    <input type="text" name="phone_number" class="form-control" value="<?php echo e(old('phone_number', $patient->phone_number)); ?>">
                    <?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-hint"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.portal.field_email', [], $l) ?: 'Email'); ?></label>
                    <input type="email" name="email" class="form-control" value="<?php echo e(old('email', $patient->email)); ?>">
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-hint"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.portal.field_address', [], $l) ?: 'Address'); ?></label>
                    <textarea name="address" rows="2" class="form-control"><?php echo e(old('address', $patient->address)); ?></textarea>
                    <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-hint"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.portal.field_date_of_birth', [], $l) ?: 'Date of Birth'); ?></label>
                    <input type="date" name="date_of_birth" class="form-control"
                        value="<?php echo e(old('date_of_birth', $patient->date_of_birth?->format('Y-m-d'))); ?>"
                        max="<?php echo e(now()->subDay()->format('Y-m-d')); ?>">
                    <?php if($patient->date_of_birth): ?>
                        <div class="form-hint"><?php echo e(__('public.portal.lbl_age', [], $l) ?: 'Age'); ?>: <?php echo e($patient->date_of_birth->diffInYears(now())); ?> <?php echo e(__('public.portal.lbl_years', [], $l) ?: 'years'); ?></div>
                    <?php endif; ?>
                    <?php $__errorArgs = ['date_of_birth'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-hint"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

            </div>
        </div>

        
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title"><i data-lucide="heart-pulse"></i> <?php echo e(__('public.portal.panel_clinical_info', [], $l) ?: 'Clinical Information'); ?></h2></div>
            <div class="panel-body">

                
                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.portal.field_blood_group', [], $l) ?: 'Blood Group'); ?></label>
                    <select name="blood_group" class="form-control">
                        <option value=""><?php echo e(__('public.portal.opt_not_set', [], $l) ?: '— Not set —'); ?></option>
                        <?php $__currentLoopData = ['A+','A-','B+','B-','AB+','AB-','O+','O-']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($bg); ?>" <?php echo e(old('blood_group', $patient->blood_group) === $bg ? 'selected' : ''); ?>><?php echo e($bg); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <div class="form-hint"><?php echo e(__('public.portal.hint_blood_group', [], $l) ?: 'Used in emergency profiles and clinical summaries shared with providers.'); ?></div>
                    <?php $__errorArgs = ['blood_group'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-hint"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                
                <div class="form-group">
                    <div class="flex-between mb-3">
                        <label class="form-label"><?php echo e(__('public.portal.lbl_active_allergies', [], $l) ?: 'Active Allergies'); ?></label>
                                            </div>
                    <?php if($allergies->isEmpty()): ?>
                        <div class="empty-state">
                            <i data-lucide="check-circle"></i> <?php echo e(__('public.portal.lbl_no_allergies', [], $l) ?: 'No allergies on record.'); ?>

                        </div>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:6px;">
                            <?php $__currentLoopData = $allergies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allergy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $sevCls = match(strtolower($allergy->severity ?? '')) {
                                    'life-threatening','severe','high' => 'badge-danger',
                                    'moderate','medium'               => 'badge-warning',
                                    default                           => 'badge-neutral',
                                };
                            ?>
                            <div class="list-row">
                                <span class="list-row__main">
                                    <?php if(in_array(strtolower($allergy->severity ?? ''), ['life-threatening','severe','high'])): ?>
                                        <i data-lucide="alert-triangle"></i>
                                    <?php endif; ?>
                                    <?php echo e($allergy->substance); ?>

                                </span>
                                <span class="badge <?php echo e($sevCls); ?>"><?php echo e(\App\Support\Enums::label($allergy->severity ?? 'unknown', 'severity')); ?></span>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                    <div class="form-hint"><?php echo e(__('public.portal.hint_allergies_readonly', [], $l) ?: 'Allergies are maintained by your healthcare providers. Contact your facility to add or update.'); ?></div>
                </div>

                
                <div class="form-group">
                    <div class="flex-between mb-3">
                        <label class="form-label"><?php echo e(__('public.portal.lbl_active_conditions', [], $l) ?: 'Active Conditions'); ?></label>
                                            </div>
                    <?php if($conditions->isEmpty()): ?>
                        <div class="empty-state">
                            <i data-lucide="check-circle"></i> <?php echo e(__('public.portal.lbl_no_conditions', [], $l) ?: 'No active conditions on record.'); ?>

                        </div>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:6px;">
                            <?php $__currentLoopData = $conditions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $condition): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="list-row">
                                <span class="list-row__main"><?php echo e($condition->display_name ?? $condition->code ?? '—'); ?></span>
                                <span class="badge <?php echo e($condition->status === 'chronic' ? 'badge-teal' : 'badge-primary'); ?>"><?php echo e(\App\Support\Enums::label($condition->status)); ?></span>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                    <div class="form-hint"><?php echo e(__('public.portal.hint_conditions_readonly', [], $l) ?: 'Conditions are recorded by your healthcare providers and cannot be edited here.'); ?></div>
                </div>

            </div>
        </div>

        
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title"><i data-lucide="phone-call"></i> <?php echo e(__('public.portal.panel_emergency_contact', [], $l) ?: 'Emergency Contact'); ?></h2></div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.portal.field_ec_name', [], $l) ?: 'Name'); ?></label>
                    <input type="text" name="emergency_contact[name]" class="form-control" value="<?php echo e(old('emergency_contact.name', ($patient->emergency_contact ?? [])['name'] ?? '')); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.portal.field_ec_phone', [], $l) ?: 'Phone'); ?></label>
                    <input type="text" name="emergency_contact[phone]" class="form-control" value="<?php echo e(old('emergency_contact.phone', ($patient->emergency_contact ?? [])['phone'] ?? '')); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.portal.field_ec_relationship', [], $l) ?: 'Relationship'); ?></label>
                    <input type="text" name="emergency_contact[relationship]" class="form-control" value="<?php echo e(old('emergency_contact.relationship', ($patient->emergency_contact ?? [])['relationship'] ?? '')); ?>">
                </div>
            </div>
        </div>

    </div>

    
    <div style="display:flex;flex-direction:column;gap:var(--p-space-5);">

        
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title"><i data-lucide="fingerprint"></i> <?php echo e(__('public.portal.panel_identity', [], $l) ?: 'Identity'); ?></h2></div>
            <div class="panel-body">
                <div class="kv-table">
                    <div class="kv-strong"><?php echo e(__('public.portal.lbl_health_id', [], $l) ?: 'Health ID'); ?></div>
                    <div class="mono"><?php echo e($patient->health_id); ?></div>
                    <?php if($patient->cnamgs_id): ?>
                    <div class="kv-strong"><?php echo e(__('public.portal.lbl_cnamgs_id', [], $l) ?: 'CNAMGS ID'); ?></div>
                    <div class="mono"><?php echo e($patient->cnamgs_id); ?></div>
                    <?php endif; ?>
                    <div class="kv-strong"><?php echo e(__('public.portal.lbl_status', [], $l) ?: 'Status'); ?></div>
                    <div>
                        <?php
                            $portalStatus = $patient->verification_status ?? $patient->identity_status ?? 'Active';
                            $portalStatus = $portalStatus instanceof \BackedEnum ? $portalStatus->value : $portalStatus;
                        ?>
                        <?php echo e(\App\Support\Enums::label($portalStatus, 'verification')); ?>
                    </div>
                </div>
                <div class="form-hint mt-3"><?php echo e(__('public.portal.hint_identity_managed', [], $l) ?: 'Identity fields are managed by OpesCare. To update, contact your registered facility.'); ?></div>
            </div>
        </div>

        
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title"><i data-lucide="shield-check"></i> <?php echo e(__('public.portal.panel_privacy_settings', [], $l) ?: 'Privacy Settings'); ?></h2></div>
            <div class="panel-body">

                <label class="form-check">
                    <input type="hidden" name="privacy_require_consent" value="0">
                    <input type="checkbox" name="privacy_require_consent" value="1"
                        <?php echo e(($patient->privacy_preferences['require_consent_for_full_record'] ?? true) ? 'checked' : ''); ?>>
                    <div>
                        <div class="td-strong"><?php echo e(__('public.portal.chk_require_consent_lbl', [], $l) ?: 'Require Consent for Full Record'); ?></div>
                        <div class="text-sm text-muted"><?php echo e(__('public.portal.chk_require_consent_hint', [], $l) ?: 'Providers can only see a masked preview without your explicit consent.'); ?></div>
                    </div>
                </label>

                <label class="form-check">
                    <input type="hidden" name="privacy_emergency_access" value="0">
                    <input type="checkbox" name="privacy_emergency_access" value="1"
                        <?php echo e(($patient->privacy_preferences['emergency_access_allowed'] ?? true) ? 'checked' : ''); ?>>
                    <div>
                        <div class="td-strong"><?php echo e(__('public.portal.chk_emergency_access_lbl', [], $l) ?: 'Allow Emergency Access'); ?></div>
                        <div class="text-sm text-muted"><?php echo e(__('public.portal.chk_emergency_access_hint', [], $l) ?: 'Permit audited break-glass access during emergencies without standard consent.'); ?></div>
                    </div>
                </label>

                <hr style="margin:1rem 0;border:none;border-top:1px solid var(--color-border);">
                                <p class="text-sm text-muted" style="margin-top:.5rem;"><?php echo e(__('public.portal.data_export_hint', [], $l) ?: 'Download a copy of all your personal health data (Law No. 2010/012).'); ?></p>

            </div>
        </div>

        
        <div>
            <button type="submit" class="btn btn-primary btn-block">
                <i data-lucide="save"></i> <?php echo e(__('public.portal.btn_save_changes_form', [], $l) ?: 'Save Changes'); ?>

            </button>
        </div>

    </div>

</div>
</form>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\profile.blade.php ENDPATH**/ ?>
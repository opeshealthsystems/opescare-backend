<?php $__env->startSection('title', __('public.portal.profile_page_title', [], app()->getLocale()) ?: 'My Profile — OpesCare Patient Portal'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.profile_breadcrumb', [], app()->getLocale()) ?: 'Profile'); ?>

<?php $__env->startSection('head'); ?>
    <?php echo $__env->make('portals.patient._finder_head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('patient_banner'); ?>
    <?php echo $__env->make('partials.guardian-context-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

<div class="pf pf--wide">

    <div class="pf-head">
        <div>
            <h1><?php echo e(__('public.portal.profile_page_heading', [], $l) ?: 'My Profile'); ?></h1>
            <p class="pf-sub"><?php echo e(__('public.portal.profile_page_subtitle', [], $l) ?: 'Manage your contact details, clinical information, and privacy preferences.'); ?></p>
        </div>
    </div>

    <?php if(!$patient): ?>
    <div class="f-card" style="text-align:center;padding:40px 20px;">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-triangle', 'size' => 24], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <h2 style="font-size:1.125rem;font-weight:750;margin:0 0 6px;"><?php echo e(__('public.portal.profile_no_profile_h3', [], $l) ?: 'No Patient Profile Found'); ?></h2>
        <p class="f-muted" style="margin:0;"><?php echo e(__('public.portal.profile_no_profile_p', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.'); ?></p>
    </div>
    <?php else: ?>

    <form method="POST" action="<?php echo e(route('portals.patient.profile.update')); ?>">
    <?php echo csrf_field(); ?>

    <div class="pf-cols">

        
        <div class="pf-stack" style="gap:16px;">

            
            <div class="f-card">
                <h2 class="pf-card-head">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'phone', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('public.portal.panel_contact_details', [], $l) ?: 'Contact Details'); ?>

                </h2>

                <div class="f-field">
                    <label class="f-label" for="phone_number"><?php echo e(__('public.portal.field_phone_number', [], $l) ?: 'Phone Number'); ?></label>
                    <input type="text" id="phone_number" name="phone_number" class="f-input" inputmode="tel" autocomplete="tel"
                           value="<?php echo e(old('phone_number', $patient->phone_number)); ?>">
                    <?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="f-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="f-field">
                    <label class="f-label" for="email"><?php echo e(__('public.portal.field_email', [], $l) ?: 'Email'); ?></label>
                    <input type="email" id="email" name="email" class="f-input" autocomplete="email"
                           value="<?php echo e(old('email', $patient->email)); ?>">
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="f-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="f-field">
                    <label class="f-label" for="address"><?php echo e(__('public.portal.field_address', [], $l) ?: 'Address'); ?></label>
                    <textarea id="address" name="address" rows="2" class="f-input"><?php echo e(old('address', $patient->address)); ?></textarea>
                    <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="f-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="f-field" style="margin-bottom:0;">
                    <label class="f-label" for="date_of_birth"><?php echo e(__('public.portal.field_date_of_birth', [], $l) ?: 'Date of Birth'); ?></label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="f-input"
                        value="<?php echo e(old('date_of_birth', $patient->date_of_birth?->format('Y-m-d'))); ?>"
                        max="<?php echo e(now()->subDay()->format('Y-m-d')); ?>">
                    <?php if($patient->date_of_birth): ?>
                        <p class="f-hint"><?php echo e(__('public.portal.lbl_age', [], $l) ?: 'Age'); ?>: <?php echo e($patient->date_of_birth->diffInYears(now())); ?> <?php echo e(__('public.portal.lbl_years', [], $l) ?: 'years'); ?></p>
                    <?php endif; ?>
                    <?php $__errorArgs = ['date_of_birth'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="f-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            
            <div class="f-card">
                <h2 class="pf-card-head">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('public.portal.panel_clinical_info', [], $l) ?: 'Clinical Information'); ?>

                </h2>

                <div class="f-field">
                    <label class="f-label" for="blood_group"><?php echo e(__('public.portal.field_blood_group', [], $l) ?: 'Blood Group'); ?></label>
                    <select id="blood_group" name="blood_group" class="f-input" style="max-width:220px;">
                        <option value=""><?php echo e(__('public.portal.opt_not_set', [], $l) ?: '— Not set —'); ?></option>
                        <?php $__currentLoopData = ['A+','A-','B+','B-','AB+','AB-','O+','O-']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($bg); ?>" <?php echo e(old('blood_group', $patient->blood_group) === $bg ? 'selected' : ''); ?>><?php echo e($bg); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <p class="f-hint"><?php echo e(__('public.portal.hint_blood_group', [], $l) ?: 'Used in emergency profiles and clinical summaries shared with providers.'); ?></p>
                    <?php $__errorArgs = ['blood_group'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="f-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                
                <div class="f-field">
                    <span class="f-label"><?php echo e(__('public.portal.lbl_active_allergies', [], $l) ?: 'Active Allergies'); ?></span>
                    <?php if($allergies->isEmpty()): ?>
                        <p class="f-muted f-small" style="display:flex;align-items:center;gap:6px;margin:4px 0 0;">
                            <?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <?php echo e(__('public.portal.lbl_no_allergies', [], $l) ?: 'No allergies on record.'); ?>

                        </p>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:6px;">
                            <?php $__currentLoopData = $allergies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allergy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $sevCls = match(strtolower($allergy->severity ?? '')) {
                                    'life-threatening','severe','high' => 'f-badge--danger',
                                    'moderate','medium'                => 'f-badge--warn',
                                    default                            => 'pf-badge--muted',
                                };
                            ?>
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--f-line);">
                                <span style="font-weight:650;"><?php echo e($allergy->substance); ?></span>
                                <span class="f-badge <?php echo e($sevCls); ?>"><?php echo e(\App\Support\Enums::label($allergy->severity ?? 'unknown', 'severity')); ?></span>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                    <p class="f-hint"><?php echo e(__('public.portal.hint_allergies_readonly', [], $l) ?: 'Allergies are maintained by your healthcare providers. Contact your facility to add or update.'); ?></p>
                </div>

                
                <div class="f-field" style="margin-bottom:0;">
                    <span class="f-label"><?php echo e(__('public.portal.lbl_active_conditions', [], $l) ?: 'Active Conditions'); ?></span>
                    <?php if($conditions->isEmpty()): ?>
                        <p class="f-muted f-small" style="display:flex;align-items:center;gap:6px;margin:4px 0 0;">
                            <?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <?php echo e(__('public.portal.lbl_no_conditions', [], $l) ?: 'No active conditions on record.'); ?>

                        </p>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:6px;">
                            <?php $__currentLoopData = $conditions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $condition): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--f-line);">
                                <span style="font-weight:650;"><?php echo e($condition->display_name ?? $condition->code ?? '—'); ?></span>
                                <span class="f-badge"><?php echo e(\App\Support\Enums::label($condition->status)); ?></span>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                    <p class="f-hint"><?php echo e(__('public.portal.hint_conditions_readonly', [], $l) ?: 'Conditions are recorded by your healthcare providers and cannot be edited here.'); ?></p>
                </div>
            </div>

            
            <div class="f-card">
                <h2 class="pf-card-head">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'phone', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('public.portal.panel_emergency_contact', [], $l) ?: 'Emergency Contact'); ?>

                </h2>
                <div class="f-field">
                    <label class="f-label" for="ec_name"><?php echo e(__('public.portal.field_ec_name', [], $l) ?: 'Name'); ?></label>
                    <input type="text" id="ec_name" name="emergency_contact[name]" class="f-input" value="<?php echo e(old('emergency_contact.name', ($patient->emergency_contact ?? [])['name'] ?? '')); ?>">
                </div>
                <div class="f-field">
                    <label class="f-label" for="ec_phone"><?php echo e(__('public.portal.field_ec_phone', [], $l) ?: 'Phone'); ?></label>
                    <input type="text" id="ec_phone" name="emergency_contact[phone]" class="f-input" inputmode="tel" value="<?php echo e(old('emergency_contact.phone', ($patient->emergency_contact ?? [])['phone'] ?? '')); ?>">
                </div>
                <div class="f-field" style="margin-bottom:0;">
                    <label class="f-label" for="ec_rel"><?php echo e(__('public.portal.field_ec_relationship', [], $l) ?: 'Relationship'); ?></label>
                    <input type="text" id="ec_rel" name="emergency_contact[relationship]" class="f-input" value="<?php echo e(old('emergency_contact.relationship', ($patient->emergency_contact ?? [])['relationship'] ?? '')); ?>">
                </div>
            </div>

        </div>

        
        <div class="pf-stack" style="gap:16px;">

            
            <div class="f-card">
                <h2 class="pf-card-head">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'shield-check', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('public.portal.panel_identity', [], $l) ?: 'Identity'); ?>

                </h2>
                <dl class="pf-kv">
                    <div>
                        <dt><?php echo e(__('public.portal.lbl_health_id', [], $l) ?: 'Health ID'); ?></dt>
                        <dd style="font-family:ui-monospace,Menlo,Consolas,monospace;"><?php echo e($patient->health_id); ?></dd>
                    </div>
                    <?php if($patient->cnamgs_id): ?>
                    <div>
                        <dt><?php echo e(__('public.portal.lbl_cnamgs_id', [], $l) ?: 'CNAMGS ID'); ?></dt>
                        <dd style="font-family:ui-monospace,Menlo,Consolas,monospace;"><?php echo e($patient->cnamgs_id); ?></dd>
                    </div>
                    <?php endif; ?>
                    <div>
                        <dt><?php echo e(__('public.portal.lbl_status', [], $l) ?: 'Status'); ?></dt>
                        <dd>
                            <?php
                                $portalStatus = $patient->verification_status ?? $patient->identity_status ?? 'Active';
                                $portalStatus = $portalStatus instanceof \BackedEnum ? $portalStatus->value : $portalStatus;
                            ?>
                            <?php echo e(\App\Support\Enums::label($portalStatus, 'verification')); ?>
                        </dd>
                    </div>
                </dl>
                <p class="f-hint"><?php echo e(__('public.portal.hint_identity_managed', [], $l) ?: 'Identity fields are managed by OpesCare. To update, contact your registered facility.'); ?></p>
            </div>

            
            <div class="f-card">
                <h2 class="pf-card-head">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'shield-check', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('public.portal.panel_privacy_settings', [], $l) ?: 'Privacy Settings'); ?>

                </h2>

                <label class="pf-check">
                    <input type="hidden" name="privacy_require_consent" value="0">
                    <input type="checkbox" name="privacy_require_consent" value="1"
                        <?php echo e(($patient->privacy_preferences['require_consent_for_full_record'] ?? true) ? 'checked' : ''); ?>>
                    <span>
                        <span style="display:block;font-weight:700;"><?php echo e(__('public.portal.chk_require_consent_lbl', [], $l) ?: 'Require Consent for Full Record'); ?></span>
                        <span class="f-small f-muted"><?php echo e(__('public.portal.chk_require_consent_hint', [], $l) ?: 'Providers can only see a masked preview without your explicit consent.'); ?></span>
                    </span>
                </label>

                <label class="pf-check">
                    <input type="hidden" name="privacy_emergency_access" value="0">
                    <input type="checkbox" name="privacy_emergency_access" value="1"
                        <?php echo e(($patient->privacy_preferences['emergency_access_allowed'] ?? true) ? 'checked' : ''); ?>>
                    <span>
                        <span style="display:block;font-weight:700;"><?php echo e(__('public.portal.chk_emergency_access_lbl', [], $l) ?: 'Allow Emergency Access'); ?></span>
                        <span class="f-small f-muted"><?php echo e(__('public.portal.chk_emergency_access_hint', [], $l) ?: 'Permit audited break-glass access during emergencies without standard consent.'); ?></span>
                    </span>
                </label>

                <p class="f-hint" style="border-top:1px solid var(--f-line);padding-top:12px;margin-top:8px;"><?php echo e(__('public.portal.data_export_hint', [], $l) ?: 'Download a copy of all your personal health data (Law No. 2010/012).'); ?></p>
            </div>

            
            <button type="submit" class="f-btn f-btn--primary f-btn--block" style="min-height:48px;">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('public.portal.btn_save_changes_form', [], $l) ?: 'Save Changes'); ?>

            </button>

        </div>

    </div>
    </form>
    <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\profile.blade.php ENDPATH**/ ?>
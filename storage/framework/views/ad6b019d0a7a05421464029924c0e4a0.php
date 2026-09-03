<?php $__env->startSection('title', __('public.medical_id.health_id', [], app()->getLocale()) . ' — OpesCare Patient Portal'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>

<?php $__env->startSection('head'); ?>
    <?php echo $__env->make('portals.patient._finder_head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

<?php if($patient): ?>

<div class="pf pf--wide">

    
    <div class="pf-head">
        <div>
            <h1>
                <?php if($patient->first_name): ?>
                    <?php echo e(__('finder_patient.greeting', ['name' => $patient->first_name])); ?>

                <?php else: ?>
                    <?php echo e(__('finder_patient.greeting_generic')); ?>

                <?php endif; ?>
            </h1>
            <p class="pf-sub"><?php echo e(__('finder_patient.greeting_sub')); ?></p>
        </div>
    </div>

    
    <div class="pf-hid pf-section">
        <div class="pf-hid__row">
            <div style="min-width:0;flex:1;">
                <span class="pf-hid__label">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'shield-check', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('public.medical_id.health_id', [], $l) ?: 'OpesCare Health ID'); ?>

                </span>
                <div class="pf-hid__value"><?php echo e($patient->health_id); ?></div>
                <div class="pf-hid__meta">
                    <div>
                        <div class="pf-hid__meta-label"><?php echo e(__('public.portal.status', [], $l) ?: 'Status'); ?></div>
                        <div class="pf-hid__meta-value">
                            <?php echo e(\App\Support\Enums::label(($patient->verification_status instanceof \BackedEnum ? $patient->verification_status->value : $patient->verification_status) ?? 'Active', 'verification')); ?>
                        </div>
                    </div>
                    <div>
                        <div class="pf-hid__meta-label"><?php echo e(__('public.portal.country', [], $l) ?: 'Country'); ?></div>
                        <div class="pf-hid__meta-value"><?php echo e($patient->country_code ?? '—'); ?></div>
                    </div>
                    <div>
                        <div class="pf-hid__meta-label"><?php echo e(__('public.portal.registered', [], $l) ?: 'Registered'); ?></div>
                        <div class="pf-hid__meta-value"><?php echo e($patient->created_at?->format('M Y') ?? '—'); ?></div>
                    </div>
                </div>
            </div>
            <div class="pf-hid__qr">
                <?php if($staticQrDataUri): ?>
                    <img src="<?php echo e($staticQrDataUri); ?>" alt="<?php echo e(__('public.medical_id.health_id_qr_alt', [], $l) ?: 'Health ID QR Code'); ?>">
                <?php else: ?>
                    <div style="width:88px;height:88px;display:flex;align-items:center;justify-content:center;">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'qr-code', 'size' => 40], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                <?php endif; ?>
                <span><?php echo e(__('public.medical_id.scan_qr', [], $l) ?: 'Scan QR'); ?></span>
            </div>
        </div>
    </div>

    
    <?php if(isset($onboarding)): ?>
        <?php
            $obDone  = collect($onboarding)->where('done', true)->count();
            $obTotal = count($onboarding);
            $obIcons = ['id-card' => 'shield-check', 'badge-check' => 'check', 'users' => 'users', 'gift' => 'gift'];
        ?>
        <?php if($obTotal && $obDone < $obTotal): ?>
        <div class="f-card pf-section">
            <h2 class="pf-card-head">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'star', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('public.pat_onb_title')); ?>

                <span class="f-badge"><?php echo e(__('public.pat_onb_progress', ['done' => $obDone, 'total' => $obTotal])); ?></span>
            </h2>
            <p class="f-hint" style="margin:-6px 0 0;"><?php echo e(__('public.pat_onb_subtitle')); ?></p>
            <div class="pf-progress"><span style="width:<?php echo e(round($obDone / $obTotal * 100)); ?>%;"></span></div>
            <ul style="list-style:none;display:grid;gap:10px;">
                <?php $__currentLoopData = $onboarding; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li style="display:flex;align-items:center;gap:10px;">
                    <span class="pf-qa__icon" style="width:30px;height:30px;border-radius:50%;<?php echo e($step['done'] ? 'background:var(--f-good-wash);color:var(--f-good);' : ''); ?>">
                        <?php echo $__env->make('partials.finder.icon', ['name' => $step['done'] ? 'check' : ($obIcons[$step['icon']] ?? 'chevron-right'), 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </span>
                    <span style="flex:1;<?php echo e($step['done'] ? 'color:var(--f-muted);' : ''); ?>"><?php echo e(__('public.pat_onb_' . $step['key'])); ?></span>
                    <?php if($step['done']): ?>
                        <span class="f-badge f-badge--good"><?php echo e(__('public.pat_onb_done')); ?></span>
                    <?php elseif($step['url']): ?>
                        <a href="<?php echo e($step['url']); ?>" class="f-btn" style="min-height:40px;padding:6px 12px;" aria-label="<?php echo e(__('public.pat_onb_' . $step['key'])); ?>">
                            <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </a>
                    <?php endif; ?>
                </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    
    <div class="pf-section">
        <h2 class="pf-card-head" style="margin-bottom:10px;"><?php echo e(__('finder_patient.quick_actions')); ?></h2>
        <div class="pf-qa">
            <a href="<?php echo e(route('portals.patient.appointments')); ?>" class="f-card pf-qa__item">
                <span class="pf-qa__icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'calendar', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                <?php echo e(__('public.portal.nav_appointments', [], $l) ?: 'Appointments'); ?>

            </a>
            <a href="<?php echo e(route('portals.patient.prescriptions')); ?>" class="f-card pf-qa__item">
                <span class="pf-qa__icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'pill', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                <?php echo e(__('public.portal.nav_prescriptions', [], $l) ?: 'Prescriptions'); ?>

            </a>
            <a href="<?php echo e(route('portals.patient.profile')); ?>" class="f-card pf-qa__item">
                <span class="pf-qa__icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'shield-check', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                <?php echo e(__('public.portal.nav_profile', [], $l) ?: 'My Profile'); ?>

            </a>
            <a href="<?php echo e(route('public.care-map')); ?>" class="f-card pf-qa__item">
                <span class="pf-qa__icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'map-pin', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                <?php echo e(__('public.portal.nav_care_map', [], $l) ?: 'Care Map'); ?>

            </a>
            <a href="<?php echo e(route('portals.patient.family')); ?>" class="f-card pf-qa__item">
                <span class="pf-qa__icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'users', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                <?php echo e(__('public.portal.nav_family', [], $l) ?: 'Family'); ?>

            </a>
        </div>
    </div>

    
    <div class="pf-section">
        <h2 class="pf-card-head" style="margin-bottom:10px;">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'calendar', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('public.portal.upcoming_appointments', [], $l) ?: 'Upcoming Appointments'); ?>

            <?php if(isset($upcomingAppointments) && $upcomingAppointments->isNotEmpty()): ?>
            <a href="<?php echo e(route('portals.patient.appointments')); ?>" class="f-btn f-btn--ghost" style="margin-left:auto;min-height:36px;padding:4px 12px;">
                <?php echo e(__('public.portal.view_all', [], $l) ?: 'View all'); ?>

            </a>
            <?php endif; ?>
        </h2>
        <?php if(isset($upcomingAppointments) && $upcomingAppointments->isNotEmpty()): ?>
        <div class="pf-stack">
            <?php $__currentLoopData = $upcomingAppointments->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="f-card">
                <div class="pf-appt">
                    <div class="pf-appt__date" aria-hidden="true">
                        <div class="d"><?php echo e($appt->scheduled_at?->format('d') ?? '—'); ?></div>
                        <div class="m"><?php echo e($appt->scheduled_at?->isoFormat('MMM') ?? ''); ?></div>
                    </div>
                    <div class="pf-appt__body">
                        <p class="pf-appt__title">
                            <?php echo e(\App\Support\Enums::label($appt->appointment_type ?? 'consultation')); ?>
                            <span class="f-badge f-badge--good"><?php echo e(\App\Support\Enums::label($appt->status ?? 'scheduled')); ?></span>
                        </p>
                        <div class="pf-appt__meta">
                            <?php if($appt->scheduled_at): ?>
                            <span>
                                <?php echo $__env->make('partials.finder.icon', ['name' => 'clock', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php echo e($appt->scheduled_at->isoFormat('ddd D MMM, HH:mm')); ?>

                            </span>
                            <?php endif; ?>
                            <span>
                                <?php echo $__env->make('partials.finder.icon', ['name' => 'map-pin', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php echo e($appt->facility?->name ?? '—'); ?>

                            </span>
                            <?php if($appt->provider?->name): ?>
                            <span>
                                <?php echo $__env->make('partials.finder.icon', ['name' => 'users', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php echo e(__('finder_patient.with_provider', ['name' => $appt->provider->name])); ?>

                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php else: ?>
        <div class="f-card" style="text-align:center;padding:28px 20px;">
            <p style="font-weight:700;margin:0 0 4px;"><?php echo e(__('finder_patient.no_upcoming_title')); ?></p>
            <p class="f-muted f-small" style="margin:0 0 14px;"><?php echo e(__('finder_patient.no_upcoming_body')); ?></p>
            <a href="<?php echo e(route('portals.patient.appointments.book')); ?>" class="f-btn f-btn--primary" style="min-height:48px;">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'calendar', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('public.appt_book_btn')); ?>

            </a>
        </div>
        <?php endif; ?>
    </div>

    
    <div class="f-card pf-section">
        <h2 class="pf-card-head">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('public.portal.clinical_safety_summary', [], $l) ?: 'Clinical Safety Summary'); ?>

        </h2>
        <div class="pf-tiles">
            <div>
                <div class="pf-tile__label"><?php echo e(__('public.portal.blood_group', [], $l) ?: 'Blood Group'); ?></div>
                <div class="pf-tile__value"><?php echo e($patient->blood_group ?? '—'); ?></div>
                <?php if(!$patient->blood_group): ?>
                    <a href="<?php echo e(route('portals.patient.profile')); ?>" class="f-small"><?php echo e(__('public.portal.lnk_add_in_profile', [], $l) ?: 'Add in profile →'); ?></a>
                <?php endif; ?>
            </div>
            <div>
                <div class="pf-tile__label"><?php echo e(__('public.portal.critical_allergies', [], $l) ?: 'Critical Allergies'); ?></div>
                <?php if(($criticalAllergies ?? collect())->isEmpty()): ?>
                    <div class="pf-tile__value" style="color:var(--f-good);font-size:.9375rem;"><?php echo e(__('public.portal.none_on_record', [], $l) ?: 'None on record'); ?></div>
                <?php else: ?>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:4px;">
                        <?php $__currentLoopData = ($criticalAllergies ?? collect())->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <span class="f-badge f-badge--danger"><?php echo e($a->substance); ?></span>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <div class="pf-tile__label"><?php echo e(__('public.portal.active_allergies', [], $l) ?: 'Active Allergies'); ?></div>
                <div class="pf-tile__value"><?php echo e(($activeAllergies ?? collect())->count()); ?></div>
            </div>
            <div>
                <div class="pf-tile__label"><?php echo e(__('public.portal.active_conditions', [], $l) ?: 'Active Conditions'); ?></div>
                <div class="pf-tile__value"><?php echo e(($activeConditions ?? collect())->count()); ?></div>
            </div>
        </div>
        <?php if(($activeAllergies ?? collect())->isNotEmpty() || ($activeConditions ?? collect())->isNotEmpty()): ?>
        <div class="pf-alert pf-alert--warn" style="margin:14px 0 0;">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <p><?php echo e(__('public.portal.allergy_safety_notice', [], $l) ?: 'Ensure your care team has your up-to-date allergy and condition list before any procedure or prescription.'); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <div class="pf-cols pf-section">

        
        <div class="f-card" style="text-align:center;">
            <h2 class="pf-card-head" style="justify-content:center;">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'qr-code', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('public.medical_id.temporary_access_qr', [], $l) ?: 'Temporary Access QR'); ?>

            </h2>
            <p class="f-muted f-small" style="margin:0 0 16px;">
                <?php echo e(__('public.portal.temp_qr_desc', [], $l) ?: 'Generate a secure, time-limited QR code to share with a new healthcare provider. Expires in 1 hour.'); ?>

            </p>

            <button id="generate-temp-qr" class="f-btn f-btn--primary" style="min-height:48px;"
                    data-label-generating="<?php echo e(__('public.portal.generating', [], $l) ?: 'Generating…'); ?>"
                    data-label-regenerate="<?php echo e(__('public.portal.regenerate_qr', [], $l) ?: 'Regenerate QR'); ?>">
                <?php echo e(__('public.portal.generate_qr', [], $l) ?: 'Generate Temporary QR'); ?>

            </button>

            <div id="temp-qr-container" style="display:none;flex-direction:column;align-items:center;margin-top:16px;" aria-live="polite">
                <div style="padding:10px;border-radius:var(--f-r);border:1px solid var(--f-line);background:var(--f-surface);">
                    <img id="temp-qr-img" src="" alt="<?php echo e(__('public.portal.temp_qr_alt', [], $l) ?: 'Temporary Access QR Code'); ?>"
                         style="width:8rem;height:8rem;border-radius:6px;display:none;">
                    <div id="temp-qr-placeholder" style="width:8rem;height:8rem;display:flex;align-items:center;justify-content:center;background:var(--f-wash);border-radius:6px;color:var(--f-brand);">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'qr-code', 'size' => 48], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
                <p class="f-small" style="color:var(--f-warn);font-weight:700;display:flex;align-items:center;gap:6px;margin:10px 0 0;">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'clock', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('public.portal.expires_in', [], $l) ?: 'Expires in'); ?>: <span id="countdown">60:00</span>
                </p>
            </div>

            <div class="pf-alert" style="text-align:left;margin:16px 0 0;">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'info', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <p class="f-small"><?php echo e(__('public.portal.qr_audit_notice', [], $l) ?: 'Each QR scan is audited. You can view access history in your Access Logs.'); ?></p>
            </div>
        </div>

        <div class="pf-stack">
            
            <div class="f-card">
                <p class="f-muted f-small" style="margin:0 0 12px;"><?php echo e(__('public.portal.profile_prompt_desc', [], $l) ?: 'Manage your privacy preferences, contact details and emergency contacts in your profile.'); ?></p>
                <a href="<?php echo e(route('portals.patient.profile')); ?>" class="f-btn">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'shield-check', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('public.portal.profile_prompt_btn', [], $l) ?: 'Go to Profile'); ?>

                </a>
            </div>

            
            <div class="pf-alert pf-alert--warn" style="margin:0;">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <p class="f-small"><?php echo e(__('onboarding.brand.clinical_disclaimer', [], $l) ?: 'OpesCare facilitates access to your health records but is not a substitute for clinical advice. Always consult a licensed healthcare provider for medical decisions.'); ?></p>
            </div>
        </div>

    </div>
</div>

<?php else: ?>

<div class="pf">
    <div class="f-card" style="text-align:center;padding:40px 20px;">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 24], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <h2 style="font-size:1.125rem;font-weight:750;margin:0 0 6px;"><?php echo e(__('public.portal.no_profile_title', [], app()->getLocale()) ?: 'No Patient Profile Found'); ?></h2>
        <p class="f-muted" style="margin:0 0 18px;"><?php echo e(__('public.portal.no_profile_desc', [], app()->getLocale()) ?: 'Your patient profile could not be loaded. Please contact support if this problem persists.'); ?></p>
        <a href="<?php echo e(route('public.help')); ?>" class="f-btn f-btn--primary">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'info', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('public.portal.nav_help', [], app()->getLocale()) ?: 'Get Help'); ?>

        </a>
    </div>
</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var countdownInterval = null;
    var btnGen = document.getElementById('generate-temp-qr');

    if (btnGen) {
        var lblGenerating   = btnGen.getAttribute('data-label-generating');
        var lblRegenerateQr = btnGen.getAttribute('data-label-regenerate');
        var lblQrExpired    = <?php echo json_encode(__('public.portal.lbl_qr_expired', [], app()->getLocale()) ?: 'Expired') ?>;

        btnGen.addEventListener('click', async function () {
            btnGen.disabled = true;
            btnGen.textContent = lblGenerating;

            try {
                var response = await fetch("<?php echo e(route('portals.patient.qr')); ?>", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' }
                });
                if (!response.ok) throw new Error('QR generation failed (' + response.status + ')');
                var data = await response.json();
                if (data.qr_image) {
                    var img         = document.getElementById('temp-qr-img');
                    var placeholder = document.getElementById('temp-qr-placeholder');
                    var container   = document.getElementById('temp-qr-container');
                    img.src         = data.qr_image;
                    img.style.display         = 'block';
                    placeholder.style.display = 'none';
                    container.style.display   = 'flex';
                    startCountdown(data.expires_in || 3600);
                }
            } catch (e) {
                console.error('Temp QR error:', e);
            } finally {
                btnGen.disabled = false;
                btnGen.textContent = lblRegenerateQr;
            }
        });

        function startCountdown(seconds) {
            if (countdownInterval) clearInterval(countdownInterval);
            var el = document.getElementById('countdown');
            countdownInterval = setInterval(function () {
                if (seconds <= 0) {
                    clearInterval(countdownInterval);
                    countdownInterval = null;
                    if (el) el.textContent = lblQrExpired;
                    return;
                }
                seconds--;
                var m = Math.floor(seconds / 60).toString().padStart(2, '0');
                var s = (seconds % 60).toString().padStart(2, '0');
                if (el) el.textContent = m + ':' + s;
            }, 1000);
        }
    }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\index.blade.php ENDPATH**/ ?>